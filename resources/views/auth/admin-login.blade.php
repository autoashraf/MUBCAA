@extends('layouts.app')

@section('content')
    @php
        $logoPath = config('site.brand.logo_path');
        $brandName = config('site.brand.name', 'MUBCAA');
    @endphp

    <section class="section login-auth-section">
        <div class="wrap login-auth-wrap">
            <form class="login-auth-card" method="POST" action="{{ route('admin.login.attempt') }}">
                @csrf

                <div class="login-auth-brand">
                    @if ($logoPath)
                        <span class="login-auth-logo">
                            <img src="{{ asset($logoPath) }}" alt="{{ $brandName }} logo">
                        </span>
                    @else
                        <span class="login-auth-logo login-auth-logo-fallback">M</span>
                    @endif
                    <p class="login-auth-brand-name">{{ $brandName }}</p>
                </div>

                <div class="login-auth-copy">
                    <p class="panel-card-label">Admin Access</p>
                    <h1>Admin Login</h1>
                    <p>Use your admin email address and password to access the admin panel.</p>
                </div>

                <div class="login-auth-form">
                    <label class="login-auth-label">
                        <span>Email address</span>
                        <input class="login-auth-input" type="email" name="email" value="{{ old('email') }}" placeholder="Enter admin email" required>
                        @error('email') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="login-auth-label">
                        <span>Password</span>
                        <input class="login-auth-input" type="password" name="password" placeholder="Enter password" required>
                        @error('password') <small>{{ $message }}</small> @enderror
                    </label>

                    <div class="login-auth-actions">
                        <button class="button button-primary login-auth-submit" type="submit">Admin Login</button>
                        <a class="button button-secondary login-auth-secondary" href="{{ route('login') }}">Member OTP Login</a>
                    </div>
                </div>
            </form>
        </div>
    </section>
@endsection
