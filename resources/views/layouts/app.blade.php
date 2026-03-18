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
            $mobileProfileLink = auth()->check()
                ? (auth()->user()->isAdmin() ? route('admin.dashboard') : route('member.dashboard'))
                : route('login');
            $mobileProfileLabel = auth()->check()
                ? (auth()->user()->isAdmin() ? 'Open admin dashboard' : 'Open member dashboard')
                : 'Open login page';
        @endphp
        <div class="page-shell">
            <div class="site-backdrop" aria-hidden="true">
                <div class="backdrop-orb backdrop-orb-one"></div>
                <div class="backdrop-orb backdrop-orb-two"></div>
                <div class="backdrop-grid"></div>
            </div>

            <header class="site-header">
                <div class="wrap header-bar">
                    <a class="brand brand-desktop" href="{{ route('home') }}">
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
                        <label class="nav-button" for="nav-toggle">
                            <span class="nav-button-icon" aria-hidden="true">
                                <i></i>
                                <i></i>
                                <i></i>
                            </span>
                            <span>Menu</span>
                        </label>
                        <a class="brand brand-mobile" href="{{ route('home') }}">
                            <strong>{{ $brandName }}</strong>
                        </a>
                        <div class="mobile-quick-actions">
                            <a class="mobile-quick-link" href="{{ $mobileProfileLink }}" aria-label="{{ $mobileProfileLabel }}">
                                @auth
                                    <span class="mobile-user-initial">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                                @else
                                    <span class="mobile-icon mobile-icon-user"></span>
                                @endauth
                            </a>
                        </div>
                        <nav class="site-nav">
                            @foreach ($menu as $item)
                                <div class="nav-group @if ($item['children']) has-children @endif" @if ($item['children']) data-mobile-nav-group @endif>
                                    @if ($item['children'])
                                        <button
                                            class="nav-link nav-toggle-link @if (request()->routeIs($item['active'])) is-active @endif"
                                            type="button"
                                            data-mobile-nav-trigger
                                            aria-expanded="@if (request()->routeIs($item['active'])) true @else false @endif"
                                        >
                                            {{ $item['label'] }}
                                        </button>
                                    @else
                                        <a href="{{ $item['route'] }}" class="nav-link @if (request()->routeIs($item['active'])) is-active @endif">
                                            {{ $item['label'] }}
                                        </a>
                                    @endif
                                    @if ($item['children'])
                                        <div class="nav-dropdown @if (request()->routeIs($item['active'])) is-open @endif" data-mobile-nav-panel>
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
                            <a class="mini-link" href="{{ route('login') }}">Login</a>
                            <a class="mini-link mini-link-primary" href="{{ route('membership.apply') }}">Join Us</a>
                        @endauth
                    </div>
                </div>
            </header>

            <main>
                @yield('content')
            </main>

            <footer class="site-footer">
                <div class="wrap footer-bar">
                    <div class="footer-column">
                        <h3 class="footer-title">Stay Connected</h3>
                        <p class="footer-copy">Subscribe to our newsletter for updates.</p>
                        <form class="footer-subscribe" action="{{ route('contact') }}" method="get">
                            <input type="email" name="footer_email" placeholder="Your Email">
                            <button type="submit">Subscribe</button>
                        </form>
                    </div>
                    <div class="footer-column">
                        <h3 class="footer-title">Quick Links</h3>
                        <nav class="footer-list">
                            <a href="{{ route('about.mission') }}">About Us</a>
                            <a href="{{ route('events.upcoming') }}">Events</a>
                            <a href="{{ route('memories.list') }}">News</a>
                            <a href="{{ route('contact') }}">Contact</a>
                        </nav>
                    </div>
                    <div class="footer-column">
                        <h3 class="footer-title">Resources</h3>
                        <div class="footer-list">
                            <span>Mentorship Program</span>
                            <span>Career Support</span>
                            <span>Volunteer Opportunities</span>
                        </div>
                    </div>
                    <div class="footer-column">
                        <h3 class="footer-title">Contact Us</h3>
                        <div class="footer-list footer-contact-list">
                            <span>Email: info@mubcaa.org</span>
                            <span>Phone: +880 1773-658804</span>
                        </div>
                    </div>
                </div>
                <div class="wrap footer-bottom-bar">
                    <p>&copy; {{ now()->year }} {{ $brandName }}</p>
                    <p>All rights reserved.</p>
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
                    var mobileNavGroups = document.querySelectorAll('[data-mobile-nav-group]');

                    mobileNavGroups.forEach(function (group) {
                        var trigger = group.querySelector('[data-mobile-nav-trigger]');
                        var panel = group.querySelector('[data-mobile-nav-panel]');

                        if (!trigger || !panel) {
                            return;
                        }

                        trigger.addEventListener('click', function () {
                            var willOpen = !panel.classList.contains('is-open');

                            mobileNavGroups.forEach(function (item) {
                                item.querySelector('[data-mobile-nav-panel]')?.classList.remove('is-open');
                                var itemTrigger = item.querySelector('[data-mobile-nav-trigger]');

                                if (itemTrigger) {
                                    itemTrigger.setAttribute('aria-expanded', 'false');
                                }
                            });

                            if (willOpen) {
                                panel.classList.add('is-open');
                                trigger.setAttribute('aria-expanded', 'true');
                            }
                        });
                    });

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

                    if (sliderRoot && slides.length >= 2) {
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
                    }
                });
            </script>
        @endif
    </body>
</html>
