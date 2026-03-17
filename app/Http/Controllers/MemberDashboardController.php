<?php

namespace App\Http\Controllers;

use App\Support\SiteNavigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user()->load('profile.membershipType.workflowSteps', 'application.membershipType.workflowSteps');
        $profile = $user->profile;
        $fields = [
            $user->name,
            $user->email,
            $user->phone,
            $profile?->address,
            $profile?->city,
            $profile?->country,
            $profile?->occupation,
            $profile?->date_of_birth,
            $profile?->emergency_contact_name,
            $profile?->emergency_contact_phone,
            $profile?->bio,
        ];
        $completedFields = collect($fields)->filter(fn ($value) => filled($value))->count();
        $profileCompletion = (int) round(($completedFields / count($fields)) * 100);

        return view('member.dashboard', [
            'menu' => SiteNavigation::menu(),
            'user' => $user,
            'application' => $user->application,
            'workflowSteps' => $user->application?->membershipType?->workflowSteps ?? collect(),
            'profileCompletion' => $profileCompletion,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],
        ]);

        $user = $request->user();
        $user->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
        ]);

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            $validated,
        );

        return back()->with('success', 'Profile updated successfully.');
    }
}
