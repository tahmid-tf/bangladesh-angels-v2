<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Resource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminResourceApiController extends BaseApiController
{
    /**
     * List all events/resources.
     */
    public function index(Request $request): JsonResponse
    {
        $resources = Resource::with('media')->latest('id')->paginate((int) $request->query('per_page', 20));

        return $this->success($resources);
    }

    /**
     * Store new event/resource.
     */
    public function store(Request $request): JsonResponse
    {
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

        if ($request->hasFile('banner_image')) {
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
