import VendorLayout from '@/Layouts/VendorLayout';
import { Head, Link } from '@inertiajs/react';

const vendorBrown = 'bg-[#5c4d3d] text-white';

function PayoutHero({ payout, gross, orderCount, commissionPercent }) {
    return (
        <div className="rounded-2xl border border-emerald-200/80 bg-gradient-to-br from-emerald-50 to-white p-4 sm:p-6 lg:p-8">
            <p className="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-800/80">Your earnings</p>
            <p className="mt-2 text-3xl font-bold tracking-tight text-emerald-900 sm:text-4xl">{payout}</p>
            <p className="mt-2 text-sm text-stone-600">
                From {gross} in sales across {orderCount} {orderCount === 1 ? 'order' : 'orders'}
            </p>
            <p className="mt-3 text-xs text-stone-500">
                After the {commissionPercent}% platform fee.{' '}
                <Link href={route('billing')} className="font-medium text-[#5c4d3d] hover:underline">
                    How payouts work
                </Link>
            </p>
        </div>
    );
}

function BalanceCard({ title, description, payout, gross, orderCount }) {
    return (
        <div className="rounded-xl border border-stone-200 p-4">
            <p className="text-sm font-semibold text-stone-900">{title}</p>
            <p className="mt-1 text-xs text-stone-500">{description}</p>
            <p className="mt-4 text-2xl font-bold text-emerald-800">{payout}</p>
            <p className="mt-1 text-xs text-stone-500">
                {orderCount} {orderCount === 1 ? 'order' : 'orders'} · {gross} sold
            </p>
        </div>
    );
}

