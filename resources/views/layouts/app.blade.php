<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? 'Membership Site' }}</title>
        <meta name="description" content="{{ $description ?? 'Membership association website built with Laravel.' }}">
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <style>{!! file_get_contents(resource_path('css/app.css')) !!}</style>
        @endif
    </head>
    <body>
        @php
            $brandName = config('site.brand.name', 'Membership Association');
            $brandTagline = config('site.brand.tagline', 'Membership, events, committees, and memories');
            $logoPath = config('site.brand.logo_path');
        @endphp
        <div class="page-shell">
            <div class="site-backdrop" aria-hidden="true">
                <div class="backdrop-orb backdrop-orb-one"></div>
                <div class="backdrop-orb backdrop-orb-two"></div>
                <div class="backdrop-grid"></div>
            </div>

            <header class="site-header">
                <div class="wrap header-bar">
                    <a class="brand" href="{{ route('home') }}">
                        @if ($logoPath)
                            <span class="brand-logo">
                                <img src="{{ asset($logoPath) }}" alt="{{ $brandName }} logo">
                            </span>
                        @else
                            <span class="brand-mark">M</span>
                        @endif
                        <span>
                            <strong>{{ $brandName }}</strong>
                            <small>{{ $brandTagline }}</small>
                        </span>
                    </a>
                    <div class="nav-shell">
                        <input id="nav-toggle" class="nav-toggle" type="checkbox">
                        <label class="nav-button" for="nav-toggle">Menu</label>
                        <nav class="site-nav">
                            @foreach ($menu as $item)
                                <div class="nav-group">
                                    <a href="{{ $item['route'] }}" class="nav-link @if (request()->routeIs($item['active'])) is-active @endif">
                                        {{ $item['label'] }}
                                    </a>
                                    @if ($item['children'])
                                        <div class="nav-dropdown">
                                            @foreach ($item['children'] as $child)
                                                <a href="{{ $child['route'] }}">{{ $child['label'] }}</a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </nav>
                    </div>
                    <div class="header-actions">
                        @auth
                            <a class="mini-link" href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('member.dashboard') }}">
                                {{ auth()->user()->isAdmin() ? 'Admin' : 'Dashboard' }}
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="mini-link mini-link-button" type="submit">Logout</button>
                            </form>
                        @else
                            <a class="mini-link" href="{{ route('membership.apply') }}">Join</a>
                            <a class="mini-link" href="{{ route('login') }}">Login</a>
                        @endauth
                    </div>
                </div>
            </header>

            <main>
                @yield('content')
            </main>

            <footer class="site-footer">
                <div class="wrap footer-bar">
                    <div class="footer-brandline">
                        @if ($logoPath)
                            <span class="brand-logo footer-logo">
                                <img src="{{ asset($logoPath) }}" alt="{{ $brandName }} logo">
                            </span>
                        @else
                            <span class="brand-mark footer-mark">M</span>
                        @endif
                        <div>
                            <p class="footer-title">{{ $brandName }}</p>
                            <p class="footer-subtitle">&copy; {{ now()->year }} All rights reserved.</p>
                        </div>
                    </div>
                    <div class="footer-nav">
                        <a href="{{ route('about.mission') }}">About</a>
                        <a href="{{ route('membership.members') }}">Members</a>
                        <a href="{{ route('events.upcoming') }}">Events</a>
                        <a href="{{ route('memories.list') }}">Memories</a>
                    </div>
                    <div class="footer-actions">
                        @auth
                            <a class="mini-link" href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('member.dashboard') }}">
                                {{ auth()->user()->isAdmin() ? 'Admin' : 'Dashboard' }}
                            </a>
                        @else
                            <a class="mini-link" href="{{ route('membership.apply') }}">Join</a>
                            <a class="mini-link" href="{{ route('login') }}">Login</a>
                        @endauth
                    </div>
                </div>
            </footer>
        </div>
        @if (! file_exists(public_path('build/manifest.json')) && ! file_exists(public_path('hot')))
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var panelRoot = document.querySelector('[data-admin-panels]');
                    var triggers = document.querySelectorAll('[data-admin-panel-trigger]');
                    var sliderRoot = document.querySelector('[data-home-slider]');
                    var slides = document.querySelectorAll('[data-home-slide]');
                    var dots = document.querySelectorAll('[data-home-slider-dot]');

                    if (panelRoot && triggers.length) {
                        var panels = panelRoot.querySelectorAll('[data-admin-panel]');

                        function setActivePanel(target) {
                            panels.forEach(function (panel) {
                                panel.classList.toggle('is-active', panel.dataset.adminPanel === target);
                            });

                            triggers.forEach(function (trigger) {
                                trigger.classList.toggle('is-active', trigger.dataset.adminPanelTrigger === target);
                            });
                        }

                        triggers.forEach(function (trigger) {
                            trigger.addEventListener('click', function () {
                                setActivePanel(trigger.dataset.adminPanelTrigger);
                            });
                        });
                    }

                    if (!sliderRoot || slides.length < 2) {
                        return;
                    }

                    var activeIndex = 0;
                    var timerId = null;

                    function showSlide(index) {
                        activeIndex = index;

                        slides.forEach(function (slide, slideIndex) {
                            slide.classList.toggle('is-active', slideIndex === index);
                        });

                        dots.forEach(function (dot, dotIndex) {
                            dot.classList.toggle('is-active', dotIndex === index);
                        });
                    }

                    function startSlider() {
                        timerId = window.setInterval(function () {
                            showSlide((activeIndex + 1) % slides.length);
                        }, 5000);
                    }

                    function resetSlider() {
                        if (timerId) {
                            window.clearInterval(timerId);
                        }

                        startSlider();
                    }

                    dots.forEach(function (dot, index) {
                        dot.addEventListener('click', function () {
                            showSlide(index);
                            resetSlider();
                        });
                    });

                    sliderRoot.addEventListener('mouseenter', function () {
                        if (timerId) {
                            window.clearInterval(timerId);
                        }
                    });

                    sliderRoot.addEventListener('mouseleave', function () {
                        resetSlider();
                    });

                    showSlide(0);
                    startSlider();
                });
            </script>
        @endif
    </body>
</html>
