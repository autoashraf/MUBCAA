@extends('layouts.app')

@section('content')
    <section class="section admin-shell">
        <div class="wrap">
            <div class="admin-workspace">
                <section id="overview" class="page-hero admin-page-hero">
                    <div>
                        <p class="eyebrow">Admin Dashboard</p>
                        <h1>Admin Panel</h1>
                        <p class="lead">Review applications, search the queue, and move members through approval with a cleaner control surface.</p>
                    </div>
                </section>

                <section class="admin-card-grid">
                    <article class="admin-summary-card">
                        <span>Pending</span>
                        <strong>{{ $summary['pending'] }}</strong>
                        <p>New applications waiting for first review.</p>
                    </article>
                    <article class="admin-summary-card">
                        <span>Under Review</span>
                        <strong>{{ $summary['under_review'] }}</strong>
                        <p>Applications currently moving through approval steps.</p>
                    </article>
                    <article class="admin-summary-card">
                        <span>Approved</span>
                        <strong>{{ $summary['approved'] }}</strong>
                        <p>Members who completed the full workflow.</p>
                    </article>
                    <article class="admin-summary-card">
                        <span>Rejected</span>
                        <strong>{{ $summary['rejected'] }}</strong>
                        <p>Applications declined with admin notes.</p>
                    </article>
                </section>

                <section class="admin-filter-bar">
                    <form class="admin-filter-form" method="GET" action="{{ route('admin.dashboard') }}">
                        <label>
                            <span>Search</span>
                            <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Search by name, email, or phone">
                        </label>
                        <label>
                            <span>Status</span>
                            <select name="status">
                                @foreach ($statusOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <div class="admin-filter-actions">
                            <button class="button button-primary" type="submit">Apply Filters</button>
                            <a class="button button-secondary" href="{{ route('admin.dashboard') }}">Reset</a>
                        </div>
                    </form>
                </section>

                <section id="queue" class="workspace-panel admin-queue-panel">
                    @if (session('success'))
                        <div class="alert-success">{{ session('success') }}</div>
                    @endif

                    <div class="application-list">
                        @forelse ($applications as $application)
                            <article class="application-card admin-application-card">
                                <div class="application-meta">
                                    <div>
                                        <p class="panel-card-label">Alumni Membership Application</p>
                                        <h3>{{ $application->user->name }}</h3>
                                        <p class="dashboard-copy">{{ $application->user->email }} | {{ $application->user->phone }}</p>
                                        <p class="dashboard-copy">Step {{ $application->current_step }} / {{ $application->total_steps }}</p>
                                    </div>
                                    <span class="status-pill status-{{ $application->status }}">{{ str($application->status)->replace('_', ' ')->title() }}</span>
                                </div>

                                <div class="admin-application-summary">
                                    <div><span>Member No</span><strong>{{ $application->user->memberNumber() }}</strong></div>
                                    <div><span>Submitted</span><strong>{{ optional($application->submitted_at)->format('d M Y') ?: 'Not submitted' }}</strong></div>
                                    <div><span>City</span><strong>{{ $application->user->profile?->city_district ?: 'Not added' }}</strong></div>
                                    <div><span>Occupation</span><strong>{{ $application->user->profile?->occupation ?: 'Not added' }}</strong></div>
                                </div>

                                <div class="admin-card-actions">
                                    <a class="button button-secondary" href="{{ route('admin.applications.show', $application) }}">Open Application</a>
                                </div>

                                @if (! in_array($application->status, ['approved', 'rejected'], true))
                                    <div class="admin-actions-grid">
                                        <form class="panel-card admin-action-card" method="POST" action="{{ route('admin.applications.approve', $application) }}">
                                            @csrf
                                            <p class="panel-card-label">Approve</p>
                                            <label for="advance-notes-{{ $application->id }}">Admin notes</label>
                                            <textarea id="advance-notes-{{ $application->id }}" name="admin_notes" rows="4" placeholder="Optional committee note"></textarea>
                                            <button class="button button-primary" type="submit">Approve Application</button>
                                        </form>

                                        <form class="panel-card admin-action-card" method="POST" action="{{ route('admin.applications.reject', $application) }}">
                                            @csrf
                                            <p class="panel-card-label">Reject</p>
                                            <label for="reject-notes-{{ $application->id }}">Rejection note</label>
                                            <textarea id="reject-notes-{{ $application->id }}" name="admin_notes" rows="4" placeholder="Required rejection reason" required></textarea>
                                            <button class="button danger-button" type="submit">Reject Application</button>
                                        </form>
                                    </div>
                                @endif
                            </article>
                        @empty
                            <div class="list-card">
                                <h3>No applications yet</h3>
                                <p class="dashboard-copy">New registrations will appear here for committee review.</p>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </section>
@endsection
