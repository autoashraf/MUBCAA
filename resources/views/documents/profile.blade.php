@extends('layouts.print')

@section('content')
    <div class="print-toolbar">
        <button onclick="window.print()">Print / Save PDF</button>
    </div>

    <section class="a4-sheet">
        <header class="document-header">
            <div class="document-brand-block">
                <p class="document-kicker">MUBCAA Member Registration Profile</p>
                <h1>A4 Membership Profile</h1>
                <p class="document-subtitle">Official member information sheet for review, print filing, and administrative record keeping.</p>
            </div>
            <div class="document-meta">
                <div><span>Member No</span><strong>{{ $user->memberNumber() }}</strong></div>
                <div><span>Status</span><strong>{{ str($user->membership_status)->replace('_', ' ')->title() }}</strong></div>
                <div><span>Generated</span><strong>{{ now()->format('d M Y') }}</strong></div>
            </div>
        </header>

        <section class="document-section">
            <div class="section-titlebar">
                <p class="document-kicker">Section 01</p>
                <h2>Personal Information</h2>
            </div>
            <dl class="detail-list detail-list-document">
                <div><dt>Full Name</dt><dd>{{ $user->name }}</dd></div>
                <div><dt>Email</dt><dd>{{ $user->email }}</dd></div>
                <div><dt>Phone</dt><dd>{{ $user->phone ?: 'N/A' }}</dd></div>
                <div><dt>Date of Birth</dt><dd>{{ optional($profile?->date_of_birth)->format('d M Y') ?: 'N/A' }}</dd></div>
                <div><dt>Occupation</dt><dd>{{ $profile?->occupation ?: 'N/A' }}</dd></div>
            </dl>
        </section>

        <section class="document-section">
            <div class="section-titlebar">
                <p class="document-kicker">Section 02</p>
                <h2>Membership Information</h2>
            </div>
            <dl class="detail-list detail-list-document">
                <div><dt>Membership Type</dt><dd>{{ $profile?->membershipType?->name ?: 'N/A' }}</dd></div>
                <div><dt>Workflow Status</dt><dd>{{ str($application?->status ?? 'pending')->replace('_', ' ')->title() }}</dd></div>
                <div><dt>Approval Step</dt><dd>{{ $application?->current_step ?? 1 }} / {{ $application?->total_steps ?? 1 }}</dd></div>
                <div><dt>Submitted At</dt><dd>{{ optional($application?->submitted_at)->format('d M Y h:i A') ?: 'N/A' }}</dd></div>
                <div><dt>Approved At</dt><dd>{{ optional($application?->approved_at)->format('d M Y h:i A') ?: 'Pending' }}</dd></div>
            </dl>
        </section>

        <div class="detail-grid compact-document-grid">
            <section class="detail-card">
                <h2>Address</h2>
                <dl class="detail-list single-column detail-list-document">
                    <div><dt>Address</dt><dd>{{ $profile?->address ?: 'N/A' }}</dd></div>
                    <div><dt>City</dt><dd>{{ $profile?->city ?: 'N/A' }}</dd></div>
                    <div><dt>Country</dt><dd>{{ $profile?->country ?: 'N/A' }}</dd></div>
                </dl>
            </section>

            <section class="detail-card">
                <h2>Emergency Contact</h2>
                <dl class="detail-list single-column detail-list-document">
                    <div><dt>Contact Name</dt><dd>{{ $profile?->emergency_contact_name ?: 'N/A' }}</dd></div>
                    <div><dt>Contact Phone</dt><dd>{{ $profile?->emergency_contact_phone ?: 'N/A' }}</dd></div>
                </dl>
            </section>
        </div>

        <section class="detail-card full-span">
            <h2>Short Bio</h2>
            <dl class="detail-list single-column detail-list-document">
                <div><dt>Bio</dt><dd>{{ $profile?->bio ?: 'N/A' }}</dd></div>
            </dl>
        </section>

        <footer class="document-footer">
            <div>
                <span>Applicant Signature</span>
                <div class="signature-line"></div>
            </div>
            <div>
                <span>Office Use</span>
                <div class="signature-line"></div>
            </div>
        </footer>
    </section>
@endsection
