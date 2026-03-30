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
                            <a class="button button-primary" href="{{ route('member.profile.complete', ['step' => max(2, min(10, $user->profile?->completion_step ?? 2))]) }}">
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
                <div class="dashboard-hero-meta">
                    <div class="dashboard-kpi-grid">
                        <article class="dashboard-kpi-card">
                            <span>Profile Completion</span>
                            <strong>{{ $profileCompletion }}%</strong>
                            <small>{{ $profileCompletion >= 100 ? 'All required profile steps completed' : 'Complete the remaining steps to unlock review' }}</small>
                        </article>
                        <article class="dashboard-kpi-card">
                            <span>Application Status</span>
                            <strong>{{ $documentsUnlocked ? 'Approved' : 'Under Review' }}</strong>
                            <small>{{ $documentsUnlocked ? 'Your documents are now available' : 'Documents unlock after approval' }}</small>
                        </article>
                        <article class="dashboard-kpi-card">
                            <span>Documents</span>
                            <strong>{{ $documentsUnlocked ? ($certificateUnlocked ? '3' : '2') : '0' }}</strong>
                            <small>{{ $documentsUnlocked ? 'Printable member files available' : 'Waiting for review completion' }}</small>
                        </article>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="wrap">
            <div class="dashboard-review-banner">
                <div class="dashboard-review-status-row">
                    <span class="panel-card-label">Application Status</span>
                    <span class="status-pill status-{{ $documentsUnlocked ? 'verified' : 'under_review' }}">{{ $documentsUnlocked ? 'Approved' : 'Under Review' }}</span>
                </div>
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

                <aside class="profile-summary-card dashboard-quick-links-card">
                    <span class="panel-card-label">Quick Actions</span>
                    <div class="dashboard-quick-links">
                        <a class="dashboard-quick-link" href="{{ route('member.profile') }}">
                            <strong>Open Profile Summary</strong>
                            <span>Review your saved alumni information</span>
                        </a>
                        <a class="dashboard-quick-link" href="{{ route('member.profile.complete', ['step' => max(2, min(10, $user->profile?->completion_step ?? 2))]) }}">
                            <strong>Profile Wizard</strong>
                            <span>Continue editing your registration steps</span>
                        </a>
                        @if ($documentsUnlocked)
                            <a class="dashboard-quick-link" href="{{ route('member.documents.profile') }}" target="_blank">
                                <strong>Printable A4 Profile</strong>
                                <span>Open the member profile document</span>
                            </a>
                            <a class="dashboard-quick-link" href="{{ route('member.documents.id-card') }}" target="_blank">
                                <strong>Printable ID Card</strong>
                                <span>Open your member ID card</span>
                            </a>
                        @endif
                        @if ($certificateUnlocked)
                            <a class="dashboard-quick-link" href="{{ route('member.documents.certificate') }}" target="_blank">
                                <strong>Certificate</strong>
                                <span>Download your membership certificate</span>
                            </a>
                        @endif
                    </div>
                </aside>
            </div>
        </div>
    </section>

@endsection
