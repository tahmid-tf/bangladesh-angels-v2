<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Publication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PublicationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdmin();

        $status = (string) $request->query('status', 'all');
        $search = trim((string) $request->query('search', ''));

        $publications = Publication::query()
            ->with('creator')
            ->when(in_array($status, [Publication::STATUS_ARCHIVED, Publication::STATUS_PUBLISHED], true), fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('title', 'like', '%'.$search.'%')
                    ->orWhere('excerpt', 'like', '%'.$search.'%')
                    ->orWhere('category', 'like', '%'.$search.'%');
            }))
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.publications.index', compact('publications', 'status', 'search'));
    }

    public function create(): View
    {
        $this->authorizeAdmin();

        return view('admin.publications.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();
        $data = $this->validated($request, true);
        $file = $request->file('pdf');
        $path = $this->storePdf($file);
        unset($data['pdf']);

        try {
            Publication::query()->create([
                ...$data,
                'pdf_path' => $path,
                'pdf_original_name' => $file->getClientOriginalName(),
                'created_by' => auth()->id(),
                'published_at' => $this->publicationDate($data),
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return redirect()->route('admin.publications.index')->with('success', 'Publication added successfully.');
    }

    public function edit(Publication $publication): View
    {
        $this->authorizeAdmin();

        return view('admin.publications.edit', compact('publication'));
    }

    public function update(Request $request, Publication $publication): RedirectResponse
    {
        $this->authorizeAdmin();
        $data = $this->validated($request);
        $oldPath = $publication->pdf_path;
        $newPath = null;
        $file = $request->file('pdf');

        if ($file) {
            $newPath = $this->storePdf($file);
            $data['pdf_path'] = $newPath;
            $data['pdf_original_name'] = $file->getClientOriginalName();
        }
        unset($data['pdf']);

        try {
            $publication->update([
                ...$data,
                'published_at' => $this->publicationDate($data, $publication),
            ]);
        } catch (\Throwable $exception) {
            if ($newPath) {
                Storage::disk('local')->delete($newPath);
            }
            throw $exception;
        }

        if ($newPath && $oldPath !== $newPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return redirect()->route('admin.publications.index')->with('success', 'Publication updated successfully.');
    }

    public function updateStatus(Request $request, Publication $publication): RedirectResponse
    {
        $this->authorizeAdmin();
        $data = $request->validate(['status' => ['required', Rule::in([Publication::STATUS_ARCHIVED, Publication::STATUS_PUBLISHED])]]);

        $publication->update([
            'status' => $data['status'],
            'published_at' => $data['status'] === Publication::STATUS_PUBLISHED
                ? ($publication->published_at ?? now())
                : $publication->published_at,
        ]);

        return back()->with('success', $data['status'] === Publication::STATUS_PUBLISHED
            ? 'Publication published.'
            : 'Publication archived and removed from the public page.');
    }

    public function destroy(Publication $publication): RedirectResponse
    {
        $this->authorizeAdmin();
        Storage::disk('local')->delete($publication->pdf_path);
        $publication->delete();

        return redirect()->route('admin.publications.index')->with('success', 'Publication removed from the library.');
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    private function validated(Request $request, bool $requiredPdf = false): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', 'string', 'max:100'],
            'status' => ['required', Rule::in([Publication::STATUS_ARCHIVED, Publication::STATUS_PUBLISHED])],
            'published_at' => ['nullable', 'date'],
            'pdf' => [$requiredPdf ? 'required' : 'nullable', 'file', 'mimes:pdf', 'max:20480'],
        ]);
    }

    private function storePdf(?UploadedFile $file): string
    {
        if (! $file?->isValid()) {
            throw ValidationException::withMessages([
                'pdf' => 'The PDF upload could not be read. Please choose the file again and retry.',
            ]);
        }

        // UploadedFile::store() resolves the temporary filename through
        // realpath(). On some Windows/PHP setups that returns false for a
        // valid upload, which leads to fopen('') and "Path cannot be empty".
        $stream = @fopen($file->getPathname(), 'rb');

        if (! is_resource($stream)) {
            throw ValidationException::withMessages([
                'pdf' => 'The PDF upload could not be read. Please choose the file again and retry.',
            ]);
        }

        $path = 'publications/'.Str::uuid().'.pdf';

        try {
            $stored = Storage::disk('local')->put($path, $stream);
        } finally {
            fclose($stream);
        }

        if (! $stored) {
            throw ValidationException::withMessages([
                'pdf' => 'The PDF could not be saved. Please retry the upload.',
            ]);
        }

        return $path;
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
