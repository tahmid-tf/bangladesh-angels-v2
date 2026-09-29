<?php

namespace Tests\Feature;

use App\Models\Publication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\IsolatedDatabaseTestCase;

class PublicationFeatureTest extends IsolatedDatabaseTestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_a_pdf_when_its_real_path_cannot_be_resolved(): void
    {
        config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin', 'gender' => 'other']);
        $temporaryPath = tempnam(sys_get_temp_dir(), 'publication-');
        file_put_contents($temporaryPath, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF");

        $pdf = new class($temporaryPath, 'ecosystem-report.pdf', 'application/pdf', null, true) extends UploadedFile
        {
            public function getRealPath(): string|false
            {
                return false;
            }
        };

        try {
            $response = $this->actingAs($admin)->post(route('admin.publications.store'), [
                'title' => 'Bangladesh startup ecosystem report',
                'excerpt' => 'The annual ecosystem overview.',
                'category' => 'Market report',
                'status' => Publication::STATUS_PUBLISHED,
                'published_at' => '',
                'pdf' => $pdf,
            ]);

            $publication = Publication::query()->firstOrFail();

            $response->assertRedirect(route('admin.publications.index'));
            $this->assertSame('ecosystem-report.pdf', $publication->pdf_original_name);
            $this->assertNotNull($publication->published_at);
            Storage::disk('local')->assertExists($publication->pdf_path);
        } finally {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }
}
