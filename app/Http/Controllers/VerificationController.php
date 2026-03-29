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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class VerificationController extends Controller
{
    public function __construct(
        private readonly ContactVerificationService $verificationService,
    ) {
    }

    public function show(Request $request): View
    {
        [$target, $mode] = $this->resolveVerificationTarget($request);
        $captchaLeft = (int) $request->session()->get('registration_captcha_a', 1);
        $captchaRight = (int) $request->session()->get('registration_captcha_b', 1);

        return view('pages.apply', [
            'menu' => SiteNavigation::menu(),
            'registrationStatuses' => ['Draft', 'Unverified', 'In Progress', 'Pending Review', 'Verified'],
            'captchaLeft' => $captchaLeft,
            'captchaRight' => $captchaRight,
            'showVerificationModal' => true,
            'verificationMode' => $mode,
            'verificationTarget' => $target,
            'verificationEmail' => $mode === 'pending' ? $target->email : $target->email,
            'verificationMobile' => $mode === 'pending' ? $target->mobile_number : ($target->profile?->mobile_number ?: $target->phone),
            'emailVerified' => $target->hasVerifiedEmail(),
            'mobileVerified' => $target->hasVerifiedMobile(),
            'verificationContinueUrl' => $mode === 'pending'
                ? route('member.profile.complete', ['step' => 2])
                : route('member.profile.complete', ['step' => max(2, $target->profile?->completion_step ?? 2)]),
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

        return $this->verificationRedirect($request, 'Email verified successfully.');
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

        if ($mode === 'pending') {
            $this->verificationService->resendPending($target, $channel);
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
                'modal_html' => $this->renderVerificationModal($freshTarget, $freshMode, $message),
            ]);
        }

        return back()->with('success', $message);
    }

    private function verificationRedirect(Request $request, string $message): JsonResponse|RedirectResponse
    {
        [$target, $mode] = $this->resolveVerificationTarget($request);

        if ($mode === 'pending' && $target->hasCompletedContactVerification()) {
            $user = $this->createUserFromPendingRegistration($target, $request);

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => true,
                    'message' => $message.' Both contact methods are verified. Your member account has been created.',
                    'completed' => true,
                    'continue_url' => route('member.profile.complete', ['step' => max(2, $user->profile?->completion_step ?? 2)]),
                    'modal_html' => $this->renderVerificationModal($user, 'user', $message.' Both contact methods are verified. Your member account has been created.'),
                ]);
            }

            return redirect()
                ->route('member.profile.complete', ['step' => max(2, $user->profile?->completion_step ?? 2)])
                ->with('success', $message.' Both contact methods are verified. Your member account has been created.');
        }

        if ($mode === 'user' && $target->fresh()->hasCompletedContactVerification()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => true,
                    'message' => $message.' Both contact methods are now verified.',
                    'completed' => true,
                    'continue_url' => route('member.profile.complete', ['step' => max(2, $target->profile?->completion_step ?? 2)]),
                    'modal_html' => $this->renderVerificationModal($target->fresh()->load('profile', 'verificationTokens'), 'user', $message.' Both contact methods are now verified.'),
                ]);
            }

            return redirect()
                ->route('member.profile.complete', ['step' => max(2, $target->profile?->completion_step ?? 2)])
                ->with('success', $message.' Both contact methods are now verified.');
        }

        if ($request->expectsJson()) {
            [$freshTarget, $freshMode] = $this->resolveVerificationTarget($request);

            return response()->json([
                'ok' => true,
                'message' => $message,
                'completed' => false,
                'modal_html' => $this->renderVerificationModal($freshTarget, $freshMode, $message),
            ]);
        }

        return back()->with('success', $message);
    }

    private function renderVerificationModal(object $target, string $mode, ?string $message = null): string
    {
        $target = $mode === 'user' ? $target->fresh()->load('profile', 'verificationTokens') : $target->fresh();

        return view('partials.verification-modal', [
            'verificationEmail' => $target->email,
            'verificationMobile' => $mode === 'pending' ? $target->mobile_number : ($target->profile?->mobile_number ?: $target->phone),
            'emailVerified' => $target->hasVerifiedEmail(),
            'mobileVerified' => $target->hasVerifiedMobile(),
            'verificationContinueUrl' => $mode === 'pending'
                ? route('member.profile.complete', ['step' => 2])
                : route('member.profile.complete', ['step' => max(2, $target->profile?->completion_step ?? 2)]),
            'verificationSuccessMessage' => $message,
        ])->render();
    }

    private function resolveVerificationTarget(Request $request): array
    {
        if ($request->user()) {
            return [$request->user()->load('profile', 'verificationTokens'), 'user'];
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
        Auth::login($user);

        return $user;
    }
}
