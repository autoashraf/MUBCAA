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
            @if ($photos->isNotEmpty())
                <div class="memory-archive-grid">
                    @foreach ($photos as $photo)
                        <article class="memory-archive-card">
                            <div class="memory-archive-photo">
                                <img src="{{ asset('storage/'.$photo->photo_path) }}" alt="{{ $photo->title ?: __('Gallery photo') }}">
                            </div>
                            <div class="memory-archive-body">
                                <div class="memory-archive-meta">
                                    <span>{{ $photo->uploader->name }}</span>
                                    <span>{{ $photo->created_at?->format('d M Y') }}</span>
                                </div>
                                <h3>{{ $photo->title ?: __('Gallery Photo') }}</h3>
                                @if (filled($photo->caption))
                                    <p>{{ $photo->caption }}</p>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="list-card memory-empty-state">
                    <p class="aside-label">{{ __('Gallery Status') }}</p>
                    <h3>{{ __('No photos yet') }}</h3>
                    <p class="dashboard-copy">{{ __('Gallery photos uploaded by admin will appear here.') }}</p>
                </div>
            @endif
        </div>
    </section>
@endsection
