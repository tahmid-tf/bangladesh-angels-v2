<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class TeamAboutSection extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const MEDIA_IMAGE = 'image';

    protected $fillable = [
        'kicker',
        'heading',
        'body',
        'image_alt',
        'image_badge',
    ];

    protected function casts(): array
    {
        return [
            'body' => 'array',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_IMAGE)
            ->singleFile()
            ->useDisk('public');
    }

    public function imageUrl(): string
    {
        $media = $this->getFirstMedia(self::MEDIA_IMAGE);

        return $media ? $media->getUrl() : asset('DSC00467.jpg');
    }

    /**
     * @return list<string>
     */
    public function paragraphs(): array
    {
        return collect($this->body)
            ->filter(fn (mixed $paragraph) => is_string($paragraph) && filled($paragraph))
            ->values()
            ->all();
    }

    public static function current(): ?self
    {
        return static::query()->first();
    }
}
