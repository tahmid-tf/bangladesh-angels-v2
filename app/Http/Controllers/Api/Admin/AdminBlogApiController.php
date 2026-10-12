<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Blog;
use App\Support\BlogContentSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminBlogApiController extends BaseApiController
{
    /**
     * List all blogs (published and archived).
     */
    public function index(Request $request): JsonResponse
    {
        $status = (string) $request->query('status', 'all');
        $search = trim((string) $request->query('search', ''));

        $blogs = Blog::query()
            ->with('creator')
            ->when(in_array($status, [Blog::STATUS_ARCHIVED, Blog::STATUS_PUBLISHED], true), fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->where('title', 'like', '%'.$search.'%'))
            ->latest('updated_at')
            ->paginate((int) $request->query('per_page', 15));

        $counts = [
            'all' => Blog::count(),
            'published' => Blog::where('status', Blog::STATUS_PUBLISHED)->count(),
            'archived' => Blog::where('status', Blog::STATUS_ARCHIVED)->count(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => $blogs,
            'counts' => $counts,
        ]);
    }

    /**
     * Store new blog post.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data['slug'] = Blog::makeUniqueSlug(! empty($data['slug']) ? $data['slug'] : $data['title']);
        $data['content'] = BlogContentSanitizer::clean($data['content']);
        $data['category'] = ! empty($data['category']) ? $data['category'] : 'Educational';
        $data['status'] = ! empty($data['status']) ? $data['status'] : Blog::STATUS_PUBLISHED;
        $data['author_name'] = ! empty($data['author_name']) ? $data['author_name'] : ($request->user()->name ?? 'Bangladesh Angels Network');
        $data['author_organization'] = ! empty($data['author_organization']) ? $data['author_organization'] : 'Bangladesh Angels Network';
        $data['created_by'] = $request->user()->id;
        $data['published_at'] = $this->publicationDate($data);

        $blog = Blog::create($data);

        return $this->success($blog->fresh(['creator']), 'Blog post created successfully.', 201);
    }

    /**
     * Show single blog post.
     */
    public function show(Blog $blog): JsonResponse
    {
        $blog->load('creator');

        return $this->success($blog);
    }

    /**
     * Update blog post.
     */
    public function update(Request $request, Blog $blog): JsonResponse
    {
        $data = $this->validated($request, $blog);
        if (! empty($data['title'])) {
            $data['slug'] = Blog::makeUniqueSlug(! empty($data['slug']) ? $data['slug'] : $data['title'], $blog->id);
        }
        if (isset($data['content'])) {
            $data['content'] = BlogContentSanitizer::clean($data['content']);
        }
        if (isset($data['status'])) {
            $data['published_at'] = $this->publicationDate($data, $blog);
        }

        $blog->update($data);

        return $this->success($blog->fresh(['creator']), 'Blog post updated successfully.');
    }

    /**
     * Update status (archive / publish).
     */
    public function updateStatus(Request $request, Blog $blog): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([Blog::STATUS_ARCHIVED, Blog::STATUS_PUBLISHED])],
        ]);

        $blog->update([
            'status' => $data['status'],
            'published_at' => $data['status'] === Blog::STATUS_PUBLISHED
                ? ($blog->published_at ?? now())
                : $blog->published_at,
        ]);

        return $this->success($blog->fresh(['creator']), 'Blog status updated.');
    }

    /**
     * Delete blog post.
     */
    public function destroy(Blog $blog): JsonResponse
    {
        $blog->delete();

        return $this->success(null, 'Blog post deleted successfully.');
    }

    private function validated(Request $request, ?Blog $blog = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('blogs', 'slug')->ignore($blog?->id)],
            'excerpt' => ['nullable', 'string', 'max:2000'],
            'content' => ['required', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'author_name' => ['nullable', 'string', 'max:255'],
            'author_role' => ['nullable', 'string', 'max:255'],
            'author_organization' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in([Blog::STATUS_ARCHIVED, Blog::STATUS_PUBLISHED])],
            'published_at' => ['nullable', 'date'],
        ]);
    }

    private function publicationDate(array $data, ?Blog $blog = null): mixed
    {
        if (! empty($data['published_at'])) {
            return $data['published_at'];
        }

        return $data['status'] === Blog::STATUS_PUBLISHED
            ? ($blog?->published_at ?? now())
            : $blog?->published_at;
    }
}
