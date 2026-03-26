<?php

namespace App\Http\Controllers;

use App\Models\PendingRegistration;
use App\Services\ContactVerificationService;
use App\Support\SiteNavigation;
use App\Mail\VerificationOtpMail;
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
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        private readonly ContactVerificationService $verificationService,
    ) {
    }

    public function showLogin(): View
    {
        return view('auth.login', [
            'menu' => SiteNavigation::menu(),
            'loginOtpPending' => session()->has('login_otp_user_id'),
            'loginOtpChannel' => session('login_otp_channel'),
            'loginOtpContact' => session('login_otp_contact'),
            'loginOtpPreview' => App::environment(['local', 'testing']) ? session('login_otp_code') : null,
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

        [$channel, $contactValue] = $this->resolveLoginChannel($user, $validated['identifier']);

        $this->issueLoginOtp($request, $user, $channel, $contactValue);

        return redirect()
            ->route('login')
            ->with('success', 'We sent a 6-digit OTP to your registered '.($channel === 'email' ? 'email address.' : 'mobile number.'));
    }

    public function verifyLoginOtp(Request $request): RedirectResponse
    {
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

        $this->issueLoginOtp($request, $user, $payload['channel'], $payload['contact']);

        return redirect()->route('login')->with('success', 'A new OTP has been sent.');
    }

    public function showRegistration(Request $request): View
    {
        [$captchaLeft, $captchaRight] = $this->generateCaptchaChallenge($request);

        return view('pages.apply', [
            'menu' => SiteNavigation::menu(),
            'registrationStatuses' => ['Draft', 'Unverified', 'In Progress', 'Pending Review', 'Verified'],
            'captchaLeft' => $captchaLeft,
            'captchaRight' => $captchaRight,
        ]);
    }

    public function register(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'mobile_number' => ['required', 'string', 'max:50', 'unique:users,phone'],
            'passing_year_batch' => ['required', 'string', 'max:50'],
            'captcha_answer' => ['required', 'integer'],
        ]);

        $captchaSum = (int) $request->session()->get('registration_captcha_a', -1) + (int) $request->session()->get('registration_captcha_b', -1);

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

        $registration = DB::transaction(function () use ($validated): PendingRegistration {
            PendingRegistration::query()
                ->whereIn('email', [$validated['email']])
                ->orWhere('mobile_number', $validated['mobile_number'])
                ->delete();

            return PendingRegistration::query()->create([
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'mobile_number' => $validated['mobile_number'],
                'passing_year_batch' => $validated['passing_year_batch'],
            ]);
        });

        $request->session()->put('pending_registration_id', $registration->id);
        $this->verificationService->issueForPendingRegistration($registration);
        $request->session()->forget(['registration_captcha_a', 'registration_captcha_b']);

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
                    'emailToken' => $registration->fresh()->email_code,
                    'mobileToken' => $registration->fresh()->mobile_code,
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
            Log::info('Login mobile OTP generated.', [
                'user_id' => $user->id,
                'mobile_number' => $contactValue,
                'otp' => $code,
            ]);
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
        ]);
    }
}
