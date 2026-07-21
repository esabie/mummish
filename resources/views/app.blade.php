<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#0f766e">
        <meta name="google-site-verification" content="W7qPFeiw2kJx842pUU1u2DINljGp-fH83qfY6x8hvrM" />

        @php
            $siteName = config('app.name', 'Mummish');
            $siteUrl = rtrim((string) config('app.url'), '/');
            $seoTitle = config('seo.title', $siteName);
            $siteDescription = config('seo.description');
            $seoTaglines = array_values(array_filter(config('seo.taglines', [])));
            $ogImage = $siteUrl.'/images/logo.png';
            $websiteJsonLd = [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => $siteName,
                'url' => $siteUrl.'/',
                'description' => $siteDescription,
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => $siteUrl.'/shop?q={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ],
            ];
            if ($seoTaglines !== []) {
                $websiteJsonLd['alternateName'] = $seoTaglines;
            }
        @endphp

        {{-- Server-rendered defaults so crawlers see Mummish copy without waiting on Inertia JS --}}
        <meta name="description" content="{{ $siteDescription }}">
        <link rel="canonical" href="{{ url()->current() }}">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ $siteName }}">
        <meta property="og:title" content="{{ $seoTitle }}">
        <meta property="og:description" content="{{ $siteDescription }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ $ogImage }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $seoTitle }}">
        <meta name="twitter:description" content="{{ $siteDescription }}">
        <meta name="twitter:image" content="{{ $ogImage }}">
        <script type="application/ld+json">{!! json_encode($websiteJsonLd, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>

        <title inertia>{{ $seoTitle }}</title>

        <link rel="icon" href="/images/logo.png?v=3" type="image/png">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite(['resources/js/app.jsx', "resources/js/Pages/{$page['component']}.jsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
