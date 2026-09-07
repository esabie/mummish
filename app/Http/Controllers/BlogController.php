<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    public function index(): Response
    {
        $posts = BlogPost::query()
            ->with('author')
            ->published()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (BlogPost $post): array => $post->toPublicArray())
            ->values()
            ->all();

        return Inertia::render('Blog/Index', [
            'posts' => $posts,
        ]);
    }

    public function show(string $slug): Response
    {
        $post = BlogPost::query()
            ->with('author')
            ->where('slug', $slug)
            ->published()
            ->firstOrFail();

        return Inertia::render('Blog/Show', [
            'post' => $post->toPublicArray(includeBody: true),
        ]);
    }
}
