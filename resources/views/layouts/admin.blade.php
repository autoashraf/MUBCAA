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
    <body class="admin-layout-body min-h-screen bg-slate-100 text-slate-900">
        @php
            $isDashboardView = request()->routeIs('admin.dashboard');
            $isApplicationView = request()->routeIs('admin.applications.*');
            $isAffiliateView = request()->routeIs('admin.affiliates.*');
        @endphp
        <div class="admin-layout-shell min-h-screen bg-[radial-gradient(circle_at_top_left,rgba(103,232,249,0.16),transparent_28%),radial-gradient(circle_at_bottom_right,rgba(251,146,60,0.12),transparent_28%)]">
            <header class="admin-layout-header sticky top-0 z-40 border-b border-slate-200/80 bg-white/95 shadow-sm shadow-slate-200/60 backdrop-blur">
                <div class="wrap admin-layout-bar flex items-center justify-between gap-4 py-4">
                    <div class="admin-layout-brand grid gap-1">
                        <p class="panel-card-label">MUBCAA</p>
                        <strong class="text-2xl font-bold tracking-tight text-slate-950">Admin Panel</strong>
                    </div>
                    <div class="admin-layout-actions flex flex-wrap items-center gap-3">
                        <a class="mini-link" href="{{ route('home') }}">Website</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="mini-link mini-link-button" type="submit">Logout</button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="admin-layout-main px-4 py-6 sm:px-6 lg:px-8">
                @include('partials.flash-messages')
                <div class="admin-layout-grid mx-auto grid w-full max-w-[96rem] gap-6 lg:grid-cols-[17.5rem_minmax(0,1fr)] xl:grid-cols-[18.5rem_minmax(0,1fr)]">
                    <aside class="admin-layout-sidebar self-start rounded-[2rem] border border-slate-200/90 bg-white/95 p-4 shadow-xl shadow-slate-200/60 backdrop-blur lg:sticky lg:top-24">
                        <nav class="admin-nav grid gap-2">
                            <a class="admin-nav-item block rounded-[1.35rem] border px-4 py-4 transition hover:border-cyan-100 hover:bg-cyan-50/70 hover:shadow-sm @if ($isDashboardView) is-active border-cyan-200 bg-cyan-50 text-cyan-950 shadow-sm @else border-transparent bg-slate-50 @endif" href="{{ route('admin.dashboard') }}">
                                <div>
                                    <strong class="block text-sm font-semibold text-slate-900">Dashboard</strong>
                                    <span class="mt-1 block text-sm leading-6 text-slate-500">Overview</span>
                                </div>
                            </a>
                            <a class="admin-nav-item block rounded-[1.35rem] border px-4 py-4 transition hover:border-cyan-100 hover:bg-cyan-50/70 hover:shadow-sm @if ($isApplicationView) is-active border-cyan-200 bg-cyan-50 text-cyan-950 shadow-sm @else border-transparent bg-slate-50 @endif" href="{{ route('admin.applications.index') }}">
                                <div>
                                    <strong class="block text-sm font-semibold text-slate-900">Applications</strong>
                                    <span class="mt-1 block text-sm leading-6 text-slate-500">Submitted profiles</span>
                                </div>
                            </a>
                            <a class="admin-nav-item block rounded-[1.35rem] border px-4 py-4 transition hover:border-cyan-100 hover:bg-cyan-50/70 hover:shadow-sm @if ($isAffiliateView) is-active border-cyan-200 bg-cyan-50 text-cyan-950 shadow-sm @else border-transparent bg-slate-50 @endif" href="{{ route('admin.affiliates.index') }}">
                                <div>
                                    <strong class="block text-sm font-semibold text-slate-900">Affiliates</strong>
                                    <span class="mt-1 block text-sm leading-6 text-slate-500">Referral members</span>
                                </div>
                            </a>
                            <a class="admin-nav-item block rounded-[1.35rem] border border-transparent bg-slate-50 px-4 py-4 transition hover:border-cyan-100 hover:bg-cyan-50/70 hover:shadow-sm" href="{{ route('home') }}">
                                <div>
                                    <strong class="block text-sm font-semibold text-slate-900">Website</strong>
                                    <span class="mt-1 block text-sm leading-6 text-slate-500">Public site</span>
                                </div>
                            </a>
                        </nav>
                    </aside>

                    <div class="admin-layout-content min-w-0">
                        <div class="wrap max-w-none px-0">
                            @yield('content')
                        </div>
                    </div>
                </div>
            </main>
        </div>
        @stack('scripts')
    </body>
</html>
