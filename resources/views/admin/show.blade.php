@extends('layouts.app')

@section('content')
    <section class="section admin-shell">
        <div class="wrap admin-detail-stack">
            <div class="admin-detail-head">
                <div>
                    <p class="eyebrow">Admin Application View</p>
                    <h1>{{ $user->name }}</h1>
                    <p class="lead">Review the complete submitted alumni profile and take a direct decision from one screen.</p>
                </div>
                <div class="admin-detail-actions">
                    <a class="button button-secondary" href="{{ route('admin.dashboard') }}">Back to Admin Panel</a>
                </div>
            </div>

            <div class="admin-detail-grid">
                <article class="profile-summary-card">
                    <span class="panel-card-label">Application</span>
                    <dl class="profile-summary-list">
                        <div><dt>Status</dt><dd>{{ str($application->status)->replace('_', ' ')->title() }}</dd></div>
                        <div><dt>Submitted At</dt><dd>{{ optional($application->submitted_at)->format('d M Y h:i A') ?: 'Not submitted' }}</dd></div>
                        <div><dt>Reviewed By</dt><dd>{{ $application->reviewer?->name ?: 'Not assigned' }}</dd></div>
                        <div><dt>Approved At</dt><dd>{{ optional($application->approved_at)->format('d M Y h:i A') ?: 'Pending' }}</dd></div>
                    </dl>
                </article>

                <article class="profile-summary-card">
                    <span class="panel-card-label">Member</span>
                    <dl class="profile-summary-list">
                        <div><dt>Member No</dt><dd>{{ $user->memberNumber() }}</dd></div>
                        <div><dt>Email</dt><dd>{{ $user->email }}</dd></div>
                        <div><dt>Phone</dt><dd>{{ $user->phone ?: 'Not added' }}</dd></div>
                        <div><dt>Verification</dt><dd>{{ $user->hasCompletedContactVerification() ? 'Email and mobile verified' : 'Verification incomplete' }}</dd></div>
                    </dl>
                </article>
            </div>

            <div class="admin-detail-grid">
                <article class="profile-summary-card">
                    <span class="panel-card-label">Academic Information</span>
                    <dl class="profile-summary-list">
                        <div><dt>Passing Year SSC</dt><dd>{{ $profile?->ssc_passing_year ?: 'Not added' }}</dd></div>
                        <div><dt>Passing Year HSC</dt><dd>{{ $profile?->hsc_passing_year ?: 'Not added' }}</dd></div>
                        <div><dt>Group</dt><dd>{{ $profile?->group ?: 'Not added' }}</dd></div>
                        <div><dt>Shift</dt><dd>{{ $profile?->shift ?: 'Not added' }}</dd></div>
                        <div><dt>Campus / Branch</dt><dd>{{ $profile?->campus_branch ?: 'Not added' }}</dd></div>
                    </dl>
                </article>

                <article class="profile-summary-card">
                    <span class="panel-card-label">Personal Information</span>
                    <dl class="profile-summary-list">
                        <div><dt>Father’s Name</dt><dd>{{ $profile?->father_name ?: 'Not added' }}</dd></div>
                        <div><dt>Mother’s Name</dt><dd>{{ $profile?->mother_name ?: 'Not added' }}</dd></div>
                        <div><dt>Date of Birth</dt><dd>{{ optional($profile?->date_of_birth)->format('d M Y') ?: 'Not added' }}</dd></div>
                        <div><dt>Gender</dt><dd>{{ $profile?->gender ?: 'Not added' }}</dd></div>
                        <div><dt>Blood Group</dt><dd>{{ $profile?->blood_group ?: 'Not added' }}</dd></div>
                        <div><dt>Marital Status</dt><dd>{{ $profile?->marital_status ?: 'Not added' }}</dd></div>
                    </dl>
                </article>
            </div>

            <div class="admin-detail-grid">
                <article class="profile-summary-card">
                    <span class="panel-card-label">Contact Information</span>
                    <dl class="profile-summary-list">
                        <div><dt>Primary Mobile</dt><dd>{{ $profile?->primary_mobile ?: 'Not added' }}</dd></div>
                        <div><dt>Secondary Mobile</dt><dd>{{ $profile?->secondary_mobile ?: 'Not added' }}</dd></div>
                        <div><dt>WhatsApp</dt><dd>{{ $profile?->whatsapp_number ?: 'Not added' }}</dd></div>
                        <div><dt>Email Address</dt><dd>{{ $profile?->email_address ?: 'Not added' }}</dd></div>
                        <div><dt>Present Address</dt><dd>{{ $profile?->present_address ?: 'Not added' }}</dd></div>
                        <div><dt>Permanent Address</dt><dd>{{ $profile?->permanent_address ?: 'Not added' }}</dd></div>
                        <div><dt>Country</dt><dd>{{ $profile?->country ?: 'Not added' }}</dd></div>
                        <div><dt>City / District</dt><dd>{{ $profile?->city_district ?: 'Not added' }}</dd></div>
                        <div><dt>Postal Code</dt><dd>{{ $profile?->postal_code ?: 'Not added' }}</dd></div>
                    </dl>
                </article>

                <article class="profile-summary-card">
                    <span class="panel-card-label">Professional Information</span>
                    <dl class="profile-summary-list">
                        <div><dt>Occupation</dt><dd>{{ $profile?->occupation ?: 'Not added' }}</dd></div>
                        <div><dt>Organization</dt><dd>{{ $profile?->organization_name ?: 'Not added' }}</dd></div>
                        <div><dt>Designation</dt><dd>{{ $profile?->designation ?: 'Not added' }}</dd></div>
                        <div><dt>Industry</dt><dd>{{ $profile?->industry ?: 'Not added' }}</dd></div>
                        <div><dt>Office Address</dt><dd>{{ $profile?->office_address ?: 'Not added' }}</dd></div>
                    </dl>
                </article>
            </div>

            <div class="admin-detail-grid">
                <article class="profile-summary-card">
                    <span class="panel-card-label">Media & Links</span>
                    <dl class="profile-summary-list">
                        <div>
                            <dt>Profile Photo</dt>
                            <dd>
                                @if ($profile?->profile_photo)
                                    <a class="file-link-with-icon" href="{{ asset('storage/'.$profile->profile_photo) }}" target="_blank" rel="noopener"><span aria-hidden="true">↗</span><span>View uploaded file</span></a>
                                @else
                                    Not uploaded
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt>Cover Photo</dt>
                            <dd>
                                @if ($profile?->cover_photo)
                                    <a class="file-link-with-icon" href="{{ asset('storage/'.$profile->cover_photo) }}" target="_blank" rel="noopener"><span aria-hidden="true">↗</span><span>View uploaded file</span></a>
                                @else
                                    Not uploaded
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt>Business Card</dt>
                            <dd>
                                @if ($profile?->business_card_upload)
                                    <a class="file-link-with-icon" href="{{ asset('storage/'.$profile->business_card_upload) }}" target="_blank" rel="noopener"><span aria-hidden="true">↗</span><span>View uploaded file</span></a>
                                @else
                                    Not uploaded
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt>Facebook</dt>
                            <dd>
                                @if ($profile?->facebook_profile_link)
                                    <a class="file-link-with-icon" href="{{ $profile->facebook_profile_link }}" target="_blank" rel="noopener"><span aria-hidden="true">↗</span><span>{{ $profile->facebook_profile_link }}</span></a>
                                @else
                                    Not added
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt>LinkedIn</dt>
                            <dd>
                                @if ($profile?->linkedin_profile_link)
                                    <a class="file-link-with-icon" href="{{ $profile->linkedin_profile_link }}" target="_blank" rel="noopener"><span aria-hidden="true">↗</span><span>{{ $profile->linkedin_profile_link }}</span></a>
                                @else
                                    Not added
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt>Website</dt>
                            <dd>
                                @if ($profile?->website_portfolio_link)
                                    <a class="file-link-with-icon" href="{{ $profile->website_portfolio_link }}" target="_blank" rel="noopener"><span aria-hidden="true">↗</span><span>{{ $profile->website_portfolio_link }}</span></a>
                                @else
                                    Not added
                                @endif
                            </dd>
                        </div>
                    </dl>
                </article>

                <article class="profile-summary-card">
                    <span class="panel-card-label">Engagement</span>
                    <dl class="profile-summary-list">
                        <div><dt>Interested in Activities</dt><dd>{{ is_null($profile?->interested_in_alumni_activities) ? 'Not answered' : ($profile?->interested_in_alumni_activities ? 'Yes' : 'No') }}</dd></div>
                        <div><dt>Areas of Interest</dt><dd>{{ filled($profile?->areas_of_interest) ? implode(', ', $profile->areas_of_interest) : 'Not added' }}</dd></div>
                        <div><dt>Volunteer Interest</dt><dd>{{ is_null($profile?->volunteer_interest) ? 'Not answered' : ($profile?->volunteer_interest ? 'Yes' : 'No') }}</dd></div>
                        <div><dt>Donor / Sponsor Interest</dt><dd>{{ $profile?->donor_sponsor_interest ?: 'Not added' }}</dd></div>
                        <div><dt>Mentor Interest</dt><dd>{{ is_null($profile?->mentor_interest) ? 'Not answered' : ($profile?->mentor_interest ? 'Yes' : 'No') }}</dd></div>
                        <div><dt>Suggestions</dt><dd>{{ $profile?->suggestions ?: 'Not added' }}</dd></div>
                    </dl>
                </article>
            </div>

            <div class="admin-detail-grid">
                <article class="profile-summary-card">
                    <span class="panel-card-label">Verification & Privacy</span>
                    <dl class="profile-summary-list">
                        <div>
                            <dt>SSC Certificate / Testimonial / Admit Card</dt>
                            <dd>
                                @if ($profile?->certificate_testimonial_upload)
                                    <a class="file-link-with-icon" href="{{ asset('storage/'.$profile->certificate_testimonial_upload) }}" target="_blank" rel="noopener"><span aria-hidden="true">↗</span><span>View uploaded file</span></a>
                                @else
                                    Not uploaded
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt>Supporting Document</dt>
                            <dd>
                                @if ($profile?->supporting_document_upload)
                                    <a class="file-link-with-icon" href="{{ asset('storage/'.$profile->supporting_document_upload) }}" target="_blank" rel="noopener"><span aria-hidden="true">↗</span><span>View uploaded file</span></a>
                                @else
                                    Not uploaded
                                @endif
                            </dd>
                        </div>
                        <div><dt>Profile Visibility</dt><dd>{{ $profile?->profile_visibility ?: 'Not added' }}</dd></div>
                        <div><dt>Contact Visibility</dt><dd>{{ $profile?->contact_visibility ?: 'Not added' }}</dd></div>
                    </dl>
                </article>

                <article class="profile-summary-card">
                    <span class="panel-card-label">Declaration & Review</span>
                    <dl class="profile-summary-list">
                        <div><dt>Accuracy Confirmation</dt><dd>{{ $profile?->information_accuracy_confirmation ? 'Accepted' : 'Not accepted' }}</dd></div>
                        <div><dt>Terms & Privacy</dt><dd>{{ $profile?->terms_privacy_agreement ? 'Accepted' : 'Not accepted' }}</dd></div>
                        <div><dt>Admin Verification Agreement</dt><dd>{{ $profile?->admin_verification_agreement ? 'Accepted' : 'Not accepted' }}</dd></div>
                        <div><dt>Admin Notes</dt><dd>{{ $application->admin_notes ?: 'No admin notes added yet.' }}</dd></div>
                    </dl>
                </article>
            </div>

            <div class="admin-detail-grid">
                <article class="profile-summary-card">
                    <span class="panel-card-label">Admin Actions</span>
                    @if (! in_array($application->status, ['approved', 'rejected'], true))
                        <div class="admin-actions-grid">
                            <form class="panel-card admin-action-card" method="POST" action="{{ route('admin.applications.approve', $application) }}">
                                @csrf
                                <p class="panel-card-label">Approve</p>
                                <label for="detail-approve-notes">Admin notes</label>
                                <textarea id="detail-approve-notes" name="admin_notes" rows="4" placeholder="Optional approval note"></textarea>
                                <button class="button button-primary" type="submit">Approve Application</button>
                            </form>

                            <form class="panel-card admin-action-card" method="POST" action="{{ route('admin.applications.reject', $application) }}">
                                @csrf
                                <p class="panel-card-label">Reject</p>
                                <label for="detail-reject-notes">Rejection note</label>
                                <textarea id="detail-reject-notes" name="admin_notes" rows="4" placeholder="Required rejection reason" required></textarea>
                                <button class="button danger-button" type="submit">Reject Application</button>
                            </form>
                        </div>
                    @else
                        <p class="dashboard-copy">This application has already been finalized.</p>
                    @endif
                </article>
            </div>
        </div>
    </section>
@endsection
