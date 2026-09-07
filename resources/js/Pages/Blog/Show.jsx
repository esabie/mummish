import { Link } from '@inertiajs/react';
import SeoHead from '@/Components/SeoHead';
import SiteFooter from '@/Components/SiteFooter';
import { BlogHeader } from '@/Pages/Blog/BlogHeader';

export default function BlogShow({ post }) {
    return (
        <>
            <SeoHead
                title={post.title}
                description={post.excerpt || `Read ${post.title} on the Mummish blog.`}
                image={post.featured_image_url}
            />

            <div className="flex min-h-screen flex-col bg-[#faf9f7] text-stone-900 antialiased">
                <BlogHeader />

                <main className="flex-1">
                    <article>
                        <header className="border-b border-stone-200/80 bg-white">
                            <div className="mx-auto max-w-3xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
                                <Link
                                    href={route('blogs.index')}
                                    className="inline-flex items-center gap-1.5 text-sm font-medium text-stone-600 transition hover:text-market"
                                >
                                    <span aria-hidden>←</span> All posts
                                </Link>
                                <p className="mt-6 text-xs font-semibold uppercase tracking-wide text-stone-500">
                                    {post.published_at_label}
                                    {post.author_name ? ` · ${post.author_name}` : ''}
                                </p>
                                <h1 className="mt-3 text-3xl font-bold tracking-tight text-stone-900 sm:text-4xl lg:text-5xl">
                                    {post.title}
                                </h1>
                                {post.excerpt ? (
                                    <p className="mt-5 text-lg leading-relaxed text-stone-600">{post.excerpt}</p>
                                ) : null}
                            </div>
                        </header>

                        {post.featured_image_url ? (
                            <div className="border-b border-stone-200/80 bg-white">
                                <div className="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
                                    <img
                                        src={post.featured_image_url}
                                        alt=""
                                        className="w-full rounded-2xl object-cover shadow-sm"
                                    />
                                </div>
                            </div>
                        ) : null}

                        <div className="mx-auto max-w-3xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
                            <div
                                className="blog-content text-base leading-relaxed text-stone-700 [&_a]:font-medium [&_a]:text-market [&_a]:underline [&_blockquote]:border-l-4 [&_blockquote]:border-stone-300 [&_blockquote]:pl-4 [&_blockquote]:italic [&_blockquote]:text-stone-600 [&_h2]:mb-4 [&_h2]:mt-10 [&_h2]:text-2xl [&_h2]:font-bold [&_h2]:text-stone-900 [&_h3]:mb-3 [&_h3]:mt-8 [&_h3]:text-xl [&_h3]:font-bold [&_h3]:text-stone-900 [&_li]:my-1 [&_ol]:my-4 [&_ol]:list-decimal [&_ol]:pl-6 [&_p]:my-4 [&_ul]:my-4 [&_ul]:list-disc [&_ul]:pl-6"
                                dangerouslySetInnerHTML={{ __html: post.body }}
                            />
                        </div>
                    </article>
                </main>

                <SiteFooter />
            </div>
        </>
    );
}
