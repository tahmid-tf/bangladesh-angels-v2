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
        $submissions = FounderPitchSubmission::with(['user', 'media'])
            ->latest()
            ->paginate((int) $request->query('per_page', 20));

        return $this->success($submissions);
    }

    public function founderPitch(FounderPitchSubmission $founderPitchSubmission): JsonResponse
    {
        $founderPitchSubmission->load(['user', 'media']);

        return $this->success($founderPitchSubmission);
    }

    public function downloadFounderPitchDeck(FounderPitchSubmission $founderPitchSubmission)
    {
        $media = $founderPitchSubmission->getFirstMedia(FounderPitchSubmission::MEDIA_PITCH_DECK);

        if (! $media) {
            return $this->error('Pitch deck file not found.', 404);
        }

        return response()->download($media->getPath(), $media->file_name, [
            'Content-Type' => 'application/pdf',
        ]);
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
