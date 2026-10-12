<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class TeamMember extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const SECTION_MANAGEMENT = 'management';

    public const SECTION_GOVERNING_BOARD = 'governing_board';

    public const MEDIA_PHOTO = 'photo';

    protected $fillable = [
        'section',
        'name',
        'title',
        'subtitle',
        'linkedin_url',
        'sort_order',
    ];

    protected $appends = [
        'photo_url',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function getPhotoUrlAttribute(): string
    {
        return $this->photoUrl() ?: asset('default_pfp.jpg');
    }

    public function scopeForSection(Builder $query, string $section): Builder
    {
        return $query->where('section', $section);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_PHOTO)
            ->singleFile()
            ->useDisk('public');
    }

    public function photoUrl(): string
    {
        $media = $this->getFirstMedia(self::MEDIA_PHOTO);

        return $media ? $media->getUrl() : '';
    }
}
