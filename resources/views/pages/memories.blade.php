@extends('layouts.app')

@section('content')
    <section class="page-hero">
        <div class="wrap">
            <div class="page-hero-card memory-hero-card">
                <p class="eyebrow">{{ $page['eyebrow'] }}</p>
                <h1>{{ $page['title'] }}</h1>
                <p class="lead">{{ $page['intro'] }}</p>
                <div class="mt-6">
                    @auth
                        <a class="button button-primary" href="{{ route('memories.submit') }}">Submit Your Memory</a>
                    @else
                        <a class="button button-primary" href="{{ route('login') }}">Login to Submit a Memory</a>
                    @endauth
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="wrap">
            @if ($memories->isNotEmpty())
                <div class="memory-archive-grid">
                    @foreach ($memories as $memory)
                        @php
                            $coverPhoto = $memory->photos[0] ?? null;
                        @endphp
                        <article class="memory-archive-card">
                            @if ($coverPhoto)
                                <div class="memory-archive-photo">
                                    <img src="{{ asset('storage/'.$coverPhoto) }}" alt="{{ $memory->title }}">
                                </div>
                            @endif

                            <div class="memory-archive-body">
                                <div class="memory-archive-meta">
                                    <span>{{ $memory->user->name }}</span>
                                    <span>{{ optional($memory->approved_at ?? $memory->created_at)->format('d M Y') }}</span>
                                </div>
                                <h3>{{ $memory->title }}</h3>
                                <p>{{ \Illuminate\Support\Str::limit($memory->memory, 240) }}</p>

                                @if (! empty($memory->photos))
                                    <div class="memory-archive-count">
                                        {{ count($memory->photos) }} photo{{ count($memory->photos) === 1 ? '' : 's' }}
                                    </div>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="list-card memory-empty-state">
                    <p class="aside-label">Archive Status</p>
                    <h3>No approved memories yet</h3>
                    <p class="dashboard-copy">Once admins approve member submissions, they will appear here as part of the public memory archive.</p>
                    @auth
                        <div>
                            <a class="button button-primary" href="{{ route('memories.submit') }}">Be the First to Submit</a>
                        </div>
                    @endauth
                </div>
            @endif
        </div>
    </section>
@endsection
