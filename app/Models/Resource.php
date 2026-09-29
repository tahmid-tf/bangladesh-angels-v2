<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Resource extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'type',
        'date',
        'start_time',
        'end_time',
        'location',
        'registration_fee',
        'benefits',
        'event_highlights',
        'target_audience',
        'registration_details',
        'speakers',
        'cta_link',
        'show_on_landing',
    ];

    protected $casts = [
        'benefits' => 'array',
        'event_highlights' => 'array',
        'target_audience' => 'array',
        'speakers' => 'array',
        'date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'registration_fee' => 'decimal:2',
        'show_on_landing' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Resource $resource): void {
            if (filled($resource->slug)) {
                return;
            }
            $resource->slug = static::makeUniqueSlug(static::slugBaseFromTitle($resource->title));
        });
    }

    public static function slugBaseFromTitle(string $title): string
    {
        $base = Str::slug($title);

        return $base !== '' ? $base : 'resource';
    }

    /**
     * Generate a unique slug for this model (append -2, -3, … on collision).
     * Purely numeric slugs get a suffix so they do not collide with legacy /resources/{id} redirects.
     */
    public static function makeUniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = $base;
        if (ctype_digit($slug)) {
            $slug = $slug.'-event';
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

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('banner')
            ->singleFile()
            ->useDisk('public');

        $this->addMediaCollection('speakers')
            ->useDisk('public');
    }

    /**
     * Banner for list cards: uploaded media, else legacy BWIN asset if present, else shared placeholder.
     */
    public function cardBannerUrl(): string
    {
        $media = $this->getFirstMedia('banner');
        if ($media !== null) {
            return $media->getUrl();
        }

        if (is_file(public_path('bwin.png'))) {
            return asset('bwin.png');
        }

        return asset('what-we-do-placeholder.svg');
    }

    /**
     * Human-readable time range (e.g. "3:00 PM – 5:00 PM" or "3:00 PM").
     */
    public function formattedTimeRange(): ?string
    {
        $start = $this->start_time ? Carbon::parse($this->start_time)->format('g:i A') : null;
        $end = $this->end_time ? Carbon::parse($this->end_time)->format('g:i A') : null;

        if ($start && $end) {
            return $start === $end ? $start : "{$start} – {$end}";
        }

        return $start ?? $end;
    }

    /**
     * Formatted date and time (e.g. "Sep 24, 2026 · 3:00 PM – 5:00 PM").
     */
    public function formattedDateTime(): ?string
    {
        $date = $this->date?->format('M j, Y');
        $time = $this->formattedTimeRange();

        if ($date && $time) {
            return "{$date} · {$time}";
        }

        return $date ?? $time;
    }
}
