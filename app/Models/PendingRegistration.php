<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendingRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'mobile_number',
        'email',
        'passing_year_batch',
        'student_id_or_roll',
        'current_city',
        'email_code',
        'mobile_code',
        'email_code_expires_at',
        'mobile_code_expires_at',
        'email_verified_at',
        'mobile_verified_at',
        'completed_at',
    ];

    protected $casts = [
        'email_code_expires_at' => 'datetime',
        'mobile_code_expires_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'mobile_verified_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function hasVerifiedEmail(): bool
    {
        return ! is_null($this->email_verified_at);
    }

    public function hasVerifiedMobile(): bool
    {
        return ! is_null($this->mobile_verified_at);
    }

    public function hasCompletedContactVerification(): bool
    {
        return $this->hasVerifiedEmail() && $this->hasVerifiedMobile();
    }
}
