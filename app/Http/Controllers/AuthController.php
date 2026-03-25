<?php

namespace App\Http\Controllers;

use App\Models\PendingRegistration;
use App\Services\ContactVerificationService;
use App\Support\SiteNavigation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ]);
        }

        $request->session()->regenerate();

        if ($request->user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        if ($request->user()->profile && ! $request->user()->hasCompletedContactVerification()) {
            return redirect()->route('member.verification.show');
        }

        return redirect()->route('member.dashboard');
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
}
