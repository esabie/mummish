import VendorLayout from '@/Layouts/VendorLayout';
import { Head, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

function formatDate(iso) {
    if (!iso) return '—';
    try {
        return new Date(iso).toLocaleString(undefined, {
            dateStyle: 'medium',
            timeStyle: 'short',
        });
    } catch {
        return iso;
    }
}

function OrderCard({ order, onFulfill, fulfillingId }) {
    const isFulfilling = fulfillingId === order.order_id;

    return (
        <article
            className={`overflow-hidden rounded-2xl border bg-white shadow-sm ${
                order.is_ready_for_pickup ? 'border-stone-200' : 'border-amber-200 ring-1 ring-amber-100'
            }`}
        >
            <header className="flex flex-wrap items-start justify-between gap-4 border-b border-stone-100 bg-stone-50/80 px-5 py-4">
                <div>
                    <div className="flex flex-wrap items-center gap-2">
                        <p className="text-sm font-bold text-stone-900">{order.order_number}</p>
                        <span
                            className={`rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide ${
                                order.is_ready_for_pickup
                                    ? 'bg-stone-200 text-stone-700'
                                    : 'bg-amber-100 text-amber-900'
                            }`}
                        >
                            {order.is_ready_for_pickup ? 'Ready for Pickup' : 'New'}
                        </span>
                    </div>
                    <p className="mt-1 text-xs text-stone-500">
                        Placed {formatDate(order.placed_at)}
                        {order.is_ready_for_pickup && order.ready_for_pickup_at
                            ? ` · Ready ${formatDate(order.ready_for_pickup_at)}`
                            : ''}
                    </p>
                </div>
                <div className="text-right">
                    <p className="text-sm font-bold text-stone-900">{order.formatted_total}</p>
                    <p className="mt-1 text-xs text-stone-500">
                        {order.item_count} item{order.item_count === 1 ? '' : 's'}
                    </p>
                </div>
            </header>

            <div className="grid gap-4 px-5 py-4 sm:grid-cols-[1fr_auto]">
                <div className="text-sm text-stone-600">
                    <p className="font-semibold text-stone-900">Order prep only</p>
                    <p className="mt-2 text-xs text-stone-500">
                        Mummish handles pickup, warehouse sorting/repackaging, and customer delivery.
                    </p>
                    {order.status_label ? (
                        <p className="mt-1 text-xs font-medium text-stone-600">Pipeline status: {order.status_label}</p>
                    ) : null}
                </div>
                {!order.is_ready_for_pickup && (
                    <button
                        type="button"
                        onClick={() => onFulfill(order.order_id)}
                        disabled={isFulfilling}
                        className="h-fit shrink-0 rounded-xl bg-stone-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-stone-800 disabled:opacity-50"
                    >
                        {isFulfilling ? 'Saving…' : 'Ready for pickup'}
                    </button>
                )}
            </div>

            <ul className="divide-y divide-stone-100 border-t border-stone-100">
                {order.items.map((item, index) => (
                    <li key={`${item.title}-${index}`} className="flex gap-4 px-5 py-4">
                        <div className="h-14 w-14 shrink-0 overflow-hidden rounded-lg border border-stone-200 bg-stone-50">
                            {item.image ? (
                                <img src={item.image} alt="" className="h-full w-full object-cover" />
                            ) : null}
                        </div>
                        <div className="min-w-0 flex-1">
                            <p className="font-semibold text-stone-900">{item.title}</p>
                            {item.brand ? <p className="text-xs text-stone-500">{item.brand}</p> : null}
                            {item.attributes ? (
                                <p className="mt-0.5 text-xs text-stone-500">{item.attributes}</p>
                            ) : null}
                            <p className="mt-1 text-sm text-stone-600">Qty {item.quantity}</p>
                        </div>
                        <p className="text-sm font-semibold text-stone-900">{item.formatted_line_total}</p>
                    </li>
                ))}
            </ul>
        </article>
    );
}

export default function VendorOrdersIndex({ shopName, orders, counts }) {
    const { flash } = usePage().props;
    const [tab, setTab] = useState('new');
    const [fulfillingId, setFulfillingId] = useState(null);

    const filteredOrders = useMemo(() => {
        if (tab === 'ready') {
            return orders.filter((order) => order.is_ready_for_pickup);
        }

        return orders.filter((order) => !order.is_ready_for_pickup);
    }, [orders, tab]);

    const handleFulfill = (orderId) => {
        setFulfillingId(orderId);
        router.post(route('vendor.orders.fulfill', orderId), {}, {
            preserveScroll: true,
            onFinish: () => setFulfillingId(null),
        });
    };

    return (
        <VendorLayout title="Orders">
            <Head title="Orders" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-stone-900">Orders</h1>
                <p className="mt-1 text-stone-600">Paid orders containing items from {shopName}.</p>
            </div>

            {flash?.success && (
                <div className="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">
                    {flash.success}
                </div>
            )}

            {orders.length > 0 && (
                <div className="mb-6 flex flex-wrap gap-2">
                    <button
                        type="button"
                        onClick={() => setTab('new')}
                        className={`rounded-full px-4 py-2 text-sm font-semibold transition ${
                            tab === 'new'
                                ? 'bg-stone-900 text-white'
                                : 'bg-white text-stone-700 ring-1 ring-stone-200 hover:bg-stone-50'
                        }`}
                    >
                        New ({counts.new})
                    </button>
                    <button
                        type="button"
                        onClick={() => setTab('ready')}
                        className={`rounded-full px-4 py-2 text-sm font-semibold transition ${
                            tab === 'ready'
                                ? 'bg-stone-900 text-white'
                                : 'bg-white text-stone-700 ring-1 ring-stone-200 hover:bg-stone-50'
                        }`}
                    >
                        Ready for pickup ({counts.ready})
                    </button>
                </div>
            )}

            {orders.length === 0 ? (
                <div className="rounded-2xl border border-stone-200 bg-white p-10 text-center shadow-sm">
                    <p className="text-stone-600">No orders yet. When customers buy your products, they will appear here.</p>
                </div>
            ) : filteredOrders.length === 0 ? (
                <div className="rounded-2xl border border-dashed border-stone-200 bg-white p-10 text-center shadow-sm">
                    <p className="text-stone-600">
                        {tab === 'new' ? 'No new orders right now.' : 'No ready-for-pickup orders yet.'}
                    </p>
                </div>
            ) : (
                <div className="space-y-6">
                    {filteredOrders.map((order) => (
                        <OrderCard
                            key={order.order_id}
                            order={order}
                            onFulfill={handleFulfill}
                            fulfillingId={fulfillingId}
                        />
                    ))}
                </div>
            )}
        </VendorLayout>
    );
}
