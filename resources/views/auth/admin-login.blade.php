@extends('layouts.app')

@section('content')
    <section class="page-hero">
        <div class="wrap narrow">
            <p class="eyebrow">Admin Access</p>
            <h1>Admin Login</h1>
            <p class="lead">Use your admin email address and password to access the MUBCAA admin panel.</p>
        </div>
    </section>

    <section class="section">
        <div class="wrap narrow">
            <form class="form-card login-card" method="POST" action="{{ route('admin.login.attempt') }}">
                @csrf

                <div class="dashboard-form-head login-card-head">
                    <div>
                        <p class="panel-card-label">Admin Login</p>
                        <h2>Sign in as admin</h2>
                    </div>
                </div>

                <label>
                    <span>Email address</span>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="Enter admin email" required>
                    @error('email') <small>{{ $message }}</small> @enderror
                </label>

                <label>
                    <span>Password</span>
                    <input type="password" name="password" placeholder="Enter password" required>
                    @error('password') <small>{{ $message }}</small> @enderror
                </label>

                <div class="action-row login-card-actions">
                    <button class="button button-primary" type="submit">Admin Login</button>
                    <a class="button button-secondary" href="{{ route('login') }}">Member OTP Login</a>
                </div>
            </form>
        </div>
    </section>
@endsection
