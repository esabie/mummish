import VendorLayout from '@/Layouts/VendorLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

const vendorBrown = 'bg-[#5c4d3d] hover:bg-[#4a3e32] text-white';

const tabs = [
    { key: 'all', label: 'All Products', countKey: 'all', activeClass: vendorBrown },
    { key: 'active', label: 'Active', countKey: 'active', activeClass: 'bg-emerald-100 text-emerald-800' },
    { key: 'draft', label: 'Drafts', countKey: 'draft', activeClass: 'bg-stone-200 text-stone-700' },
    { key: 'low_stock', label: 'Low Stock', countKey: 'low_stock', activeClass: 'bg-red-100 text-red-800' },
];

function StockBar({ product }) {
    let barColor = 'bg-emerald-600';
    if (product.is_out_of_stock) {
        barColor = 'bg-stone-300';
    } else if (product.is_low_stock) {
        barColor = 'bg-red-500';
    }

    return (
        <div className="min-w-[120px]">
            <div className="flex items-center gap-2">
                <span className="text-sm font-semibold tabular-nums text-stone-800">{product.stock_quantity}</span>
                {product.is_low_stock && (
                    <span className="rounded bg-red-100 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-red-700">
                        Low stock
                    </span>
                )}
            </div>
            <div className="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-stone-100">
                <div
                    className={`h-full rounded-full transition-all ${barColor}`}
                    style={{ width: `${product.is_out_of_stock ? 8 : product.stock_percent}%` }}
                />
            </div>
        </div>
    );
}

