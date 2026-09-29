<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LandingProgramCard extends Model
{
    public const MAX_CARDS = 5;

    public const THEMES = [
        'blush' => 'Blush',
        'dark' => 'Dark green',
        'mint' => 'Mint',
        'sand' => 'Sand',
        'white' => 'White',
    ];

    protected $fillable = [
        'eyebrow',
        'title',
        'description',
        'primary_label',
        'primary_link',
        'secondary_label',
        'secondary_link',
        'dropdown_items',
        'theme',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'dropdown_items' => 'array',
        ];
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function themeClass(): string
    {
        return array_key_exists($this->theme, self::THEMES) ? $this->theme : 'white';
    }

    public function hasDropdown(): bool
    {
        return filled($this->dropdown_items['label'] ?? null);
    }
}
