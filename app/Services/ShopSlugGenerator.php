<?php

namespace App\Services;

use App\Models\VendorApplication;
use Illuminate\Support\Str;

class ShopSlugGenerator
{
    public function generate(string $shopName, ?int $exceptApplicationId = null): string
    {
        $base = Str::slug($shopName);

        if ($base === '') {
            $base = 'shop';
        }

        $slug = $base;
        $suffix = 2;

        while ($this->exists($slug, $exceptApplicationId)) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function exists(string $slug, ?int $exceptApplicationId): bool
    {
        return VendorApplication::query()
            ->when(
                $exceptApplicationId !== null,
                fn ($query) => $query->where('id', '!=', $exceptApplicationId),
            )
            ->where('shop_slug', $slug)
            ->exists();
    }
}
