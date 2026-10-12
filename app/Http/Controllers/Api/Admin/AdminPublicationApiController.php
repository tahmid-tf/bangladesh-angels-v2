<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Publication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminPublicationApiController extends BaseApiController
{
    /**
     * List all publications for admin.
     */
    public function index(Request $request): JsonResponse
    {
        $status = (string) $request->query('status', 'all');
        $search = trim((string) $request->query('search', ''));

        $publications = Publication::query()
            ->with('creator')
            ->when(in_array($status, [Publication::STATUS_ARCHIVED, Publication::STATUS_PUBLISHED], true), fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->where(function ($inner) use ($search) {
                $inner->where('title', 'like', '%'.$search.'%')
                    ->orWhere('excerpt', 'like', '%'.$search.'%')
                    ->orWhere('category', 'like', '%'.$search.'%');
            }))
            ->latest('updated_at')
            ->paginate((int) $request->query('per_page', 15));

        $counts = [
            'all' => Publication::count(),
            'published' => Publication::where('status', Publication::STATUS_PUBLISHED)->count(),
            'archived' => Publication::where('status', Publication::STATUS_ARCHIVED)->count(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Publications retrieved successfully.',
            'data' => $publications,
            'counts' => $counts,
        ]);
    }

    /**
     * Store a publication with PDF upload.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request, false);
        $file = $request->file('pdf');
        $path = $file ? $this->storePdf($file) : ($request->input('pdf_path') ?: 'publications/document.pdf');
        $originalName = $file ? $file->getClientOriginalName() : ($request->input('pdf_original_name') ?: (Str::slug($data['title']) . '.pdf'));
        unset($data['pdf']);

        try {
            $pub = Publication::create([
                ...$data,
                'pdf_path' => $path,
                'pdf_original_name' => $originalName,
                'created_by' => $request->user()->id,
                'published_at' => $this->publicationDate($data),
            ]);
        } catch (\Throwable $e) {
            if ($file && $path) {
                Storage::disk('local')->delete($path);
            }
            throw $e;
        }

        return $this->success($pub, 'Publication created successfully.', 201);
    }

    /**
     * Show single publication.
     */
    public function show(Publication $publication): JsonResponse
    {
        $publication->load('creator');

        return $this->success($publication);
    }

    /**
     * Update publication.
     */
    public function update(Request $request, Publication $publication): JsonResponse
    {
        $data = $this->validated($request, false);
        $newPath = null;
        $file = $request->file('pdf');

        if ($file) {
            $newPath = $this->storePdf($file);
            $data['pdf_path'] = $newPath;
            $data['pdf_original_name'] = $file->getClientOriginalName();
        }
        unset($data['pdf']);

        $oldPath = $publication->pdf_path;

        try {
            $publication->update([
                ...$data,
                'published_at' => $this->publicationDate($data, $publication),
            ]);

            if ($newPath && $oldPath && $oldPath !== $newPath) {
                Storage::disk('local')->delete($oldPath);
            }
        } catch (\Throwable $e) {
            if ($newPath) {
                Storage::disk('local')->delete($newPath);
            }
            throw $e;
        }

        return $this->success($publication->fresh(), 'Publication updated successfully.');
    }

    /**
     * Update status (archive / publish).
     */
    public function updateStatus(Request $request, Publication $publication): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([Publication::STATUS_ARCHIVED, Publication::STATUS_PUBLISHED])],
        ]);

        $publication->update([
            'status' => $data['status'],
            'published_at' => $data['status'] === Publication::STATUS_PUBLISHED
                ? ($publication->published_at ?? now())
                : $publication->published_at,
        ]);

        return $this->success($publication->fresh(), 'Publication status updated.');
    }

    /**
     * Delete publication.
     */
    public function destroy(Publication $publication): JsonResponse
    {
        $publication->delete();

        return $this->success(null, 'Publication deleted successfully.');
    }

    /**
     * Download publication PDF file for admin.
     */
    public function download(Publication $publication)
    {
        if (! $publication->pdf_path || ! Storage::disk('local')->exists($publication->pdf_path)) {
            return $this->error('Publication PDF file not found.', 404);
        }

        return Storage::disk('local')->download(
            $publication->pdf_path,
            $publication->pdf_original_name ?: 'publication.pdf',
            ['X-Content-Type-Options' => 'nosniff']
        );
    }

    private function validated(Request $request, bool $pdfRequired = false): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', 'string', 'max:100'],
            'status' => ['required', Rule::in([Publication::STATUS_ARCHIVED, Publication::STATUS_PUBLISHED])],
            'published_at' => ['nullable', 'date'],
            'pdf' => [$pdfRequired ? 'required' : 'nullable', 'file', 'max:20480'],
            'pdf_original_name' => ['nullable', 'string', 'max:255'],
            'pdf_path' => ['nullable', 'string', 'max:500'],
        ]);
    }

    private function storePdf(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension() ?: 'pdf';
        $filename = Str::random(40).'.'.$extension;

        return $file->storeAs('publications', $filename, 'local');
    }

    private function publicationDate(array $data, ?Publication $publication = null): mixed
    {
        if (! empty($data['published_at'])) {
            return $data['published_at'];
        }

        return $data['status'] === Publication::STATUS_PUBLISHED
            ? ($publication?->published_at ?? now())
            : $publication?->published_at;
    }
}
