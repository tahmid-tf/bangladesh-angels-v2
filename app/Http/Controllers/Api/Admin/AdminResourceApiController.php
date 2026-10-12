<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Resource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminResourceApiController extends BaseApiController
{
    /**
     * List all events/resources with search, type filtering, and counts.
     */
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $type = $request->query('type');
        $landing = $request->query('landing');

        $query = Resource::with('media')
            ->when($type && in_array($type, ['event', 'webinar'], true), fn ($q) => $q->where('type', $type))
            ->when($landing !== null && $landing !== '', fn ($q) => $q->where('show_on_landing', filter_var($landing, FILTER_VALIDATE_BOOLEAN)))
            ->when($search !== '', fn ($q) => $q->where(function ($inner) use ($search) {
                $inner->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            }))
            ->latest('id');

        $resources = $query->paginate((int) $request->query('per_page', 20));

        $counts = [
            'all' => Resource::count(),
            'events' => Resource::where('type', 'event')->count(),
            'webinars' => Resource::where('type', 'webinar')->count(),
            'landing' => Resource::where('show_on_landing', true)->count(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Events retrieved successfully.',
            'data' => $resources,
            'counts' => $counts,
        ]);
    }

    /**
     * Store new event/resource.
     */
    public function store(Request $request): JsonResponse
    {
        foreach (['benefits', 'event_highlights', 'target_audience', 'speakers'] as $field) {
            if (is_string($request->input($field))) {
                $decoded = json_decode($request->input($field), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $request->merge([$field => $decoded]);
                }
            }
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:event,webinar',
            'location' => 'nullable|string|max:255',
            'date' => 'nullable|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'registration_fee' => 'nullable|numeric',
            'description' => 'required|string',
            'registration_details' => 'nullable|string',
            'cta_link' => ['nullable', 'string', 'max:2048'],
            'show_on_landing' => ['sometimes', 'boolean'],
            'benefits' => 'nullable|array',
            'event_highlights' => 'nullable|array',
            'target_audience' => 'nullable|array',
            'speakers' => 'nullable|array',
            'speakers.*.name' => 'nullable|string|max:255',
            'speakers.*.designation' => 'nullable|string|max:255',
            'banner_image' => 'nullable|image|max:5120',
        ]);

        $speakers = array_values(array_filter((array) ($request->input('speakers') ?? []), function ($s) {
            return ! empty($s['name']) || ! empty($s['designation']);
        }));

        $resource = Resource::create([
            'title' => $validated['title'],
            'type' => $validated['type'],
            'location' => $validated['location'] ?? null,
            'date' => $validated['date'] ?? null,
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'registration_fee' => $validated['registration_fee'] ?? null,
            'description' => $validated['description'],
            'registration_details' => $validated['registration_details'] ?? null,
            'cta_link' => $validated['cta_link'] ?? null,
            'show_on_landing' => $request->boolean('show_on_landing'),
            'benefits' => $validated['benefits'] ?? [],
            'event_highlights' => $validated['event_highlights'] ?? [],
            'target_audience' => $validated['target_audience'] ?? [],
            'speakers' => $speakers,
        ]);

        if ($request->hasFile('banner_image')) {
            $resource->addMediaFromRequest('banner_image')->toMediaCollection('banner');
        }

        return $this->success($resource->fresh()->load('media'), 'Event created successfully.', 201);
    }

    /**
     * Show single event/resource.
     */
    public function show(Resource $resource): JsonResponse
    {
        $resource->loadMissing('media');

        return $this->success($resource);
    }

    /**
     * Update event/resource.
     */
    public function update(Request $request, Resource $resource): JsonResponse
    {
        foreach (['benefits', 'event_highlights', 'target_audience', 'speakers'] as $field) {
            if (is_string($request->input($field))) {
                $decoded = json_decode($request->input($field), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $request->merge([$field => $decoded]);
                }
            }
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:event,webinar',
            'location' => 'nullable|string|max:255',
            'date' => 'nullable|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'registration_fee' => 'nullable|numeric',
            'description' => 'required|string',
            'registration_details' => 'nullable|string',
            'cta_link' => ['nullable', 'string', 'max:2048'],
            'show_on_landing' => ['sometimes', 'boolean'],
            'benefits' => 'nullable|array',
            'event_highlights' => 'nullable|array',
            'target_audience' => 'nullable|array',
            'speakers' => 'nullable|array',
            'speakers.*.name' => 'nullable|string|max:255',
            'speakers.*.designation' => 'nullable|string|max:255',
            'banner_image' => 'nullable|image|max:5120',
        ]);

        $speakers = array_values(array_filter((array) ($request->input('speakers') ?? []), function ($s) {
            return ! empty($s['name']) || ! empty($s['designation']);
        }));

        $resource->update([
            'title' => $validated['title'],
            'type' => $validated['type'],
            'location' => $validated['location'] ?? null,
            'date' => $validated['date'] ?? null,
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'registration_fee' => $validated['registration_fee'] ?? null,
            'description' => $validated['description'],
            'registration_details' => $validated['registration_details'] ?? null,
            'cta_link' => $validated['cta_link'] ?? null,
            'show_on_landing' => $request->boolean('show_on_landing'),
            'benefits' => $validated['benefits'] ?? [],
            'event_highlights' => $validated['event_highlights'] ?? [],
            'target_audience' => $validated['target_audience'] ?? [],
            'speakers' => $speakers,
        ]);

        if ($request->boolean('remove_banner')) {
            $resource->clearMediaCollection('banner');
        } elseif ($request->hasFile('banner_image')) {
            $resource->clearMediaCollection('banner');
            $resource->addMediaFromRequest('banner_image')->toMediaCollection('banner');
        }

        return $this->success($resource->fresh()->load('media'), 'Event updated successfully.');
    }

    /**
     * Delete event/resource.
     */
    public function destroy(Resource $resource): JsonResponse
    {
        $resource->delete();

        return $this->success(null, 'Event deleted successfully.');
    }
}
