import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { LogoIcon } from '@/Components/LogoMark';

function NavIcon({ name, className = 'h-5 w-5' }) {
    const icons = {
        dashboard: (
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"
            />
        ),
        schedule: (
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"
            />
        ),
        settings: (
            <>
                <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"
                />
                <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </>
        ),
    };

    return (
        <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5" aria-hidden>
            {icons[name]}
        </svg>
    );
}

function SidebarLink({ item, active, onNavigate }) {
    return (
        <Link
            href={item.href}
            onClick={onNavigate}
            className={`flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition ${
                active
                    ? 'bg-[#5c4d3d]/10 text-[#5c4d3d]'
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
            href={item.href}
            className={`flex min-w-0 flex-1 flex-col items-center gap-0.5 px-1 py-2 text-[10px] font-semibold transition ${
                active ? 'text-[#5c4d3d]' : 'text-stone-500'
            }`}
        >
            <NavIcon name={item.icon} className="h-5 w-5" />
            <span className="truncate">{item.label}</span>
        </Link>
    );
}

export default function HealthProfessionalLayout({ title, professional = null, children }) {
    const { auth, flash } = usePage().props;
    const user = auth?.user;
    const [mobileNavOpen, setMobileNavOpen] = useState(false);
    const current = route().current() ?? '';

    const settingsHref = professional?.id
        ? route('health-professionals.edit', professional.id)
        : route('health-professionals.dashboard');

    const navItems = [
        { label: 'Dashboard', href: route('health-professionals.dashboard'), routeName: 'health-professionals.dashboard', icon: 'dashboard' },
        { label: 'Schedule', href: route('health-professionals.schedule'), routeName: 'health-professionals.schedule', icon: 'schedule' },
        { label: 'Settings', href: settingsHref, routeName: 'health-professionals.edit', icon: 'settings' },
    ];

    const closeMobileNav = () => setMobileNavOpen(false);

    const isActive = (routeName) => {
        if (routeName === 'health-professionals.dashboard') {
            return current === 'health-professionals.dashboard';
        }
        return current === routeName;
    };

    const displayName = professional?.name || user?.name || 'Professional';
    const initials = displayName
        .split(' ')
        .map((n) => n[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();

    return (
        <div className="min-h-screen bg-[#f5f4f2] text-stone-900">
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
                    <Link href={route('health-professionals.dashboard')} className="flex min-w-0 items-center gap-2">
                        <LogoIcon className="h-8 w-auto shrink-0 sm:h-10 lg:h-12" />
                        <span className="hidden truncate text-sm font-semibold text-stone-500 sm:inline">
                            Health Professional
                        </span>
                    </Link>
                    <div className="ml-auto flex items-center gap-2">
                        {professional?.image_url ? (
                            <img
                                src={professional.image_url}
                                alt=""
                                className="h-8 w-8 rounded-full object-cover ring-1 ring-stone-200 sm:h-9 sm:w-9"
                            />
                        ) : (
                            <div
                                className="flex h-8 w-8 items-center justify-center rounded-full bg-[#5c4d3d] text-xs font-bold text-white sm:h-9 sm:w-9"
                                title={displayName}
                            >
                                {initials}
                            </div>
                        )}
                    </div>
                </div>
            </header>

            <div className="flex">
                <aside
                    className={`fixed inset-y-0 left-0 z-20 mt-14 w-56 transform border-r border-stone-200/80 bg-white transition-transform lg:static lg:mt-0 lg:translate-x-0 ${
                        mobileNavOpen ? 'translate-x-0' : '-translate-x-full'
                    }`}
                >
                    <nav className="flex flex-col gap-1 p-4">
                        {navItems.map((item) => (
                            <SidebarLink
                                key={item.routeName}
                                item={item}
                                active={isActive(item.routeName)}
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
                                href={route('health-services.index')}
                                onClick={closeMobileNav}
                                className="mt-1 flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-stone-500 hover:bg-stone-100"
                            >
                                Browse Health Services
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

                <main className="min-h-[calc(100vh-3.5rem)] min-w-0 flex-1 overflow-x-hidden p-3 pb-24 sm:p-6 sm:pb-24 lg:p-8 lg:pb-8">
                    {flash?.success && (
                        <div
                            className="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"
                            role="status"
                        >
                            {flash.success}
                        </div>
                    )}
                    {flash?.error && (
                        <div
                            className="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900"
                            role="alert"
                        >
                            {flash.error}
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

            <nav
                className="fixed inset-x-0 bottom-0 z-30 border-t border-stone-200/80 bg-white pb-[env(safe-area-inset-bottom)] lg:hidden"
                aria-label="Primary"
            >
                <div className="flex items-stretch">
                    {navItems.map((item) => (
                        <BottomTabLink key={item.routeName} item={item} active={isActive(item.routeName)} />
                    ))}
                </div>
            </nav>
        </div>
    );
}
