@extends('layouts.print')

@section('content')
    <div class="print-toolbar">
        <button onclick="window.print()">{{ __('Print / Save PDF') }}</button>
    </div>

    <section class="a4-sheet">
        <header class="document-header">
            <div class="document-brand-block">
                <p class="document-kicker">MUBCAA Member Registration Profile</p>
                <h1>{{ __('A4 Alumni Profile') }}</h1>
                <p class="document-subtitle">{{ __('Official alumni registration sheet for review, print filing, and administrative record keeping.') }}</p>
            </div>
            <div class="document-meta">
                <div><span>{{ __('Member No') }}</span><strong>{{ $user->memberNumber() }}</strong></div>
                <div><span>{{ __('Status') }}</span><strong>{{ $user->membership_status === 'pending_review' ? __('Under Review') : str($user->membership_status)->replace('_', ' ')->title() }}</strong></div>
                <div><span>{{ __('Generated') }}</span><strong>{{ now()->format('d M Y') }}</strong></div>
            </div>
        </header>

        <section class="document-section">
            <div class="section-titlebar">
                <p class="document-kicker">Section 01</p>
                <h2>{{ __('Personal Information') }}</h2>
            </div>
            <dl class="detail-list detail-list-document">
                <div><dt>{{ __('Full Name') }}</dt><dd>{{ $user->name }}</dd></div>
                <div><dt>{{ __('Email') }}</dt><dd>{{ $user->email }}</dd></div>
                <div><dt>{{ __('Phone') }}</dt><dd>{{ $profile?->primary_mobile ?: $profile?->mobile_number ?: $user->phone ?: __('N/A') }}</dd></div>
                <div><dt>{{ __('Date of Birth') }}</dt><dd>{{ optional($profile?->date_of_birth)->format('d M Y') ?: __('N/A') }}</dd></div>
                <div><dt>{{ __('Occupation') }}</dt><dd>{{ $profile?->occupation ?: __('N/A') }}</dd></div>
            </dl>
        </section>

        <section class="document-section">
            <div class="section-titlebar">
                <p class="document-kicker">Section 02</p>
                <h2>{{ __('Membership Information') }}</h2>
            </div>
            <dl class="detail-list detail-list-document">
                <div><dt>{{ __('Registration Type') }}</dt><dd>{{ __('Alumni Membership') }}</dd></div>
                <div><dt>{{ __('Workflow Status') }}</dt><dd>{{ ($application?->status ?? 'pending') === 'pending_review' ? __('Under Review') : str($application?->status ?? 'pending')->replace('_', ' ')->title() }}</dd></div>
                <div><dt>{{ __('Approval Step') }}</dt><dd>{{ $application?->current_step ?? 1 }} / {{ $application?->total_steps ?? 1 }}</dd></div>
                <div><dt>{{ __('Submitted At') }}</dt><dd>{{ optional($application?->submitted_at)->format('d M Y h:i A') ?: __('N/A') }}</dd></div>
                <div><dt>{{ __('Approved At') }}</dt><dd>{{ optional($application?->approved_at)->format('d M Y h:i A') ?: __('Pending') }}</dd></div>
            </dl>
        </section>

        <div class="detail-grid compact-document-grid">
            <section class="detail-card">
                <h2>{{ __('Address') }}</h2>
                <dl class="detail-list single-column detail-list-document">
                    <div><dt>{{ __('Present Address') }}</dt><dd>{{ $profile?->present_address ?: __('N/A') }}</dd></div>
                    <div><dt>{{ __('City / District') }}</dt><dd>{{ $profile?->city_district ?: $profile?->current_city ?: __('N/A') }}</dd></div>
                    <div><dt>{{ __('Country') }}</dt><dd>{{ $profile?->country ?: __('N/A') }}</dd></div>
                </dl>
            </section>
        </div>

        <section class="detail-card full-span">
            <h2>{{ __('Short Bio') }}</h2>
            <dl class="detail-list single-column detail-list-document">
                <div><dt>{{ __('Bio') }}</dt><dd>{{ $profile?->short_bio ?: __('N/A') }}</dd></div>
            </dl>
        </section>

        <footer class="document-footer">
            <div>
                <span>{{ __('Applicant Signature') }}</span>
                <div class="signature-line"></div>
            </div>
            <div>
                <span>{{ __('Office Use') }}</span>
                <div class="signature-line"></div>
            </div>
        </footer>
    </section>
@endsection
