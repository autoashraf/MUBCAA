<?php

namespace App\Http\Controllers;

use App\Models\MembershipApplication;
use App\Support\SiteNavigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(): View
    {
        $applications = MembershipApplication::query()
            ->with(['user.profile'])
            ->latest()
            ->get();

        return view('admin.dashboard', [
            'menu' => SiteNavigation::menu(),
            'applications' => $applications,
            'summary' => [
                'pending' => $applications->whereIn('status', ['draft', 'unverified', 'in_progress', 'pending_review', 'pending'])->count(),
                'under_review' => $applications->whereIn('status', ['under_review', 'needs_correction'])->count(),
                'approved' => $applications->where('status', 'approved')->count(),
                'rejected' => $applications->where('status', 'rejected')->count(),
            ],
        ]);
    }

    public function advance(Request $request, MembershipApplication $application): RedirectResponse
    {
        $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $application->load('user');

        $nextStep = min($application->current_step + 1, $application->total_steps);
        $isFinalStep = $nextStep >= $application->total_steps;
        $status = $isFinalStep ? 'approved' : 'under_review';

        $application->update([
            'current_step' => $nextStep,
            'status' => $status,
            'admin_notes' => $request->input('admin_notes'),
            'reviewed_by' => $request->user()->id,
            'approved_at' => $isFinalStep ? now() : null,
        ]);

        $application->user->update([
            'approval_step' => $nextStep,
            'membership_status' => $isFinalStep ? 'verified' : 'pending_review',
        ]);

        return back()->with('success', 'Application moved to the next workflow step.');
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
