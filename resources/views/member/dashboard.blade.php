@extends('layouts.app')

@section('content')
    @php
        $documentStatus = $application?->status ?? $user->membership_status;
        $documentsUnlocked = in_array($documentStatus, ['approved', 'verified'], true) || $user->membership_status === 'verified';
        $certificateUnlocked = $user->membership_status === 'verified';
    @endphp
    <section class="page-hero">
        <div class="wrap">
            <div class="dashboard-hero dashboard-hero-member">
                <div class="dashboard-hero-copy">
                    <div class="dashboard-profile-band">
                        <div class="dashboard-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                        <div>
                            <p class="eyebrow">Member Dashboard</p>
                            <h1>{{ $user->name }}</h1>
                            <p class="lead">Your member area for profile management, workflow tracking, and printable membership documents.</p>
                        </div>
                    </div>
                    <div class="hero-actions">
                        @if ($profileCompletion < 100)
                            <a class="button button-primary" href="{{ route('member.profile.complete', ['step' => max(2, ($user->profile?->completion_step ?? 1) + 1 > 10 ? 10 : ($user->profile?->completion_step ?? 1) + 1)]) }}">
                                Complete Alumni Profile
                            </a>
                        @endif
                        @if ($documentsUnlocked)
                            <a class="button button-secondary" href="{{ route('member.documents.profile') }}" target="_blank">View A4 Profile</a>
                            <a class="button button-secondary" href="{{ route('member.documents.id-card') }}" target="_blank">View ID Card</a>
                        @endif
                        @if ($certificateUnlocked)
                            <a class="button button-secondary" href="{{ route('member.documents.certificate') }}" target="_blank">View Certificate</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="wrap">
            <div class="dashboard-review-banner">
                <span class="panel-card-label">Application Status</span>
                <strong>{{ $documentsUnlocked ? 'Application Approved' : 'Application Under Review' }}</strong>
                <p>{{ $documentsUnlocked ? 'Your review is complete. Your member documents are now available.' : 'Your alumni membership application is currently under review. Documents will unlock after approval.' }}</p>
                <div class="dashboard-review-progress">
                    <div class="dashboard-review-progress-head">
                        <span>Profile Progress</span>
                        <strong>{{ $profileCompletion }}%</strong>
                    </div>
                    <div class="dashboard-review-progress-track" aria-hidden="true">
                        <span style="width: {{ $profileCompletion }}%"></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="wrap dashboard-overview">
            <div class="dashboard-main"></div>
        </div>
    </section>

@endsection
