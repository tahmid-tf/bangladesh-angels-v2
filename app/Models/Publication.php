<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Publication extends Model
{
    use SoftDeletes;

    public const STATUS_ARCHIVED = 'archived';

    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'title',
        'excerpt',
        'category',
        'pdf_path',
        'pdf_original_name',
        'status',
        'published_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
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
}
