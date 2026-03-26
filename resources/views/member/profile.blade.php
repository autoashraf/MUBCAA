@extends('layouts.app')

@section('content')
    <section class="page-hero">
        <div class="wrap">
            <div class="dashboard-hero dashboard-hero-member">
                <div class="dashboard-hero-copy">
                    <div class="dashboard-profile-band">
                        <div class="dashboard-avatar">
                            @if ($profile?->profile_photo)
                                <img src="{{ asset('storage/'.$profile->profile_photo) }}" alt="{{ $user->name }}">
                            @else
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            @endif
                        </div>
                        <div>
                            <p class="eyebrow">Member Profile</p>
                            <h1>{{ $user->name }}</h1>
                            <p class="lead">Your profile summary, application status, and saved alumni information.</p>
                        </div>
                    </div>
                    <div class="hero-actions">
                        <a class="button button-secondary" href="{{ route('member.profile.complete', ['step' => max(2, $profile?->completion_step ?? 2)]) }}">Open Profile Wizard</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="wrap">
            <div class="profile-summary-grid">
                <article class="profile-summary-card">
                    <span class="panel-card-label">Basic Information</span>
                    <dl class="profile-summary-list">
                        <div><dt>Full Name</dt><dd>{{ $user->name }}</dd></div>
                        <div><dt>Email</dt><dd>{{ $user->email }}</dd></div>
                        <div><dt>Mobile</dt><dd>{{ $user->phone ?: ($profile?->primary_mobile ?? 'Not added') }}</dd></div>
                        <div><dt>Passing Year / Batch</dt><dd>{{ $profile?->passing_year_batch ?: 'Not added' }}</dd></div>
                    </dl>
                </article>

                <article class="profile-summary-card">
                    <span class="panel-card-label">Academic Information</span>
                    <dl class="profile-summary-list">
                        <div><dt>Passing Year SSC</dt><dd>{{ $profile?->ssc_passing_year ?: 'Not added' }}</dd></div>
                        <div><dt>Passing Year HSC</dt><dd>{{ $profile?->hsc_passing_year ?: 'Not added' }}</dd></div>
                        <div><dt>Group</dt><dd>{{ $profile?->group ?: 'Not added' }}</dd></div>
                        <div><dt>Campus / Branch</dt><dd>{{ $profile?->campus_branch ?: 'Not added' }}</dd></div>
                    </dl>
                </article>

                <article class="profile-summary-card">
                    <span class="panel-card-label">Personal & Contact</span>
                    <dl class="profile-summary-list">
                        <div><dt>Date of Birth</dt><dd>{{ optional($profile?->date_of_birth)->format('d M Y') ?: 'Not added' }}</dd></div>
                        <div><dt>City / District</dt><dd>{{ $profile?->city_district ?: 'Not added' }}</dd></div>
                        <div><dt>Present Address</dt><dd>{{ $profile?->present_address ?: 'Not added' }}</dd></div>
                        <div><dt>Permanent Address</dt><dd>{{ $profile?->permanent_address ?: 'Not added' }}</dd></div>
                    </dl>
                </article>

                <article class="profile-summary-card">
                    <span class="panel-card-label">Professional Information</span>
                    <dl class="profile-summary-list">
                        <div><dt>Occupation</dt><dd>{{ $profile?->occupation ?: 'Not added' }}</dd></div>
                        <div><dt>Organization</dt><dd>{{ $profile?->organization_name ?: 'Not added' }}</dd></div>
                        <div><dt>Designation</dt><dd>{{ $profile?->designation ?: 'Not added' }}</dd></div>
                        <div><dt>Industry</dt><dd>{{ $profile?->industry ?: 'Not added' }}</dd></div>
                    </dl>
                </article>
            </div>
        </div>
    </section>
@endsection
