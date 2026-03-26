<?php

namespace App\Http\Controllers;

use App\Models\MembershipApplication;
use App\Support\SiteNavigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $status = (string) $request->input('status', 'all');

        $applications = MembershipApplication::query()
            ->with(['user.profile'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->whereHas('user', function ($userQuery) use ($search): void {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($status !== 'all', function ($query) use ($status): void {
                $query->where('status', $status);
            })
            ->latest()
            ->get();

        $allApplications = MembershipApplication::query()->get();

        return view('admin.dashboard', [
            'menu' => SiteNavigation::menu(),
            'applications' => $applications,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'statusOptions' => [
                'all' => 'All statuses',
                'draft' => 'Draft',
                'unverified' => 'Unverified',
                'in_progress' => 'In Progress',
                'pending_review' => 'Pending Review',
                'under_review' => 'Under Review',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
            ],
            'summary' => [
                'pending' => $allApplications->whereIn('status', ['draft', 'unverified', 'in_progress', 'pending_review', 'pending'])->count(),
                'under_review' => $allApplications->whereIn('status', ['under_review', 'needs_correction'])->count(),
                'approved' => $allApplications->where('status', 'approved')->count(),
                'rejected' => $allApplications->where('status', 'rejected')->count(),
            ],
        ]);
    }

    public function show(MembershipApplication $application): View
    {
        $application->load(['user.profile', 'reviewer']);

        return view('admin.show', [
            'menu' => SiteNavigation::menu(),
            'application' => $application,
            'user' => $application->user,
            'profile' => $application->user->profile,
        ]);
    }

    public function approve(Request $request, MembershipApplication $application): RedirectResponse
    {
        $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $application->load('user');

        $application->update([
            'current_step' => $application->total_steps,
            'status' => 'approved',
            'admin_notes' => $request->input('admin_notes'),
            'reviewed_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $application->user->update([
            'approval_step' => $application->total_steps,
            'membership_status' => 'verified',
        ]);

        return back()->with('success', 'Application approved successfully.');
    }

    public function reject(Request $request, MembershipApplication $application): RedirectResponse
    {
        $validated = $request->validate([
            'admin_notes' => ['required', 'string', 'max:2000'],
        ]);

        $application->load('user');

        $application->update([
            'status' => 'rejected',
            'admin_notes' => $validated['admin_notes'],
            'reviewed_by' => $request->user()->id,
        ]);

        $application->user->update([
            'membership_status' => 'rejected',
        ]);

        return back()->with('success', 'Application rejected.');
    }
}
