<?php

namespace App\Http\Controllers;

use App\Models\MemberProfile;
use App\Models\MembershipApplication;
use App\Models\MembershipType;
use App\Models\User;
use App\Support\SiteNavigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
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

        return $request->user()->isAdmin()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('member.dashboard');
    }

    public function showRegistration(): View
    {
        $types = MembershipType::query()->with('workflowSteps')->where('is_active', true)->orderBy('sort_order')->get();

        return view('pages.apply', [
            'menu' => SiteNavigation::menu(),
            'membershipTypes' => $types,
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'phone' => ['required', 'string', 'max:50'],
            'membership_type_id' => ['required', 'exists:membership_types,id'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],
        ]);

        $membershipType = MembershipType::query()->findOrFail($validated['membership_type_id']);

        $user = DB::transaction(function () use ($validated, $membershipType): User {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => $validated['password'],
                'role' => 'member',
                'membership_status' => 'pending',
                'approval_step' => 1,
            ]);

            MemberProfile::query()->create([
                'user_id' => $user->id,
                'membership_type_id' => $membershipType->id,
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'city' => $validated['city'],
                'country' => $validated['country'],
                'occupation' => $validated['occupation'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'bio' => $validated['bio'] ?? null,
                'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
            ]);

            MembershipApplication::query()->create([
                'user_id' => $user->id,
                'membership_type_id' => $membershipType->id,
                'status' => 'pending',
                'current_step' => 1,
                'total_steps' => $membershipType->steps_count,
                'submitted_at' => now(),
            ]);

            return $user;
        });

        Auth::login($user);

        return redirect()->route('member.dashboard')->with('success', 'Registration completed. Your membership is now in the approval workflow.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
