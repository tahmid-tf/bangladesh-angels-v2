<?php

namespace App\Http\Controllers;

use App\Models\Publication;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PublicationController extends Controller
{
    public function index(): View
    {
        return view('publications.index');
    }

    public function download(Publication $publication)
    {
        abort_unless($publication->isPublished(), 404);
        abort_unless(Storage::disk('local')->exists($publication->pdf_path), 404);

        return Storage::disk('local')->download(
            $publication->pdf_path,
            $publication->pdf_original_name ?: 'publication.pdf',
            ['X-Content-Type-Options' => 'nosniff']
        );
    }
}
