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

                @if (! empty($affiliateReferrer))
                    <div class="registration-referral-banner">
                        <strong>Referred by {{ $affiliateReferrer->name }}</strong>
                        <span>Referral code: {{ $affiliateReferrer->affiliate_code }}</span>
                    </div>
                @endif

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
                        <select name="passing_year_batch" required>
                            <option value="">Select year</option>
                            @foreach ($passingYears as $option)
                                <option value="{{ $option }}" @selected(old('passing_year_batch') === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                        @error('passing_year_batch') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        <span>Security Check</span>
                        <div class="captcha-inline">
                            <span class="captcha-chip">{{ $captchaLeft }} + {{ $captchaRight }} = ?</span>
                            <input type="hidden" name="captcha_left" value="{{ $captchaLeft }}">
                            <input type="hidden" name="captcha_right" value="{{ $captchaRight }}">
                            <input type="text" name="captcha_answer" value="{{ old('captcha_answer') }}" placeholder="Enter answer" required>
                        </div>
                        @error('captcha_answer') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <div class="registration-note">
                    <strong>Note:</strong> First submit your basic information, then verify your mobile number and email address with OTP codes. After verification, you can continue with academic, personal, contact, professional, media, engagement, verification, privacy, and declaration steps.
                </div>

                <div class="action-row">
                    <button class="button button-primary button-loading-trigger" type="submit" data-loading-text="Sending OTP...">
                        <span class="button-loading-label">Continue to OTP Verification</span>
                    </button>
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
