<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class StartupService extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const MEDIA_LOGO = 'logo';

    public const MEDIA_BROCHURE = 'brochure';

    protected $fillable = [
        'title',
        'intro',
        'bullets',
        'footer_note',
        'link',
        'cta_label',
        'brochure_url',
        'show_brochure_link',
        'sort_order',
    ];

    protected $appends = [
        'logo_url',
        'brochure_pdf_url',
        'brochure_public_href',
        'bullet_list',
    ];

    protected function casts(): array
    {
        return [
            'bullets' => 'array',
            'show_brochure_link' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getLogoUrlAttribute(): ?string
    {
        $url = $this->logoUrl();

        return $url !== '' ? $url : null;
    }

    public function getBrochurePdfUrlAttribute(): ?string
    {
        return $this->getFirstMedia(self::MEDIA_BROCHURE)?->getUrl();
    }

    public function getBrochurePublicHrefAttribute(): string
    {
        return $this->brochurePublicHref();
    }

    public function getBulletListAttribute(): array
    {
        return $this->bulletList();
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_LOGO)
            ->singleFile()
            ->useDisk('public');

        $this->addMediaCollection(self::MEDIA_BROCHURE)
            ->singleFile()
            ->useDisk('public')
            ->acceptsMimeTypes(['application/pdf']);
    }

    public function logoUrl(): string
    {
        $media = $this->getFirstMedia(self::MEDIA_LOGO);

        return $media ? $media->getUrl() : '';
    }

    /**
     * Resolved brochure href for the public “brochure” link: uploaded PDF first, then custom URL, then site default.
     */
    public function brochurePublicHref(): string
    {
        $media = $this->getFirstMedia(self::MEDIA_BROCHURE);
        if ($media) {
            return $media->getUrl();
        }

        $url = $this->brochure_url;
        if (is_string($url) && $url !== '') {
            return $url;
        }

        return asset('MoU.pdf');
    }

    /**
     * @return list<string>
     */
    public function bulletList(): array
    {
        $b = $this->bullets;

        return is_array($b) ? array_values(array_filter($b, fn ($item) => is_string($item) && $item !== '')) : [];
    }
}
