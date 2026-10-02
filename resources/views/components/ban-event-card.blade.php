@props([
    'resource',
    'detailHref' => null,
])

@php
    /** @var \App\Models\Resource $resource */
    $detailHref = $detailHref ?? route('resource.public.view', $resource);
    $banner = $resource->cardBannerUrl();
    $cta = $resource->cta_link ? trim($resource->cta_link) : '';
    $ctaIsExternal = $cta !== '' && preg_match('#^https?://#i', $cta);
    $snippet = \Illuminate\Support\Str::limit(trim(strip_tags((string) $resource->description)), 320);
    $timeRange = $resource->formattedTimeRange();
@endphp

<article class="ban-event-card">
    <a href="{{ $detailHref }}" class="ban-event-card__image">
        <img src="{{ $banner }}"
             alt=""
             width="400"
             height="320"
             loading="lazy"
             decoding="async">
        <span class="sr-only">{{ $resource->title }} — view details</span>
    </a>
    <div class="ban-event-card__body">
        <div>
            <p class="ban-event-card__type">{{ $resource->type === 'webinar' ? 'Webinar' : 'Event' }}</p>
            <h3 class="ban-event-card__title">
                <a href="{{ $detailHref }}">
                    {{ $resource->title }}
                </a>
            </h3>
            @if ($snippet !== '')
                <p class="ban-event-card__snippet">{{ $snippet }}</p>
            @endif
        </div>
        <div class="ban-event-card__footer">
            <div class="ban-event-card__links">
                @if ($cta !== '')
                    <a href="{{ $cta }}"
                       @if ($ctaIsExternal) target="_blank" rel="noopener noreferrer" @endif
                       class="ban-event-card__register">
                        Register / RSVP
                    </a>
                @endif
                <a href="{{ $detailHref }}"
                   class="ban-event-card__details">
                    Event details
                </a>
            </div>
            @if ($resource->date || $timeRange || $resource->location)
                <ul class="ban-event-card__meta">
                    @if ($resource->date && $timeRange)
                        <li>
                            <span class="whitespace-nowrap">{{ $resource->date->format('M j, Y') }}</span>
                            <span aria-hidden="true"> · </span>
                            <span class="whitespace-nowrap">{{ $timeRange }}</span>
                        </li>
                    @elseif ($resource->date)
                        <li><span class="whitespace-nowrap">{{ $resource->date->format('M j, Y') }}</span></li>
                    @elseif ($timeRange)
                        <li><span class="whitespace-nowrap">{{ $timeRange }}</span></li>
                    @endif
                    @if ($resource->location)
                        <li class="line-clamp-2">{{ $resource->location }}</li>
                    @endif
                </ul>
            @endif
        </div>
    </div>
</article>
