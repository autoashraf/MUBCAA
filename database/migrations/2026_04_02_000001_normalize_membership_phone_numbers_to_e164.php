<?php

use App\Support\PhoneNumber;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->select(['id', 'phone'])
            ->orderBy('id')
            ->get()
            ->each(function (object $user): void {
                $normalized = PhoneNumber::normalize($user->phone, '+880');

                if ($normalized !== null && $normalized !== $user->phone) {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['phone' => $normalized]);
                }
            });

        DB::table('pending_registrations')
            ->select(['id', 'mobile_number'])
            ->orderBy('id')
            ->get()
            ->each(function (object $registration): void {
                $normalized = PhoneNumber::normalize($registration->mobile_number, '+880');

                if ($normalized !== null && $normalized !== $registration->mobile_number) {
                    DB::table('pending_registrations')
                        ->where('id', $registration->id)
                        ->update(['mobile_number' => $normalized]);
                }
            });

        DB::table('member_profiles')
            ->select(['id', 'mobile_number', 'primary_mobile', 'secondary_mobile', 'whatsapp_number'])
            ->orderBy('id')
            ->get()
            ->each(function (object $profile): void {
                $updates = array_filter([
                    'mobile_number' => $this->normalizedValue($profile->mobile_number),
                    'primary_mobile' => $this->normalizedValue($profile->primary_mobile),
                    'secondary_mobile' => $this->normalizedValue($profile->secondary_mobile),
                    'whatsapp_number' => $this->normalizedValue($profile->whatsapp_number),
                ], fn ($value) => $value !== null);

                if ($updates !== []) {
                    DB::table('member_profiles')
                        ->where('id', $profile->id)
                        ->update($updates);
                }
            });

        DB::table('contact_verification_tokens')
            ->select(['id', 'channel', 'contact_value'])
            ->where('channel', 'mobile')
            ->orderBy('id')
            ->get()
            ->each(function (object $token): void {
                $normalized = PhoneNumber::normalize($token->contact_value, '+880');

                if ($normalized !== null && $normalized !== $token->contact_value) {
                    DB::table('contact_verification_tokens')
                        ->where('id', $token->id)
                        ->update(['contact_value' => $normalized]);
                }
            });
    }

    public function down(): void
    {
        // Irreversible data normalization.
    }

    private function normalizedValue(?string $value): ?string
    {
        $normalized = PhoneNumber::normalize($value, '+880');

        if ($normalized === null || $normalized === $value) {
            return null;
        }

        return $normalized;
    }
};
