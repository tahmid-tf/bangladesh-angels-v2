<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Deal extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected static function booted(): void
    {
        static::saved(function (Deal $deal) {
            if (! $deal->isListedInPortfolio()) {
                $deal->homepagePortfolio()?->delete();
            }
        });

        static::deleted(function (Deal $deal) {
            $deal->homepagePortfolio()?->delete();
        });
    }

    /**
     * Routes beginning with /deals/{slug} cannot collide with these path segments.
     */
    public static function reservedSlugSegments(): array
    {
        return ['invest', 'commit', 'review'];
    }

    public static function slugBaseFromTitle(string $title): string
    {
        $base = Str::slug($title);

        return $base !== '' ? $base : 'deal';
    }

    /**
     * Unique slug for the deals table (append -2, -3 on collision; avoid reserved segments).
     */
    public static function makeUniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = $base;

        if ($slug !== '' && ctype_digit((string) $slug)) {
            $slug = $slug.'-startup';
        }

        if ($slug !== '' && in_array(strtolower($slug), self::reservedSlugSegments(), true)) {
            $slug = $slug.'-deal';
        }

        if ($slug === '') {
            $slug = 'deal';
        }

        $original = $slug;
        $n = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $original.'-'.$n;
            $n++;
        }

        return $slug;
    }

    protected $fillable = [
        'title',
        'description',
        'sector',
        'type',
        'is_portfolio',
        'key_metrics',
        'investment_stage',
        'amount_seeking',
        'company_logo',
        'company_cover',
        'pitch_deck_url',
        'substack_link',
        'invest_link',
        'commit_link',
        'groupchat_invite_link',
        'created_by',
        'status',
        'slug',
        'monthly_revenue',
        'growth_rate',
        'revenue_model',
        'future_plans',
        'partnerships',
        'video_url',
        'image_gallery',
        'likes',
        'views',
        'investor_commits',
    ];

    protected function casts(): array
    {
        return [
            'is_portfolio' => 'boolean',
        ];
    }

    /**
     * Include legacy portfolio-only records and deals listed in both sections.
     */
    public function scopeInPortfolio($query)
    {
        return $query->where(function ($query) {
            $query->where('type', 'portfolio')
                ->orWhere('is_portfolio', true);
        });
    }

    public function isListedInPortfolio(): bool
    {
        return $this->type === 'portfolio' || $this->is_portfolio;
    }

    public function homepagePortfolio(): HasOne
    {
        return $this->hasOne(HomepagePortfolio::class);
    }

    public function isHomepageSelected(): bool
    {
        return $this->homepagePortfolio !== null;
    }

    /**
     * Retrieve the portfolio companies to display on the homepage.
     * Returns custom admin-selected portfolios (up to 8) if configured,
     * otherwise falls back to the latest 8 published portfolio companies.
     */
    public static function getHomepageDeals(int $limit = HomepagePortfolio::MAX_SELECTIONS)
    {
        $selected = HomepagePortfolio::query()
            ->with(['deal.media'])
            ->ordered()
            ->limit($limit)
            ->get()
            ->map(fn ($item) => $item->deal)
            ->filter(fn ($deal) => $deal !== null && $deal->isListedInPortfolio())
            ->values();

        if ($selected->isNotEmpty()) {
            return $selected;
        }

        return static::query()
            ->with('media')
            ->inPortfolio()
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getLogoUrl(): string
    {
        $media = $this->getFirstMedia('company_logo');

        return $media ? $media->getUrl() : asset('default_pfp.jpg');
    }

    public function amountSeeking(): string
    {
        return number_format((float) $this->amount_seeking, 0, '.', ',');
    }

    public function getCoverUrl(): string
    {
        $media = $this->getFirstMedia('company_cover');

        return $media ? $media->getUrl() : asset('default_pfp.jpg');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function hasKeyMetric(): bool
    {
        $keyMetrics = json_decode($this->key_metrics, true);

        if (! is_array($keyMetrics) || empty($keyMetrics)) {
            return false;
        }

        foreach ($keyMetrics as $metric) {
            if (isset($metric['name'], $metric['value'])) {
                if ($metric['name'] !== null || $metric['value'] !== null) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getKeyMetrics()
    {
        $keyMetrics = json_decode($this->key_metrics, true);

        return $keyMetrics ?? [];
    }

    public function getOtherDeals($limit = 5)
    {
        $query = self::query()
            ->with('media')
            ->where('id', '!=', $this->id);

        if ($this->type === 'portfolio') {
            $query->inPortfolio();
        } else {
            $query->where('type', '!=', 'portfolio')
                ->where('status', 'active');
        }

        return $query
            ->orderByDesc('created_at')
            ->take($limit)
            ->get();
    }

    public function getExcerpt(): string
    {
        $excerpt = Str::limit($this->description, 75, '...');

        if (strlen($this->description) > 75) {
            return $excerpt.' [Read more]';
        }

        return $excerpt;
    }

    public function investments()
    {
        return $this->hasMany(Investment::class, 'deal_id');
    }

    public function completedInvestments()
    {
        return $this->hasMany(InvestorInvestment::class, 'deal_id');
    }

    public function commits()
    {
        return $this->hasMany(Commit::class);
    }

    public function commitSubmissions()
    {
        return $this->hasMany(CommitSubmission::class);
    }

    public function investorsCount()
    {
        return $this->investments()->whereIn('type', ['interested', 'invest'])->count();
    }

    public function commitCount()
    {
        return $this->commits()->count();
    }

    public function reviewCount()
    {
        return $this->investments()->where('type', 'review')->count();
    }

    public function updateLogo($file)
    {
        $this->clearMediaCollection('company_logo');

        $this->addMedia($file)
            ->toMediaCollection('company_logo');
    }

    public function updateCover($file)
    {
        $this->clearMediaCollection('company_cover');

        $this->addMedia($file)
            ->toMediaCollection('company_cover');
    }
}
