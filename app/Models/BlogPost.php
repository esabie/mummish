<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogPost extends Model
{
    /** @use HasFactory<\Database\Factories\BlogPostFactory> */
    use HasFactory;
    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'body',
        'featured_image_path',
        'is_published',
        'published_at',
        'author_user_id',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function featuredImageUrl(): ?string
    {
        if (! filled($this->featured_image_path)) {
            return null;
        }

        return asset('storage/'.$this->featured_image_path);
    }

    public function toPublicArray(bool $includeBody = false): array
    {
        $data = [
            'slug' => $this->slug,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'featured_image_url' => $this->featuredImageUrl(),
            'published_at' => optional($this->published_at ?? $this->created_at)?->toIso8601String(),
            'published_at_label' => optional($this->published_at ?? $this->created_at)?->format('M j, Y'),
            'author_name' => $this->author?->name,
        ];

        if ($includeBody) {
            $data['body'] = $this->body;
        }

        return $data;
    }
}
