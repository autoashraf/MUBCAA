<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'current_step',
        'total_steps',
        'admin_notes',
        'reviewed_by',
        'submitted_at',
        'approved_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function isFinalReviewReady(): bool
    {
        return in_array($this->status, ['pending_review', 'under_review', 'approved', 'rejected'], true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
