<?php

namespace App\Support;

class PhoneNumber
{
    public static function normalize(?string $value, ?string $countryCode = '+880'): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, '+')) {
            $digits = self::digits(substr($value, 1));

            return $digits !== '' ? '+'.$digits : null;
        }

        $digits = self::digits($value);

        if ($digits === '') {
            return null;
        }

        $normalizedCountryCode = self::normalizeCountryCode($countryCode);

        if ($normalizedCountryCode === '+880') {
            if (str_starts_with($digits, '880')) {
                return '+'.$digits;
            }

            if (str_starts_with($digits, '0')) {
                return '+880'.substr($digits, 1);
            }

            if (strlen($digits) === 10 && str_starts_with($digits, '1')) {
                return '+880'.$digits;
            }
        }

        if ($normalizedCountryCode !== null) {
            $countryDigits = ltrim($normalizedCountryCode, '+');

            if (str_starts_with($digits, $countryDigits)) {
                return '+'.$digits;
            }

            return $normalizedCountryCode.$digits;
        }

        return '+'.$digits;
    }

    public static function split(?string $value, string $fallbackCountryCode = '+880'): array
    {
        $fallbackCountryCode = self::normalizeCountryCode($fallbackCountryCode) ?? '+880';
        $value = trim((string) $value);

        if ($value === '') {
            return [
                'country_code' => $fallbackCountryCode,
                'national_number' => '',
            ];
        }

        $canonical = self::normalize($value, $fallbackCountryCode);

        if ($canonical === null) {
            return [
                'country_code' => $fallbackCountryCode,
                'national_number' => self::digits($value),
            ];
        }

        $countryCode = self::detectCountryCode($canonical) ?? $fallbackCountryCode;
        $nationalNumber = self::nationalNumber($canonical, $countryCode) ?? '';

        return [
            'country_code' => $countryCode,
            'national_number' => $nationalNumber,
        ];
    }

    public static function candidates(?string $value, ?string $countryCode = '+880'): array
    {
        $raw = trim((string) $value);
        $digits = self::digits($raw);
        $canonical = self::normalize($raw, $countryCode);
        $normalizedCountryCode = self::normalizeCountryCode($countryCode) ?? '+880';
        $candidates = array_filter([
            $raw,
            $digits,
            $canonical,
            $canonical ? ltrim($canonical, '+') : null,
        ]);

        if ($normalizedCountryCode === '+880') {
            $split = self::split($raw !== '' ? $raw : $canonical, '+880');
            $nationalNumber = $split['national_number'] ?? '';

            if ($nationalNumber !== '') {
                $candidates[] = $nationalNumber;
                $candidates[] = '0'.$nationalNumber;
                $candidates[] = '880'.$nationalNumber;
                $candidates[] = '+880'.$nationalNumber;
            }
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    public static function smsDialString(?string $value, ?string $countryCode = '+880'): string
    {
        $canonical = self::normalize($value, $countryCode);

        return $canonical ? ltrim($canonical, '+') : '';
    }

    public static function digits(?string $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?: '';
    }

    private static function normalizeCountryCode(?string $countryCode): ?string
    {
        $digits = self::digits($countryCode);

        return $digits !== '' ? '+'.$digits : null;
    }

    private static function detectCountryCode(string $canonical): ?string
    {
        $digits = ltrim($canonical, '+');
        $dialCodes = collect(CountryDialCodes::all())
            ->pluck('dial_code')
            ->filter()
            ->sortByDesc(fn (string $dialCode) => strlen($dialCode));

        foreach ($dialCodes as $dialCode) {
            if (str_starts_with($canonical, $dialCode)) {
                return $dialCode;
            }

            if (str_starts_with($digits, ltrim($dialCode, '+'))) {
                return $dialCode;
            }
        }

        return null;
    }

    private static function nationalNumber(string $canonical, string $countryCode): ?string
    {
        $countryDigits = ltrim($countryCode, '+');
        $canonicalDigits = ltrim($canonical, '+');

        if (! str_starts_with($canonicalDigits, $countryDigits)) {
            return null;
        }

        return substr($canonicalDigits, strlen($countryDigits));
    }
}
