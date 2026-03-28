<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'membership_status',
        'approval_step',
        'affiliate_code',
        'referred_by_user_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected static function booted(): void
    {
        static::created(function (User $user): void {
            if (blank($user->affiliate_code)) {
                $user->forceFill([
                    'affiliate_code' => $user->defaultAffiliateCode(),
                ])->saveQuietly();
            }
        });
    }

    public function profile(): HasOne
    {
        return $this->hasOne(MemberProfile::class);
    }

    public function application(): HasOne
    {
        return $this->hasOne(MembershipApplication::class);
    }

    public function verificationTokens(): HasMany
    {
        return $this->hasMany(ContactVerificationToken::class);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by_user_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by_user_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function hasVerifiedMobile(): bool
    {
        return (bool) $this->profile?->mobile_verified;
    }

    public function hasVerifiedEmail(): bool
    {
        return ! is_null($this->email_verified_at);
    }

    public function hasCompletedContactVerification(): bool
    {
        return $this->hasVerifiedEmail() && $this->hasVerifiedMobile();
    }

    public function memberNumber(): string
    {
        return 'MUBCAA-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function certificateNumber(): string
    {
        return 'CERT-'.now()->format('Y').'-'.Str::padLeft((string) $this->id, 5, '0');
    }

    public function defaultAffiliateCode(): string
    {
        return 'AFF'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function affiliateLink(): string
    {
        return URL::signedRoute('affiliate.redirect', ['user' => $this->id]);
    }
}
