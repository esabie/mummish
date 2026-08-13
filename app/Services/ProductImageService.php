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
        AppLog::info('[ProductImage] Storing uploads.', [
            'vendor_user_id' => $user->id,
            'file_count' => count($files),
            'files' => array_map(static function ($file) {
                if (! $file instanceof UploadedFile) {
                    return null;
                }

                return [
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime' => $file->getMimeType(),
                ];
            }, $files),
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
            'paths' => $urls,
        ]);

        return $urls;
    }

    /**
     * Permanently remove every listing (and stored images) for a vendor.
     */
    public function deleteListingsForVendor(User $user): int
    {
        return $this->deleteListingsForVendorId($user->id);
    }

    public function deleteListingsForVendorId(int $userId): int
    {
        $deletedCount = 0;
        $products = Product::query()->where('user_id', $userId)->get();

        foreach ($products as $product) {
            $this->deleteStoredUrls($product->image_urls ?? []);
            if (is_string($product->image_url) && $product->image_url !== '') {
                $this->deleteStoredUrl($product->image_url);
            }
            $product->delete();
            $deletedCount++;
        }

        AppLog::info('[ProductImage] Vendor listings deleted.', [
            'vendor_user_id' => $userId,
            'deleted_count' => $deletedCount,
        ]);

        return $deletedCount;
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
