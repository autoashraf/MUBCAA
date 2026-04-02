<?php

namespace App\Http\Controllers;

use App\Mail\VerificationOtpMail;
use App\Models\PendingRegistration;
use App\Models\User;
use App\Services\ContactVerificationService;
use App\Services\MimSmsService;
use App\Support\CountryDialCodes;
use App\Support\PhoneNumber;
use App\Support\SiteNavigation;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Mail\Transport\TransportExceptionInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        private readonly ContactVerificationService $verificationService,
        private readonly MimSmsService $sms,
    ) {}

    public function showLogin(): View
    {
        if (request()->boolean('close_otp')) {
            $this->forgetLoginOtp(request());
        }

        return view('auth.login', [
            'menu' => SiteNavigation::menu(),
            'loginOtpPending' => session()->has('login_otp_user_id'),
            'loginOtpChannel' => session('login_otp_channel'),
            'loginOtpContact' => session('login_otp_contact'),
            'loginOtpResendCooldown' => $this->loginOtpResendCooldown(request()),
            'loginOtpExpiryCountdown' => $this->loginOtpExpiryCountdown(request()),
            'localLoginOtpCode' => App::environment('local') ? session('login_otp_code') : null,
            'countryDialCodes' => CountryDialCodes::all(),
        ]);
    }

    public function showAdminLogin(): View
    {
        return view('auth.admin-login', [
            'menu' => SiteNavigation::menu(),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $base = $request->validate([
            'login_channel' => ['required', Rule::in(['email', 'mobile'])],
            'email_identifier' => ['nullable', 'string', 'max:255'],
            'mobile_identifier' => ['nullable', 'string', 'max:255'],
            'mobile_country_code' => ['nullable', 'string', 'max:20'],
        ]);

        $channel = (string) $base['login_channel'];
        $countryCode = trim((string) ($base['mobile_country_code'] ?? '+880'));

        if ($channel === 'email') {
            $validated = $request->validate([
                'email_identifier' => ['required', 'email', 'max:255'],
            ]);

            $user = $this->resolveLoginEmailUser($validated['email_identifier']);
        } else {
            $validated = $request->validate([
                'mobile_identifier' => ['required', 'string', 'max:255'],
            ]);

            $user = $this->resolveLoginMobileUser($validated['mobile_identifier'], $countryCode);
        }

        if (! $user || $user->isAdmin()) {
            $this->forgetLoginOtp($request);

            return redirect()
                ->route('login')
                ->with('success', 'If a member account matches that email or mobile number, a 6-digit OTP has been sent.');
        }

        [$channel, $contactValue] = $this->resolveLoginChannel($user, $channel);

        $this->issueLoginOtp($request, $user, $channel, $contactValue);

        return redirect()
            ->route('login')
            ->with('success', 'If a member account matches that email or mobile number, a 6-digit OTP has been sent.');
    }

    public function checkLoginIdentifier(Request $request): JsonResponse
    {
        $request->validate([
            'login_channel' => ['required', Rule::in(['email', 'mobile'])],
            'identifier' => ['required', 'string', 'max:255'],
            'identifier_country_code' => ['nullable', 'string', 'max:20'],
        ]);

        return response()->json([
            'exists' => true,
            'message' => 'If this is a registered member account, you can request an OTP.',
        ]);
    }

    public function checkRegistrationField(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'field' => ['required', Rule::in(['email', 'mobile_number', 'referral_code'])],
            'value' => ['nullable', 'string', 'max:255'],
            'mobile_country_code' => ['nullable', 'string', 'max:20'],
            'context_email' => ['nullable', 'string', 'max:255'],
            'context_mobile_number' => ['nullable', 'string', 'max:255'],
            'context_mobile_country_code' => ['nullable', 'string', 'max:20'],
        ]);

        $value = trim((string) $validated['value']);
        $mobileCountryCode = trim((string) ($validated['mobile_country_code'] ?? '+880'));
        $contextEmail = trim((string) ($validated['context_email'] ?? ''));
        $contextMobileNumber = trim((string) ($validated['context_mobile_number'] ?? ''));
        $contextMobileCountryCode = trim((string) ($validated['context_mobile_country_code'] ?? '+880'));

        if ($value === '') {
            return response()->json([
                'valid' => false,
                'message' => '',
                'type' => 'loading',
            ]);
        }

        return match ($validated['field']) {
            'email' => $this->checkRegistrationEmail($value, $contextMobileNumber, $contextMobileCountryCode),
            'mobile_number' => $this->checkRegistrationMobile($value, $contextEmail, $mobileCountryCode),
            'referral_code' => $this->checkRegistrationReferralCode($value),
        };
    }

    public function adminLogin(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, false)) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'The provided admin credentials do not match our records.',
            ]);
        }

        $request->session()->regenerate();

        if (! $request->user()->isAdmin()) {
            Auth::logout();

            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'This login form is for admin access only.',
            ]);
        }

        return redirect()->route('admin.dashboard');
    }

    public function verifyLoginOtp(Request $request): RedirectResponse
    {
        $request->merge([
            'code' => preg_replace('/\D+/', '', (string) $request->input('code')),
        ]);

        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $payload = $this->loginOtpPayload($request);
        $expiresAt = $payload['expires_at'] ?? null;

        if (
            ! $payload
            || blank($payload['code'] ?? null)
            || blank($expiresAt)
            || now()->greaterThan($expiresAt)
            || ($payload['code'] ?? null) !== $validated['code']
        ) {
            return back()->withErrors([
                'code' => 'The OTP is invalid or has expired.',
            ]);
        }

        $user = User::query()->find($payload['user_id']);

        if (! $user) {
            $this->forgetLoginOtp($request);

            return redirect()->route('login')->withErrors([
                'identifier' => 'The selected account could not be found. Please request a new OTP.',
            ]);
        }

        $this->forgetLoginOtp($request);

        Auth::login($user);
        $request->session()->regenerate();

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->profile && ! $user->hasCompletedContactVerification()) {
            return redirect()->route('member.verification.show');
        }

        return redirect()->route('member.dashboard');
    }

    public function resendLoginOtp(Request $request): RedirectResponse
    {
        $payload = $this->loginOtpPayload($request);

        if (! $payload) {
            return redirect()->route('login')->withErrors([
                'identifier' => 'Request a new OTP first.',
            ]);
        }

        $user = User::query()->find($payload['user_id']);

        if (! $user) {
            $this->forgetLoginOtp($request);

            return redirect()->route('login')->withErrors([
                'identifier' => 'The selected account could not be found. Please request a new OTP.',
            ]);
        }

        $lastSentAt = $payload['sent_at'] ?? null;

        if ($lastSentAt && now()->diffInSeconds($lastSentAt, false) > -60) {
            $remaining = 60 - abs(now()->diffInSeconds($lastSentAt));
            $remaining = max(1, $remaining);

            return redirect()->route('login')->withErrors([
                'identifier' => "Please wait {$remaining} seconds before requesting another OTP.",
            ]);
        }

        $this->issueLoginOtp($request, $user, $payload['channel'], $payload['contact']);

        return redirect()->route('login')->with('success', 'A new OTP has been sent.');
    }

    public function showRegistration(Request $request): View
    {
        [$captchaLeft, $captchaRight] = $this->generateCaptchaChallenge($request);
        $affiliateReferrer = User::query()
            ->whereKey($request->session()->get('affiliate_referrer_id'))
            ->where('role', 'member')
            ->first();

        return view('pages.apply', [
            'menu' => SiteNavigation::menu(),
            'registrationStatuses' => ['Draft', 'Unverified', 'In Progress', 'Pending Review', 'Verified'],
            'captchaLeft' => $captchaLeft,
            'captchaRight' => $captchaRight,
            'affiliateReferrer' => $affiliateReferrer,
            'passingYears' => $this->passingYearOptions(),
            'discoverySources' => $this->howDidYouFindUsOptions(),
            'countryDialCodes' => CountryDialCodes::all(),
        ]);
    }

    public function register(Request $request): JsonResponse|RedirectResponse
    {
        $mobileCountryCode = (string) $request->input('mobile_country_code', '+880');
        $pendingRegistration = $this->reusablePendingRegistration(
            $request,
            (string) $request->input('email'),
            (string) $request->input('mobile_number'),
            $mobileCountryCode,
        );

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
                Rule::unique('pending_registrations', 'email')
                    ->ignore($pendingRegistration?->id)
                    ->where(fn ($query) => $query->whereNull('completed_at')),
            ],
            'mobile_country_code' => ['nullable', 'string', 'max:20'],
            'mobile_number' => [
                'required',
                'string',
                'max:50',
                function (string $attribute, mixed $value, Closure $fail) use ($request, $pendingRegistration): void {
                    $countryCode = (string) $request->input('mobile_country_code', '+880');

                    if (! $this->isValidPhoneNumberForCountry((string) $value, $countryCode)) {
                        $fail($countryCode === '+880'
                            ? 'For Bangladesh numbers, enter 10 or 11 digits.'
                            : 'Enter a valid mobile number.');

                        return;
                    }

                    $candidates = PhoneNumber::candidates((string) $value, $countryCode);

                    if ($this->registeredMobileExists($candidates)) {
                        $fail('This mobile number is already registered.');
                        return;
                    }

                    $pendingExists = PendingRegistration::query()
                        ->whereIn('mobile_number', $candidates)
                        ->whereNull('completed_at')
                        ->when($pendingRegistration?->id, fn ($query) => $query->whereKeyNot($pendingRegistration->id))
                        ->exists();

                    if ($pendingExists) {
                        $fail('This mobile number is already linked to another registration.');
                    }
                },
            ],
            'passing_year_batch' => ['required', Rule::in($this->passingYearOptions())],
            'discovery_source' => ['nullable', Rule::in(array_merge($this->howDidYouFindUsOptions(), ['Referral Code']))],
            'referral_code' => ['nullable', 'string', 'max:50'],
            'captcha_left' => ['required', 'integer', 'between:1,9'],
            'captcha_right' => ['required', 'integer', 'between:1,9'],
            'captcha_answer' => ['required', 'integer'],
        ]);

        $canonicalMobileNumber = PhoneNumber::normalize($validated['mobile_number'], $validated['mobile_country_code'] ?? '+880');

        $completedRegistrationConflicts = $this->completedPendingRegistrationConflicts(
            $validated['email'],
            $canonicalMobileNumber ?? $validated['mobile_number'],
            $pendingRegistration?->id,
            $validated['mobile_country_code'] ?? '+880',
        );

        if ($completedRegistrationConflicts !== []) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'errors' => $completedRegistrationConflicts,
                ], 422);
            }

            return back()
                ->withErrors($completedRegistrationConflicts)
                ->withInput();
        }

        $resolvedReferrer = $this->resolveAffiliateReferrer(
            $request,
            ($validated['discovery_source'] ?? null) === 'Referral Code'
                ? ($validated['referral_code'] ?? null)
                : null,
        );

        if (($validated['discovery_source'] ?? null) === 'Referral Code' && blank($validated['referral_code'] ?? null)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'errors' => [
                        'referral_code' => ['Enter a referral code.'],
                    ],
                ], 422);
            }

            return back()
                ->withErrors(['referral_code' => 'Enter a referral code.'])
                ->withInput();
        }

        if (($validated['discovery_source'] ?? null) === 'Referral Code' && ! $resolvedReferrer) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'errors' => [
                        'referral_code' => ['The referral code is invalid.'],
                    ],
                ], 422);
            }

            return back()
                ->withErrors(['referral_code' => 'The referral code is invalid.'])
                ->withInput();
        }

        $captchaSum = (int) $validated['captcha_left'] + (int) $validated['captcha_right'];

        if ((int) $validated['captcha_answer'] !== $captchaSum) {
            $validator = Validator::make([], []);
            $validator->errors()->add('captcha_answer', 'The captcha answer is incorrect.');

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'errors' => [
                        'captcha_answer' => ['The captcha answer is incorrect.'],
                    ],
                ], 422);
            }

            return back()
                ->withErrors($validator)
                ->withInput($request->except('captcha_answer'));
        }

        $payload = [
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'mobile_number' => $canonicalMobileNumber,
            'passing_year_batch' => $validated['passing_year_batch'],
            'how_did_you_find_us' => ($validated['discovery_source'] ?? null) === 'Referral Code'
                ? null
                : ($validated['discovery_source'] ?? null),
            'referred_by_user_id' => $resolvedReferrer?->id,
            'email_code' => null,
            'mobile_code' => null,
            'email_code_expires_at' => null,
            'mobile_code_expires_at' => null,
            'email_verified_at' => null,
            'mobile_verified_at' => null,
            'completed_at' => null,
        ];

        try {
            $registration = DB::transaction(function () use ($payload, $pendingRegistration): PendingRegistration {
                if ($pendingRegistration) {
                    $pendingRegistration->fill($payload)->save();

                    return $pendingRegistration->fresh();
                }

                return PendingRegistration::query()->create($payload);
            });
        } catch (QueryException $exception) {
            $fallbackRegistration = $this->matchingPendingRegistration(
                $validated['email'],
                $canonicalMobileNumber ?? $validated['mobile_number'],
                false,
                $validated['mobile_country_code'] ?? '+880',
            );

            if (! $this->isDuplicatePendingRegistrationError($exception) || ! $fallbackRegistration) {
                throw $exception;
            }

            $fallbackRegistration->fill($payload)->save();
            $registration = $fallbackRegistration->fresh();
        }

        $request->session()->put('pending_registration_id', $registration->id);
        $issuedChannel = $this->verificationService->issueForPendingRegistration($registration);
        $registration = $registration->fresh();

        if ($issuedChannel) {
            $request->session()->put('pending_verification_sent_at', [
                $issuedChannel => now()->toDateTimeString(),
            ]);
        } else {
            $request->session()->forget('pending_verification_sent_at');
        }

        $request->session()->forget(['registration_captcha_a', 'registration_captcha_b']);
        $request->session()->forget('affiliate_referrer_id');

        $message = 'Thank you for completing Step 1. Your preliminary registration has been successfully submitted. Please verify your email and mobile OTP to continue the remaining steps of your membership application. Once all required information has been submitted, the Alumni Association will review and verify your details carefully. Upon successful verification, you will be officially confirmed as a Verified Member.';

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'modal_html' => view('partials.verification-modal', [
                    'verificationEmail' => $registration->email,
                    'verificationMobile' => $registration->mobile_number,
                    'emailVerified' => false,
                    'mobileVerified' => false,
                    'emailResendCooldown' => $issuedChannel === 'email' ? 60 : 0,
                    'mobileResendCooldown' => $issuedChannel === 'mobile' ? 60 : 0,
                    'emailExpiryCountdown' => $registration->email_code_expires_at
                        ? max(0, now()->diffInSeconds($registration->email_code_expires_at, false))
                        : 0,
                    'mobileExpiryCountdown' => $registration->mobile_code_expires_at
                        ? max(0, now()->diffInSeconds($registration->mobile_code_expires_at, false))
                        : 0,
                    'verificationContinueUrl' => route('member.profile.complete', ['step' => 2]),
                    'verificationSuccessMessage' => $message,
                    'localOtpCodes' => $this->localPendingOtpCodes($registration),
                ])->render(),
            ]);
        }

        return redirect()
            ->route('member.verification.show')
            ->with('success', $message);
    }

    private function generateCaptchaChallenge(Request $request): array
    {
        $request->session()->put('registration_captcha_a', random_int(1, 9));
        $request->session()->put('registration_captcha_b', random_int(1, 9));

        return [
            (int) $request->session()->get('registration_captcha_a'),
            (int) $request->session()->get('registration_captcha_b'),
        ];
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

    private function currentPendingRegistration(Request $request): ?PendingRegistration
    {
        return PendingRegistration::query()
            ->whereKey($request->session()->get('pending_registration_id'))
            ->whereNull('completed_at')
            ->first();
    }

    private function reusablePendingRegistration(Request $request, string $email, string $mobileNumber, string $countryCode = '+880'): ?PendingRegistration
    {
        return $this->currentPendingRegistration($request)
            ?? $this->matchingPendingRegistration($email, $mobileNumber, false, $countryCode)
            ?? $this->matchingPendingRegistration($email, $mobileNumber, true, $countryCode);
    }

    private function matchingPendingRegistration(string $email, string $mobileNumber, bool $includeCompleted = false, string $countryCode = '+880'): ?PendingRegistration
    {
        $normalizedEmail = trim(strtolower($email));
        $mobileCandidates = PhoneNumber::candidates($mobileNumber, $countryCode);

        if ($normalizedEmail === '' || $mobileCandidates === []) {
            return null;
        }

        $query = PendingRegistration::query()
            ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
            ->whereIn('mobile_number', $mobileCandidates);

        if (! $includeCompleted) {
            $query->whereNull('completed_at');
        }

        return $query->first();
    }

    private function completedPendingRegistrationConflicts(string $email, string $mobileNumber, ?int $ignoreId = null, string $countryCode = '+880'): array
    {
        $normalizedEmail = trim(strtolower($email));
        $mobileCandidates = PhoneNumber::candidates($mobileNumber, $countryCode);
        $normalizedMobile = PhoneNumber::normalize($mobileNumber, $countryCode);

        if ($normalizedEmail === '' || $mobileCandidates === [] || $normalizedMobile === null) {
            return [];
        }

        $baseQuery = PendingRegistration::query()
            ->whereNotNull('completed_at')
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId));

        $emailConflict = (clone $baseQuery)
            ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
            ->whereNotIn('mobile_number', $mobileCandidates)
            ->exists();

        $mobileConflict = (clone $baseQuery)
            ->whereIn('mobile_number', $mobileCandidates)
            ->whereRaw('LOWER(email) != ?', [$normalizedEmail])
            ->exists();

        return array_filter([
            'email' => $emailConflict
                ? 'This email address is already linked to another registration.'
                : null,
            'mobile_number' => $mobileConflict
                ? 'This mobile number is already linked to another registration.'
                : null,
        ]);
    }

    private function isDuplicatePendingRegistrationError(QueryException $exception): bool
    {
        if ((string) $exception->getCode() !== '23000') {
            return false;
        }

        $message = strtolower($exception->getMessage());

        return str_contains($message, 'pending_registrations_email_unique')
            || str_contains($message, 'pending_registrations_mobile_number_unique');
    }

    private function resolveAffiliateReferrer(Request $request, ?string $referralCode = null): ?User
    {
        $normalizedCode = filled($referralCode) ? strtoupper(trim($referralCode)) : null;

        if ($normalizedCode) {
            return User::query()
                ->where('role', 'member')
                ->where('affiliate_code', $normalizedCode)
                ->first();
        }

        return User::query()
            ->whereKey($request->session()->get('affiliate_referrer_id'))
            ->where('role', 'member')
            ->first();
    }

    private function checkRegistrationEmail(string $value, string $contextMobileNumber = '', string $contextMobileCountryCode = '+880'): JsonResponse
    {
        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'valid' => false,
                'message' => 'Enter a valid email address.',
                'type' => 'error',
            ]);
        }

        $exists = User::query()->where('email', $value)->exists();

        if ($exists) {
            return response()->json([
                'valid' => false,
                'message' => 'This email address is already registered.',
                'type' => 'error',
            ]);
        }

        if (filled($contextMobileNumber) && $this->matchingPendingRegistration($value, $contextMobileNumber, true, $contextMobileCountryCode)) {
            return response()->json([
                'valid' => true,
                'message' => 'An existing registration was found for this email and mobile number.',
                'type' => 'success',
            ]);
        }

        $pendingExists = PendingRegistration::query()
            ->whereRaw('LOWER(email) = ?', [strtolower($value)])
            ->exists();

        return response()->json([
            'valid' => ! $pendingExists,
            'message' => $pendingExists
                ? 'This email address is already linked to another registration.'
                : 'Email address is available.',
            'type' => $pendingExists ? 'error' : 'success',
        ]);
    }

    private function checkRegistrationMobile(string $value, string $contextEmail = '', string $countryCode = '+880'): JsonResponse
    {
        if (! $this->isValidPhoneNumberForCountry($value, $countryCode)) {
            return response()->json([
                'valid' => false,
                'message' => $countryCode === '+880'
                    ? 'For Bangladesh numbers, enter 10 or 11 digits.'
                    : 'Enter a valid mobile number.',
                'type' => 'error',
            ]);
        }

        $candidates = PhoneNumber::candidates($value, $countryCode);
        $exists = $this->registeredMobileExists($candidates);

        if ($exists) {
            return response()->json([
                'valid' => false,
                'message' => 'This mobile number is already registered.',
                'type' => 'error',
            ]);
        }

        if (filled($contextEmail) && $this->matchingPendingRegistration($contextEmail, $value, true, $countryCode)) {
            return response()->json([
                'valid' => true,
                'message' => 'An existing registration was found for this email and mobile number.',
                'type' => 'success',
            ]);
        }

        $pendingExists = PendingRegistration::query()
            ->whereIn('mobile_number', $candidates)
            ->exists();

        return response()->json([
            'valid' => ! $pendingExists,
            'message' => $pendingExists
                ? 'This mobile number is already linked to another registration.'
                : 'Mobile number is available.',
            'type' => $pendingExists ? 'error' : 'success',
        ]);
    }

    private function registeredMobileExists(array $candidates): bool
    {
        $candidates = array_values(array_unique(array_filter($candidates)));

        if ($candidates === []) {
            return false;
        }

        return User::query()
            ->whereIn('phone', $candidates)
            ->orWhereHas('profile', function ($query) use ($candidates): void {
                $query->whereIn('mobile_number', $candidates)
                    ->orWhereIn('primary_mobile', $candidates);
            })
            ->exists();
    }

    private function checkRegistrationReferralCode(string $value): JsonResponse
    {
        $referrer = User::query()
            ->where('role', 'member')
            ->where('affiliate_code', strtoupper($value))
            ->first();

        return response()->json([
            'valid' => (bool) $referrer,
            'message' => $referrer
                ? 'Referral code found.'
                : 'Referral code was not found.',
            'type' => $referrer ? 'success' : 'error',
        ]);
    }

    public function affiliateRedirect(Request $request, User $user): RedirectResponse
    {
        if (! $request->hasValidSignature() || $user->role !== 'member') {
            return redirect()->route('membership.apply');
        }

        $request->session()->put('affiliate_referrer_id', $user->id);

        return redirect()->route('membership.apply');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function resolveLoginEmailUser(string $identifier): ?User
    {
        $identifier = trim($identifier);

        return User::query()->where('email', $identifier)->first();
    }

    private function resolveLoginMobileUser(string $identifier, string $countryCode = '+880'): ?User
    {
        $identifier = trim($identifier);

        return User::query()
            ->whereIn('phone', PhoneNumber::candidates($identifier, $countryCode))
            ->first();
    }

    private function resolveLoginChannel(User $user, string $channel): array
    {
        if ($channel === 'email') {
            return ['email', $user->email];
        }

        return ['mobile', $user->phone ?: $user->profile?->mobile_number ?: ''];
    }

    private function issueLoginOtp(Request $request, User $user, string $channel, string $contactValue): void
    {
        $code = (string) random_int(100000, 999999);

        $request->session()->put('login_otp_user_id', $user->id);
        $request->session()->put('login_otp_channel', $channel);
        $request->session()->put('login_otp_contact', $contactValue);
        $request->session()->put('login_otp_code', $code);
        $request->session()->put('login_otp_expires_at', now()->addMinutes(15)->toDateTimeString());
        $request->session()->put('login_otp_sent_at', now()->toDateTimeString());

        if ($channel === 'email' && filled($contactValue)) {
            try {
                Mail::to($contactValue)->send(new VerificationOtpMail($user, $code, 'login'));
                Log::info('Login email OTP sent.', [
                    'user_id' => $user->id,
                    'email' => $contactValue,
                    'mailer' => config('mail.default'),
                    'smtp_host' => config('mail.mailers.smtp.host'),
                    'smtp_port' => config('mail.mailers.smtp.port'),
                    'smtp_username' => config('mail.mailers.smtp.username'),
                ]);
            } catch (TransportExceptionInterface $exception) {
                Log::error('Login email OTP failed.', [
                    'user_id' => $user->id,
                    'email' => $contactValue,
                    'mailer' => config('mail.default'),
                    'smtp_host' => config('mail.mailers.smtp.host'),
                    'smtp_port' => config('mail.mailers.smtp.port'),
                    'smtp_username' => config('mail.mailers.smtp.username'),
                    'error' => $exception->getMessage(),
                    'exception' => $exception::class,
                ]);

                if (! App::environment(['local', 'testing'])) {
                    throw $exception;
                }

                Log::warning('Login email OTP could not be sent through configured mail transport. Falling back to log output.', [
                    'user_id' => $user->id,
                    'email' => $contactValue,
                    'otp' => $code,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        if ($channel === 'mobile' && filled($contactValue)) {
            $this->sms->sendOtp($contactValue, $code, 'member-login');
        }
    }

    private function isValidPhoneNumberForCountry(string $value, string $countryCode = '+880'): bool
    {
        $normalized = PhoneNumber::normalize($value, $countryCode);

        if ($normalized === null) {
            return false;
        }

        $split = PhoneNumber::split($value, $countryCode);
        $nationalNumber = $split['national_number'] ?? '';

        if ($countryCode === '+880') {
            return in_array(strlen($nationalNumber), [10, 11], true);
        }

        return strlen($nationalNumber) >= 4 && strlen($nationalNumber) <= 15;
    }

    private function loginOtpPayload(Request $request): ?array
    {
        if (! $request->session()->has('login_otp_user_id')) {
            return null;
        }

        return [
            'user_id' => $request->session()->get('login_otp_user_id'),
            'channel' => $request->session()->get('login_otp_channel'),
            'contact' => $request->session()->get('login_otp_contact'),
            'code' => $request->session()->get('login_otp_code'),
            'expires_at' => $request->session()->get('login_otp_expires_at'),
            'sent_at' => $request->session()->get('login_otp_sent_at')
                ? Carbon::parse($request->session()->get('login_otp_sent_at'))
                : null,
        ];
    }

    private function localPendingOtpCodes(PendingRegistration $registration): ?array
    {
        if (! App::environment('local')) {
            return null;
        }

        return array_filter([
            'email' => $registration->email_code,
            'mobile' => $registration->mobile_code,
        ]);
    }

    private function forgetLoginOtp(Request $request): void
    {
        $request->session()->forget([
            'login_otp_user_id',
            'login_otp_channel',
            'login_otp_contact',
            'login_otp_code',
            'login_otp_expires_at',
            'login_otp_sent_at',
        ]);
    }

    private function loginOtpResendCooldown(Request $request): int
    {
        $sentAt = $request->session()->get('login_otp_sent_at');

        if (! filled($sentAt)) {
            return 0;
        }

        $remaining = 60 - now()->diffInSeconds(Carbon::parse($sentAt));

        return max(0, $remaining);
    }

    private function loginOtpExpiryCountdown(Request $request): int
    {
        $expiresAt = $request->session()->get('login_otp_expires_at');

        if (! filled($expiresAt)) {
            return 0;
        }

        return max(0, now()->diffInSeconds(Carbon::parse($expiresAt), false));
    }
}