export default function VendorDashboard({
    shopName,
    applicationStatus,
    applicationStatusLabel,
    rejectionReason,
    listingLimit,
    stats,
    earnings,
}) {
    const cards = [
        { label: 'Total products', value: stats.total_products, href: route('vendor.inventory.index') },
        { label: 'Active', value: stats.active_products, href: route('vendor.inventory.index', { tab: 'active' }) },
        { label: 'Drafts', value: stats.draft_products, href: route('vendor.inventory.index', { tab: 'draft' }) },
        { label: 'Low stock', value: stats.low_stock_products, href: route('vendor.inventory.index', { tab: 'low_stock' }) },
    ];

    const hasSales = earnings.totals.gross_cents > 0;

    return (
        <VendorLayout title="Dashboard">
            <Head title="Vendor Dashboard" />

            <div className="mb-6 sm:mb-8">
                <h1 className="text-xl font-bold text-stone-900 sm:text-2xl">Dashboard</h1>
                <p className="mt-1 text-sm text-stone-600 sm:text-base">Welcome back to {shopName}.</p>
            </div>

            {applicationStatus === 'pending' && (
                <div className="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    Your seller application is <span className="font-semibold">{applicationStatusLabel}</span>.
                    {listingLimit?.max != null && (
                        <>
                            {' '}
                            You can list up to {listingLimit.max} products while you wait ({listingLimit.remaining}{' '}
                            remaining).
                        </>
                    )}
                </div>
            )}

            {applicationStatus === 'rejected' && rejectionReason && (
                <div className="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                    Application not approved: {rejectionReason}
                </div>
            )}

            {applicationStatus === 'closed' && (
                <div className="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                    Your shop has been closed and your product listings have been removed from the marketplace.
                    {rejectionReason ? <> {rejectionReason}</> : null}
                </div>
            )}

            <section className="mb-8 rounded-2xl border border-stone-200/90 bg-white p-4 shadow-sm sm:p-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 className="text-lg font-bold text-stone-900">Your earnings</h2>
                        <p className="mt-1 text-sm text-stone-600">
                            What you&apos;ve earned from paid sales, and what&apos;s on the way.
                        </p>
                    </div>
                </div>

                {hasSales ? (
                    <div className="mt-6 space-y-6">
                        <PayoutHero
                            payout={earnings.totals.formatted_payout}
                            gross={earnings.totals.formatted_gross}
                            orderCount={earnings.totals.order_count}
                            commissionPercent={earnings.commission_percent}
                        />

                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <BalanceCard
                                title="In escrow"
                                description="Paid orders waiting for the buyer to confirm delivery."
                                payout={earnings.escrow.formatted_payout}
                                gross={earnings.escrow.formatted_gross}
                                orderCount={earnings.escrow.order_count}
                            />
                            <BalanceCard
                                title="Available in wallet"
                                description="Ready for payout after delivery was confirmed."
                                payout={earnings.wallet_due.formatted_payout}
                                gross={earnings.wallet_due.formatted_gross}
                                orderCount={earnings.wallet_due.order_count}
                            />
                            <BalanceCard
                                title="Paid out"
                                description="Already settled to your MoMo or bank account."
                                payout={earnings.wallet_settled.formatted_payout}
                                gross={earnings.wallet_settled.formatted_gross}
                                orderCount={earnings.wallet_settled.order_count}
                            />
                        </div>

                        {earnings.recent_sales.length > 0 && (
                            <div>
                                <h3 className="text-sm font-semibold text-stone-900">Recent sales</h3>

                                {/* Mobile sale cards */}
                                <ul className="mt-3 space-y-3 sm:hidden">
                                    {earnings.recent_sales.map((sale) => (
                                        <li
                                            key={`${sale.order_number}-${sale.product_title}`}
                                            className="rounded-xl border border-stone-200 bg-white p-4"
                                        >
                                            <div className="flex items-start justify-between gap-3">
                                                <div className="min-w-0">
                                                    <p className="text-sm font-semibold text-stone-900">
                                                        {sale.order_number}
                                                    </p>
                                                    <p className="mt-0.5 truncate text-sm text-stone-600">
                                                        {sale.product_title}
                                                    </p>
                                                </div>
                                                <span
                                                    className={`shrink-0 inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${
                                                        sale.status === 'settled'
                                                            ? 'bg-stone-100 text-stone-700'
                                                            : sale.status === 'released'
                                                              ? 'bg-emerald-100 text-emerald-800'
                                                              : 'bg-amber-100 text-amber-900'
                                                    }`}
                                                >
                                                    {sale.status_label}
                                                </span>
                                            </div>
                                            <p className="mt-3 text-base font-semibold text-emerald-800">
                                                {sale.formatted_payout}
                                            </p>
                                            <p className="mt-0.5 text-xs text-stone-500">
                                                from {sale.formatted_gross} sale
                                            </p>
                                        </li>
                                    ))}
                                </ul>

                                {/* Desktop / tablet table */}
                                <div className="mt-3 hidden overflow-hidden rounded-xl border border-stone-200 sm:block">
                                    <table className="min-w-full divide-y divide-stone-200 text-sm">
                                        <thead className="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">
                                            <tr>
                                                <th className="px-4 py-3">Order</th>
                                                <th className="px-4 py-3">Item</th>
                                                <th className="px-4 py-3">Sale price</th>
                                                <th className="px-4 py-3">You receive</th>
                                                <th className="px-4 py-3">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-stone-100 bg-white">
                                            {earnings.recent_sales.map((sale) => (
                                                <tr key={`${sale.order_number}-${sale.product_title}`}>
                                                    <td className="px-4 py-3 font-medium text-stone-900">
                                                        {sale.order_number}
                                                    </td>
                                                    <td className="px-4 py-3 text-stone-600">{sale.product_title}</td>
                                                    <td className="px-4 py-3 text-stone-500">{sale.formatted_gross}</td>
                                                    <td className="px-4 py-3">
                                                        <p className="font-semibold text-emerald-800">
                                                            {sale.formatted_payout}
                                                        </p>
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <span
                                                            className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${
                                                                sale.status === 'settled'
                                                                    ? 'bg-stone-100 text-stone-700'
                                                                    : sale.status === 'released'
                                                                      ? 'bg-emerald-100 text-emerald-800'
                                                                      : 'bg-amber-100 text-amber-900'
                                                            }`}
                                                        >
                                                            {sale.status_label}
                                                        </span>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        )}
                    </div>
                ) : (
                    <div className="mt-6 rounded-xl border border-dashed border-stone-300 bg-stone-50/80 px-5 py-8 text-center">
                        <p className="text-sm font-medium text-stone-800">No paid sales yet</p>
                        <p className="mt-1 text-sm text-stone-600">
                            When orders come in, your earnings will show up here after the {earnings.commission_percent}%
                            platform fee.
                        </p>
                    </div>
                )}
            </section>

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {cards.map((card) => (
                    <Link
                        key={card.label}
                        href={card.href}
                        className="rounded-xl border border-stone-200/80 bg-white p-5 shadow-sm transition hover:border-stone-300 hover:shadow"
                    >
                        <p className="text-sm font-medium text-stone-500">{card.label}</p>
                        <p className="mt-2 text-3xl font-bold text-stone-900">{card.value}</p>
                    </Link>
                ))}
            </div>

            <div className="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                <Link
                    href={route('vendor.inventory.index')}
                    className={`inline-flex w-full items-center justify-center rounded-lg px-5 py-2.5 text-sm font-semibold shadow-sm sm:w-auto ${vendorBrown} hover:bg-[#4a3e32]`}
                >
                    Manage inventory →
                </Link>
                <Link
                    href={route('vendor.orders.index')}
                    className="inline-flex w-full items-center justify-center rounded-lg border border-stone-300 bg-white px-5 py-2.5 text-sm font-semibold text-stone-700 shadow-sm transition hover:bg-stone-50 sm:w-auto"
                >
                    View orders
                </Link>
            </div>
        </VendorLayout>
    );
}
