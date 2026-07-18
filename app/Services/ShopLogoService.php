<?php

namespace App\Services;

use App\Models\User;
use App\Support\PublicStorageUrl;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ShopLogoService
{
    public function store(User $user, UploadedFile $file): string
    {
        return $file->store("shops/{$user->id}", 'public');
    }

    public function deleteStoredPath(?string $path): void
    {
        $stored = PublicStorageUrl::toStoredPath($path);

        if ($stored !== null && ! str_contains($stored, '://') && Storage::disk('public')->exists($stored)) {
            Storage::disk('public')->delete($stored);
        }
    }
}
