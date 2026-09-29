<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Support\BlogContentSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdmin();

        $status = (string) $request->query('status', 'all');
        $search = trim((string) $request->query('search', ''));

        $blogs = Blog::query()
            ->with('creator')
            ->when(in_array($status, [Blog::STATUS_ARCHIVED, Blog::STATUS_PUBLISHED], true), fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where('title', 'like', '%'.$search.'%'))
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.blogs.index', compact('blogs', 'status', 'search'));
    }

    public function create(): View
    {
        $this->authorizeAdmin();

        return view('admin.blogs.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();
        $data = $this->validated($request);
        $data['slug'] = Blog::makeUniqueSlug($data['slug'] ?: $data['title']);
        $data['content'] = BlogContentSanitizer::clean($data['content']);
        $data['created_by'] = auth()->id();
        $data['published_at'] = $this->publicationDate($data);

        $blog = Blog::query()->create($data);

        return redirect()->route('admin.blogs.show', $blog)->with('success', 'Blog post created successfully.');
    }

    public function show(Blog $blog): View
    {
        $this->authorizeAdmin();

        return view('admin.blogs.show', compact('blog'));
    }

    public function edit(Blog $blog): View
    {
        $this->authorizeAdmin();

        return view('admin.blogs.edit', compact('blog'));
    }

    public function update(Request $request, Blog $blog): RedirectResponse
    {
        $this->authorizeAdmin();
        $data = $this->validated($request, $blog);
        $data['slug'] = Blog::makeUniqueSlug($data['slug'] ?: $data['title'], $blog->id);
        $data['content'] = BlogContentSanitizer::clean($data['content']);
        $data['published_at'] = $this->publicationDate($data, $blog);

        $blog->update($data);

        return redirect()->route('admin.blogs.show', $blog)->with('success', 'Blog post updated successfully.');
    }

    public function updateStatus(Request $request, Blog $blog): RedirectResponse
    {
        $this->authorizeAdmin();
        $data = $request->validate([
            'status' => ['required', Rule::in([Blog::STATUS_ARCHIVED, Blog::STATUS_PUBLISHED])],
        ]);

        $blog->update([
            'status' => $data['status'],
            'published_at' => $data['status'] === Blog::STATUS_PUBLISHED
                ? ($blog->published_at ?? now())
                : $blog->published_at,
        ]);

        return back()->with('success', $data['status'] === Blog::STATUS_PUBLISHED
            ? 'Blog post published.'
            : 'Blog post archived and removed from the public page.');
    }

    public function destroy(Blog $blog): RedirectResponse
    {
        $this->authorizeAdmin();
        $blog->delete();

        return redirect()->route('admin.blogs.index')->with('success', 'Blog post moved out of the active library.');
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    private function validated(Request $request, ?Blog $blog = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('blogs', 'slug')->ignore($blog?->id)],
            'excerpt' => ['nullable', 'string', 'max:2000'],
            'content' => ['required', 'string'],
            'category' => ['required', 'string', 'max:100'],
            'author_name' => ['nullable', 'string', 'max:255'],
            'author_role' => ['nullable', 'string', 'max:255'],
            'author_organization' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in([Blog::STATUS_ARCHIVED, Blog::STATUS_PUBLISHED])],
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
