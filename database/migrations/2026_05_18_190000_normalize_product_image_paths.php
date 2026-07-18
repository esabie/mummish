<?php

use App\Support\PublicStorageUrl;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $products = DB::table('products')->orderBy('id')->get();

        foreach ($products as $product) {
            $urls = json_decode($product->image_urls ?? '[]', true);

            if (! is_array($urls)) {
                $urls = [];
            }

            $normalized = collect($urls)
                ->map(fn ($url) => PublicStorageUrl::toStoredPath(is_string($url) ? $url : null))
                ->filter()
                ->values()
                ->all();

            $imageUrl = PublicStorageUrl::toStoredPath($product->image_url);

            if ($normalized === [] && $imageUrl !== null) {
                $normalized = [$imageUrl];
            }

            if ($imageUrl === null && $normalized !== []) {
                $imageUrl = $normalized[0];
            }

            DB::table('products')->where('id', $product->id)->update([
                'image_urls' => $normalized === [] ? null : json_encode($normalized),
                'image_url' => $imageUrl,
            ]);
        }
    }

    public function down(): void
    {
        // Paths cannot be restored to full URLs without the original host.
    }
};
