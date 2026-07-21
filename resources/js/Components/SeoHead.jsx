import { Head, usePage } from '@inertiajs/react';

function absoluteUrl(base, pathOrUrl) {
    if (!pathOrUrl) {
        return null;
    }

    if (/^https?:\/\//i.test(pathOrUrl)) {
        return pathOrUrl;
    }

    const baseUrl = (base || '').replace(/\/$/, '');
    const path = pathOrUrl.startsWith('/') ? pathOrUrl : `/${pathOrUrl}`;

    return `${baseUrl}${path}`;
}

function truncate(text, max = 160) {
    if (!text) {
        return '';
    }

    const clean = String(text).replace(/\s+/g, ' ').trim();

    if (clean.length <= max) {
        return clean;
    }

    return `${clean.slice(0, max - 1).trimEnd()}…`;
}

/**
 * Shared SEO head tags for public Inertia pages.
 * Pass `title` without the brand — app.jsx appends " - Mummish".
 */
export default function SeoHead({
    title,
    description,
    image,
    url,
    type = 'website',
    noindex = false,
    jsonLd = null,
}) {
    const page = usePage();
    const { appUrl, appName } = page.props;
    const siteName = appName || 'Mummish';
    const pathOnly = (page.url || '/').split('?')[0];
    const canonical = absoluteUrl(appUrl, url) || absoluteUrl(appUrl, pathOnly);
    const ogImage = absoluteUrl(appUrl, image) || absoluteUrl(appUrl, '/images/logo.png');
    const metaDescription = truncate(description);
    const fullTitle = title ? `${title} - ${siteName}` : siteName;

    return (
        <Head title={title || ''}>
            {metaDescription && <meta head-key="description" name="description" content={metaDescription} />}
            {canonical && <link head-key="canonical" rel="canonical" href={canonical} />}
            {noindex && <meta head-key="robots" name="robots" content="noindex, nofollow" />}

            <meta head-key="og:type" property="og:type" content={type} />
            <meta head-key="og:site_name" property="og:site_name" content={siteName} />
            <meta head-key="og:title" property="og:title" content={fullTitle} />
            {metaDescription && (
                <meta head-key="og:description" property="og:description" content={metaDescription} />
            )}
            {canonical && <meta head-key="og:url" property="og:url" content={canonical} />}
            {ogImage && <meta head-key="og:image" property="og:image" content={ogImage} />}

            <meta head-key="twitter:card" name="twitter:card" content="summary_large_image" />
            <meta head-key="twitter:title" name="twitter:title" content={fullTitle} />
            {metaDescription && (
                <meta head-key="twitter:description" name="twitter:description" content={metaDescription} />
            )}
            {ogImage && <meta head-key="twitter:image" name="twitter:image" content={ogImage} />}

            {jsonLd && (
                <script type="application/ld+json">{JSON.stringify(jsonLd)}</script>
            )}
        </Head>
    );
}
