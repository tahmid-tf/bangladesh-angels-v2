<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\IsolatedDatabaseTestCase;

class BlogFeatureTest extends IsolatedDatabaseTestCase
{
    use RefreshDatabase;

    public function test_public_blog_lists_only_published_posts_and_supports_search_and_categories(): void
    {
        $visible = Blog::query()->create([
            'title' => 'Angel investing in Bangladesh',
            'slug' => 'angel-investing-in-bangladesh',
            'excerpt' => 'A practical introduction.',
            'content' => '<p>Long-form educational content.</p>',
            'category' => 'Educational',
            'status' => Blog::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
        Blog::query()->create([
            'title' => 'Internal archived draft',
            'slug' => 'internal-archived-draft',
            'content' => '<p>Not public.</p>',
            'category' => 'Announcement',
            'status' => Blog::STATUS_ARCHIVED,
        ]);

        $this->get(route('blogs.index'))
            ->assertOk()
            ->assertSee($visible->title)
            ->assertDontSee('Internal archived draft');

        $this->get(route('blogs.index', ['search' => 'Angel', 'category' => 'Educational']))
            ->assertOk()
            ->assertSee($visible->title);

        $this->get(route('blogs.index', ['search' => 'does-not-exist']))
            ->assertOk()
            ->assertSee('No articles found.');
    }

    public function test_archived_blog_cannot_be_opened_publicly(): void
    {
        $blog = Blog::query()->create([
            'title' => 'Archived blog',
            'slug' => 'archived-blog',
            'content' => '<p>Hidden article.</p>',
            'category' => 'Misc',
            'status' => Blog::STATUS_ARCHIVED,
        ]);

        $this->get(route('blogs.show', $blog->slug))->assertNotFound();
    }

    public function test_admin_can_create_update_read_archive_and_soft_delete_a_blog(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $create = $this->actingAs($admin)->post(route('admin.blogs.store'), [
            'title' => 'A new investment guide',
            'slug' => '',
            'excerpt' => 'A useful summary.',
            'content' => '<h2>Introduction</h2><p onclick="bad()">Useful <strong>content</strong>.</p><script>alert(1)</script>',
            'category' => 'Educational',
            'author_name' => 'BAN Editorial Team',
            'author_role' => 'Research',
            'author_organization' => 'Bangladesh Angels Network',
            'status' => Blog::STATUS_PUBLISHED,
            'published_at' => '',
        ]);

        $blog = Blog::query()->firstOrFail();
        $create->assertRedirect(route('admin.blogs.show', $blog));
        $this->assertSame('a-new-investment-guide', $blog->slug);
        $this->assertStringNotContainsString('onclick', $blog->content);
        $this->assertStringNotContainsString('alert(1)', $blog->content);
        $this->assertNotNull($blog->published_at);

        $this->get(route('admin.blogs.show', $blog))
            ->assertOk()
            ->assertSee('Useful', false);

        $this->put(route('admin.blogs.update', $blog), [
            'title' => 'An updated investment guide',
            'slug' => $blog->slug,
            'excerpt' => 'Updated summary.',
            'content' => '<p>Updated long-form content.</p>',
            'category' => 'Investment',
            'author_name' => 'BAN Editorial Team',
            'author_role' => '',
            'author_organization' => 'Bangladesh Angels Network',
            'status' => Blog::STATUS_PUBLISHED,
            'published_at' => $blog->published_at->format('Y-m-d H:i:s'),
        ])->assertRedirect(route('admin.blogs.show', $blog));

        $this->patch(route('admin.blogs.status', $blog), ['status' => Blog::STATUS_ARCHIVED])
            ->assertSessionHas('success');
        $this->assertSame(Blog::STATUS_ARCHIVED, $blog->fresh()->status);
        $this->get(route('blogs.show', $blog->slug))->assertNotFound();

        $this->delete(route('admin.blogs.destroy', $blog))->assertRedirect(route('admin.blogs.index'));
        $this->assertSoftDeleted('blogs', ['id' => $blog->id]);
    }

    public function test_non_admin_cannot_manage_blogs(): void
    {
        $member = User::factory()->create(['role' => 'investor']);

        $this->actingAs($member)->get(route('admin.blogs.index'))->assertForbidden();
        $this->actingAs($member)->get(route('admin.blogs.create'))->assertForbidden();
    }
}
