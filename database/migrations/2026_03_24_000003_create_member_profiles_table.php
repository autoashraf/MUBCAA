<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('mobile_number')->nullable();
            $table->boolean('mobile_verified')->default(false);
            $table->string('passing_year_batch')->nullable();
            $table->string('student_id_or_roll')->nullable();
            $table->string('current_city')->nullable();

            $table->string('ssc_passing_year')->nullable();
            $table->string('hsc_passing_year')->nullable();
            $table->string('group')->nullable();
            $table->string('shift')->nullable();
            $table->string('campus_branch')->nullable();

            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('blood_group')->nullable();
            $table->string('marital_status')->nullable();

            $table->string('primary_mobile')->nullable();
            $table->string('secondary_mobile')->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->string('email_address')->nullable();
            $table->text('present_address')->nullable();
            $table->text('permanent_address')->nullable();
            $table->string('country')->default('Bangladesh');
            $table->string('city_district')->nullable();
            $table->string('postal_code')->nullable();

            $table->string('occupation')->nullable();
            $table->string('organization_name')->nullable();
            $table->string('designation')->nullable();
            $table->string('industry')->nullable();
            $table->text('office_address')->nullable();
            $table->string('work_email')->nullable();
            $table->string('business_name')->nullable();
            $table->text('professional_skills')->nullable();

            $table->string('profile_photo')->nullable();
            $table->string('cover_photo')->nullable();
            $table->string('business_card_upload')->nullable();
            $table->text('short_bio')->nullable();
            $table->string('facebook_profile_link')->nullable();
            $table->string('linkedin_profile_link')->nullable();
            $table->string('website_portfolio_link')->nullable();

            $table->boolean('interested_in_alumni_activities')->nullable();
            $table->json('areas_of_interest')->nullable();
            $table->boolean('volunteer_interest')->nullable();
            $table->string('donor_sponsor_interest')->nullable();
            $table->boolean('mentor_interest')->nullable();
            $table->text('suggestions')->nullable();

            $table->string('certificate_testimonial_upload')->nullable();
            $table->string('supporting_document_upload')->nullable();

            $table->string('profile_visibility')->nullable();
            $table->string('contact_visibility')->nullable();

            $table->boolean('information_accuracy_confirmation')->default(false);
            $table->boolean('terms_privacy_agreement')->default(false);
            $table->boolean('admin_verification_agreement')->default(false);

            $table->unsignedTinyInteger('completion_step')->default(1);
            $table->timestamp('submitted_for_review_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_profiles');
    }
};
