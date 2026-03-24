@extends('layouts.app')

@section('content')
    <section class="page-hero">
        <div class="wrap narrow">
            <p class="eyebrow">Alumni Membership Registration Form</p>
            <h1>Step 1: Basic Info</h1>
            <p class="lead">Please complete your registration step by step. You can save your progress at each step and continue later. Your membership profile will be submitted for admin verification after the final step.</p>
        </div>
    </section>

    <section class="section">
        <div class="wrap registration-shell">
            <aside class="list-card registration-side-card">
                <h3>Registration roadmap</h3>
                <p class="dashboard-copy">Please complete your registration step by step. You may save your progress at each step and continue later. Your membership will be submitted for admin verification after the final step.</p>
                <ol class="registration-step-list">
                    <li class="is-active"><strong>Step 1</strong><span>Basic Info</span></li>
                    <li><strong>Step 2</strong><span>Academic Info</span></li>
                    <li><strong>Step 3</strong><span>Personal Info</span></li>
                    <li><strong>Step 4</strong><span>Contact Info</span></li>
                    <li><strong>Step 5</strong><span>Professional Info</span></li>
                    <li><strong>Step 6</strong><span>Media &amp; Links</span></li>
                    <li><strong>Step 7</strong><span>Engagement</span></li>
                    <li><strong>Step 8</strong><span>Verification</span></li>
                    <li><strong>Step 9</strong><span>Privacy</span></li>
                    <li><strong>Step 10</strong><span>Declaration</span></li>
                </ol>

                <div class="registration-status-card">
                    <p class="panel-card-label">Admin Status Flow</p>
                    <div class="type-stack">
                        @foreach ($registrationStatuses as $status)
                            <div class="type-card">{{ $status }}</div>
                        @endforeach
                    </div>
                </div>
            </aside>

            <form class="form-card registration-form-card" method="POST" action="{{ route('membership.apply.store') }}" data-ajax-form="registration">
                @csrf

                <div class="dashboard-form-head">
                    <div>
                        <p class="panel-card-label">Step 1: Basic Info</p>
                        <h3>Basic information and OTP verification</h3>
                        <p class="dashboard-copy">Please provide your basic information to begin your alumni registration. Your member account will be created only after both your mobile number and email address are verified with OTP codes.</p>
                    </div>
                    <span class="dashboard-chip">Quick Registration</span>
                </div>

                <div class="form-grid">
                    <label>
                        <span>Full Name</span>
                        <input type="text" name="full_name" value="{{ old('full_name') }}" placeholder="Enter your full name" required>
                        @error('full_name') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        <span>Mobile Number</span>
                        <input type="text" name="mobile_number" value="{{ old('mobile_number') }}" placeholder="Enter your mobile number" required>
                        @error('mobile_number') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        <span>Email Address</span>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="Enter your email address" required>
                        @error('email') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        <span>Passing Year / Batch</span>
                        <input type="text" name="passing_year_batch" value="{{ old('passing_year_batch') }}" placeholder="Enter your passing year or batch" required>
                        @error('passing_year_batch') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        <span>Student ID / Roll Number</span>
                        <input type="text" name="student_id_or_roll" value="{{ old('student_id_or_roll') }}" placeholder="Enter your student ID or roll number" required>
                        @error('student_id_or_roll') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        <span>Current City</span>
                        <input type="text" name="current_city" value="{{ old('current_city') }}" placeholder="Enter your current city" required>
                        @error('current_city') <small>{{ $message }}</small> @enderror
                    </label>

                </div>

                <div class="registration-note">
                    <strong>Note:</strong> First submit your basic information, then verify your mobile number and email address with OTP codes. After verification, you can continue with academic, personal, contact, professional, media, engagement, verification, privacy, and declaration steps.
                </div>

                <div class="action-row">
                    <button class="button button-primary" type="submit">Continue to OTP Verification</button>
                    <a class="button button-secondary" href="{{ route('login') }}">Already have an account?</a>
                </div>
            </form>
        </div>
    </section>

    <div data-verification-modal-root>
        @if (! empty($showVerificationModal))
            @include('partials.verification-modal', ['verificationSuccessMessage' => session('success')])
        @endif
    </div>
@endsection
