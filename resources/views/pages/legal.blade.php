@extends('layouts.app')

@section('content')
    <section class="page-hero">
        <div class="wrap narrow">
            <div class="page-hero-card">
                <p class="eyebrow">{{ $page['eyebrow'] }}</p>
                <h1>{{ $page['title'] }}</h1>
                <p class="lead">{{ $page['intro'] }}</p>
                @if (!empty($page['lead_note']))
                    <p class="lead">{{ $page['lead_note'] }}</p>
                @endif
            </div>
        </div>
    </section>

    <section class="section">
        <div class="wrap narrative-layout">
            <aside class="page-aside">
                <p class="aside-label">{{ __('Effective Date') }}</p>
                <p class="aside-text">{{ $page['effective_date'] ?? __('Updated policy') }}</p>
                <div class="aside-divider"></div>
                <p class="aside-note">{{ __('These legal pages explain how the site is used, how information is handled, and how visitors and members can contact MUBCAA about policy questions.') }}</p>
            </aside>
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

                @if (!empty($page['contact_details']))
                    <article class="prose-block legal-section-card">
                        <h2>{{ __('Contact Us') }}</h2>
                        <p>{{ __('If you have any questions regarding this policy, please contact us:') }}</p>
                        <ul class="legal-list">
                            @foreach ($page['contact_details'] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </article>
                @endif
            </div>
        </div>
    </section>
@endsection
