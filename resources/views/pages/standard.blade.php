@extends('layouts.app')

@section('content')
    <section class="page-hero">
        <div class="wrap narrow">
            <p class="eyebrow">{{ $page['eyebrow'] }}</p>
            <h1>{{ $page['title'] }}</h1>
            <p class="lead">{{ $page['intro'] }}</p>
        </div>
    </section>

    <section class="section">
        <div class="wrap narrative-layout">
            <aside class="page-aside">
                <p class="aside-label">Section Focus</p>
                <p class="aside-text">{{ $page['eyebrow'] }}</p>
                <div class="aside-divider"></div>
                <p class="aside-note">This layout keeps the introductory narrative separate from the supporting cards, lists, or galleries below.</p>
            </aside>
            <div class="prose-block">
            @foreach ($page['body'] as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach
            </div>
        </div>
    </section>

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
                                Full name
                                <input type="text" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <small>{{ $message }}</small>
                                @enderror
                            </label>
                            <label>
                                Email address
                                <input type="email" name="email" value="{{ old('email') }}" required>
                                @error('email')
                                    <small>{{ $message }}</small>
                                @enderror
                            </label>
                            <label>
                                Phone
                                <input type="text" name="phone" value="{{ old('phone') }}">
                                @error('phone')
                                    <small>{{ $message }}</small>
                                @enderror
                            </label>
                            <label>
                                Subject
                                <input type="text" name="subject" value="{{ old('subject') }}" required>
                                @error('subject')
                                    <small>{{ $message }}</small>
                                @enderror
                            </label>
                            <label class="form-span-2">
                                Message
                                <textarea name="message" rows="6" required>{{ old('message') }}</textarea>
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
