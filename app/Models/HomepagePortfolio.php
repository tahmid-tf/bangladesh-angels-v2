<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomepagePortfolio extends Model
{
    use HasFactory;

    public const MAX_SELECTIONS = 8;

    protected $fillable = [
        'deal_id',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'deal_id' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
