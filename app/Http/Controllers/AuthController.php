<?php

namespace App\Http\Controllers;

use App\Models\PendingRegistration;
use App\Services\ContactVerificationService;
use App\Support\SiteNavigation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function showRegistration(): View
    {
        return view('pages.apply', [
            'menu' => SiteNavigation::menu(),
            'registrationStatuses' => ['Draft', 'Unverified', 'In Progress', 'Pending Review', 'Verified'],
        ]);
    }

    public function register(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'mobile_number' => ['required', 'string', 'max:50', 'unique:users,phone'],
            'passing_year_batch' => ['required', 'string', 'max:50'],
            'student_id_or_roll' => ['required', 'string', 'max:100', 'unique:member_profiles,student_id_or_roll'],
            'current_city' => ['required', 'string', 'max:100'],
        ]);

        $registration = DB::transaction(function () use ($validated): PendingRegistration {
            PendingRegistration::query()
                ->whereIn('email', [$validated['email']])
                ->orWhere('mobile_number', $validated['mobile_number'])
                ->orWhere('student_id_or_roll', $validated['student_id_or_roll'])
                ->delete();

            return PendingRegistration::query()->create([
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'mobile_number' => $validated['mobile_number'],
                'passing_year_batch' => $validated['passing_year_batch'],
                'student_id_or_roll' => $validated['student_id_or_roll'],
                'current_city' => $validated['current_city'],
            ]);
        });

        $request->session()->put('pending_registration_id', $registration->id);
        $this->verificationService->issueForPendingRegistration($registration);

        $message = 'Basic information saved. Verify your mobile number and email address with OTP codes to create your member account.';

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

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
