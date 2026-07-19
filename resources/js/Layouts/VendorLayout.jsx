import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { LogoIcon } from '@/Components/LogoMark';
import VendorNotificationBell from '@/Components/Vendor/VendorNotificationBell';

const vendorBrown = 'bg-[#5c4d3d] hover:bg-[#4a3e32]';

function NavIcon({ name, className = 'h-5 w-5' }) {
    const icons = {
        dashboard: (
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"
            />
        ),
        inventory: (
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"
            />
        ),
        orders: (
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-4.772 4.772a48.108 48.108 0 00-3.228 0 47.897 47.897 0 00-7.344 4.582 2.25 2.25 0 01-2.104.65 2.25 2.25 0 00-1.86 2.104v.915m12.772-2.104a2.25 2.25 0 012.104.65 2.25 2.25 0 002.25 2.25v1.372a2.25 2.25 0 01-2.25 2.25H6.75a2.25 2.25 0 01-2.25-2.25v-1.372a2.25 2.25 0 012.25-2.25h.006"
            />
        ),
        customers: (
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"
            />
        ),
    };

    return (
        <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5" aria-hidden>
            {icons[name]}
        </svg>
    );
}

const navItems = [
    { label: 'Dashboard', href: 'vendor.dashboard', icon: 'dashboard' },
    { label: 'Inventory', href: 'vendor.inventory.index', icon: 'inventory' },
    { label: 'Orders', href: 'vendor.orders.index', icon: 'orders' },
    { label: 'Customers', href: 'vendor.customers.index', icon: 'customers' },
];

function SidebarLink({ item, active, onNavigate }) {
    return (
        <Link
            href={route(item.href)}
            onClick={onNavigate}
            className={`flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition ${
                active
                    ? 'bg-orange-100 text-[#5c4d3d]'
                    : 'text-stone-600 hover:bg-stone-100 hover:text-stone-900'
            }`}
        >
            <NavIcon name={item.icon} />
            {item.label}
        </Link>
    );
}

function BottomTabLink({ item, active }) {
    return (
        <Link
            href={route(item.href)}
            className={`flex min-w-0 flex-1 flex-col items-center gap-0.5 px-1 py-2 text-[10px] font-semibold transition ${
                active ? 'text-[#5c4d3d]' : 'text-stone-500'
            }`}
        >
            <NavIcon name={item.icon} className="h-5 w-5" />
            <span className="truncate">{item.label}</span>
        </Link>
    );
}

export default function VendorLayout({ title, children }) {
    const { auth, flash } = usePage().props;
    const user = auth.user;
    const [mobileNavOpen, setMobileNavOpen] = useState(false);
    const current = route().current() ?? '';

    const closeMobileNav = () => setMobileNavOpen(false);

    const isActive = (href) => {
        if (href === 'vendor.dashboard') {
            return current === 'vendor.dashboard';
        }
        return current?.startsWith(href.replace('.index', '')) || current === href;
    };

    const initials = user.name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();

    return (
        <div className="min-h-screen bg-[#f5f4f2] text-stone-900">
            {/* Top bar */}
            <header className="sticky top-0 z-30 border-b border-stone-200/80 bg-white">
                <div className="flex h-14 items-center gap-3 px-3 sm:gap-4 sm:px-4 lg:px-6">
                    <button
                        type="button"
                        className="rounded-lg p-2 text-stone-600 hover:bg-stone-100 lg:hidden"
                        onClick={() => setMobileNavOpen((o) => !o)}
                        aria-label="Toggle navigation"
                    >
                        <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <Link href={route('vendor.dashboard')} className="flex min-w-0 items-center gap-2">
                        <LogoIcon className="h-8 w-auto shrink-0 sm:h-10 lg:h-12" />
                        <span className="truncate text-xs font-semibold text-stone-500 sm:text-sm">
                            Vendor Central
                        </span>
                    </Link>
                    <div className="ml-auto flex items-center gap-1 sm:gap-2">
                        <VendorNotificationBell />
                        <Link
                            href={route('profile.edit')}
                            className="rounded-full p-2 text-stone-500 hover:bg-stone-100"
                            aria-label="Settings"
                        >
                            <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={1.5}
                                    d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"
                                />
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </Link>
                        <div
                            className={`flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold text-white sm:h-9 sm:w-9 ${vendorBrown}`}
                            title={user.name}
                        >
                            {initials}
                        </div>
                    </div>
                </div>
            </header>

            <div className="flex">
                {/* Sidebar */}
                <aside
                    className={`fixed inset-y-0 left-0 z-20 mt-14 w-56 transform border-r border-stone-200/80 bg-white transition-transform lg:static lg:mt-0 lg:translate-x-0 ${
                        mobileNavOpen ? 'translate-x-0' : '-translate-x-full'
                    }`}
                >
                    <nav className="flex flex-col gap-1 p-4">
                        {navItems.map((item) => (
                            <SidebarLink
                                key={item.href}
                                item={item}
                                active={isActive(item.href)}
                                onNavigate={closeMobileNav}
                            />
                        ))}
                        <div className="mt-6 border-t border-stone-100 pt-4">
                            <Link
                                href={route('logout')}
                                method="post"
                                as="button"
                                onClick={closeMobileNav}
                                className="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-stone-600 hover:bg-stone-100"
                            >
                                Log out
                            </Link>
                            <Link
                                href="/"
                                onClick={closeMobileNav}
                                className="mt-1 flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-stone-500 hover:bg-stone-100"
                            >
                                View marketplace
                            </Link>
                        </div>
                    </nav>
                </aside>

                {mobileNavOpen && (
                    <button
                        type="button"
                        className="fixed inset-0 z-10 bg-black/20 lg:hidden"
                        onClick={closeMobileNav}
                        aria-label="Close navigation"
                    />
                )}

                <main className="min-h-[calc(100vh-3.5rem)] flex-1 p-3 pb-24 sm:p-6 sm:pb-24 lg:p-8 lg:pb-8">
                    {flash?.success && (
                        <div className="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">
                            {flash.success}
                        </div>
                    )}
                    {flash?.error && (
                        <div className="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900" role="alert">
                            {flash.error}
                        </div>
                    )}
                    {flash?.vendorApplicationSubmitted && (
                        <div
                            className="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"
                            role="status"
                        >
                            Welcome to Vendor Central! Your application is under review — you can start adding
                            products while you wait.
                        </div>
                    )}
                    {title && (
                        <div className="sr-only">
                            <h1>{title}</h1>
                        </div>
                    )}
                    {children}
                </main>
            </div>

            {/* Mobile bottom tab bar */}
            <nav
                className="fixed inset-x-0 bottom-0 z-30 border-t border-stone-200/80 bg-white pb-[env(safe-area-inset-bottom)] lg:hidden"
                aria-label="Primary"
            >
                <div className="flex items-stretch">
                    {navItems.map((item) => (
                        <BottomTabLink key={item.href} item={item} active={isActive(item.href)} />
                    ))}
                </div>
            </nav>
        </div>
    );
}
