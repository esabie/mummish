import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

function timeAgo(iso) {
    if (!iso) {
        return '';
    }

    const seconds = Math.max(0, Math.floor((Date.now() - new Date(iso).getTime()) / 1000));

    if (seconds < 60) {
        return 'Just now';
    }

    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) {
        return `${minutes}m ago`;
    }

    const hours = Math.floor(minutes / 60);
    if (hours < 24) {
        return `${hours}h ago`;
    }

    const days = Math.floor(hours / 24);
    if (days < 7) {
        return `${days}d ago`;
    }

    return new Date(iso).toLocaleDateString();
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

export default function VendorNotificationBell() {
    const { vendorNotifications } = usePage().props;
    const [open, setOpen] = useState(false);
    const [loading, setLoading] = useState(false);
    const [items, setItems] = useState([]);
    const [unreadCount, setUnreadCount] = useState(vendorNotifications?.unread_count ?? 0);
    const panelRef = useRef(null);

    useEffect(() => {
        setUnreadCount(vendorNotifications?.unread_count ?? 0);
    }, [vendorNotifications?.unread_count]);

    useEffect(() => {
        if (!open) {
            return undefined;
        }

        const onPointerDown = (event) => {
            if (panelRef.current && !panelRef.current.contains(event.target)) {
                setOpen(false);
            }
        };

        const onKeyDown = (event) => {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        };

        document.addEventListener('mousedown', onPointerDown);
        document.addEventListener('keydown', onKeyDown);

        return () => {
            document.removeEventListener('mousedown', onPointerDown);
            document.removeEventListener('keydown', onKeyDown);
        };
    }, [open]);

    const fetchNotifications = async () => {
        setLoading(true);

        try {
            const response = await fetch(route('vendor.notifications.index'), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('Failed to load notifications');
            }

            const payload = await response.json();
            setItems(payload.notifications ?? []);
            setUnreadCount(payload.unread_count ?? 0);
        } catch {
            setItems([]);
        } finally {
            setLoading(false);
        }
    };

    const toggleOpen = () => {
        const next = !open;
        setOpen(next);

        if (next) {
            fetchNotifications();
        }
    };

    const markAsRead = async (notification) => {
        if (!notification.read_at) {
            try {
                const response = await fetch(route('vendor.notifications.read', notification.id), {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (response.ok) {
                    const payload = await response.json();
                    setUnreadCount(payload.unread_count ?? 0);
                    setItems((current) =>
                        current.map((item) =>
                            item.id === notification.id
                                ? { ...item, read_at: payload.notification?.read_at ?? new Date().toISOString() }
                                : item,
                        ),
                    );
                }
            } catch {
                // Navigation still proceeds below.
            }
        }

        setOpen(false);

        if (notification.url) {
            router.visit(notification.url);
        }
    };

    const markAllAsRead = async () => {
        try {
            const response = await fetch(route('vendor.notifications.read-all'), {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (response.ok) {
                setUnreadCount(0);
                setItems((current) =>
                    current.map((item) => ({
                        ...item,
                        read_at: item.read_at ?? new Date().toISOString(),
                    })),
                );
            }
        } catch {
            // Keep current unread state on failure.
        }
    };

    return (
        <div className="relative" ref={panelRef}>
            <button
                type="button"
                className="relative rounded-full p-2 text-stone-500 hover:bg-stone-100"
                aria-label="Notifications"
                aria-expanded={open}
                aria-haspopup="true"
                onClick={toggleOpen}
            >
                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={1.5}
                        d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"
                    />
                </svg>
                {unreadCount > 0 && (
                    <span className="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-orange-500 px-1 text-[10px] font-bold leading-none text-white">
                        {unreadCount > 9 ? '9+' : unreadCount}
                    </span>
                )}
            </button>

            {open && (
                <div className="absolute right-0 z-40 mt-2 w-[22rem] max-w-[calc(100vw-2rem)] overflow-hidden rounded-xl border border-stone-200 bg-white shadow-lg">
                    <div className="flex items-center justify-between border-b border-stone-100 px-4 py-3">
                        <p className="text-sm font-semibold text-stone-900">Notifications</p>
                        {unreadCount > 0 && (
                            <button
                                type="button"
                                onClick={markAllAsRead}
                                className="text-xs font-medium text-[#5c4d3d] hover:underline"
                            >
                                Mark all read
                            </button>
                        )}
                    </div>

                    <div className="max-h-96 overflow-y-auto">
                        {loading && (
                            <p className="px-4 py-8 text-center text-sm text-stone-500">Loading…</p>
                        )}

                        {!loading && items.length === 0 && (
                            <p className="px-4 py-8 text-center text-sm text-stone-500">
                                No notifications yet. New orders and order updates will show up here.
                            </p>
                        )}

                        {!loading &&
                            items.map((notification) => (
                                <button
                                    key={notification.id}
                                    type="button"
                                    onClick={() => markAsRead(notification)}
                                    className={`flex w-full flex-col gap-1 border-b border-stone-50 px-4 py-3 text-left transition hover:bg-stone-50 ${
                                        notification.read_at ? 'bg-white' : 'bg-orange-50/60'
                                    }`}
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <p className="text-sm font-semibold text-stone-900">
                                            {notification.title}
                                        </p>
                                        {!notification.read_at && (
                                            <span className="mt-1 h-2 w-2 shrink-0 rounded-full bg-orange-500" />
                                        )}
                                    </div>
                                    <p className="text-xs leading-relaxed text-stone-600">{notification.body}</p>
                                    <p className="text-[11px] text-stone-400">{timeAgo(notification.created_at)}</p>
                                </button>
                            ))}
                    </div>

                    <div className="border-t border-stone-100 px-4 py-2">
                        <Link
                            href={route('vendor.orders.index')}
                            className="text-xs font-medium text-[#5c4d3d] hover:underline"
                            onClick={() => setOpen(false)}
                        >
                            View orders
                        </Link>
                    </div>
                </div>
            )}
        </div>
    );
}
