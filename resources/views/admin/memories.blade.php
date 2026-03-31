@extends('layouts.admin')

@section('content')
    <div class="admin-workspace">
        <section class="page-hero admin-page-hero">
            <div>
                <p class="eyebrow">Memory Review</p>
                <h1>Memories</h1>
                <p class="lead">Review member stories and decide which submissions are approved.</p>
            </div>
        </section>

        <section class="admin-card-grid grid gap-4 sm:grid-cols-3">
            <article class="admin-summary-card rounded-[1.5rem] border border-cyan-100 bg-cyan-50/80 px-5 py-5 shadow-sm">
                <span class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-800">Pending Review</span>
                <strong class="mt-3 block text-3xl font-bold tracking-tight text-slate-950">{{ $memorySummary['pending_review'] }}</strong>
            </article>
            <article class="admin-summary-card rounded-[1.5rem] border border-cyan-100 bg-cyan-50/80 px-5 py-5 shadow-sm">
                <span class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-800">Approved</span>
                <strong class="mt-3 block text-3xl font-bold tracking-tight text-slate-950">{{ $memorySummary['approved'] }}</strong>
            </article>
            <article class="admin-summary-card rounded-[1.5rem] border border-cyan-100 bg-cyan-50/80 px-5 py-5 shadow-sm">
                <span class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-800">Rejected</span>
                <strong class="mt-3 block text-3xl font-bold tracking-tight text-slate-950">{{ $memorySummary['rejected'] }}</strong>
            </article>
        </section>

        <section class="admin-filter-bar">
            <form class="admin-filter-form" method="GET" action="{{ route('admin.memories.index') }}">
                <label>
                    <span>Search</span>
                    <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Search by title, story, member, or email">
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
                    <a class="button button-secondary" href="{{ route('admin.memories.index') }}">Reset</a>
                </div>
            </form>
        </section>

        <section class="workspace-panel admin-queue-panel">
            <div class="admin-simple-list">
                @forelse ($memorySubmissions as $memorySubmission)
                    <article class="admin-simple-row">
                        <div class="grid gap-5">
                            <div class="admin-simple-main">
                                <div>
                                    <h3>{{ $memorySubmission->title }}</h3>
                                    <p class="dashboard-copy">{{ $memorySubmission->user->name }} | {{ $memorySubmission->user->email }}</p>
                                </div>
                                <span class="status-pill status-{{ $memorySubmission->status }}">{{ str($memorySubmission->status)->replace('_', ' ')->title() }}</span>
                            </div>

                            <div class="grid gap-3">
                                <p class="dashboard-copy">{{ \Illuminate\Support\Str::limit($memorySubmission->memory, 320) }}</p>

                                <div class="flex flex-wrap gap-3 text-sm text-slate-500">
                                    <span>{{ count($memorySubmission->photos ?? []) }} photo{{ count($memorySubmission->photos ?? []) === 1 ? '' : 's' }}</span>
                                    <span>Submitted {{ $memorySubmission->created_at?->diffForHumans() }}</span>
                                    @if ($memorySubmission->reviewer)
                                        <span>Reviewed by {{ $memorySubmission->reviewer->name }}</span>
                                    @endif
                                </div>

                                @if (! empty($memorySubmission->photos))
                                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                        @foreach ($memorySubmission->photos as $photo)
                                            <a href="{{ asset('storage/'.$photo) }}" target="_blank" rel="noopener" class="overflow-hidden rounded-[1.25rem] border border-slate-200 bg-slate-50">
                                                <img class="h-40 w-full object-cover" src="{{ asset('storage/'.$photo) }}" alt="Memory submission photo">
                                            </a>
                                        @endforeach
                                    </div>
                                @endif

                                @if (filled($memorySubmission->admin_notes))
                                    <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                        <strong class="block text-slate-900">Admin note</strong>
                                        <p class="mt-1">{{ $memorySubmission->admin_notes }}</p>
                                    </div>
                                @endif
                            </div>

                            @if ($memorySubmission->status !== 'approved')
                                <form class="grid gap-3 rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-4" method="POST" action="{{ route('admin.memories.approve', $memorySubmission) }}">
                                    @csrf
                                    <label>
                                        <span class="block text-sm font-semibold text-slate-900">Approval note</span>
                                        <textarea class="mt-2 w-full rounded-[1rem] border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700" name="admin_notes" rows="3" placeholder="Optional note for this submission"></textarea>
                                    </label>
                                    <div>
                                        <button class="button button-primary" type="submit">Approve Memory</button>
                                    </div>
                                </form>
                            @endif

                            @if ($memorySubmission->status !== 'rejected')
                                <form class="grid gap-3 rounded-[1.25rem] border border-rose-100 bg-rose-50/60 px-4 py-4" method="POST" action="{{ route('admin.memories.reject', $memorySubmission) }}">
                                    @csrf
                                    <label>
                                        <span class="block text-sm font-semibold text-slate-900">Rejection note</span>
                                        <textarea class="mt-2 w-full rounded-[1rem] border border-rose-200 bg-white px-4 py-3 text-sm text-slate-700" name="admin_notes" rows="3" placeholder="Explain why this submission is being rejected" required></textarea>
                                    </label>
                                    <div>
                                        <button class="button button-secondary" type="submit">Reject Memory</button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="list-card">
                        <h3>No memories yet</h3>
                        <p class="dashboard-copy">New member stories will appear here for review.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
