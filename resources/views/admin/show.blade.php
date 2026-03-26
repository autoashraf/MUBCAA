@extends('layouts.app')

@section('content')
    <section class="section admin-shell">
        <div class="wrap admin-detail-stack">
            <div class="admin-detail-head">
                <div>
                    <p class="eyebrow">Admin Application View</p>
                    <h1>{{ $user->name }}</h1>
                    <p class="lead">Review the submitted alumni profile, current workflow state, and supporting documents.</p>
                </div>
                <div class="admin-detail-actions">
                    <a class="button button-secondary" href="{{ route('admin.dashboard') }}">Back to Admin Panel</a>
                </div>
            </div>

            <div class="admin-detail-grid">
                <article class="profile-summary-card">
                    <span class="panel-card-label">Application</span>
                    <dl class="profile-summary-list">
                        <div><dt>Status</dt><dd>{{ str($application->status)->replace('_', ' ')->title() }}</dd></div>
                        <div><dt>Current Step</dt><dd>{{ $application->current_step }} / {{ $application->total_steps }}</dd></div>
                        <div><dt>Submitted At</dt><dd>{{ optional($application->submitted_at)->format('d M Y h:i A') ?: 'Not submitted' }}</dd></div>
                        <div><dt>Reviewed By</dt><dd>{{ $application->reviewer?->name ?: 'Not assigned' }}</dd></div>
                    </dl>
                </article>

                <article class="profile-summary-card">
                    <span class="panel-card-label">Member</span>
                    <dl class="profile-summary-list">
                        <div><dt>Member No</dt><dd>{{ $user->memberNumber() }}</dd></div>
                        <div><dt>Email</dt><dd>{{ $user->email }}</dd></div>
                        <div><dt>Phone</dt><dd>{{ $user->phone ?: 'Not added' }}</dd></div>
                        <div><dt>Verification</dt><dd>{{ $user->hasCompletedContactVerification() ? 'Email and mobile verified' : 'Verification incomplete' }}</dd></div>
                    </dl>
                </article>

                <article class="profile-summary-card">
                    <span class="panel-card-label">Academic</span>
                    <dl class="profile-summary-list">
                        <div><dt>SSC Year</dt><dd>{{ $profile?->ssc_passing_year ?: 'Not added' }}</dd></div>
                        <div><dt>HSC Year</dt><dd>{{ $profile?->hsc_passing_year ?: 'Not added' }}</dd></div>
                        <div><dt>Group</dt><dd>{{ $profile?->group ?: 'Not added' }}</dd></div>
                        <div><dt>Campus</dt><dd>{{ $profile?->campus_branch ?: 'Not added' }}</dd></div>
                    </dl>
                </article>

                <article class="profile-summary-card">
                    <span class="panel-card-label">Contact & Professional</span>
                    <dl class="profile-summary-list">
                        <div><dt>City / District</dt><dd>{{ $profile?->city_district ?: 'Not added' }}</dd></div>
                        <div><dt>Occupation</dt><dd>{{ $profile?->occupation ?: 'Not added' }}</dd></div>
                        <div><dt>Organization</dt><dd>{{ $profile?->organization_name ?: 'Not added' }}</dd></div>
                        <div><dt>Designation</dt><dd>{{ $profile?->designation ?: 'Not added' }}</dd></div>
                    </dl>
                </article>
            </div>

            <div class="admin-detail-grid">
                <article class="profile-summary-card">
                    <span class="panel-card-label">Admin Notes</span>
                    <p class="dashboard-copy">{{ $application->admin_notes ?: 'No admin notes added yet.' }}</p>
                </article>

                <article class="profile-summary-card">
                    <span class="panel-card-label">Admin Actions</span>
                    @if (! in_array($application->status, ['approved', 'rejected'], true))
                        <div class="admin-actions-grid">
                            <form class="panel-card admin-action-card" method="POST" action="{{ route('admin.applications.advance', $application) }}">
                                @csrf
                                <p class="panel-card-label">{{ $application->current_step >= $application->total_steps - 1 ? 'Approve Member' : 'Advance Step' }}</p>
                                <label for="detail-advance-notes">Admin notes</label>
                                <textarea id="detail-advance-notes" name="admin_notes" rows="4" placeholder="Optional committee note"></textarea>
                                <button class="button button-primary" type="submit">
                                    {{ $application->current_step >= $application->total_steps - 1 ? 'Approve Application' : 'Advance to Next Step' }}
                                </button>
                            </form>

                            <form class="panel-card admin-action-card" method="POST" action="{{ route('admin.applications.reject', $application) }}">
                                @csrf
                                <p class="panel-card-label">Reject</p>
                                <label for="detail-reject-notes">Rejection note</label>
                                <textarea id="detail-reject-notes" name="admin_notes" rows="4" placeholder="Required rejection reason" required></textarea>
                                <button class="button danger-button" type="submit">Reject Application</button>
                            </form>
                        </div>
                    @else
                        <p class="dashboard-copy">This application has already been finalized.</p>
                    @endif
                </article>
            </div>
        </div>
    </section>
@endsection
