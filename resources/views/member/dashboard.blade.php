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
            <div class="dashboard-main">
                <article class="profile-summary-card dashboard-affiliate-card">
                    <span class="panel-card-label">Affiliate</span>
                    <div class="dashboard-affiliate-head">
                        <div>
                            <h3>Invite Alumni</h3>
                            <p class="dashboard-copy">Share your referral link to bring other alumni into the network.</p>
                        </div>
                        <div class="dashboard-affiliate-metrics">
                            <span class="dashboard-chip">{{ $affiliateSummary['total'] }} referrals</span>
                            <span class="dashboard-chip">{{ $affiliateSummary['verified'] }} verified</span>
                            <span class="dashboard-chip">{{ $affiliateSummary['under_review'] }} under review</span>
                        </div>
                    </div>

                    <div class="dashboard-affiliate-grid">
                        <label>
                            <span>Your Affiliate Code</span>
                            <input type="text" value="{{ $affiliateSummary['code'] }}" readonly>
                        </label>
                        <label class="label-wide">
                            <span>Your Referral Link</span>
                            <input type="text" value="{{ $affiliateSummary['link'] }}" readonly>
                        </label>
                    </div>

                    @if ($affiliateSummary['recent']->isNotEmpty())
                        <div class="dashboard-affiliate-list">
                            @foreach ($affiliateSummary['recent'] as $referral)
                                <div class="dashboard-affiliate-row">
                                    <div>
                                        <strong>{{ $referral->name }}</strong>
                                        <span>{{ $referral->email }}</span>
                                    </div>
                                    <span class="status-pill status-{{ $referral->membership_status }}">{{ str($referral->membership_status)->replace('_', ' ')->title() }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="dashboard-copy">No referred members yet.</p>
                    @endif
                </article>
            </div>
        </div>
    </section>

@endsection
