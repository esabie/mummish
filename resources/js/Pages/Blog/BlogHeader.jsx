import { Link, usePage } from '@inertiajs/react';
import LogoMark from '@/Components/LogoMark';
import SeoHead from '@/Components/SeoHead';
import SiteFooter from '@/Components/SiteFooter';
import { useCart } from '@/context/CartContext';

function IconCart(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" {...props}>
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.5a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"
            />
        </svg>
    );
}

function IconUser(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" {...props}>
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"
            />
        </svg>
    );
}

function BlogHeader({ active = 'blog' }) {
    const { auth, canLogin, canRegister } = usePage().props;
    const { cartCount, openCart } = useCart();

    return (
        <header className="sticky top-0 z-40 border-b border-stone-200/90 bg-white/95 backdrop-blur">
            <div className="mx-auto flex max-w-7xl items-center gap-3 px-4 py-3 sm:px-6 lg:px-8">
                <LogoMark variant="shop" />
                <nav className="ml-6 hidden gap-6 text-sm font-medium text-stone-700 sm:flex">
                    <Link href={route('shop.index')} className="transition hover:text-market">
                        Shop
                    </Link>
                    <Link href={route('about')} className="transition hover:text-market">
                        About
                    </Link>
                    <Link
                        href={route('blogs.index')}
                        className={active === 'blog' ? 'font-semibold text-market' : 'transition hover:text-market'}
                    >
                        Blog
                    </Link>
                </nav>
                <div className="ml-auto flex items-center gap-1 sm:gap-2">
                    {canRegister && (
                        <Link
                            href={route('vendor.signup')}
                            className="hidden rounded-full border border-market px-3 py-1.5 text-sm font-semibold text-market transition hover:bg-market-muted sm:inline-block"
                        >
                            Sell
                        </Link>
                    )}
                    <button
                        type="button"
                        onClick={openCart}
                        className="relative rounded-full p-2 text-stone-700 transition hover:bg-stone-100"
                        aria-label="Shopping bag"
                    >
                        <IconCart className="h-6 w-6" />
                        {cartCount > 0 && (
                            <span className="absolute -right-0.5 -top-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-market px-1 text-[10px] font-bold leading-none text-white">
                                {cartCount > 99 ? '99+' : cartCount}
                            </span>
                        )}
                    </button>
                    {auth.user ? (
                        <Link
                            href={route('dashboard')}
                            className="flex h-9 w-9 items-center justify-center rounded-full bg-stone-200 text-sm font-semibold text-stone-800 transition hover:bg-stone-300"
                            aria-label="Account"
                        >
                            {auth.user.name?.charAt(0)?.toUpperCase() ?? '?'}
                        </Link>
                    ) : canLogin ? (
                        <Link
                            href={route('login')}
                            className="rounded-full p-2 text-stone-700 transition hover:bg-stone-100"
                            aria-label="Sign in"
                        >
                            <IconUser className="h-6 w-6" />
                        </Link>
                    ) : null}
                </div>
            </div>
        </header>
    );
}

export { BlogHeader };
