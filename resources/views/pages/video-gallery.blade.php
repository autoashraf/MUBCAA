@extends('layouts.app')

@section('content')
    <section class="page-hero">
        <div class="wrap">
            <div class="page-hero-card">
                <p class="eyebrow">{{ $page['eyebrow'] }}</p>
                <h1>{{ $page['title'] }}</h1>
                <p class="lead">{{ $page['intro'] }}</p>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="wrap">
            @if ($videos->isNotEmpty())
                <div class="memory-archive-grid">
                    @foreach ($videos as $video)
                        <article class="memory-archive-card">
                            <div class="video-gallery-frame">
                                <video controls preload="metadata">
                                    <source src="{{ asset('storage/'.$video->video_path) }}">
                                </video>
                            </div>
                            <div class="memory-archive-body">
                                <div class="memory-archive-meta">
                                    <span>{{ $video->uploader->name }}</span>
                                    <span>{{ $video->created_at?->format('d M Y') }}</span>
                                </div>
                                <h3>{{ $video->title ?: 'Gallery Video' }}</h3>
                                @if (filled($video->caption))
                                    <p>{{ $video->caption }}</p>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="list-card memory-empty-state">
                    <p class="aside-label">Gallery Status</p>
                    <h3>No videos yet</h3>
                    <p class="dashboard-copy">Gallery videos uploaded by admin will appear here.</p>
                </div>
            @endif
        </div>
    </section>
@endsection
