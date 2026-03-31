@extends('layouts.admin')

@section('content')
    <div class="admin-workspace">
        <section class="page-hero admin-page-hero">
            <div>
                <p class="eyebrow">Contact Inbox</p>
                <h1>Contact Messages</h1>
                <p class="lead">Review messages submitted from the public contact form.</p>
            </div>
        </section>

        <section class="admin-card-grid grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="admin-summary-card rounded-[1.5rem] border border-cyan-100 bg-cyan-50/80 px-5 py-5 shadow-sm">
                <span class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-800">Submissions</span>
                <strong class="mt-3 block text-3xl font-bold tracking-tight text-slate-950">{{ $contactSummary['submissions'] }}</strong>
            </article>
        </section>

        <section class="admin-filter-bar">
            <form class="admin-filter-form admin-filter-form-single" method="GET" action="{{ route('admin.contacts.index') }}">
                <label>
                    <span>Search</span>
                    <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Search by name, email, phone, subject, or message">
                </label>
                <div class="admin-filter-actions">
                    <button class="button button-primary" type="submit">Apply Filters</button>
                    <a class="button button-secondary" href="{{ route('admin.contacts.index') }}">Reset</a>
                </div>
            </form>
        </section>

        <section class="workspace-panel admin-queue-panel">
            <div class="admin-simple-list">
                @forelse ($contactSubmissions as $contactSubmission)
                    <article class="admin-simple-row">
                        <div class="grid gap-4">
                            <div class="admin-simple-main">
                                <div>
                                    <h3>{{ $contactSubmission->subject }}</h3>
                                    <p class="dashboard-copy">{{ $contactSubmission->name }} | {{ $contactSubmission->email }}@if (filled($contactSubmission->phone)) | {{ $contactSubmission->phone }}@endif</p>
                                </div>
                                <span class="status-pill status-approved">{{ $contactSubmission->created_at?->format('d M Y') }}</span>
                            </div>

                            <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-4">
                                <p class="text-sm leading-7 text-slate-600">{{ $contactSubmission->message }}</p>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="list-card">
                        <h3>No contact submissions yet</h3>
                        <p class="dashboard-copy">New messages from the public contact form will appear here.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
