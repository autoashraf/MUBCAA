<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemorySubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'memory',
        'photos',
        'status',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
        'approved_at',
    ];

    protected $casts = [
        'photos' => 'array',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
