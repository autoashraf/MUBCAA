@extends('layouts.app')

@section('content')
    <section class="page-hero">
        <div class="wrap narrow">
            <div class="page-hero-card">
                <p class="eyebrow">{{ $page['eyebrow'] }}</p>
                <h1>{{ $page['title'] }}</h1>
                <p class="lead">{{ $page['intro'] }}</p>
            </div>
        </div>
    </section>

    @if (empty($page['hide_narrative']))
        <section class="section">
            <div class="wrap narrative-layout">
                <aside class="page-aside">
                    <p class="aside-label">Section Focus</p>
                    <p class="aside-text">{{ $page['eyebrow'] }}</p>
                    <div class="aside-divider"></div>
                    <p class="aside-note">{{ $page['aside_note'] ?? 'This section highlights the main information and supporting details for this page.' }}</p>
                </aside>
                <div class="prose-block">
                @foreach ($page['body'] as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach
                </div>
            </div>
        </section>
    @endif

    @if (!empty($page['cards']))
        <section class="section">
            <div class="wrap">
                <div class="card-grid">
                    @foreach ($page['cards'] as $card)
                        <article class="info-card">
                            <h3>{{ $card['title'] }}</h3>
                            <p>{{ $card['text'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if (!empty($page['lists']))
        <section class="section">
            <div class="wrap dual-grid">
                @foreach ($page['lists'] as $title => $items)
                    <div class="list-card">
                        <h3>{{ $title }}</h3>
                        <ul>
                            @foreach ($items as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if (!empty($page['gallery']))
        <section class="section">
            <div class="wrap gallery-grid">
                @foreach ($page['gallery'] as $item)
                    <article class="gallery-card">
                        <span>{{ $item }}</span>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @if (!empty($page['contact_form']))
        <section class="section">
            <div class="wrap dual-grid">
                <div class="list-card contact-panel">
                    <p class="aside-label">Send a Message</p>
                    <h3>Contact MUBCAA</h3>
                    <p class="dashboard-copy">Use this form for membership queries, event coordination, or general enquiries. A team member can follow up from the details you provide.</p>
                </div>
                <div class="form-card contact-form-card">
                    <form method="POST" action="{{ route('contact.store') }}">
                        @csrf
                        <div class="form-grid">
                            <label>
                                <span>Full name</span>
                                <input type="text" name="name" value="{{ old('name') }}" placeholder="Enter your full name" autocomplete="name" required>
                                @error('name')
                                    <small>{{ $message }}</small>
                                @enderror
                            </label>
                            <label>
                                <span>Email address</span>
                                <input type="email" name="email" value="{{ old('email') }}" placeholder="Enter your email address" autocomplete="email" required>
                                @error('email')
                                    <small>{{ $message }}</small>
                                @enderror
                            </label>
                            <label>
                                <span>Phone</span>
                                <input type="text" name="phone" value="{{ old('phone') }}" placeholder="Enter your phone number" autocomplete="tel">
                                @error('phone')
                                    <small>{{ $message }}</small>
                                @enderror
                            </label>
                            <label>
                                <span>Subject</span>
                                <input type="text" name="subject" value="{{ old('subject') }}" placeholder="What is this about?" required>
                                @error('subject')
                                    <small>{{ $message }}</small>
                                @enderror
                            </label>
                            <label class="form-span-2">
                                <span>Message</span>
                                <textarea name="message" rows="6" placeholder="Write your message" required>{{ old('message') }}</textarea>
                                @error('message')
                                    <small>{{ $message }}</small>
                                @enderror
                            </label>
                        </div>
                        <button class="button button-primary" type="submit">Send Message</button>
                    </form>
                </div>
            </div>
        </section>
    @endif
@endsection
