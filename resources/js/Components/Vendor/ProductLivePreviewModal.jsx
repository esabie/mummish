import { useEffect, useState } from 'react';

function PreviewBadge({ children }) {
    return (
        <span className="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-amber-950 ring-1 ring-amber-200/80">
            {children}
        </span>
    );
}

export default function ProductLivePreviewModal({
    open,
    onClose,
    title,
    price,
    description,
    categoryLabel,
    brandLabel,
    conditionLabel,
    sizeLabel,
    shopName,
    materialTags = [],
    allowsCustomization,
    images = [],
    shopUrl = null,
}) {
    const [activeImage, setActiveImage] = useState(0);

    useEffect(() => {
        if (open) {
            setActiveImage(0);
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }

        return () => {
            document.body.style.overflow = '';
        };
    }, [open]);

    useEffect(() => {
        if (!open) {
            return undefined;
        }

        const onKeyDown = (e) => {
            if (e.key === 'Escape') {
                onClose();
            }
        };

        window.addEventListener('keydown', onKeyDown);

        return () => window.removeEventListener('keydown', onKeyDown);
    }, [open, onClose]);

    if (!open) {
        return null;
    }

    const displayPrice = price ? `GHS ${parseFloat(price || 0).toFixed(2)}` : 'GHS 0.00';
    const gallery = images.length > 0 ? images : [null];
    const mainSrc = gallery[Math.min(activeImage, gallery.length - 1)];
    const sellerInitial = (shopName || '?').charAt(0).toUpperCase();

    return (
        <div className="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4" role="dialog" aria-modal="true" aria-labelledby="live-preview-title">
            <button
                type="button"
                className="absolute inset-0 bg-stone-900/60 backdrop-blur-sm"
                onClick={onClose}
                aria-label="Close preview"
            />

            <div className="relative flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-t-2xl bg-[#f7f5f2] shadow-2xl sm:rounded-2xl">
                <div className="flex items-center justify-between border-b border-stone-200 bg-white px-4 py-3 sm:px-6">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-wider text-[#5c4d3d]">Live preview</p>
                        <p className="text-sm text-stone-600">How shoppers will see this listing</p>
                    </div>
                    <div className="flex items-center gap-2">
                        {shopUrl && (
                            <a
                                href={shopUrl}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="hidden rounded-lg border border-stone-200 bg-white px-3 py-2 text-sm font-medium text-[#5c4d3d] transition hover:bg-stone-50 sm:inline-flex"
                            >
                                Open on shop
                            </a>
                        )}
                        <button
                            type="button"
                            onClick={onClose}
                            className="rounded-lg border border-stone-200 bg-white px-3 py-2 text-sm font-semibold text-stone-700 transition hover:bg-stone-50"
                        >
                            Close
                        </button>
                    </div>
                </div>

                <div className="overflow-y-auto px-4 py-6 sm:px-6">
                    <div className="grid gap-8 lg:grid-cols-2 lg:gap-10">
                        <div>
                            <div className="overflow-hidden rounded-2xl border border-stone-200/80 bg-stone-100 shadow-sm">
                                <div className="aspect-[4/3]">
                                    {mainSrc ? (
                                        <img src={mainSrc} alt="" className="h-full w-full object-cover" />
                                    ) : (
                                        <div className="flex h-full items-center justify-center text-sm text-stone-400">
                                            No image uploaded yet
                                        </div>
                                    )}
                                </div>
                            </div>
                            {gallery.length > 1 && (
                                <div className="mt-4 flex gap-3 overflow-x-auto pb-1">
                                    {gallery.map((src, index) => (
                                        <button
                                            key={`${src}-${index}`}
                                            type="button"
                                            onClick={() => setActiveImage(index)}
                                            className={`relative h-16 w-16 shrink-0 overflow-hidden rounded-xl border-2 transition sm:h-20 sm:w-20 ${
                                                activeImage === index
                                                    ? 'border-[#5c4d3d] ring-2 ring-[#5c4d3d]/20'
                                                    : 'border-transparent opacity-80 hover:opacity-100'
                                            }`}
                                        >
                                            {src ? (
                                                <img src={src} alt="" className="h-full w-full object-cover" />
                                            ) : (
                                                <span className="flex h-full items-center justify-center bg-stone-200 text-xs text-stone-500">
                                                    —
                                                </span>
                                            )}
                                        </button>
                                    ))}
                                </div>
                            )}
                        </div>

                        <div>
                            <div className="flex flex-wrap items-center gap-2">
                                <PreviewBadge>Preview</PreviewBadge>
                                {allowsCustomization && (
                                    <PreviewBadge>Customizable</PreviewBadge>
                                )}
                            </div>

                            <h2 id="live-preview-title" className="mt-4 text-2xl font-bold leading-tight text-[#3d3429] sm:text-3xl">
                                {title?.trim() || 'Product name'}
                            </h2>

                            <p className="mt-2 text-sm text-stone-500">
                                {[categoryLabel, brandLabel, conditionLabel, sizeLabel].filter(Boolean).join(' · ') || 'Category · Brand'}
                            </p>

                            <p className="mt-4 text-3xl font-bold text-[#5c4d3d]">{displayPrice}</p>

                            <div className="mt-6 rounded-2xl border border-stone-200/80 bg-stone-100/80 p-4">
                                <div className="flex items-start gap-3">
                                    <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-orange-400 text-base font-bold text-white">
                                        {sellerInitial}
                                    </div>
                                    <div>
                                        <p className="text-sm text-stone-600">
                                            Sold by <span className="font-semibold text-stone-900">{shopName}</span>
                                        </p>
                                        <p className="mt-1 text-xs text-stone-500">Preview — buyer view</p>
                                    </div>
                                </div>
                            </div>

                            {materialTags.length > 0 && (
                                <div className="mt-6 flex flex-wrap gap-2">
                                    {materialTags.map((tag) => (
                                        <span
                                            key={tag}
                                            className="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-900 ring-1 ring-emerald-200/70"
                                        >
                                            {tag}
                                        </span>
                                    ))}
                                </div>
                            )}

                            <p className="mt-6 whitespace-pre-wrap text-base leading-relaxed text-stone-600">
                                {description?.trim() || 'Your product description will appear here.'}
                            </p>

                            {sizeLabel && (
                                <div className="mt-6">
                                    <p className="text-sm font-semibold text-stone-900">Size</p>
                                    <span className="mt-2 inline-flex min-h-[2.75rem] items-center justify-center rounded-md border border-stone-900 bg-stone-900 px-4 text-sm font-semibold text-white">
                                        {sizeLabel}
                                    </span>
                                </div>
                            )}

                            <div className="mt-8 rounded-xl border border-dashed border-stone-300 bg-white/80 px-4 py-3 text-sm text-stone-500">
                                Cart and checkout actions are disabled in preview mode.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
