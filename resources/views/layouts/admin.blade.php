<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? 'Admin Panel' }}</title>
        <meta name="description" content="{{ $description ?? 'MUBCAA admin panel.' }}">
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <style>{!! file_get_contents(resource_path('css/app.css')) !!}</style>
        @endif
    </head>
    <body class="admin-layout-body">
        @php
            $isDashboardView = request()->routeIs('admin.dashboard');
            $isApplicationView = request()->routeIs('admin.applications.*');
            $isAffiliateView = request()->routeIs('admin.affiliates.*');
        @endphp
        <div class="admin-layout-shell">
            <header class="admin-layout-header">
                <div class="wrap admin-layout-bar">
                    <div class="admin-layout-brand">
                        <p class="panel-card-label">MUBCAA</p>
                        <strong>Admin Panel</strong>
                    </div>
                    <div class="admin-layout-actions">
                        <a class="mini-link" href="{{ route('home') }}">Website</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="mini-link mini-link-button" type="submit">Logout</button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="admin-layout-main">
                <div class="admin-layout-grid">
                    <aside class="admin-layout-sidebar">
                        <nav class="admin-nav">
                            <a class="admin-nav-item @if ($isDashboardView) is-active @endif" href="{{ route('admin.dashboard') }}">
                                <div>
                                    <strong>Dashboard</strong>
                                    <span>Overview</span>
                                </div>
                            </a>
                            <a class="admin-nav-item @if ($isApplicationView) is-active @endif" href="{{ route('admin.applications.index') }}">
                                <div>
                                    <strong>Applications</strong>
                                    <span>Submitted profiles</span>
                                </div>
                            </a>
                            <a class="admin-nav-item @if ($isAffiliateView) is-active @endif" href="{{ route('admin.affiliates.index') }}">
                                <div>
                                    <strong>Affiliates</strong>
                                    <span>Referral members</span>
                                </div>
                            </a>
                            <a class="admin-nav-item" href="{{ route('home') }}">
                                <div>
                                    <strong>Website</strong>
                                    <span>Public site</span>
                                </div>
                            </a>
                        </nav>
                    </aside>

                    <div class="admin-layout-content">
                        <div class="wrap">
                            @yield('content')
                        </div>
                    </div>
                </div>
            </main>
        </div>
        @stack('scripts')
    </body>
</html>
