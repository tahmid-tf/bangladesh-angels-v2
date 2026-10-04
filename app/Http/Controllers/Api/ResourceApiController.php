<?php

namespace App\Http\Controllers\Api;

use App\Models\Resource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResourceApiController extends BaseApiController
{
    /**
     * List of resources (events, webinars, workshops).
     */
    public function index(Request $request): JsonResponse
    {
        $type = $request->query('type');
        $search = trim((string) $request->query('search', ''));

        $resources = Resource::query()
            ->with('media')
            ->when($type && in_array($type, ['event', 'webinar']), fn ($q) => $q->where('type', $type))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('show_on_landing')
            ->orderByRaw('CASE WHEN date IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate((int) $request->query('per_page', 12))
            ->through(fn (Resource $res) => $this->formatResource($res));

        return $this->success($resources);
    }

    /**
     * Single resource by slug or ID.
     */
    public function show(string $slugOrId): JsonResponse
    {
        $resource = ctype_digit($slugOrId)
            ? Resource::where('id', (int) $slugOrId)->orWhere('slug', $slugOrId)->first()
            : Resource::where('slug', $slugOrId)->first();

        if (! $resource) {
            return $this->error('Resource not found.', 404);
        }

        $resource->loadMissing('media');

        return $this->success($this->formatResource($resource, true));
    }

    private function formatResource(Resource $res, bool $detailed = false): array
    {
        $speakerImages = $res->getMedia('speakers');

        $speakers = array_map(function ($speaker, $index) use ($speakerImages) {
            $media = $speakerImages->first(fn ($m) => $m->getCustomProperty('speaker_index') === $index)
                ?? $speakerImages->get($index);

            return [
                'name' => $speaker['name'] ?? '',
                'designation' => $speaker['designation'] ?? '',
                'image_url' => $media?->getUrl() ?? asset('default_pfp.jpg'),
            ];
        }, (array) ($res->speakers ?? []), array_keys((array) ($res->speakers ?? [])));

        $data = [
            'id' => $res->id,
            'title' => $res->title,
            'slug' => $res->slug,
            'type' => $res->type,
            'location' => $res->location,
            'date' => $res->date?->format('Y-m-d'),
            'start_time' => $res->start_time,
            'end_time' => $res->end_time,
            'registration_fee' => (float) $res->registration_fee,
            'description' => $res->description,
            'registration_details' => $res->registration_details,
            'cta_link' => $res->cta_link,
            'show_on_landing' => (bool) $res->show_on_landing,
            'banner_url' => $res->getFirstMedia('banner')?->getUrl(),
            'speakers' => $speakers,
            'benefits' => $res->benefits ?? [],
            'event_highlights' => $res->event_highlights ?? [],
            'target_audience' => $res->target_audience ?? [],
            'created_at' => $res->created_at?->toIso8601String(),
        ];

        return $data;
    }
}
