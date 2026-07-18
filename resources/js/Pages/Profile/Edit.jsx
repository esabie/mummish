import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';
import { Head, Link } from '@inertiajs/react';

function statusBadgeClass(status) {
    switch (status) {
        case 'paid':
        case 'approved':
            return 'bg-emerald-100 text-emerald-800';
        case 'pending':
        case 'pending_payment':
            return 'bg-amber-100 text-amber-900';
        case 'failed':
        case 'cancelled':
        case 'rejected':
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

function OrderHistory({ orders }) {
    return (
        <section>
            <header className="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 className="text-lg font-semibold text-stone-900">Order history</h2>
                    <p className="mt-1 text-sm text-stone-600">Purchases linked to this account.</p>
                </div>
                <Link href={route('shop.index')} className="text-sm font-semibold text-[#5c4d3d] hover:underline">
                    Continue shopping
                </Link>
            </header>

            {orders.length === 0 ? (
                <div className="mt-5 rounded-xl border border-dashed border-stone-300 bg-stone-50 px-5 py-10 text-center">
                    <p className="text-sm font-medium text-stone-800">No orders yet</p>
                    <p className="mt-1 text-sm text-stone-500">When you check out while logged in, your orders will show up here.</p>
                    <Link
                        href={route('shop.index')}
                        className="mt-4 inline-flex rounded-full bg-[#5c4d3d] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#4a3e32]"
                    >
                        Browse the shop
                    </Link>
                </div>
            ) : (
                <ul className="mt-5 space-y-4">
                    {orders.map((order) => (
                        <li
                            key={order.id}
                            className="overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm"
                        >
                            <div className="flex flex-wrap items-start justify-between gap-3 border-b border-stone-100 bg-stone-50/80 px-4 py-3 sm:px-5">
                                <div>
                                    <div className="flex flex-wrap items-center gap-2">
                                        <p className="text-sm font-bold text-stone-900">{order.order_number}</p>
                                        <span
                                            className={`rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide ${statusBadgeClass(order.payment_status)}`}
                                        >
                                            {order.payment_status_label}
                                        </span>
                                        {order.status !== order.payment_status ? (
                                            <span
                                                className={`rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide ${statusBadgeClass(order.status)}`}
                                            >
                                                {order.status_label}
                                            </span>
                                        ) : null}
                                    </div>
                                    <p className="mt-1 text-xs text-stone-500">
                                        {formatPlacedAt(order.placed_at)}
                                        {order.shipping_city
                                            ? ` · ${order.shipping_city}${order.shipping_region ? `, ${order.shipping_region}` : ''}`
                                            : ''}
                                    </p>
                                </div>
                                <div className="text-right">
                                    <p className="text-sm font-bold text-stone-900">{order.formatted_total}</p>
                                    <p className="mt-0.5 text-xs text-stone-500">
                                        {order.item_count} {order.item_count === 1 ? 'item' : 'items'}
                                    </p>
                                </div>
                            </div>

                            <div className="px-4 py-4 sm:px-5">
                                <ul className="space-y-3">
                                    {order.items.map((item, index) => (
                                        <li key={`${order.id}-${index}`} className="flex items-center gap-3">
                                            <div className="h-12 w-12 shrink-0 overflow-hidden rounded-lg border border-stone-200 bg-stone-50">
                                                {item.image ? (
                                                    <img
                                                        src={item.image}
                                                        alt=""
                                                        className="h-full w-full object-cover"
                                                    />
                                                ) : null}
                                            </div>
                                            <div className="min-w-0 flex-1">
                                                <p className="truncate text-sm font-medium text-stone-900">{item.title}</p>
                                                <p className="text-xs text-stone-500">Qty {item.quantity}</p>
                                            </div>
                                            <p className="text-sm font-semibold text-stone-800">
                                                {item.formatted_line_total}
                                            </p>
                                        </li>
                                    ))}
                                </ul>

                                {order.receipt_url || order.track_url ? (
                                    <div className="mt-4 flex flex-wrap gap-4">
                                        {order.track_url ? (
                                            <Link
                                                href={order.track_url}
                                                className="text-sm font-semibold text-[#5c4d3d] hover:underline"
                                            >
                                                Track order
                                            </Link>
                                        ) : null}
                                        {order.receipt_url ? (
                                            <Link
                                                href={order.receipt_url}
                                                className="text-sm font-semibold text-[#5c4d3d] hover:underline"
                                            >
                                                View receipt
                                            </Link>
                                        ) : null}
                                    </div>
                                ) : null}
                            </div>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}

function VendorShopSummary({ shop, isVendor }) {
    if (!shop && !isVendor) {
        return (
            <section className="rounded-xl border border-stone-200 bg-gradient-to-br from-[#f7f3ee] via-white to-white p-5 shadow-sm sm:p-6">
                <h2 className="text-lg font-semibold text-stone-900">Sell on Mummish</h2>
                <p className="mt-1 text-sm text-stone-600">
                    Turn pre-loved kids&apos; essentials into income. Set up a shop and reach parents across Ghana.
                </p>
                <Link
                    href={route('vendor.signup')}
                    className="mt-4 inline-flex rounded-full bg-[#5c4d3d] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#4a3e32]"
                >
                    Apply to sell
                </Link>
            </section>
        );
    }

    const initial = (shop?.shop_name || 'S').trim().charAt(0).toUpperCase();

    return (
        <section className="overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm">
            <div className="border-b border-stone-100 bg-gradient-to-br from-[#f7f3ee] via-white to-white px-5 py-5 sm:px-6">
                <div className="flex flex-wrap items-start gap-4">
                    {shop?.logo ? (
                        <div className="h-16 w-16 shrink-0 overflow-hidden rounded-2xl ring-1 ring-stone-200">
                            <img src={shop.logo} alt="" className="h-full w-full object-cover" />
                        </div>
                    ) : (
                        <div className="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-[#5c4d3d] text-2xl font-bold text-white">
                            {initial}
                        </div>
                    )}
                    <div className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-center gap-2">
                            <h2 className="text-lg font-semibold text-stone-900">
                                {shop?.shop_name || 'Your shop'}
                            </h2>
                            {shop?.status_label ? (
                                <span
                                    className={`rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide ${statusBadgeClass(shop.status)}`}
                                >
                                    {shop.status_label}
                                </span>
                            ) : null}
                        </div>
                        <p className="mt-1 text-sm text-stone-600">
                            {shop?.category_label ? `${shop.category_label} · ` : ''}
                            {shop?.product_count ?? 0}{' '}
                            {(shop?.product_count ?? 0) === 1 ? 'product' : 'products'} listed
                        </p>
                        {shop?.phone ? <p className="mt-1 text-xs text-stone-500">Shop phone: {shop.phone}</p> : null}
                    </div>
                </div>
            </div>

            <div className="space-y-4 px-5 py-5 sm:px-6">
                {!shop?.has_application ? (
                    <p className="text-sm text-amber-900">
                        Your account is marked as a vendor, but no shop application was found. Finish applying to start
                        listing.
                    </p>
                ) : null}

                {shop?.status === 'pending' && shop?.listing_limit?.max != null ? (
                    <p className="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-950">
                        While pending approval you can list up to {shop.listing_limit.max} products (
                        {shop.listing_limit.current} used
                        {shop.listing_limit.remaining != null
                            ? `, ${shop.listing_limit.remaining} remaining`
                            : ''}
                        ).
                    </p>
                ) : null}

                {shop?.status === 'approved' ? (
                    <p className="text-sm text-emerald-800">
                        Your shop is approved. Manage inventory and fulfill orders from Vendor Central.
                    </p>
                ) : null}

                {shop?.status === 'rejected' && shop?.rejection_reason ? (
                    <p className="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-900">
                        <span className="font-medium">Rejection reason:</span> {shop.rejection_reason}
                    </p>
                ) : null}

                <div className="flex flex-wrap gap-2">
                    {shop?.has_application ? (
                        <>
                            <Link
                                href={route('vendor.inventory.index')}
                                className="inline-flex rounded-full bg-[#5c4d3d] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#4a3e32]"
                            >
                                Manage inventory
                            </Link>
                            <Link
                                href={route('vendor.orders.index')}
                                className="inline-flex rounded-full border border-stone-300 bg-white px-4 py-2 text-sm font-semibold text-stone-800 transition hover:bg-stone-50"
                            >
                                View orders
                            </Link>
                            {shop.storefront_url ? (
                                <a
                                    href={shop.storefront_url}
                                    className="inline-flex rounded-full border border-stone-300 bg-white px-4 py-2 text-sm font-semibold text-stone-800 transition hover:bg-stone-50"
                                >
                                    View storefront
                                </a>
                            ) : null}
                        </>
                    ) : (
                        <Link
                            href={route('vendor.signup')}
                            className="inline-flex rounded-full bg-[#5c4d3d] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#4a3e32]"
                        >
                            Complete application
                        </Link>
                    )}
                </div>
            </div>
        </section>
    );
}

export default function Edit({ auth, mustVerifyEmail, status, orders = [], shop = null }) {
    const isVendor = auth.user?.role === 'vendor' || Boolean(shop?.has_application);

    return (
        <AuthenticatedLayout
            user={auth.user}
            breadcrumbs={[
                { label: 'Home', href: '/' },
                { label: 'Dashboard', href: route('dashboard') },
                { label: 'Account' },
            ]}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">My account</h2>}
        >
            <Head title="My account" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="rounded-xl border border-stone-200 bg-white p-5 shadow-sm sm:p-6">
                        <p className="text-xs font-bold uppercase tracking-[0.16em] text-stone-500">Signed in as</p>
                        <h1 className="mt-1 text-2xl font-bold text-stone-900">{auth.user.name}</h1>
                        <p className="mt-1 text-sm text-stone-600">{auth.user.email}</p>
                    </div>

                    <VendorShopSummary shop={shop} isVendor={isVendor} />

                    <div className="rounded-xl border border-stone-200 bg-white p-5 shadow-sm sm:p-6">
                        <OrderHistory orders={orders} />
                    </div>

                    <div className="grid gap-6 lg:grid-cols-2">
                        <div className="rounded-xl border border-stone-200 bg-white p-5 shadow-sm sm:p-6">
                            <UpdateProfileInformationForm
                                mustVerifyEmail={mustVerifyEmail}
                                status={status}
                                className="max-w-xl"
                            />
                        </div>

                        <div className="rounded-xl border border-stone-200 bg-white p-5 shadow-sm sm:p-6">
                            <UpdatePasswordForm className="max-w-xl" />
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
