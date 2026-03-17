@extends('layouts.app')

@section('content')
    <section class="page-hero">
        <div class="wrap narrow">
            <p class="eyebrow">Member Access</p>
            <h1>Login to your membership account</h1>
            <p class="lead">Members can manage profiles and follow approval status here. Admins can review and approve applications.</p>
        </div>
    </section>

    <section class="section">
        <div class="wrap narrow">
            <form class="form-card" method="POST" action="{{ route('login.attempt') }}">
                @csrf

                <label>
                    <span>Email address</span>
                    <input type="email" name="email" value="{{ old('email') }}" required>
                    @error('email') <small>{{ $message }}</small> @enderror
                </label>

                <label>
                    <span>Password</span>
                    <input type="password" name="password" required>
                    @error('password') <small>{{ $message }}</small> @enderror
                </label>

                <label class="checkbox-row">
                    <input type="checkbox" name="remember" value="1">
                    <span>Keep me logged in</span>
                </label>

                <div class="action-row">
                    <button class="button button-primary" type="submit">Login</button>
                    <a class="button button-secondary" href="{{ route('membership.apply') }}">Create account</a>
                </div>
            </form>
        </div>
    </section>
@endsection
