<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentController extends Controller
{
    public function profile(Request $request): View
    {
        $user = $request->user()->load('profile', 'application');
        abort_unless($this->canAccessMemberDocuments($user), 403);

        return view('documents.profile', [
            'user' => $user,
            'profile' => $user->profile,
            'application' => $user->application,
        ]);
    }

    public function idCard(Request $request): View
    {
        $user = $request->user()->load('profile', 'application');
        abort_unless($this->canAccessMemberDocuments($user), 403);

        return view('documents.id-card', [
            'user' => $user,
            'profile' => $user->profile,
        ]);
    }

    public function certificate(Request $request): View
    {
        $user = $request->user()->load('profile', 'application');

        abort_unless($user->membership_status === 'verified', 403);

        return view('documents.certificate', [
            'user' => $user,
            'profile' => $user->profile,
            'application' => $user->application,
        ]);
    }

    private function canAccessMemberDocuments($user): bool
    {
        return $user->membership_status === 'verified'
            || in_array($user->application?->status, ['approved'], true);
    }
}
