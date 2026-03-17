<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipWorkflowStep extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'membership_type_id',
        'step_number',
        'title',
        'description',
    ];

    public function membershipType(): BelongsTo
    {
        return $this->belongsTo(MembershipType::class);
    }
}
