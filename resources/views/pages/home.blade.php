@extends('layouts.app')

@section('content')
    <section class="home-wireframe home-alumni-layout">
        <div class="wrap">
            <section class="alumni-hero">
                <div class="alumni-hero-copy">
                    <h1>{{ __('Stay connected. Build our shared legacy.') }}</h1>
                    <p class="lead">{{ __('MUBCAA connects alumni, celebrates achievement, and keeps the association active through membership, events, updates, and shared memories.') }}</p>
                    <div class="hero-actions">
                        @guest
                            <a class="button button-primary" href="{{ route('membership.apply') }}">{{ __('Join the Alumni Network') }}</a>
                        @endguest
                        <a class="button button-secondary" href="{{ route('about.mission') }}">{{ __('Learn More') }}</a>
                    </div>
                </div>
                <div class="alumni-hero-media">
                    <img src="{{ asset($slides[0]['image']) }}" alt="{{ __($slides[0]['title'] ?? 'MUBCAA feature image') }}">
                </div>
            </section>

            <section class="alumni-feature-grid">
                @foreach ($heroMetrics as $feature)
                    <article class="alumni-feature-card">
                        <h2>{{ $feature['label'] }}</h2>
                        <p>{{ $feature['text'] }}</p>
                        <a class="button button-secondary" href="{{ $feature['route'] }}">{{ $feature['action'] }}</a>
                    </article>
                @endforeach
            </section>

            <section class="alumni-about-band">
                <div class="alumni-about-copy">
                    <h2>{{ __('About Our Association') }}</h2>
                    <p>{{ __('Learn more about MUBCAA’s mission, values, and how the association supports alumni through community-building, professional ties, and shared service.') }}</p>
                    <a class="button button-secondary" href="{{ route('about.mission') }}">{{ __('Read More') }}</a>
                </div>
                <div class="alumni-about-media">
                    <img src="{{ asset($slides[1]['image']) }}" alt="{{ __($slides[1]['title'] ?? 'About MUBCAA') }}">
                </div>
            </section>

            <section class="home-impact-band alumni-impact-band">
                @foreach ($impactStats as $stat)
                    <article class="impact-stat">
                        <strong>{{ $stat['value'] }}</strong>
                        <span>{{ $stat['label'] }}</span>
                    </article>
                @endforeach
            </section>

            <section class="alumni-news-gallery">
                <div class="home-panel-block alumni-news-block">
                        <div class="home-panel-head">
                            <h2>{{ __('News & Updates') }}</h2>
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
                    <div class="alumni-panel-footer">
                        <a class="button button-secondary" href="{{ route('contact') }}">{{ __('View All News') }}</a>
                    </div>
                </div>

                <aside class="home-panel-block alumni-gallery-block">
                    <div class="home-panel-head">
                        <h2>{{ __('Photo Gallery') }}</h2>
                    </div>
                    <p class="gallery-intro">{{ __('View photos from reunions, committee gatherings, and alumni events.') }}</p>
                    <a class="button button-secondary" href="{{ route('events.photos') }}">{{ __('View Gallery') }}</a>
                    <div class="gallery-thumb-grid compact-gallery-grid">
                        @if ($galleryPreviewPhotos->isNotEmpty())
                            @foreach ($galleryPreviewPhotos as $photo)
                                <div class="gallery-thumb">
                                    <img src="{{ asset('storage/'.$photo->photo_path) }}" alt="{{ $photo->title ?: __('MUBCAA gallery preview :number', ['number' => $loop->iteration]) }}">
                                </div>
                            @endforeach
                        @else
                            @foreach (range(1, 4) as $tile)
                                <div class="gallery-thumb">
                                    <img src="{{ asset($slides[($loop->index + 1) % max(count($slides), 1)]['image']) }}" alt="{{ __('MUBCAA gallery preview :number', ['number' => $loop->iteration]) }}">
                                </div>
                            @endforeach
                        @endif
                    </div>
                </aside>
            </section>

            <section class="alumni-directory-search home-panel-block">
                <div class="home-panel-head">
                    <h2>{{ __('Find Alumni') }}</h2>
                </div>
                <form class="directory-search-form" action="{{ route('membership.members') }}" method="get">
                    <input type="text" name="q" placeholder="{{ __('Search Alumni Directory') }}">
                    <select name="name">
                        <option value="">{{ __('Name') }}</option>
                        @foreach ($alumni as $person)
                            <option>{{ $person['name'] }}</option>
                        @endforeach
                    </select>
                    <select name="batch">
                        <option value="">{{ __('Batch') }}</option>
                        <option>1998</option>
                        <option>2002</option>
                        <option>2005</option>
                        <option>2010</option>
                    </select>
                    <select name="profession">
                        <option value="">{{ __('Profession') }}</option>
                        <option>{{ __('Academic') }}</option>
                        <option>{{ __('Business') }}</option>
                        <option>{{ __('Technology') }}</option>
                        <option>{{ __('Community Service') }}</option>
                    </select>
                    <select name="location">
                        <option value="">{{ __('Location') }}</option>
                        <option>{{ __('Dhaka') }}</option>
                        <option>{{ __('Rangpur') }}</option>
                        <option>{{ __('Chattogram') }}</option>
                        <option>{{ __('Online') }}</option>
                    </select>
                    <button class="button button-primary" type="submit">{{ __('Search') }}</button>
                </form>
            </section>

        </div>
    </section>
@endsection
