<?php

namespace App\Http\Controllers;

use App\Models\MemberProfile;
use App\Models\MembershipApplication;
use App\Models\PendingRegistration;
use App\Models\User;
use App\Services\ContactVerificationService;
use App\Support\SiteNavigation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class VerificationController extends Controller
{
    private const RESEND_COOLDOWN_SECONDS = 60;

    public function __construct(
        private readonly ContactVerificationService $verificationService,
    ) {
    }

    public function show(Request $request): View|RedirectResponse
    {
        [$target, $mode] = $this->resolveVerificationTarget($request);

        if ($mode === 'user') {
            return redirect()->route('member.dashboard', ['verify_contacts' => 1]);
        }

        $captchaLeft = (int) $request->session()->get('registration_captcha_a', 1);
        $captchaRight = (int) $request->session()->get('registration_captcha_b', 1);

        return view('pages.apply', [
            'menu' => SiteNavigation::menu(),
            'registrationStatuses' => ['Draft', 'Unverified', 'In Progress', 'Pending Review', 'Verified'],
            'captchaLeft' => $captchaLeft,
            'captchaRight' => $captchaRight,
            'affiliateReferrer' => null,
            'passingYears' => $this->passingYearOptions(),
            'discoverySources' => $this->howDidYouFindUsOptions(),
            'showVerificationModal' => true,
            'verificationMode' => $mode,
            'verificationTarget' => $target,
            'verificationEmail' => $mode === 'pending' ? $target->email : $target->email,
            'verificationMobile' => $mode === 'pending' ? $target->mobile_number : ($target->profile?->mobile_number ?: $target->phone),
            'emailVerified' => $target->hasVerifiedEmail(),
            'mobileVerified' => $target->hasVerifiedMobile(),
            'emailResendCooldown' => $this->cooldownRemainingForChannel($request, $target, $mode, 'email'),
            'mobileResendCooldown' => $this->cooldownRemainingForChannel($request, $target, $mode, 'mobile'),
            'emailExpiryCountdown' => $this->expiryRemainingForChannel($target, $mode, 'email'),
            'mobileExpiryCountdown' => $this->expiryRemainingForChannel($target, $mode, 'mobile'),
            'verificationContinueUrl' => $mode === 'pending'
                ? route('member.profile.complete', ['step' => 2])
                : route('member.profile.complete', ['step' => max(2, $target->application?->current_step ?? $target->profile?->completion_step ?? 2)]),
            'localOtpCodes' => $this->localOtpCodes($target, $mode),
        ]);
    }

    public function verifyEmail(Request $request): JsonResponse|RedirectResponse
    {
        $request->merge([
            'code' => preg_replace('/\D+/', '', (string) $request->input('code')),
        ]);

        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        [$target, $mode] = $this->resolveVerificationTarget($request);

        $verified = $mode === 'pending'
            ? $this->verificationService->verifyPending($target, 'email', $validated['code'])
            : $this->verificationService->verify($target, 'email', $validated['code']);

        if (! $verified) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'errors' => ['code' => ['The email verification code is invalid or expired.']],
                ], 422);
            }

            return back()->withErrors(['email_code' => 'The email verification code is invalid or expired.']);
        }

        $message = 'Email verified successfully.';

        if ($mode === 'pending') {
            $target = $target->fresh();
            $issuedChannel = $this->verificationService->issueForPendingRegistration($target);

            if ($issuedChannel === 'mobile') {
                $this->storePendingResendTimestamp($request, 'mobile');
                $message = 'Email verified successfully. A mobile OTP has been sent.';
            }
        }

        return $this->verificationRedirect($request, $message);
    }

    public function verifyMobile(Request $request): JsonResponse|RedirectResponse
    {
        $request->merge([
            'code' => preg_replace('/\D+/', '', (string) $request->input('code')),
        ]);

        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        [$target, $mode] = $this->resolveVerificationTarget($request);

        $verified = $mode === 'pending'
            ? $this->verificationService->verifyPending($target, 'mobile', $validated['code'])
            : $this->verificationService->verify($target, 'mobile', $validated['code']);

        if (! $verified) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'errors' => ['code' => ['The mobile verification code is invalid or expired.']],
                ], 422);
            }

            return back()->withErrors(['mobile_code' => 'The mobile verification code is invalid or expired.']);
        }

        return $this->verificationRedirect($request, 'Mobile number verified successfully.');
    }

    public function resend(Request $request, string $channel): JsonResponse|RedirectResponse
    {
        abort_unless(in_array($channel, ['email', 'mobile'], true), 404);

        [$target, $mode] = $this->resolveVerificationTarget($request);
        $remaining = $this->cooldownRemainingForChannel($request, $target, $mode, $channel);

        if ($remaining > 0) {
            $message = "Please wait {$remaining} seconds before requesting another OTP.";

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => true,
                    'message' => $message,
                    'flash_type' => 'error',
                    'completed' => false,
                    'modal_html' => $this->renderVerificationModal($request, $target, $mode, $message),
                ]);
            }

            return back()->withErrors([$channel => $message]);
        }

        if ($mode === 'pending') {
            $this->verificationService->resendPending($target, $channel);
            $this->storePendingResendTimestamp($request, $channel);
        } else {
            $this->verificationService->resend($target, $channel);
        }

        $message = $channel === 'email'
            ? 'A new email verification code has been sent.'
            : 'A new mobile verification code has been generated.';

        if ($request->expectsJson()) {
            [$freshTarget, $freshMode] = $this->resolveVerificationTarget($request);

            return response()->json([
                'ok' => true,
                'message' => $message,
                'flash_type' => 'success',
                'modal_html' => $this->renderVerificationModal($request, $freshTarget, $freshMode, $message),
            ]);
        }

        return back()->with('success', $message);
    }

    private function verificationRedirect(Request $request, string $message): JsonResponse|RedirectResponse
    {
        [$target, $mode] = $this->resolveVerificationTarget($request);

        if ($mode === 'pending' && $target->hasCompletedContactVerification()) {
            $user = $this->createUserFromPendingRegistration($target, $request);
            $successMessage = 'Thank you for completing Step 1. Your preliminary registration has been successfully submitted. Please verify your email and mobile OTP to continue the remaining steps of your membership application. Once all required information has been submitted, the Alumni Association will review and verify your details carefully. Upon successful verification, you will be officially confirmed as a Verified Member.';

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => true,
                    'message' => $successMessage,
                    'completed' => true,
                    'continue_url' => route('member.profile.complete', ['step' => max(2, $user->application?->current_step ?? $user->profile?->completion_step ?? 2)]),
                    'modal_html' => $this->renderVerificationModal($request, $user, 'user', $successMessage),
                ]);
            }

            return redirect()
                ->route('member.profile.complete', ['step' => max(2, $user->application?->current_step ?? $user->profile?->completion_step ?? 2)])
                ->with('success', $successMessage);
        }

        if ($mode === 'user' && $target->fresh()->hasCompletedContactVerification()) {
            $successMessage = $message.' Both contact methods are now verified.';

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => true,
                    'message' => $successMessage,
                    'completed' => true,
                    'continue_url' => route('member.profile.complete', ['step' => max(2, $target->application?->current_step ?? $target->profile?->completion_step ?? 2)]),
                    'modal_html' => $this->renderVerificationModal($request, $target->fresh()->load('profile', 'verificationTokens'), 'user', $successMessage),
                ]);
            }

            return redirect()
                ->route('member.profile.complete', ['step' => max(2, $target->application?->current_step ?? $target->profile?->completion_step ?? 2)])
                ->with('success', $successMessage);
        }

        if ($request->expectsJson()) {
            [$freshTarget, $freshMode] = $this->resolveVerificationTarget($request);

            return response()->json([
                'ok' => true,
                'message' => $message,
                'completed' => false,
                'flash_type' => 'success',
                'modal_html' => $this->renderVerificationModal($request, $freshTarget, $freshMode, $message),
            ]);
        }

        return back()->with('success', $message);
    }

    private function renderVerificationModal(Request $request, object $target, string $mode, ?string $message = null): string
    {
        $target = $mode === 'user' ? $target->fresh()->load('profile', 'application', 'verificationTokens') : $target->fresh();

        return view('partials.verification-modal', [
            'verificationEmail' => $target->email,
            'verificationMobile' => $mode === 'pending' ? $target->mobile_number : ($target->profile?->mobile_number ?: $target->phone),
            'emailVerified' => $target->hasVerifiedEmail(),
            'mobileVerified' => $target->hasVerifiedMobile(),
            'emailResendCooldown' => $this->cooldownRemainingForChannel($request, $target, $mode, 'email'),
            'mobileResendCooldown' => $this->cooldownRemainingForChannel($request, $target, $mode, 'mobile'),
            'emailExpiryCountdown' => $this->expiryRemainingForChannel($target, $mode, 'email'),
            'mobileExpiryCountdown' => $this->expiryRemainingForChannel($target, $mode, 'mobile'),
            'verificationContinueUrl' => $mode === 'pending'
                ? route('member.profile.complete', ['step' => 2])
                : route('member.profile.complete', ['step' => max(2, $target->application?->current_step ?? $target->profile?->completion_step ?? 2)]),
            'verificationSuccessMessage' => $message,
            'localOtpCodes' => $this->localOtpCodes($target, $mode),
        ])->render();
    }

    private function resolveVerificationTarget(Request $request): array
    {
        if ($request->user()) {
            return [$request->user()->load('profile', 'application', 'verificationTokens'), 'user'];
        }

        $pendingId = $request->session()->get('pending_registration_id');
        $registration = PendingRegistration::query()->findOrFail($pendingId);

        return [$registration, 'pending'];
    }

    private function createUserFromPendingRegistration(PendingRegistration $registration, Request $request)
    {
        $user = DB::transaction(function () use ($registration) {
            $user = User::query()->create([
                'name' => $registration->full_name,
                'email' => $registration->email,
                'phone' => $registration->mobile_number,
                'password' => Str::password(20),
                'role' => 'member',
                'membership_status' => 'unverified',
                'approval_step' => 1,
                'referred_by_user_id' => $registration->referred_by_user_id,
            ]);

            $user->forceFill([
                'email_verified_at' => $registration->email_verified_at,
            ])->save();

            MemberProfile::query()->create([
                'user_id' => $user->id,
                'mobile_number' => $registration->mobile_number,
                'mobile_verified' => ! is_null($registration->mobile_verified_at),
                'passing_year_batch' => $registration->passing_year_batch,
                'how_did_you_find_us' => $registration->how_did_you_find_us,
                'country' => 'Bangladesh',
                'completion_step' => 1,
            ]);

            MembershipApplication::query()->create([
                'user_id' => $user->id,
                'status' => 'unverified',
                'current_step' => 1,
                'total_steps' => 10,
            ]);

            $registration->update(['completed_at' => now()]);

            return $user->load('profile');
        });

        $request->session()->forget('pending_registration_id');
        $request->session()->forget('pending_verification_sent_at');
        Auth::login($user);

        return $user;
    }

    private function cooldownRemainingForChannel(Request $request, object $target, string $mode, string $channel): int
    {
        $lastSentAt = $mode === 'pending'
            ? $this->pendingResendTimestamp($request, $channel)
            : $target->verificationTokens
                ->where('channel', $channel)
                ->whereNull('verified_at')
                ->sortByDesc('sent_at')
                ->first()?->sent_at;

        if (! $lastSentAt instanceof Carbon) {
            return 0;
        }

        $elapsed = now()->diffInSeconds($lastSentAt);

        return $elapsed >= self::RESEND_COOLDOWN_SECONDS
            ? 0
            : self::RESEND_COOLDOWN_SECONDS - $elapsed;
    }

    private function expiryRemainingForChannel(object $target, string $mode, string $channel): int
    {
        $expiresAt = $mode === 'pending'
            ? $target->{$channel.'_code_expires_at'}
            : $target->verificationTokens
                ->where('channel', $channel)
                ->whereNull('verified_at')
                ->sortByDesc('sent_at')
                ->first()?->expires_at;

        if (! $expiresAt instanceof Carbon) {
            return 0;
        }

        $remaining = now()->diffInSeconds($expiresAt, false);

        return max(0, $remaining);
    }

    private function pendingResendTimestamp(Request $request, string $channel): ?Carbon
    {
        $value = $request->session()->get("pending_verification_sent_at.{$channel}");

        return filled($value) ? Carbon::parse($value) : null;
    }

    private function storePendingResendTimestamp(Request $request, string $channel): void
    {
        $request->session()->put("pending_verification_sent_at.{$channel}", now()->toDateTimeString());
    }

    private function localOtpCodes(object $target, string $mode): ?array
    {
        if (! App::environment('local')) {
            return null;
        }

        if ($mode === 'pending') {
            return array_filter([
                'email' => $target->email_code,
                'mobile' => $target->mobile_code,
            ]);
        }

        $target = $target->fresh()->load('verificationTokens');

        return array_filter([
            'email' => $target->verificationTokens
                ->where('channel', 'email')
                ->whereNull('verified_at')
                ->sortByDesc('sent_at')
                ->first()?->code,
            'mobile' => $target->verificationTokens
                ->where('channel', 'mobile')
                ->whereNull('verified_at')
                ->sortByDesc('sent_at')
                ->first()?->code,
        ]);
    }

    private function passingYearOptions(): array
    {
        return collect(range((int) now()->year, 1970))
            ->map(fn (int $year) => (string) $year)
            ->all();
    }

    private function howDidYouFindUsOptions(): array
    {
        return [
            'Facebook',
            'Google Search',
            'Friend or Family',
            'Alumni Member',
            'School / College',
            'Event or Program',
            'Website',
            'Other',
        ];
    }
}
