<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Blog extends Model
{
    use SoftDeletes;

    public const STATUS_ARCHIVED = 'archived';

    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'category',
        'author_name',
        'author_role',
        'author_organization',
        'status',
        'published_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function displayDate(): ?Carbon
    {
        return $this->published_at ?? $this->created_at;
    }

    public function readingMinutes(): int
    {
        $words = str_word_count(strip_tags($this->content));

        return max(1, (int) ceil($words / 220));
    }

    public static function makeUniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'blog-post';
        $slug = $base;
        $suffix = 2;

        while (static::query()
            ->withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
