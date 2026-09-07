import { Link } from '@inertiajs/react';
import SeoHead from '@/Components/SeoHead';
import SiteFooter from '@/Components/SiteFooter';
import { BlogHeader } from '@/Pages/Blog/BlogHeader';

export default function BlogIndex({ posts }) {
    return (
        <>
            <SeoHead
                title="Blog"
                description="Parenting tips, marketplace updates, and stories from the Mummish community in Ghana."
            />

            <div className="flex min-h-screen flex-col bg-[#faf9f7] text-stone-900 antialiased">
                <BlogHeader />

                <main className="flex-1">
                    <section className="relative overflow-hidden border-b border-stone-200/80 bg-[#c3e9fa]">
                        <div
                            className="pointer-events-none absolute inset-0 opacity-40"
                            aria-hidden
                            style={{
                                backgroundImage:
                                    'radial-gradient(circle at 18% 75%, rgba(255,255,255,0.85) 0%, transparent 48%), radial-gradient(circle at 82% 18%, rgba(255,255,255,0.55) 0%, transparent 42%)',
                            }}
                        />
                        <div className="relative mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8 lg:py-20">
                            <Link
                                href={route('home')}
                                className="inline-flex items-center gap-1.5 text-sm font-medium text-[#5c4d3d]/80 transition hover:text-[#5c4d3d]"
                            >
                                <span aria-hidden>←</span> Back to home
                            </Link>
                            <p className="mt-6 text-xs font-bold uppercase tracking-[0.2em] text-[#5c4d3d]/70">
                                Mummish blog
                            </p>
                            <h1 className="mt-3 max-w-3xl text-3xl font-bold tracking-tight text-stone-900 sm:text-4xl lg:text-5xl">
                                Stories, tips &amp; updates
                            </h1>
                            <p className="mt-5 max-w-2xl text-base leading-relaxed text-stone-700 sm:text-lg">
                                Parenting advice, seller insights, and news from Ghana&apos;s family marketplace.
                            </p>
                        </div>
                    </section>

                    <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
                        {posts.length === 0 ? (
                            <div className="rounded-2xl border border-stone-200/90 bg-white px-6 py-16 text-center shadow-sm">
                                <p className="text-lg font-semibold text-stone-900">No posts yet</p>
                                <p className="mt-2 text-sm text-stone-600">
                                    Check back soon for new articles from the Mummish team.
                                </p>
                            </div>
                        ) : (
                            <div className="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                                {posts.map((post) => (
                                    <article
                                        key={post.slug}
                                        className="group flex flex-col overflow-hidden rounded-2xl border border-stone-200/90 bg-white shadow-sm transition hover:shadow-md"
                                    >
                                        <Link href={route('blogs.show', post.slug)} className="block">
                                            {post.featured_image_url ? (
                                                <div className="aspect-[16/10] overflow-hidden bg-stone-100">
                                                    <img
                                                        src={post.featured_image_url}
                                                        alt=""
                                                        className="h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]"
                                                    />
                                                </div>
                                            ) : (
                                                <div className="aspect-[16/10] bg-gradient-to-br from-[#c3e9fa] to-[#faf9f7]" />
                                            )}
                                        </Link>
                                        <div className="flex flex-1 flex-col p-5 sm:p-6">
                                            <p className="text-xs font-semibold uppercase tracking-wide text-stone-500">
                                                {post.published_at_label}
                                                {post.author_name ? ` · ${post.author_name}` : ''}
                                            </p>
                                            <h2 className="mt-2 text-xl font-bold text-stone-900">
                                                <Link
                                                    href={route('blogs.show', post.slug)}
                                                    className="transition hover:text-market"
                                                >
                                                    {post.title}
                                                </Link>
                                            </h2>
                                            {post.excerpt ? (
                                                <p className="mt-3 flex-1 text-sm leading-relaxed text-stone-600 line-clamp-3">
                                                    {post.excerpt}
                                                </p>
                                            ) : null}
                                            <Link
                                                href={route('blogs.show', post.slug)}
                                                className="mt-4 inline-flex text-sm font-semibold text-market transition hover:text-market-hover"
                                            >
                                                Read more →
                                            </Link>
                                        </div>
                                    </article>
                                ))}
                            </div>
                        )}
                    </section>
                </main>

                <SiteFooter />
            </div>
        </>
    );
}
