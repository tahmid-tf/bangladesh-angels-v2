<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(): View
    {
        return view('blogs.index');
    }

    public function show(string $slug): View
    {
        $blog = Blog::query()->published()->where('slug', $slug)->firstOrFail();

        return view('blogs.show', [
            'blog' => $blog,
            'categories' => $this->categories(),
            'recentBlogs' => $this->recentBlogs($blog->id),
        ]);
    }

    private function categories()
    {
        return Blog::query()
            ->published()
            ->selectRaw('category, COUNT(*) as posts_count')
            ->groupBy('category')
            ->orderBy('category')
            ->get();
    }

    private function recentBlogs(?int $exceptId = null)
    {
        return Blog::query()
            ->published()
            ->when($exceptId !== null, fn (Builder $query) => $query->whereKeyNot($exceptId))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get();
    }
}