function StatusPill({ label, status }) {
    const styles =
        status === 'active'
            ? 'bg-emerald-100 text-emerald-800'
            : 'bg-stone-100 text-stone-600';

    return (
        <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ${styles}`}>{label}</span>
    );
}

function CategoryPill({ label }) {
    return (
        <span className="inline-flex rounded-full bg-sky-100 px-2.5 py-0.5 text-xs font-medium text-sky-800">
            {label}
        </span>
    );
}

export default function VendorInventoryIndex({
    shopName,
    applicationStatus,
    applicationStatusLabel,
    listingLimit,
    filters,
    counts,
    products,
}) {
    const [search, setSearch] = useState(filters.q ?? '');
    const [selected, setSelected] = useState([]);

    const applySearch = (e) => {
        e.preventDefault();
        router.get(
            route('vendor.inventory.index'),
            { tab: filters.tab, q: search || undefined },
            { preserveState: true, replace: true },
        );
    };

    const switchTab = (tab) => {
        router.get(
            route('vendor.inventory.index'),
            { tab, q: filters.q || undefined },
            { preserveState: true, replace: true },
        );
    };

    const toggleAll = (checked) => {
        if (checked) {
            setSelected(products.data.map((p) => p.id));
        } else {
            setSelected([]);
        }
    };

    const toggleOne = (id) => {
        setSelected((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]));
    };

    const allSelected = products.data.length > 0 && selected.length === products.data.length;

    return (
        <VendorLayout title="Inventory Management">
            <Head title="Inventory Management" />

            <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-stone-900">Inventory Management</h1>
                    <p className="mt-1 text-sm text-stone-600">
                        Manage your product listings and monitor stock levels across {shopName || 'your shop'}.
                    </p>
                    {applicationStatus === 'pending' && listingLimit?.max != null && (
                        <p className="mt-2 text-sm text-amber-800">
                            Application: <span className="font-semibold">{applicationStatusLabel}</span> — you can list
                            up to {listingLimit.max} products until approved ({listingLimit.remaining} remaining).
                        </p>
                    )}
                </div>
                {listingLimit?.can_add ? (
                    <Link
                        href={route('vendor.inventory.create')}
                        className={`inline-flex shrink-0 items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition ${vendorBrown}`}
                    >
                        <span className="text-lg leading-none">+</span> Add New Product
                    </Link>
                ) : (
                    <button
                        type="button"
                        disabled
                        className={`inline-flex shrink-0 items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition disabled:cursor-not-allowed disabled:opacity-50 ${vendorBrown}`}
                        title="Listing limit reached or application not approved"
                    >
                        <span className="text-lg leading-none">+</span> Add New Product
                    </button>
                )}
            </div>

            <div className="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center">
                <form onSubmit={applySearch} className="relative flex-1">
                    <svg
                        className="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-stone-400"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={1.5}
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                        />
                    </svg>
                    <input
                        type="search"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search products by name, SKU, or category..."
                        className="w-full rounded-lg border border-stone-200 bg-white py-2.5 pl-10 pr-4 text-sm shadow-sm placeholder:text-stone-400 focus:border-[#5c4d3d] focus:outline-none focus:ring-1 focus:ring-[#5c4d3d]"
                    />
                </form>
                <div className="flex gap-2">
                    <button
                        type="button"
                        className="inline-flex items-center gap-2 rounded-lg border border-stone-200 bg-white px-4 py-2.5 text-sm font-medium text-stone-700 shadow-sm hover:bg-stone-50"
                    >
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={1.5}
                                d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z"
                            />
                        </svg>
                        Filters
                    </button>
                    <button
                        type="button"
                        className="inline-flex items-center gap-2 rounded-lg border border-stone-200 bg-white px-4 py-2.5 text-sm font-medium text-stone-700 shadow-sm hover:bg-stone-50"
                    >
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={1.5}
                                d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 7.5m0 0L7.5 12M12 7.5v9"
                            />
                        </svg>
                        Export
                    </button>
                </div>
            </div>

            <div className="mb-4 flex flex-wrap gap-2">
                {tabs.map((tab) => {
                    const isActive = filters.tab === tab.key || (tab.key === 'all' && !filters.tab);
                    const count = counts[tab.countKey] ?? 0;
                    return (
                        <button
                            key={tab.key}
                            type="button"
                            onClick={() => switchTab(tab.key)}
                            className={`rounded-full px-4 py-1.5 text-sm font-semibold transition ${
                                isActive ? tab.activeClass : 'bg-white text-stone-600 ring-1 ring-stone-200 hover:bg-stone-50'
                            }`}
                        >
                            {tab.label} ({count})
                        </button>
                    );
                })}
            </div>

            <div className="overflow-hidden rounded-xl border border-stone-200/80 bg-white shadow-sm">
                <div className="flex items-center justify-between border-b border-stone-100 px-4 py-3">
                    <label className="flex items-center gap-2 text-sm text-stone-600">
                        <input
                            type="checkbox"
                            className="rounded border-stone-300 text-[#5c4d3d] focus:ring-[#5c4d3d]"
                            checked={allSelected}
                            onChange={(e) => toggleAll(e.target.checked)}
                        />
                        <select
                            className="rounded border-stone-200 text-sm text-stone-700"
                            defaultValue=""
                            disabled={selected.length === 0}
                        >
                            <option value="">Edit Selected</option>
                            <option value="draft">Mark as draft</option>
                            <option value="active">Mark as active</option>
                        </select>
                    </label>
                    <p className="text-sm text-stone-500">
                        Showing {products.from ?? 0}–{products.to ?? 0} of {products.total} products
                    </p>
                </div>

                <div className="overflow-x-auto">
                    <table className="min-w-full text-left text-sm">
                        <thead className="border-b border-stone-100 bg-stone-50/80 text-xs font-semibold uppercase tracking-wide text-stone-500">
                            <tr>
                                <th className="w-10 px-4 py-3" />
                                <th className="px-4 py-3">Product</th>
                                <th className="px-4 py-3">Category</th>
                                <th className="px-4 py-3">Price</th>
                                <th className="px-4 py-3">Stock Level</th>
                                <th className="px-4 py-3">Status</th>
                                <th className="w-12 px-4 py-3" />
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-stone-100">
                            {products.data.length === 0 ? (
                                <tr>
                                    <td colSpan={7} className="px-4 py-16 text-center text-stone-500">
                                        <p className="font-medium text-stone-700">No products yet</p>
                                        <p className="mt-1 text-sm">
                                            Add your first product to start selling on Mummish.
                                        </p>
                                    </td>
                                </tr>
                            ) : (
                                products.data.map((product) => (
                                    <tr key={product.id} className="hover:bg-stone-50/50">
                                        <td className="px-4 py-4">
                                            <input
                                                type="checkbox"
                                                className="rounded border-stone-300 text-[#5c4d3d] focus:ring-[#5c4d3d]"
                                                checked={selected.includes(product.id)}
                                                onChange={() => toggleOne(product.id)}
                                            />
                                        </td>
                                        <td className="px-4 py-4">
                                            <div className="flex items-center gap-3">
                                                <div className="h-12 w-12 shrink-0 overflow-hidden rounded-lg bg-stone-100">
                                                    {product.image_url ? (
                                                        <img
                                                            src={product.image_url}
                                                            alt=""
                                                            className="h-full w-full object-cover"
                                                        />
                                                    ) : (
                                                        <div className="flex h-full w-full items-center justify-center text-stone-400">
                                                            <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path
                                                                    strokeLinecap="round"
                                                                    strokeLinejoin="round"
                                                                    strokeWidth={1}
                                                                    d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z"
                                                                />
                                                            </svg>
                                                        </div>
                                                    )}
                                                </div>
                                                <div>
                                                    <Link
                                                        href={route('vendor.inventory.edit', product.id)}
                                                        className="font-semibold text-stone-900 hover:text-[#5c4d3d] hover:underline"
                                                    >
                                                        {product.title}
                                                    </Link>
                                                    <p className="text-xs text-stone-500">SKU: {product.sku}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-4 py-4">
                                            <CategoryPill label={product.category_label} />
                                        </td>
                                        <td className="px-4 py-4 font-medium text-stone-800">{product.price}</td>
                                        <td className="px-4 py-4">
                                            <StockBar product={product} />
                                        </td>
                                        <td className="px-4 py-4">
                                            <StatusPill label={product.status_label} status={product.status} />
                                        </td>
                                        <td className="px-4 py-4">
                                            <Link
                                                href={route('vendor.inventory.edit', product.id)}
                                                className="text-sm font-medium text-[#5c4d3d] hover:underline"
                                            >
                                                Edit
                                            </Link>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {products.last_page > 1 && (
                    <div className="flex items-center justify-between border-t border-stone-100 px-4 py-3">
                        <div className="flex gap-1">
                            {products.links.map((link, i) => {
                                if (link.label.includes('Previous') || link.label.includes('Next')) {
                                    return null;
                                }
                                if (link.url === null) {
                                    return (
                                        <span key={i} className="px-3 py-1 text-stone-400">
                                            …
                                        </span>
                                    );
                                }
                                return (
                                    <Link
                                        key={i}
                                        href={link.url}
                                        preserveScroll
                                        className={`rounded px-3 py-1 text-sm font-medium ${
                                            link.active
                                                ? 'bg-[#5c4d3d] text-white'
                                                : 'text-stone-600 hover:bg-stone-100'
                                        }`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                );
                            })}
                        </div>
                        <div className="flex gap-2">
                            {products.prev_page_url && (
                                <Link
                                    href={products.prev_page_url}
                                    preserveScroll
                                    className="rounded-lg border border-stone-200 px-3 py-1.5 text-sm font-medium text-stone-700 hover:bg-stone-50"
                                >
                                    Previous
                                </Link>
                            )}
                            {products.next_page_url && (
                                <Link
                                    href={products.next_page_url}
                                    preserveScroll
                                    className="rounded-lg border border-stone-200 px-3 py-1.5 text-sm font-medium text-stone-700 hover:bg-stone-50"
                                >
                                    Next
                                </Link>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </VendorLayout>
    );
}
