@extends('layouts.admin')

@section('content')
    <div class="admin-workspace">
        <section class="page-hero admin-page-hero">
            <div>
                <p class="eyebrow">Affiliate System</p>
                <h1>Affiliates</h1>
                <p class="lead">Track member referral codes and the signups they generate.</p>
            </div>
        </section>

        <section class="admin-card-grid">
            <article class="admin-summary-card">
                <span>Total Affiliates</span>
                <strong>{{ $affiliateOverview['members'] }}</strong>
            </article>
            <article class="admin-summary-card">
                <span>Total Referrals</span>
                <strong>{{ $affiliateOverview['referrals'] }}</strong>
            </article>
            <article class="admin-summary-card">
                <span>Verified Referrals</span>
                <strong>{{ $affiliateOverview['verified_referrals'] }}</strong>
            </article>
            <article class="admin-summary-card">
                <span>Under Review</span>
                <strong>{{ $affiliateOverview['under_review_referrals'] }}</strong>
            </article>
        </section>

        <section class="admin-filter-bar">
            <form class="admin-filter-form" method="GET" action="{{ route('admin.affiliates.index') }}">
                <label>
                    <span>Search</span>
                    <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Search by name, email, phone, or code">
                </label>
                <div class="admin-filter-actions">
                    <button class="button button-primary" type="submit">Apply Filters</button>
                    <a class="button button-secondary" href="{{ route('admin.affiliates.index') }}">Reset</a>
                </div>
            </form>
        </section>

        <section class="workspace-panel admin-queue-panel">
            <div class="admin-simple-list">
                @forelse ($affiliates as $affiliate)
                    <article class="admin-simple-row">
                        <div class="admin-simple-main">
                            <div>
                                <h3>{{ $affiliate->name }}</h3>
                                <p class="dashboard-copy">{{ $affiliate->email }} | {{ $affiliate->phone }}</p>
                            </div>
                            <span class="status-pill status-{{ $affiliate->membership_status }}">{{ str($affiliate->membership_status)->replace('_', ' ')->title() }}</span>
                        </div>

                        <div class="admin-simple-meta">
                            <span>{{ $affiliate->affiliate_code }}</span>
                            <span>{{ $affiliate->total_referrals_count }} referrals</span>
                            <span>{{ $affiliate->verified_referrals_count }} verified</span>
                            <span>{{ $affiliate->under_review_referrals_count }} under review</span>
                        </div>

                        <div class="admin-simple-actions">
                            <details class="admin-quick-actions">
                                <summary class="button button-secondary">Quick Actions</summary>
                                <div class="admin-quick-actions-menu">
                                    <a class="admin-quick-actions-link" href="{{ $affiliate->affiliateLink() }}" target="_blank" rel="noopener">Open Referral Link</a>
                                    <a class="admin-quick-actions-link" href="{{ route('admin.applications.index', ['search' => $affiliate->email]) }}">View Application</a>
                                </div>
                            </details>
                        </div>
                    </article>
                @empty
                    <div class="list-card">
                        <h3>No affiliates yet</h3>
                        <p class="dashboard-copy">Affiliate members will appear here after referral-based signups begin.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
