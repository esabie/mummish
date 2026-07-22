import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';

function statusBadgeClass(status) {
    switch (status) {
        case 'paid':
            return 'bg-emerald-100 text-emerald-800';
        case 'pending':
        case 'pending_payment':
            return 'bg-amber-100 text-amber-900';
        case 'failed':
        case 'cancelled':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-stone-100 text-stone-700';
    }
}

function formatPlacedAt(iso) {
    if (!iso) {
        return '—';
    }

    try {
        return new Date(iso).toLocaleString(undefined, {
            dateStyle: 'medium',
            timeStyle: 'short',
        });
    } catch {
        return iso;
    }
}

function QuickLink({ href, title, body }) {
    return (
        <Link
            href={href}
            className="group rounded-2xl border border-stone-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-[#5c4d3d]/35 hover:shadow-md"
        >
            <h3 className="text-sm font-bold text-stone-900 group-hover:text-[#5c4d3d]">{title}</h3>
            <p className="mt-1.5 text-sm leading-relaxed text-stone-600">{body}</p>
        </Link>
    );
}

export default function Dashboard({ recentOrders = [], orderCount = 0 }) {
    const { auth, flash } = usePage().props;
    const firstName = (auth?.user?.name || 'there').trim().split(/\s+/)[0] || 'there';

    return (
        <AuthenticatedLayout
            user={auth.user}
            breadcrumbs={[
                { label: 'Home', href: '/' },
                { label: 'Dashboard' },
            ]}
            header={
                <div>
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">Your account</h2>
                    <p className="mt-1 text-sm text-stone-500">Welcome back, {firstName}</p>
                </div>
            }
        >
            <Head title="Dashboard" />

            <div className="py-10">
                <div className="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                    {flash?.vendorApplicationSubmitted ? (
                        <div
                            className="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"
                            role="status"
                        >
                            Your vendor application was submitted. We&apos;ll review your shop and text you with next
                            steps.
                        </div>
                    ) : null}

                    <section className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <QuickLink
                            href={route('shop.index')}
                            title="Browse the shop"
                            body="Find clothes, toys, and gear from sellers across Ghana."
                        />
                        <QuickLink
                            href={route('orders.track')}
                            title="Track an order"
                            body="Enter your order number and email to see delivery status."
                        />
                        <QuickLink
                            href={route('profile.edit')}
                            title="Account & orders"
                            body="Update your details and view your full order history."
                        />
                    </section>

                    <section className="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
                        <div className="flex flex-wrap items-end justify-between gap-3 border-b border-stone-100 px-5 py-4 sm:px-6">
                            <div>
                                <h3 className="text-lg font-semibold text-stone-900">Recent orders</h3>
                                <p className="mt-1 text-sm text-stone-600">
                                    {orderCount > 0
                                        ? `${orderCount} order${orderCount === 1 ? '' : 's'} on this account`
                                        : 'Purchases linked to this login show up here'}
                                </p>
                            </div>
                            <Link
                                href={route('profile.edit')}
                                className="text-sm font-semibold text-[#5c4d3d] hover:underline"
                            >
                                View all
                            </Link>
                        </div>

                        {recentOrders.length === 0 ? (
                            <div className="px-5 py-12 text-center sm:px-6">
                                <p className="text-sm font-medium text-stone-800">No orders yet</p>
                                <p className="mx-auto mt-1 max-w-md text-sm text-stone-500">
                                    When you check out while logged in — or complete a guest checkout that creates your
                                    account — your orders will appear here.
                                </p>
                                <Link
                                    href={route('shop.index')}
                                    className="mt-5 inline-flex rounded-full bg-[#5c4d3d] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#4a3e32]"
                                >
                                    Start shopping
                                </Link>
                            </div>
                        ) : (
                            <ul className="divide-y divide-stone-100">
                                {recentOrders.map((order) => (
                                    <li
                                        key={order.id}
                                        className="flex flex-wrap items-center justify-between gap-3 px-5 py-4 sm:px-6"
                                    >
                                        <div className="min-w-0">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <p className="text-sm font-bold text-stone-900">{order.order_number}</p>
                                                <span
                                                    className={`rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide ${statusBadgeClass(order.payment_status)}`}
                                                >
                                                    {order.payment_status_label}
                                                </span>
                                            </div>
                                            <p className="mt-1 text-xs text-stone-500">
                                                {formatPlacedAt(order.placed_at)}
                                                {order.shipping_city
                                                    ? ` · ${order.shipping_city}${order.shipping_region ? `, ${order.shipping_region}` : ''}`
                                                    : ''}
                                                {` · ${order.item_count} item${order.item_count === 1 ? '' : 's'}`}
                                            </p>
                                        </div>
                                        <div className="flex flex-wrap items-center gap-4">
                                            <p className="text-sm font-bold text-stone-900">{order.formatted_total}</p>
                                            {order.track_url ? (
                                                <Link
                                                    href={order.track_url}
                                                    className="text-sm font-semibold text-[#5c4d3d] hover:underline"
                                                >
                                                    Track
                                                </Link>
                                            ) : null}
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
