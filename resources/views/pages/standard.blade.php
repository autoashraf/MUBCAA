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
                    <p class="aside-label">{{ __('Section Focus') }}</p>
                    <p class="aside-text">{{ $page['eyebrow'] }}</p>
                    <div class="aside-divider"></div>
                    <p class="aside-note">{{ $page['aside_note'] ?? __('This section highlights the main information and supporting details for this page.') }}</p>
                </aside>
                <div class="prose-block">
                @foreach ($page['body'] as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach
                </div>
            </div>
        </section>
    @endif

    @if (!empty($page['sections']))
        <section class="section">
            <div class="wrap narrow">
                <div class="legal-sections">
                    @foreach ($page['sections'] as $section)
                        <article class="prose-block legal-section-card">
                            <h2>{{ $section['title'] }}</h2>
                            @if (!empty($section['lead']))
                                <p class="legal-section-lead">{{ $section['lead'] }}</p>
                            @endif
                            @foreach ($section['paragraphs'] ?? [] as $paragraph)
                                <p>{{ $paragraph }}</p>
                            @endforeach
                            @if (!empty($section['bullets']))
                                <ul class="legal-list">
                                    @foreach ($section['bullets'] as $item)
                                        <li>{{ $item }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </article>
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
                    <h3>{{ __('Contact MUBCAA') }}</h3>
                    <p class="dashboard-copy">{{ __('Use this form for membership queries, event coordination, or general enquiries. A team member can follow up from the details you provide.') }}</p>
                </div>
                <div class="form-card contact-form-card">
                    <form method="POST" action="{{ route('contact.store') }}">
                        @csrf
                        <div class="form-grid">
                            <label>
                                <span>{{ __('Full name') }}</span>
                                <input type="text" name="name" value="{{ old('name') }}" placeholder="{{ __('Enter your full name') }}" autocomplete="name" required>
                                @error('name')
                                    <small>{{ $message }}</small>
                                @enderror
                            </label>
                            <label>
                                <span>{{ __('Email address') }}</span>
                                <input type="email" name="email" value="{{ old('email') }}" placeholder="{{ __('Enter your email address') }}" autocomplete="email" required>
                                @error('email')
                                    <small>{{ $message }}</small>
                                @enderror
                            </label>
                            <label>
                                <span>{{ __('Phone') }}</span>
                                <input type="text" name="phone" value="{{ old('phone') }}" placeholder="{{ __('Enter your phone number') }}" autocomplete="tel">
                                @error('phone')
                                    <small>{{ $message }}</small>
                                @enderror
                            </label>
                            <label>
                                <span>{{ __('Subject') }}</span>
                                <input type="text" name="subject" value="{{ old('subject') }}" placeholder="{{ __('What is this about?') }}" required>
                                @error('subject')
                                    <small>{{ $message }}</small>
                                @enderror
                            </label>
                            <label class="form-span-2">
                                <span>{{ __('Message') }}</span>
                                <textarea name="message" rows="6" placeholder="{{ __('Write your message') }}" required>{{ old('message') }}</textarea>
                                @error('message')
                                    <small>{{ $message }}</small>
                                @enderror
                            </label>
                        </div>
                        <button class="button button-primary" type="submit">{{ __('Send Message') }}</button>
                    </form>
                </div>
            </div>
        </section>
    @endif
@endsection
