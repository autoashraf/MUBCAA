<?php

namespace App\Http\Controllers;

use App\Models\PendingRegistration;
use App\Services\ContactVerificationService;
use App\Services\MimSmsService;
use App\Support\SiteNavigation;
use App\Mail\VerificationOtpMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Mail\Transport\TransportExceptionInterface;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Support\Carbon;

class AuthController extends Controller
{
    public function __construct(
        private readonly ContactVerificationService $verificationService,
        private readonly MimSmsService $sms,
    ) {
    }

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
        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
        ]);

        $user = $this->resolveLoginUser($validated['identifier']);

        if (! $user) {
            return back()->withInput($request->only('identifier'))->withErrors([
                'identifier' => 'We could not find an account with that email or mobile number.',
            ]);
        }

        if ($user->isAdmin()) {
            return back()->withInput($request->only('identifier'))->withErrors([
                'identifier' => 'Admins must log in with email and password.',
            ]);
        }

        [$channel, $contactValue] = $this->resolveLoginChannel($user, $validated['identifier']);

        $this->issueLoginOtp($request, $user, $channel, $contactValue);

        return redirect()
            ->route('login')
            ->with('success', 'We sent a 6-digit OTP to your registered '.($channel === 'email' ? 'email address.' : 'mobile number.'));
    }

    public function checkLoginIdentifier(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
        ]);

        $user = $this->resolveLoginUser($validated['identifier']);

        if (! $user) {
            return response()->json([
                'exists' => false,
                'message' => 'No member account found with this email or mobile number.',
            ]);
        }

        if ($user->isAdmin()) {
            return response()->json([
                'exists' => false,
                'message' => 'This account uses the admin login form.',
            ]);
        }

        return response()->json([
            'exists' => true,
            'message' => 'Member account found. You can request an OTP.',
        ]);
    }

    public function checkRegistrationField(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'field' => ['required', Rule::in(['email', 'mobile_number', 'referral_code'])],
            'value' => ['nullable', 'string', 'max:255'],
        ]);

        $value = trim((string) $validated['value']);

        if ($value === '') {
            return response()->json([
                'valid' => false,
                'message' => '',
                'type' => 'loading',
            ]);
        }

        return match ($validated['field']) {
            'email' => $this->checkRegistrationEmail($value),
            'mobile_number' => $this->checkRegistrationMobile($value),
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

        $user = \App\Models\User::query()->find($payload['user_id']);

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

        $user = \App\Models\User::query()->find($payload['user_id']);

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
        ]);
    }

    public function register(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'mobile_number' => ['required', 'string', 'max:50', 'unique:users,phone'],
            'passing_year_batch' => ['required', Rule::in($this->passingYearOptions())],
            'discovery_source' => ['nullable', Rule::in(array_merge($this->howDidYouFindUsOptions(), ['Referral Code']))],
            'referral_code' => ['nullable', 'string', 'max:50'],
            'captcha_left' => ['required', 'integer', 'between:1,9'],
            'captcha_right' => ['required', 'integer', 'between:1,9'],
            'captcha_answer' => ['required', 'integer'],
        ]);

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

        $registration = DB::transaction(function () use ($validated, $resolvedReferrer): PendingRegistration {
            PendingRegistration::query()
                ->whereIn('email', [$validated['email']])
                ->orWhere('mobile_number', $validated['mobile_number'])
                ->delete();

            return PendingRegistration::query()->create([
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'mobile_number' => $validated['mobile_number'],
                'passing_year_batch' => $validated['passing_year_batch'],
                'how_did_you_find_us' => ($validated['discovery_source'] ?? null) === 'Referral Code'
                    ? null
                    : ($validated['discovery_source'] ?? null),
                'referred_by_user_id' => $resolvedReferrer?->id,
            ]);
        });

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

    private function checkRegistrationEmail(string $value): JsonResponse
    {
        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'valid' => false,
                'message' => 'Enter a valid email address.',
                'type' => 'error',
            ]);
        }

        $exists = User::query()->where('email', $value)->exists();

        return response()->json([
            'valid' => ! $exists,
            'message' => $exists
                ? 'This email address is already registered.'
                : 'Email address is available.',
            'type' => $exists ? 'error' : 'success',
        ]);
    }

    private function checkRegistrationMobile(string $value): JsonResponse
    {
        $normalized = preg_replace('/\D+/', '', $value) ?: $value;

        if (strlen($normalized) < 11) {
            return response()->json([
                'valid' => false,
                'message' => 'Enter a valid mobile number.',
                'type' => 'error',
            ]);
        }

        $exists = User::query()
            ->where('phone', $value)
            ->orWhere('phone', $normalized)
            ->exists();

        return response()->json([
            'valid' => ! $exists,
            'message' => $exists
                ? 'This mobile number is already registered.'
                : 'Mobile number is available.',
            'type' => $exists ? 'error' : 'success',
        ]);
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

    private function resolveLoginUser(string $identifier): ?\App\Models\User
    {
        $identifier = trim($identifier);

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return \App\Models\User::query()->where('email', $identifier)->first();
        }

        $normalized = preg_replace('/\D+/', '', $identifier) ?: $identifier;

        return \App\Models\User::query()
            ->where('phone', $identifier)
            ->orWhere('phone', $normalized)
            ->first();
    }

    private function resolveLoginChannel(\App\Models\User $user, string $identifier): array
    {
        $identifier = trim($identifier);

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return ['email', $user->email];
        }

        return ['mobile', $user->phone ?: $user->profile?->mobile_number ?: ''];
    }

    private function issueLoginOtp(Request $request, \App\Models\User $user, string $channel, string $contactValue): void
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
            } catch (TransportExceptionInterface $exception) {
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
                ? \Illuminate\Support\Carbon::parse($request->session()->get('login_otp_sent_at'))
                : null,
        ];
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
