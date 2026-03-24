<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'mobile_number',
        'mobile_verified',
        'passing_year_batch',
        'student_id_or_roll',
        'ssc_passing_year',
        'hsc_passing_year',
        'group',
        'shift',
        'campus_branch',
        'father_name',
        'mother_name',
        'date_of_birth',
        'gender',
        'blood_group',
        'marital_status',
        'primary_mobile',
        'secondary_mobile',
        'whatsapp_number',
        'email_address',
        'present_address',
        'permanent_address',
        'country',
        'city_district',
        'postal_code',
        'occupation',
        'organization_name',
        'designation',
        'industry',
        'office_address',
        'work_email',
        'business_name',
        'professional_skills',
        'profile_photo',
        'cover_photo',
        'short_bio',
        'facebook_profile_link',
        'linkedin_profile_link',
        'website_portfolio_link',
        'interested_in_alumni_activities',
        'areas_of_interest',
        'volunteer_interest',
        'donor_sponsor_interest',
        'mentor_interest',
        'suggestions',
        'certificate_testimonial_upload',
        'supporting_document_upload',
        'profile_visibility',
        'contact_visibility',
        'information_accuracy_confirmation',
        'terms_privacy_agreement',
        'admin_verification_agreement',
        'completion_step',
        'submitted_for_review_at',
        'current_city',
    ];

    protected $casts = [
        'mobile_verified' => 'boolean',
        'date_of_birth' => 'date',
        'areas_of_interest' => 'array',
        'interested_in_alumni_activities' => 'boolean',
        'volunteer_interest' => 'boolean',
        'mentor_interest' => 'boolean',
        'information_accuracy_confirmation' => 'boolean',
        'terms_privacy_agreement' => 'boolean',
        'admin_verification_agreement' => 'boolean',
        'submitted_for_review_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
