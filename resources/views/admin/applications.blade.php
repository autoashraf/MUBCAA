@extends('layouts.admin')

@section('content')
    <div class="admin-workspace">
        <section class="page-hero admin-page-hero">
            <div>
                <p class="eyebrow">Application Queue</p>
                <h1>Applications</h1>
                <p class="lead">Search and open submitted profiles.</p>
            </div>
        </section>

        <section class="admin-filter-bar">
            <form class="admin-filter-form" method="GET" action="{{ route('admin.applications.index') }}">
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
                    <a class="button button-secondary" href="{{ route('admin.applications.index') }}">Reset</a>
                </div>
            </form>
        </section>

        <section class="workspace-panel admin-queue-panel">
            @if (session('success'))
                <div class="alert-success">{{ session('success') }}</div>
            @endif

            <div class="admin-simple-list">
                @forelse ($applications as $application)
                    <article class="admin-simple-row">
                        <div class="admin-simple-main">
                            <div>
                                <h3>{{ $application->user->name }}</h3>
                                <p class="dashboard-copy">{{ $application->user->email }} | {{ $application->user->phone }}</p>
                            </div>
                            <span class="status-pill status-{{ $application->status }}">{{ str($application->status)->replace('_', ' ')->title() }}</span>
                        </div>

                        <div class="admin-simple-meta">
                            <span>{{ $application->profile_completion }}% complete</span>
                        </div>

                        <div class="admin-simple-actions">
                            <details class="admin-quick-actions">
                                <summary class="button button-secondary">Quick Actions</summary>
                                <div class="admin-quick-actions-menu">
                                    <a class="admin-quick-actions-link" href="{{ route('admin.applications.show', $application) }}">Open Application</a>

                                    @if (! in_array($application->status, ['approved', 'rejected'], true))
                                        <form method="POST" action="{{ route('admin.applications.approve', $application) }}">
                                            @csrf
                                            <button class="admin-quick-actions-link admin-quick-actions-link-button" type="submit">Approve Now</button>
                                        </form>
                                    @endif
                                </div>
                            </details>
                        </div>
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
@endsection
