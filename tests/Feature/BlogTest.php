<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    public function test_blogs_index_shows_only_published_posts(): void
    {
        $published = BlogPost::factory()->published()->create([
            'title' => 'Published parenting tips',
            'slug' => 'published-parenting-tips',
        ]);

        BlogPost::factory()->create([
            'title' => 'Draft post',
            'slug' => 'draft-post',
            'is_published' => false,
        ]);

        BlogPost::factory()->create([
            'title' => 'Scheduled post',
            'slug' => 'scheduled-post',
            'is_published' => true,
            'published_at' => now()->addWeek(),
        ]);

        $this->get(route('blogs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Blog/Index')
                ->has('posts', 1)
                ->where('posts.0.slug', $published->slug)
                ->where('posts.0.title', $published->title));
    }

    public function test_blogs_index_shows_empty_state_when_no_posts(): void
    {
        $this->get(route('blogs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Blog/Index')
                ->has('posts', 0));
    }

    public function test_published_blog_post_can_be_viewed(): void
    {
        $post = BlogPost::factory()->published()->create([
            'title' => 'How to sell baby gear',
            'slug' => 'how-to-sell-baby-gear',
            'body' => '<p>Start with great photos.</p>',
        ]);

        $this->get(route('blogs.show', $post->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Blog/Show')
                ->where('post.slug', $post->slug)
                ->where('post.title', $post->title)
                ->where('post.body', $post->body));
    }

    public function test_unpublished_blog_post_returns_not_found(): void
    {
        $post = BlogPost::factory()->create([
            'slug' => 'secret-draft',
            'is_published' => false,
        ]);

        $this->get(route('blogs.show', $post->slug))
            ->assertNotFound();
    }

    public function test_sitemap_includes_blog_index_and_published_posts(): void
    {
        $post = BlogPost::factory()->published()->create([
            'slug' => 'sitemap-post',
        ]);

        BlogPost::factory()->create([
            'slug' => 'draft-for-sitemap',
            'is_published' => false,
        ]);

        $response = $this->get(route('sitemap'));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('/blogs</loc>', $content);
        $this->assertStringContainsString('/blogs/'.$post->slug.'</loc>', $content);
        $this->assertStringNotContainsString('/blogs/draft-for-sitemap</loc>', $content);
    }
}
