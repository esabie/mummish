<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use App\Support\AppLog;
use App\Support\PublicStorageUrl;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProductImageService
{
    /**
     * @param  array<int, UploadedFile>  $files
     * @return array<int, string>
     */
    public function storeUploads(User $user, array $files): array
    {
        AppLog::debug('[ProductImage] Storing uploads.', [
            'vendor_user_id' => $user->id,
            'file_count' => count($files),
        ]);

        $urls = [];

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store("products/{$user->id}", 'public');
            $urls[] = $path;
        }

        AppLog::info('[ProductImage] Uploads stored.', [
            'vendor_user_id' => $user->id,
            'stored_count' => count($urls),
        ]);

        return $urls;
    }

    /**
     * @param  array<int, string>  $paths
     */
    public function deleteStoredUrls(array $paths): void
    {
        foreach ($paths as $url) {
            $this->deleteStoredUrl($url);
        }
    }

    public function deleteStoredUrl(string $url): void
    {
        $path = $this->pathFromPublicUrl($url);

        if ($path !== null && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * @param  array<int, string>  $removedUrls
     */
    public function deleteRemovedImages(Product $product, array $removedUrls): void
    {
        $stored = collect($product->image_urls ?? [])
            ->filter(fn (string $url) => $this->pathFromPublicUrl($url) !== null);

        foreach ($removedUrls as $url) {
            if ($stored->contains($url)) {
                $this->deleteStoredUrl($url);
            }
        }
    }

    private function pathFromPublicUrl(string $url): ?string
    {
        $path = PublicStorageUrl::toStoredPath($url);

        if ($path === null || str_contains($path, '://')) {
            return null;
        }

        if (Storage::disk('public')->exists($path)) {
            return $path;
        }

        return null;
    }
}
