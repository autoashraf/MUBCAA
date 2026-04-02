@extends('layouts.print')

@section('content')
    <div class="print-toolbar">
        <button onclick="window.print()">{{ __('Print / Save PDF') }}</button>
    </div>

    <section class="id-sheet">
        <article class="id-card-single">
            <div class="id-card-topbar"></div>
            <div class="id-card-single-inner">
                <header class="id-card-header">
                    <div class="id-brand">
                        <span class="id-logo-shell">
                            <img src="{{ asset(config('site.brand.logo_path')) }}" alt="{{ config('site.brand.name') }} logo">
                        </span>
                        <div class="id-brand-copy">
                            <p class="document-kicker">{{ __('Official Member ID Card') }}</p>
                            <h1>{{ config('site.brand.name') }}</h1>
                        </div>
                    </div>
                    <span class="id-badge">{{ __('Alumni Member') }}</span>
                </header>

                <section class="id-card-identity">
                    <div class="avatar-block">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                    <div class="id-card-details">
                        <h2>{{ $user->name }}</h2>
                        <p>{{ $profile?->occupation ?: __('Community Member') }}</p>
                        <strong class="id-member-number">{{ $user->memberNumber() }}</strong>
                    </div>
                </section>

                <section class="id-meta-grid">
                    <div class="id-meta">
                        <span>{{ __('Status') }}</span>
                        <strong>{{ $user->membership_status === 'pending_review' ? __('Under Review') : str($user->membership_status)->replace('_', ' ')->title() }}</strong>
                    </div>
                    <div class="id-meta">
                        <span>{{ __('Phone') }}</span>
                        <strong>{{ $user->phone ?: __('N/A') }}</strong>
                    </div>
                    <div class="id-meta">
                        <span>{{ __('City') }}</span>
                        <strong>{{ $profile?->city_district ?: $profile?->current_city ?: __('N/A') }}</strong>
                    </div>
                    <div class="id-meta">
                        <span>{{ __('Valid From') }}</span>
                        <strong>{{ $user->created_at?->format('d M Y') }}</strong>
                    </div>
                </section>

                <footer class="id-card-footer">
                    <div class="id-footer-block">
                        <span>{{ __('Office Contact') }}</span>
                        <strong>info@mubcaa.org</strong>
                    </div>
                    <div class="id-footer-block id-footer-block-right">
                        <span>{{ __('Card Type') }}</span>
                        <strong>{{ __('Alumni Member') }}</strong>
                    </div>
                </footer>
            </div>
        </article>
    </section>
@endsection
