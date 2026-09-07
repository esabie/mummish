<?php

namespace Database\Factories;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BlogPost>
 */
class BlogPostFactory extends Factory
{
    protected $model = BlogPost::class;

    public function definition(): array
    {
        $title = fake()->sentence(6);

        return [
            'title' => rtrim($title, '.'),
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(100, 999),
            'excerpt' => fake()->paragraph(),
            'body' => '<p>'.fake()->paragraphs(3, true).'</p>',
            'featured_image_path' => null,
            'is_published' => false,
            'published_at' => null,
            'author_user_id' => User::factory(),
        ];
    }

    public function published(?\DateTimeInterface $publishedAt = null): static
    {
        return $this->state(fn (): array => [
            'is_published' => true,
            'published_at' => $publishedAt ?? now()->subDay(),
        ]);
    }
}
