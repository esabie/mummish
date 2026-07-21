<?php

namespace App\Http\Controllers;

use App\Enums\VendorApplicationStatus;
use App\Models\Product;
use App\Models\VendorApplication;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $base = rtrim((string) config('app.url'), '/');
        $now = now()->toAtomString();

        $urls = [
            ['loc' => $base.'/', 'changefreq' => 'daily', 'priority' => '1.0', 'lastmod' => $now],
            ['loc' => $base.'/shop', 'changefreq' => 'daily', 'priority' => '0.9', 'lastmod' => $now],
            ['loc' => $base.'/shops', 'changefreq' => 'daily', 'priority' => '0.8', 'lastmod' => $now],
            ['loc' => $base.'/about', 'changefreq' => 'monthly', 'priority' => '0.5', 'lastmod' => $now],
            ['loc' => $base.'/sell', 'changefreq' => 'monthly', 'priority' => '0.6', 'lastmod' => $now],
            ['loc' => $base.'/terms', 'changefreq' => 'yearly', 'priority' => '0.3', 'lastmod' => $now],
            ['loc' => $base.'/privacy', 'changefreq' => 'yearly', 'priority' => '0.3', 'lastmod' => $now],
            ['loc' => $base.'/billing', 'changefreq' => 'yearly', 'priority' => '0.3', 'lastmod' => $now],
        ];

        VendorApplication::query()
            ->where('status', VendorApplicationStatus::Approved)
            ->whereNotNull('shop_slug')
            ->orderBy('id')
            ->get(['shop_slug', 'updated_at'])
            ->each(function (VendorApplication $store) use (&$urls, $base) {
                $urls[] = [
                    'loc' => $base.'/shops/'.$store->shop_slug,
                    'changefreq' => 'weekly',
                    'priority' => '0.7',
                    'lastmod' => optional($store->updated_at)->toAtomString() ?? now()->toAtomString(),
                ];
            });

        Product::query()
            ->visibleInShop()
            ->with(['user.vendorApplication'])
            ->orderBy('id')
            ->get(['id', 'user_id', 'updated_at'])
            ->each(function (Product $product) use (&$urls, $base) {
                $shop = $product->user?->vendorApplication;
                $slug = ($shop?->isApproved() && $shop?->shop_slug)
                    ? $shop->shop_slug
                    : null;

                $loc = $slug
                    ? $base.'/shops/'.$slug.'/products/'.$product->id
                    : $base.'/shop/products/'.$product->id;

                $urls[] = [
                    'loc' => $loc,
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                    'lastmod' => optional($product->updated_at)->toAtomString() ?? now()->toAtomString(),
                ];
            });

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
