<?php

namespace App\Services;

use App\Mail\VerificationOtpMail;
use App\Models\ContactVerificationToken;
use App\Models\PendingRegistration;
use App\Models\User;
use Illuminate\Mail\Transport\TransportExceptionInterface;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactVerificationService
{
    public function issueForPendingRegistration(PendingRegistration $registration): void
    {
        if (! $registration->hasVerifiedEmail()) {
            $this->issuePendingOtp($registration, 'email', $registration->email);
        }

        if (! $registration->hasVerifiedMobile()) {
            $this->issuePendingOtp($registration, 'mobile', $registration->mobile_number);
        }
    }

    public function resendPending(PendingRegistration $registration, string $channel): void
    {
        $contactValue = match ($channel) {
            'email' => $registration->email,
            'mobile' => $registration->mobile_number,
            default => throw new \InvalidArgumentException('Unsupported verification channel.'),
        };

        $this->issuePendingOtp($registration, $channel, $contactValue);
    }

    public function verifyPending(PendingRegistration $registration, string $channel, string $code): bool
    {
        $codeColumn = $channel.'_code';
        $expiresColumn = $channel.'_code_expires_at';
        $verifiedColumn = $channel.'_verified_at';

        if (
            blank($registration->{$codeColumn})
            || $registration->{$codeColumn} !== $code
            || blank($registration->{$expiresColumn})
            || $registration->{$expiresColumn}->isPast()
        ) {
            return false;
        }

        $registration->forceFill([
            $verifiedColumn => now(),
            $codeColumn => null,
            $expiresColumn => null,
        ])->save();

        return true;
    }

    public function issueForUser(User $user): void
    {
        $user->loadMissing('profile');

        if (! $user->hasVerifiedEmail()) {
            $this->issueToken($user, 'email', $user->email);
        }

        if (! $user->hasVerifiedMobile()) {
            $this->issueToken($user, 'mobile', $user->profile?->mobile_number ?: $user->phone ?: '');
        }
    }

    public function resend(User $user, string $channel): void
    {
        $user->loadMissing('profile');

        $contactValue = match ($channel) {
            'email' => $user->email,
            'mobile' => $user->profile?->mobile_number ?: $user->phone ?: '',
            default => throw new \InvalidArgumentException('Unsupported verification channel.'),
        };

        $this->issueToken($user, $channel, $contactValue, true);
    }

    public function verify(User $user, string $channel, string $code): bool
    {
        $token = ContactVerificationToken::query()
            ->where('user_id', $user->id)
            ->where('channel', $channel)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (! $token || $token->isExpired() || $token->code !== $code) {
            return false;
        }

        $token->update(['verified_at' => now()]);

        if ($channel === 'email') {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        if ($channel === 'mobile') {
            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                ['mobile_verified' => true],
            );
        }

        return true;
    }

    private function issuePendingOtp(PendingRegistration $registration, string $channel, string $contactValue): PendingRegistration
    {
        $codeColumn = $channel.'_code';
        $expiresColumn = $channel.'_code_expires_at';

        $registration->forceFill([
            $codeColumn => (string) random_int(100000, 999999),
            $expiresColumn => now()->addMinutes(15),
        ])->save();

        $code = $registration->{$codeColumn};

        if ($channel === 'email' && filled($contactValue)) {
            try {
                Mail::to($contactValue)->send(new VerificationOtpMail((object) ['name' => $registration->full_name], $code));
            } catch (TransportExceptionInterface $exception) {
                if (! App::environment(['local', 'testing'])) {
                    throw $exception;
                }

                Log::warning('Pending registration email OTP could not be sent through configured mail transport. Falling back to log output.', [
                    'pending_registration_id' => $registration->id,
                    'email' => $contactValue,
                    'otp' => $code,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        if ($channel === 'mobile' && filled($contactValue)) {
            Log::info('Pending registration mobile OTP generated.', [
                'pending_registration_id' => $registration->id,
                'mobile_number' => $contactValue,
                'otp' => $code,
            ]);
        }

        return $registration->fresh();
    }

    private function issueToken(User $user, string $channel, string $contactValue, bool $replace = false): ContactVerificationToken
    {
        if ($replace) {
            ContactVerificationToken::query()
                ->where('user_id', $user->id)
                ->where('channel', $channel)
                ->delete();
        }

        $token = ContactVerificationToken::query()->create([
            'user_id' => $user->id,
            'channel' => $channel,
            'contact_value' => $contactValue,
            'code' => (string) random_int(100000, 999999),
            'expires_at' => now()->addMinutes(15),
            'sent_at' => now(),
        ]);

        if ($channel === 'email' && filled($contactValue)) {
            try {
                Mail::to($contactValue)->send(new VerificationOtpMail($user, $token->code));
            } catch (TransportExceptionInterface $exception) {
                if (! App::environment(['local', 'testing'])) {
                    throw $exception;
                }

                Log::warning('Email verification OTP could not be sent through configured mail transport. Falling back to log output.', [
                    'user_id' => $user->id,
                    'email' => $contactValue,
                    'otp' => $token->code,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        if ($channel === 'mobile' && filled($contactValue)) {
            Log::info('Mobile verification OTP generated.', [
                'user_id' => $user->id,
                'mobile_number' => $contactValue,
                'otp' => $token->code,
            ]);
        }

        return $token;
    }
}
