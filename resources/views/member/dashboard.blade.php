@extends('layouts.app')

@section('content')
    <section class="page-hero">
        <div class="wrap">
            <div class="dashboard-hero">
                <div class="dashboard-hero-copy">
                    <div class="dashboard-profile-band">
                        <div class="dashboard-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                        <div>
                            <p class="eyebrow">Member Dashboard</p>
                            <h1>{{ $user->name }}</h1>
                            <p class="lead">Your member area for profile management, workflow tracking, and printable membership documents.</p>
                        </div>
                    </div>
                    <div class="dashboard-badges">
                        <span class="status-pill status-{{ $application?->status ?? $user->membership_status }}">
                            {{ str($application?->status ?? $user->membership_status)->replace('_', ' ')->title() }}
                        </span>
                        <span class="dashboard-chip">{{ $user->profile?->membershipType?->name ?? 'Membership Type Pending' }}</span>
                        <span class="dashboard-chip">Profile {{ $profileCompletion }}% complete</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="wrap">
            <div class="dashboard-feature-strip">
                <article class="dashboard-feature-card">
                    <span class="dashboard-feature-index">01</span>
                    <div>
                        <strong>Documents Ready</strong>
                        <p>A4 profile, ID card, and certificate access from one place.</p>
                    </div>
                </article>
                <article class="dashboard-feature-card">
                    <span class="dashboard-feature-index">02</span>
                    <div>
                        <strong>Workflow Visibility</strong>
                        <p>Track every approval step with clear status feedback.</p>
                    </div>
                </article>
                <article class="dashboard-feature-card">
                    <span class="dashboard-feature-index">03</span>
                    <div>
                        <strong>Profile Control</strong>
                        <p>Keep your personal information updated for membership services.</p>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="wrap dashboard-overview">
            <div class="dashboard-main">
                <div class="panel-card dashboard-status-card">
                    <div class="dashboard-status-head">
                        <div>
                            <p class="panel-card-label">Membership Progress</p>
                            <h3>{{ str($application?->status ?? $user->membership_status)->replace('_', ' ')->title() }}</h3>
                        </div>
                        <div class="progress-ring">
                            <strong>{{ $profileCompletion }}%</strong>
                            <span>Profile</span>
                        </div>
                    </div>
                    <div class="timeline">
                        @foreach ($workflowSteps as $step)
                            <div class="timeline-item @if (($application?->current_step ?? 0) >= $step->step_number) is-complete @endif">
                                <strong>Step {{ $step->step_number }}: {{ $step->title }}</strong>
                                <span>{{ $step->description }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <aside class="dashboard-side">
                <div class="panel-card dashboard-hero-card">
                    <p class="panel-card-label">Member Summary</p>
                    <div class="dashboard-summary-grid">
                        <div>
                            <span>Member No</span>
                            <strong>{{ $user->memberNumber() }}</strong>
                        </div>
                        <div>
                            <span>Approval Step</span>
                            <strong>{{ $application?->current_step ?? 1 }} / {{ $application?->total_steps ?? 1 }}</strong>
                        </div>
                        <div>
                            <span>Joined</span>
                            <strong>{{ $user->created_at?->format('d M Y') }}</strong>
                        </div>
                        <div>
                            <span>City</span>
                            <strong>{{ $user->profile?->city ?? 'Not set' }}</strong>
                        </div>
                    </div>
                </div>

                <div class="panel-card dashboard-documents-card">
                    <p class="panel-card-label">Documents</p>
                    <div class="dashboard-doc-links">
                        <a class="dashboard-doc-link" href="{{ route('member.documents.profile') }}" target="_blank">
                            <strong>A4 Profile</strong>
                            <span>Printable full registration form</span>
                        </a>
                        <a class="dashboard-doc-link" href="{{ route('member.documents.id-card') }}" target="_blank">
                            <strong>ID Card</strong>
                            <span>Print-ready front and back member card</span>
                        </a>
                        @if ($user->membership_status === 'active')
                            <a class="dashboard-doc-link dashboard-doc-link-active" href="{{ route('member.documents.certificate') }}" target="_blank">
                                <strong>Certificate</strong>
                                <span>Membership certificate for active members</span>
                            </a>
                        @else
                            <div class="dashboard-doc-link dashboard-doc-link-muted">
                                <strong>Certificate Locked</strong>
                                <span>Certificate becomes available after approval</span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="panel-card dashboard-snapshot-card">
                    <p class="panel-card-label">Account Snapshot</p>
                    <div class="snapshot-list">
                        <div><span>Email</span><strong>{{ $user->email }}</strong></div>
                        <div><span>Phone</span><strong>{{ $user->phone ?? 'Not provided' }}</strong></div>
                        <div><span>City</span><strong>{{ $user->profile?->city ?? 'Not provided' }}</strong></div>
                        <div><span>Occupation</span><strong>{{ $user->profile?->occupation ?? 'Not provided' }}</strong></div>
                    </div>
                </div>
            </aside>
        </div>
    </section>

    <section class="section">
        <div class="wrap">
            <form class="form-card dashboard-profile-form" method="POST" action="{{ route('member.profile.update') }}">
                @csrf
                @method('PUT')

                <div class="dashboard-form-head">
                    <div>
                        <p class="panel-card-label">Profile Management</p>
                        <h3>Edit your member profile</h3>
                        <p class="dashboard-copy">Keep your information complete so approval, documents, and future member services work correctly.</p>
                    </div>
                    <button class="button button-primary" type="submit">Save Changes</button>
                </div>

                @if (session('success'))
                    <div class="alert-success">{{ session('success') }}</div>
                @endif

                <div class="dashboard-form-sections">
                    <section class="dashboard-form-block">
                        <div class="dashboard-block-head">
                            <h4>Basic Information</h4>
                            <span>Core member identity and address details</span>
                        </div>
                        <div class="form-grid">
                            <label>
                                <span>Full name</span>
                                <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
                                @error('name') <small>{{ $message }}</small> @enderror
                            </label>
                            <label>
                                <span>Phone</span>
                                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" required>
                                @error('phone') <small>{{ $message }}</small> @enderror
                            </label>
                            <label>
                                <span>Address</span>
                                <input type="text" name="address" value="{{ old('address', $user->profile?->address) }}" required>
                                @error('address') <small>{{ $message }}</small> @enderror
                            </label>
                            <label>
                                <span>City</span>
                                <input type="text" name="city" value="{{ old('city', $user->profile?->city) }}" required>
                                @error('city') <small>{{ $message }}</small> @enderror
                            </label>
                            <label>
                                <span>Country</span>
                                <input type="text" name="country" value="{{ old('country', $user->profile?->country ?? 'Bangladesh') }}" required>
                                @error('country') <small>{{ $message }}</small> @enderror
                            </label>
                            <label>
                                <span>Occupation</span>
                                <input type="text" name="occupation" value="{{ old('occupation', $user->profile?->occupation) }}">
                                @error('occupation') <small>{{ $message }}</small> @enderror
                            </label>
                        </div>
                    </section>

                    <section class="dashboard-form-block">
                        <div class="dashboard-block-head">
                            <h4>Personal Details</h4>
                            <span>Emergency contacts and profile narrative</span>
                        </div>
                        <div class="form-grid">
                            <label>
                                <span>Date of birth</span>
                                <input type="date" name="date_of_birth" value="{{ old('date_of_birth', optional($user->profile?->date_of_birth)->format('Y-m-d')) }}">
                                @error('date_of_birth') <small>{{ $message }}</small> @enderror
                            </label>
                            <label>
                                <span>Emergency contact name</span>
                                <input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name', $user->profile?->emergency_contact_name) }}">
                                @error('emergency_contact_name') <small>{{ $message }}</small> @enderror
                            </label>
                            <label>
                                <span>Emergency contact phone</span>
                                <input type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $user->profile?->emergency_contact_phone) }}">
                                @error('emergency_contact_phone') <small>{{ $message }}</small> @enderror
                            </label>
                        </div>

                        <label>
                            <span>Short bio</span>
                            <textarea name="bio" rows="5">{{ old('bio', $user->profile?->bio) }}</textarea>
                            @error('bio') <small>{{ $message }}</small> @enderror
                        </label>
                    </section>
                </div>
            </form>
        </div>
    </section>
@endsection
