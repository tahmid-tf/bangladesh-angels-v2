<?php

namespace App\Http\Controllers\Api;

use App\Models\Blog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlogApiController extends BaseApiController
{
    /**
     * Paginated list of published blogs with filtering and categories.
     */
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $category = trim((string) $request->query('category', ''));

        $blogs = Blog::query()
            ->published()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('excerpt', 'like', '%'.$search.'%')
                        ->orWhere('content', 'like', '%'.$search.'%');
                });
            })
            ->when($category !== '', fn (Builder $query) => $query->where('category', $category))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate((int) $request->query('per_page', 10));

        $categories = Blog::query()
            ->published()
            ->selectRaw('category, COUNT(*) as posts_count')
            ->groupBy('category')
            ->orderBy('category')
            ->get();

        $recentBlogs = Blog::query()
            ->published()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id', 'title', 'slug', 'category', 'published_at']);

        return $this->success([
            'blogs' => $blogs,
            'categories' => $categories,
            'recent_blogs' => $recentBlogs,
        ]);
    }

    /**
     * Single blog post by slug.
     */
    public function show(string $slug): JsonResponse
    {
        $blog = Blog::query()->published()->where('slug', $slug)->first();

        if (! $blog) {
            return $this->error('Blog post not found.', 404);
        }

        $categories = Blog::query()
            ->published()
            ->selectRaw('category, COUNT(*) as posts_count')
            ->groupBy('category')
            ->orderBy('category')
            ->get();

        $recentBlogs = Blog::query()
            ->published()
            ->whereKeyNot($blog->id)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id', 'title', 'slug', 'category', 'published_at']);

        return $this->success([
            'blog' => $blog,
            'categories' => $categories,
            'recent_blogs' => $recentBlogs,
        ]);
    }
}
