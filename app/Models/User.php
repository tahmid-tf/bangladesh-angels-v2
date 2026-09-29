<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Authenticatable implements HasMedia, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, InteractsWithMedia, MustVerifyEmailTrait, Notifiable;

    /**
     * Self-service verification (email link) clears manual verifier attribution.
     */
    public function markEmailAsVerified(): bool
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
            'email_verified_by' => null,
        ])->save();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'gender',
        'primary_country',
        'primary_contact',
        'secondary_contact',
        'preference_sector',
        'public_profile',
        'designation',
        'organization',
        'registered_by',
        'phone',
        'email',
        'google_id',
        'joining_date',
        'renewed',
        'last_renewed_at',
        'account_owner',
        'total_invested',
        'company_name',
        'revenue_generated',
        'notes',
        'status',
        'is_overseas',
        'was_referred',
        'referred_by',
        'country',
        'role',
        'is_approved',
        'featured',
        'featured_testimonial',
        'featured_testimonial_public',
        'approved_by',
        'approved_at',
        'website_link',
        'secondary_countries',
        'password',
        'investment_expertise',
        'account_status',
        'payment_status',
        'linkedin',
        'expertise_level',
        'address',
        'used_by',
        'level',
        'email_verified_at',
        'email_verified_by',
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'featured' => 'boolean',
            'featured_testimonial_public' => 'boolean',
        ];
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function getLastRenewedAtAttribute($value)
    {
        if (! empty($value)) {
            try {
                // Trim and ensure consistent format
                return Carbon::createFromFormat('d/m/y', trim($value));
            } catch (\Exception $e) {
                // Log the issue or handle invalid formats
                \Log::error('Failed to parse last_renewed_at: '.$value);

                return null;
            }
        }

        return null;
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin()
    {
        return $this->role === 'admin' || $this->role === 'superadmin';
    }

    public function hasPublicFeaturedTestimonial(): bool
    {
        return $this->featured_testimonial_public && filled($this->featured_testimonial);
    }

    /**
     * Check if the user is an investor.
     */
    public function isInvestor()
    {
        return $this->role === 'investor';
    }

    /**
     * Check if the user is a seeker.
     */
    public function isFree()
    {
        return $this->account_status === 'free';
    }

    /**
     * Whether the account already has an active paid membership tier.
     */
    public function hasPaidMembership(): bool
    {
        return in_array(strtolower((string) $this->account_status), [
            'core',
            'advanced',
            'institutional',
        ], true);
    }

    /**
     * Whether the member may view paywalled deal listings (e.g. Active Deals on /startups).
     */
    public function canViewPaywalledDeals(): bool
    {
        if ($this->account_status === 'disabled' || $this->role === 'disabled') {
            return false;
        }

        if ($this->isAdmin()) {
            return true;
        }

        return $this->hasVerifiedEmail()
            && $this->is_approved
            && ! $this->isFree();
    }

    /**
     * Check user account status.
     */
    public function status()
    {
        if ($this->account_status == 'free') {
            return 'Free Tier';
        } elseif ($this->account_status == 'core') {
            return 'Core Tier';
        } elseif ($this->account_status == 'advanced') {
            return 'Advanced Tier';
        } elseif ($this->account_status == 'institutional') {
            return 'Institutional Tier';
        } elseif ($this->account_status == 'disabled') {
            return 'Disabled';
        }
    }

    /**
     * Check user payment status.
     */
    public function paymentStatus()
    {
        if ($this->payment_status == 'free') {
            return 'Non-Payable';
        } elseif ($this->payment_status == 'due') {
            return 'Due';
        } elseif ($this->payment_status == 'paid') {
            return 'Paid';
        }

    }

    public function getProfilePhotoUrl(): string
    {
        $media = $this->getFirstMedia('profile_photo');

        // Return the URL if media exists, otherwise return a default placeholder
        return $media ? $media->getUrl() : asset('default_pfp.jpg');
    }

    public function joinedAt(): string
    {
        // Get the year of creation
        $year = $this->created_at->format('Y');

        // Get the month of creation
        $month = $this->created_at->format('n');

        // Determine the quarter
        $quarter = ceil($month / 3); // 1-3 = Q1, 4-6 = Q2, 7-9 = Q3, 10-12 = Q4

        // Return the formatted string
        return "{$year} Q{$quarter}";
    }

    public function renewedAt(): string
    {
        // Ensure the "updated_at" field exists
        if (! $this->updated_at) {
            return 'Not renewed';
        }

        // Get the year of the last update
        $year = $this->updated_at->format('Y');

        // Get the month of the last update
        $month = $this->updated_at->format('n');

        // Determine the quarter
        $quarter = ceil($month / 3); // 1-3 = Q1, 4-6 = Q2, 7-9 = Q3, 10-12 = Q4

        // Return the formatted string
        return "{$year} Q{$quarter}";
    }

    public function commits()
    {
        return $this->hasMany(Commit::class);
    }

    public function investments()
    {
        return $this->hasMany(Investment::class, 'user_id');
    }

    public function completedInvestments()
    {
        return $this->hasMany(InvestorInvestment::class, 'investor_id');
    }

    public function banWealthOrders()
    {
        return $this->hasMany(BanWealthOrder::class, 'investor_id');
    }

    /**
     * Get the admin user who approved this user.
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Admin who manually verified this member's email (null if verified via email link or legacy).
     */
    public function emailVerifiedByAdmin()
    {
        return $this->belongsTo(User::class, 'email_verified_by');
    }
}
