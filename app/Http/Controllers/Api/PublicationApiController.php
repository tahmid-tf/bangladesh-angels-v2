<?php

namespace App\Http\Controllers\Api;

use App\Models\Publication;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PublicationApiController extends BaseApiController
{
    /**
     * Paginated list of published publications with search and categories.
     */
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $category = trim((string) $request->query('category', ''));

        $publications = Publication::query()
            ->published()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('excerpt', 'like', '%'.$search.'%')
                        ->orWhere('category', 'like', '%'.$search.'%');
                });
            })
            ->when($category !== '', fn (Builder $query) => $query->where('category', $category))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate((int) $request->query('per_page', 10))
            ->through(fn (Publication $pub) => [
                'id' => $pub->id,
                'title' => $pub->title,
                'excerpt' => $pub->excerpt,
                'category' => $pub->category,
                'published_at' => $pub->published_at?->toIso8601String(),
                'download_url' => route('api.publications.download', $pub->id),
            ]);

        $categories = Publication::query()
            ->published()
            ->selectRaw('category, COUNT(*) as publications_count')
            ->groupBy('category')
            ->orderBy('category')
            ->get();

        return $this->success([
            'publications' => $publications,
            'categories' => $categories,
        ]);
    }

    /**
     * Single publication details.
     */
    public function show(Publication $publication): JsonResponse
    {
        if (! $publication->isPublished()) {
            return $this->error('Publication not found.', 404);
        }

        return $this->success([
            'id' => $publication->id,
            'title' => $publication->title,
            'excerpt' => $publication->excerpt,
            'category' => $publication->category,
            'published_at' => $publication->published_at?->toIso8601String(),
            'download_url' => route('api.publications.download', $publication->id),
        ]);
    }

    /**
     * Download publication PDF file.
     */
    public function download(Publication $publication)
    {
        if (! $publication->isPublished()) {
            return $this->error('Publication not found.', 404);
        }

        if (! $publication->pdf_path || ! Storage::disk('local')->exists($publication->pdf_path)) {
            return $this->error('Publication PDF file not found.', 404);
        }

        return Storage::disk('local')->download(
            $publication->pdf_path,
            $publication->pdf_original_name ?: 'publication.pdf',
            ['X-Content-Type-Options' => 'nosniff']
        );
    }
}
