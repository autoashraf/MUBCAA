@extends('layouts.app')

@section('content')
    <section class="home-wireframe">
        <div class="wrap">
            <section class="home-hero-panel">
                <div class="home-hero-copy">
                    <p class="eyebrow">MUBCAA Alumni Network</p>
                    <h1>Connecting alumni, building the future.</h1>
                    <p class="lead">A stronger digital home for membership, alumni updates, events, notices, galleries, and community collaboration.</p>
                    <div class="hero-actions">
                        <a class="button button-primary" href="{{ route('membership.apply') }}">Join the Association</a>
                        <a class="button button-secondary" href="{{ route('membership.members') }}">Explore Members</a>
                    </div>
                </div>
                <div class="home-hero-media">
                    <img src="{{ asset($slides[0]['image']) }}" alt="{{ $slides[0]['title'] ?? 'MUBCAA hero image' }}">
                </div>
            </section>

            <section class="home-mini-metrics">
                @foreach ($heroMetrics as $metric)
                    <article class="metric-tile">
                        <strong>{{ $metric['label'] }}</strong>
                    </article>
                @endforeach
            </section>

            <section class="home-about-strip">
                <div class="about-strip-media">
                    <img src="{{ asset($slides[1]['image']) }}" alt="{{ $slides[1]['title'] ?? 'Association overview' }}">
                </div>
                <div class="about-strip-copy">
                    <p class="eyebrow">About MUBCAA</p>
                    <h2>About the association</h2>
                    <p>Learn about the mission, values, history, and leadership of MUBCAA through a cleaner public-facing introduction.</p>
                    <a class="button button-secondary" href="{{ route('about.mission') }}">Read More</a>
                </div>
            </section>

            <section class="home-impact-band">
                @foreach ($impactStats as $stat)
                    <article class="impact-stat">
                        <strong>{{ $stat['value'] }}</strong>
                        <span>{{ $stat['label'] }}</span>
                    </article>
                @endforeach
            </section>

            <section class="home-president-message">
                <div class="president-avatar">P</div>
                <div class="president-copy">
                    <h2>Message from the President</h2>
                    <p>Welcome to MUBCAA. This platform is designed to strengthen our alumni bond, encourage participation, and build a more active community.</p>
                    <a class="button button-secondary" href="{{ route('about.mission') }}">Full Message</a>
                </div>
            </section>

            <section class="home-service-grid">
                @foreach ($serviceLinks as $service)
                    <article class="service-chip">{{ $service }}</article>
                @endforeach
            </section>

            <section class="home-events-news">
                <div class="home-panel-block">
                    <div class="home-panel-head">
                        <h2>Upcoming Events</h2>
                    </div>
                    <div class="events-card-grid">
                        @foreach ($events as $event)
                            <article class="event-card">
                                <strong>{{ $event['title'] }}</strong>
                                <span>{{ $event['meta'] }}</span>
                                <p>{{ $event['description'] }}</p>
                                <a class="button button-secondary" href="{{ $event['route'] }}">View Details</a>
                            </article>
                        @endforeach
                    </div>
                </div>

                <aside class="home-panel-block news-panel">
                    <div class="home-panel-head">
                        <h2>Latest News & Notices</h2>
                    </div>
                    <ul class="news-list">
                        @foreach ($newsItems as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </aside>
            </section>

            <section class="home-alumni-gallery">
                <div class="home-panel-block">
                    <div class="home-panel-head">
                        <h2>Distinguished Alumni</h2>
                    </div>
                    <div class="alumni-grid">
                        @foreach ($alumni as $person)
                            <article class="alumni-card">
                                <strong>{{ $person['name'] }}</strong>
                                <span>{{ $person['meta'] }}</span>
                            </article>
                        @endforeach
                    </div>
                </div>

                <aside class="home-panel-block gallery-panel">
                    <div class="home-panel-head">
                        <h2>Photo Gallery</h2>
                    </div>
                    <div class="gallery-thumb-grid">
                        @foreach ($galleryTiles as $tile)
                            <div class="gallery-thumb" aria-hidden="true"></div>
                        @endforeach
                    </div>
                    <a class="button button-primary" href="{{ route('events.photos') }}">View Full Gallery</a>
                </aside>
            </section>

            <section class="home-testimonials-donate">
                <div class="home-panel-block">
                    <div class="home-panel-head">
                        <h2>Testimonials</h2>
                    </div>
                    <div class="testimonial-stack">
                        @foreach ($testimonials as $testimonial)
                            <blockquote class="testimonial-card">
                                <span>&ldquo;</span>
                                <p>{{ $testimonial }}</p>
                            </blockquote>
                        @endforeach
                    </div>
                </div>

                <aside class="home-panel-block donate-panel">
                    <div class="home-panel-head">
                        <h2>Support & Donate</h2>
                    </div>
                    <p>Support scholarships, alumni programs, and community-driven initiatives through the MUBCAA network.</p>
                    <div class="action-row">
                        <a class="button button-primary" href="{{ route('contact') }}">Donate Now</a>
                        <a class="button button-secondary" href="{{ route('contact') }}">Learn More</a>
                    </div>
                </aside>
            </section>

            <section class="home-final-cta">
                <div class="home-panel-head centered-head">
                    <h2>Join Our Alumni Network</h2>
                </div>
                <div class="action-row">
                    <a class="button button-primary" href="{{ route('membership.apply') }}">Register Now</a>
                    <a class="button button-secondary" href="{{ route('login') }}">Member Login</a>
                    <a class="button button-secondary" href="{{ route('contact') }}">Contact Us</a>
                </div>
            </section>
        </div>
    </section>
@endsection
