@extends('layouts.app')

@section('content')
    @php
        $heroSlide = $slides[0] ?? ['image' => 'images/slides/slide-1.jpeg', 'title' => 'MUBCAA campus'];
        $aboutSlide = $slides[1] ?? $heroSlide;
        $fallbackGallerySlides = collect($slides)->slice(1)->values();
    @endphp

    <section class="home-wireframe home-alumni-layout">
        <div class="wrap">
            <section class="alumni-hero">
                <div class="alumni-hero-copy">
                    <p class="home-eyebrow">{{ __('Monipur Uchcha Bidyalaya & College Alumni Association') }}</p>
                    <h1>{{ __('Stay connected with the people and memories that shaped us.') }}</h1>
                    <p class="lead">{{ __('MUBCAA brings alumni together through membership, events, stories, service, and a shared connection to our institution.') }}</p>
                    <div class="hero-actions">
                        @guest
                            <a class="button button-primary" href="{{ route('membership.apply') }}">{{ __('Join the Alumni Network') }}</a>
                        @endguest
                        <a class="button button-secondary" href="{{ route('about.mission') }}">{{ __('About MUBCAA') }}</a>
                    </div>
                </div>
                <div class="alumni-hero-media">
                    <img src="{{ asset($heroSlide['image']) }}" alt="{{ __($heroSlide['title'] ?? 'MUBCAA campus') }}">
                    <div class="hero-media-caption">
                        <span>{{ __('Community') }}</span>
                        <strong>{{ __('A lifelong alumni platform') }}</strong>
                    </div>
                </div>
            </section>

            <section class="home-impact-band alumni-impact-band" aria-label="{{ __('MUBCAA impact') }}">
                @foreach ($impactStats as $stat)
                    <article class="impact-stat">
                        <strong>{{ $stat['value'] }}</strong>
                        <span>{{ $stat['label'] }}</span>
                    </article>
                @endforeach
            </section>

            <section class="alumni-feature-grid" aria-label="{{ __('Quick actions') }}">
                @foreach ($heroMetrics as $feature)
                    <article class="alumni-feature-card">
                        <span>{{ sprintf('%02d', $loop->iteration) }}</span>
                        <h2>{{ $feature['label'] }}</h2>
                        <p>{{ $feature['text'] }}</p>
                        <a class="home-text-link" href="{{ $feature['route'] }}">{{ $feature['action'] }}</a>
                    </article>
                @endforeach
            </section>

            <section class="alumni-about-band">
                <div class="alumni-about-media">
                    <img src="{{ asset($aboutSlide['image']) }}" alt="{{ __($aboutSlide['title'] ?? 'About MUBCAA') }}">
                </div>
                <div class="alumni-about-copy">
                    <p class="home-eyebrow">{{ __('About the Association') }}</p>
                    <h2>{{ __('A network for connection, support, and service.') }}</h2>
                    <p>{{ __('MUBCAA keeps former students close to one another and to the institution through alumni engagement, committee work, events, memories, and member support.') }}</p>
                    <a class="button button-secondary" href="{{ route('about.mission') }}">{{ __('Read the Mission') }}</a>
                </div>
            </section>

            <section class="alumni-events-news">
                <div class="home-panel-block alumni-events-block">
                    <div class="home-panel-head">
                        <div>
                            <p class="home-eyebrow">{{ __('Programs') }}</p>
                            <h2>{{ __('Upcoming Events') }}</h2>
                        </div>
                        <a class="home-text-link" href="{{ route('events.upcoming') }}">{{ __('View All') }}</a>
                    </div>
                    <div class="event-story-list">
                        @foreach ($events as $event)
                            <article class="event-story">
                                <span>{{ $event['meta'] }}</span>
                                <h3>{{ $event['title'] }}</h3>
                                <p>{{ $event['description'] }}</p>
                                <a class="home-text-link" href="{{ $event['route'] }}">{{ __('Details') }}</a>
                            </article>
                        @endforeach
                    </div>
                </div>

                <div class="home-panel-block alumni-news-block">
                    <div class="home-panel-head">
                        <div>
                            <p class="home-eyebrow">{{ __('Updates') }}</p>
                            <h2>{{ __('News & Updates') }}</h2>
                        </div>
                    </div>
                    <div class="news-story-list">
                        @foreach ($newsItems as $item)
                            <article class="news-story">
                                <div>
                                    <strong>{{ $item['title'] }}</strong>
                                    <p>{{ $item['text'] }}</p>
                                </div>
                                <a href="{{ route('contact') }}">{{ __('Read More') }}</a>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="alumni-gallery-row">
                <aside class="home-panel-block alumni-gallery-block">
                    <div class="home-panel-head">
                        <div>
                            <p class="home-eyebrow">{{ __('Memories') }}</p>
                            <h2>{{ __('Photo Gallery') }}</h2>
                        </div>
                        <a class="home-text-link" href="{{ route('events.photos') }}">{{ __('Open Gallery') }}</a>
                    </div>
                    <p class="gallery-intro">{{ __('Browse reunions, committee gatherings, and alumni moments from the MUBCAA community.') }}</p>
                    <div class="gallery-thumb-grid compact-gallery-grid">
                        @if ($galleryPreviewPhotos->isNotEmpty())
                            @foreach ($galleryPreviewPhotos as $photo)
                                <div class="gallery-thumb">
                                    <img src="{{ asset('storage/'.$photo->photo_path) }}" alt="{{ $photo->title ?: __('MUBCAA gallery preview :number', ['number' => $loop->iteration]) }}">
                                </div>
                            @endforeach
                        @else
                            @foreach (range(1, 4) as $tile)
                                @php
                                    $gallerySlide = $fallbackGallerySlides[($loop->index) % max($fallbackGallerySlides->count(), 1)] ?? $heroSlide;
                                @endphp
                                <div class="gallery-thumb">
                                    <img src="{{ asset($gallerySlide['image']) }}" alt="{{ __('MUBCAA gallery preview :number', ['number' => $loop->iteration]) }}">
                                </div>
                            @endforeach
                        @endif
                    </div>
                </aside>
            </section>
        </div>
    </section>
@endsection
