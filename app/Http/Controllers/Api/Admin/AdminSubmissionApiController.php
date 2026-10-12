<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\AngelAcademyNetworkApplication;
use App\Models\FounderPitchSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSubmissionApiController extends BaseApiController
{
    // ==========================================
    // FOUNDER PITCHES
    // ==========================================

    public function founderPitches(Request $request): JsonResponse
    {
        $query = FounderPitchSubmission::with(['user', 'media'])->latest();

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('contact_email', 'like', "%{$search}%")
                    ->orWhere('one_line', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $filter = (string) $request->query('filter', 'all');
        if ($filter === 'with_deck') {
            $query->whereHas('media', function ($mq) {
                $mq->where('collection_name', FounderPitchSubmission::MEDIA_PITCH_DECK);
            });
        } elseif ($filter === 'no_deck') {
            $query->whereDoesntHave('media', function ($mq) {
                $mq->where('collection_name', FounderPitchSubmission::MEDIA_PITCH_DECK);
            });
        }

        $totalCount = FounderPitchSubmission::count();
        $withDeckCount = FounderPitchSubmission::whereHas('media', function ($mq) {
            $mq->where('collection_name', FounderPitchSubmission::MEDIA_PITCH_DECK);
        })->count();
        $withoutDeckCount = $totalCount - $withDeckCount;

        $perPage = max(1, min(100, (int) $request->query('per_page', 20)));
        $paginator = $query->paginate($perPage);

        $paginator->getCollection()->transform(function (FounderPitchSubmission $submission) {
            return $this->formatFounderPitch($submission);
        });

        return response()->json([
            'status' => 'success',
            'data' => $paginator->items(),
            'counts' => [
                'total' => $totalCount,
                'with_deck' => $withDeckCount,
                'without_deck' => $withoutDeckCount,
            ],
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function founderPitch(FounderPitchSubmission $founderPitchSubmission): JsonResponse
    {
        $founderPitchSubmission->load(['user', 'media']);

        return $this->success($this->formatFounderPitch($founderPitchSubmission));
    }

    public function downloadFounderPitchDeck(Request $request, FounderPitchSubmission $founderPitchSubmission)
    {
        $media = $founderPitchSubmission->getFirstMedia(FounderPitchSubmission::MEDIA_PITCH_DECK);

        if (! $media || ! file_exists($media->getPath())) {
            return $this->error('Pitch deck file not found.', 404);
        }

        if ($request->boolean('inline')) {
            return response()->file($media->getPath(), [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $media->file_name . '"',
            ]);
        }

        return response()->download($media->getPath(), $media->file_name, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function founderPitchDeckBase64(FounderPitchSubmission $founderPitchSubmission): JsonResponse
    {
        $media = $founderPitchSubmission->getFirstMedia(FounderPitchSubmission::MEDIA_PITCH_DECK);

        if (! $media || ! file_exists($media->getPath())) {
            return $this->error('Pitch deck file not found.', 404);
        }

        $contents = file_get_contents($media->getPath());
        if ($contents === false) {
            return $this->error('Could not read pitch deck file.', 500);
        }

        return $this->success([
            'file_name' => $media->file_name,
            'size' => $media->size,
            'size_formatted' => $media->size ? number_format($media->size / 1024, 1) . ' KB' : null,
            'mime_type' => $media->mime_type ?: 'application/pdf',
            'base64' => base64_encode($contents),
        ]);
    }

    public function destroyFounderPitch(FounderPitchSubmission $founderPitchSubmission): JsonResponse
    {
        $founderPitchSubmission->clearMediaCollection(FounderPitchSubmission::MEDIA_PITCH_DECK);
        $founderPitchSubmission->delete();

        return $this->success(null, 'Founder pitch submission deleted successfully.');
    }

    public function formatFounderPitch(FounderPitchSubmission $submission): array
    {
        $deck = $submission->getFirstMedia(FounderPitchSubmission::MEDIA_PITCH_DECK);

        return [
            'id' => $submission->id,
            'contact_email' => $submission->contact_email,
            'one_line' => $submission->one_line,
            'user_id' => $submission->user_id,
            'user' => $submission->user ? [
                'id' => $submission->user->id,
                'name' => $submission->user->name,
                'email' => $submission->user->email,
                'type' => $submission->user->type ?? null,
            ] : null,
            'has_deck' => (bool) $deck,
            'deck' => $deck ? [
                'id' => $deck->id,
                'file_name' => $deck->file_name,
                'size' => $deck->size,
                'size_formatted' => $deck->size ? number_format($deck->size / 1024, 1) . ' KB' : null,
                'mime_type' => $deck->mime_type,
            ] : null,
            'deck_url' => $deck ? route('api.admin.founder-pitches.deck', $submission) : null,
            'created_at' => $submission->created_at?->toISOString(),
            'created_at_formatted' => $submission->created_at?->format('M j, Y g:i A'),
            'updated_at' => $submission->updated_at?->toISOString(),
        ];
    }

    // ==========================================
    // ANGEL ACADEMY APPLICATIONS
    // ==========================================

    public function angelAcademyApplications(Request $request): JsonResponse
    {
        $applications = AngelAcademyNetworkApplication::with('user')
            ->latest()
            ->paginate((int) $request->query('per_page', 20));

        return $this->success($applications);
    }

    public function angelAcademyApplication(AngelAcademyNetworkApplication $angelAcademyNetworkApplication): JsonResponse
    {
        $angelAcademyNetworkApplication->load('user');

        return $this->success($angelAcademyNetworkApplication);
    }
}
