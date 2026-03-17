@extends('layouts.app')

@section('content')
    <section class="hero hero-home" data-home-slider>
        <div class="hero-background" aria-hidden="true">
            @foreach ($slides as $index => $slide)
                <div class="hero-background-slide @if ($index === 0) is-active @endif" data-home-slide>
                    <img src="{{ asset($slide['image']) }}" alt="">
                </div>
            @endforeach
            <div class="hero-background-overlay"></div>
        </div>
        <div class="wrap hero-home-wrap">
            <div class="hero-home-content">
                <div class="hero-copy">
                    <p class="eyebrow">Welcome to MUBCAA</p>
                    <h1>A stronger digital home for the MUBCAA community.</h1>
                    <p class="lead hero-lead-light">
                        MUBCAA now has a cleaner public-facing website for membership, events, committees, memories, and long-term community connection.
                    </p>
                    <div class="hero-actions">
                        <a class="button button-primary" href="{{ route('membership.apply') }}">Apply Now</a>
                        <a class="button button-secondary hero-button-light" href="{{ route('membership.members') }}">Explore Members</a>
                    </div>
                    <div class="hero-dots" aria-label="Homepage slider controls">
                        @foreach ($slides as $index => $slide)
                            <button
                                class="hero-dot @if ($index === 0) is-active @endif"
                                type="button"
                                data-home-slider-dot="{{ $index }}"
                                aria-label="Show slide {{ $index + 1 }}"
                            ></button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="stats-section">
        <div class="wrap stats-grid">
            @foreach ($stats as $stat)
                <article class="stat-card stat-card-enhanced">
                    <p class="stat-card-label">MUBCAA Snapshot</p>
                    <strong>{{ $stat['value'] }}</strong>
                    <span>{{ $stat['label'] }}</span>
                    <i aria-hidden="true"></i>
                </article>
            @endforeach
        </div>
    </section>

    <section class="section">
        <div class="wrap">
            <div class="section-heading">
                <p class="eyebrow">Core Sections</p>
                <h2>The essential areas that shape the public identity of MUBCAA</h2>
            </div>
            <div class="card-grid card-grid-featured">
                @foreach ($highlights as $item)
                    <article class="info-card info-card-featured">
                        <div class="info-card-topline">
                            <p class="info-card-label">{{ $item['label'] }}</p>
                            <span class="info-card-badge">Public</span>
                        </div>
                        <h3>{{ $item['title'] }}</h3>
                        <p>{{ $item['text'] }}</p>
                        <div class="info-card-footer">
                            <a href="{{ $item['route'] }}">Open section</a>
                            <span>Explore</span>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section section-showcase">
        <div class="wrap showcase-grid">
            <div class="showcase-intro-card">
                <p class="eyebrow">Why MUBCAA Online</p>
                <h2>A public platform that connects members, preserves history, and builds trust.</h2>
                <p class="lead">
                    MUBCAA should feel alive online, not just listed on a page. This website creates a clear home for membership, community activity, and the stories that define the association.
                </p>
                <div class="showcase-intro-points">
                    <div>
                        <strong>For members</strong>
                        <span>Give every member a clear place to register, stay informed, and remain connected to the organisation.</span>
                    </div>
                    <div>
                        <strong>For the community</strong>
                        <span>Present MUBCAA as a visible, credible, and active association with purpose, leadership, and shared memories.</span>
                    </div>
                </div>
            </div>
            <div class="showcase-stack">
                <article class="showcase-card showcase-card-pillar">
                    <span class="showcase-icon">A</span>
                    <div>
                        <p class="showcase-card-label">Identity</p>
                        <h3>Show who MUBCAA is</h3>
                        <p>Mission, committees, member values, and public information come together in one structure that is easy to understand.</p>
                    </div>
                </article>
                <article class="showcase-card showcase-card-pillar">
                    <span class="showcase-icon">B</span>
                    <div>
                        <p class="showcase-card-label">Engagement</p>
                        <h3>Keep members active</h3>
                        <p>Membership pages, events, galleries, and future member tools give people practical reasons to return and participate.</p>
                    </div>
                </article>
                <article class="showcase-card showcase-card-pillar">
                    <span class="showcase-icon">C</span>
                    <div>
                        <p class="showcase-card-label">Legacy</p>
                        <h3>Preserve memories and milestones</h3>
                        <p>Photos, videos, archives, and community stories turn the site into a living record of the association over time.</p>
                    </div>
                </article>
            </div>
        </div>
    </section>
@endsection
