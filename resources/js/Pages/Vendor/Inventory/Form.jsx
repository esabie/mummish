import InputError from '@/Components/InputError';
import Modal from '@/Components/Modal';
import ProductImagesUpload from '@/Components/Vendor/ProductImagesUpload';
import ProductLivePreviewModal from '@/Components/Vendor/ProductLivePreviewModal';
import VendorLayout from '@/Layouts/VendorLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useState } from 'react';

const inputClass =
    'mt-1.5 block w-full rounded-xl border border-stone-200 bg-white px-3.5 py-2.5 text-sm text-stone-900 shadow-sm placeholder:text-stone-400 focus:border-[#5c4d3d] focus:outline-none focus:ring-1 focus:ring-[#5c4d3d]';

const peachBtn =
    'rounded-xl bg-[#e8a87c] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#d9956a] disabled:opacity-50';

const outlineBtn =
    'rounded-xl border border-stone-300 bg-white px-5 py-2.5 text-sm font-semibold text-stone-700 shadow-sm transition hover:bg-stone-50 disabled:opacity-50';

const OTHER_BRAND_VALUE = '__other__';

function categoryHasBrand(categoryBrands, category, brand) {
    if (!category || !brand) {
        return false;
    }

    return (categoryBrands[category] ?? []).some((option) => option.value === brand);
}

function stripDigits(value) {
    return value.replace(/[0-9\u0660-\u0669\u06F0-\u06F9\u0966-\u096F\uFF10-\uFF19]/g, '');
}

function isDigitInput(key, code) {
    if (key && key.length === 1 && /\d/.test(key)) {
        return true;
    }

    return Boolean(code && /^(?:Digit|Numpad)\d$/.test(code));
}

function RequiredMark() {
    return <span className="text-red-600" aria-hidden="true"> *</span>;
}

function SectionCard({ icon, title, children, className = '' }) {
    return (
        <section className={`rounded-2xl border border-stone-200/90 bg-white p-6 shadow-sm ${className}`}>
            <div className="mb-5 flex items-center gap-2.5">
                <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-stone-100 text-stone-600">
                    {icon}
                </span>
                <h2 className="text-base font-bold text-stone-900">{title}</h2>
            </div>
            {children}
        </section>
    );
}

function IconTag() {
    return (
        <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5" aria-hidden>
            <path strokeLinecap="round" strokeLinejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M6 6h.008v.008H6V6z" />
        </svg>
    );
}

function IconBook() {
    return (
        <svg className="h-5 w-5 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5" aria-hidden>
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
        </svg>
    );
}

function IconShield() {
    return (
        <svg className="h-5 w-5 text-sky-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5" aria-hidden>
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
        </svg>
    );
}

function IconImage() {
    return (
        <svg className="h-5 w-5 text-sky-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5" aria-hidden>
            <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
        </svg>
    );
}

function IconCalculator() {
    return (
        <svg className="h-5 w-5 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5" aria-hidden>
            <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 15.75V18m-7.5-6.75h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V12zm0 2.25h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V18zm2.498-6.75h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V12zm0 2.25h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V18zm2.504-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V12zm0 2.25h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V18zm2.498-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V12zm0 2.25h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V18z" />
        </svg>
    );
}

function MarketplacePreview({ title, price, imageUrl, shopName, categoryLabel, sizeLabel, onLivePreview }) {
    const displayPrice = price ? `GHS ${parseFloat(price || 0).toFixed(2)}` : 'GHS 0.00';

    return (
        <div className="overflow-hidden rounded-2xl bg-[#5c4d3d] p-5 text-white shadow-lg">
            <p className="text-xs font-semibold uppercase tracking-wider text-white/70">Marketplace Preview</p>
            <p className="mt-1 text-sm text-white/90">See how your product appears in the shop feed.</p>
            <div className="mt-4 overflow-hidden rounded-xl bg-white text-stone-900 shadow-md">
                <div className="aspect-square bg-stone-100">
                    {imageUrl ? (
                        <img src={imageUrl} alt="" className="h-full w-full object-cover" />
                    ) : (
                        <div className="flex h-full items-center justify-center text-stone-300">
                            <IconImage />
                        </div>
                    )}
                </div>
                <div className="p-3">
                    <p className="line-clamp-2 text-sm font-semibold">{title || 'Product name'}</p>
                    <p className="mt-0.5 text-xs text-stone-500">
                        {categoryLabel || 'Category'}
                        {sizeLabel ? ` · ${sizeLabel}` : ''}
                    </p>
                    <div className="mt-2 flex items-center justify-between">
                        <span className="text-sm font-bold">{displayPrice}</span>
                        <span className="text-[10px] text-stone-500">by {shopName}</span>
                    </div>
                </div>
            </div>
            <button
                type="button"
                onClick={onLivePreview}
                className="mt-4 w-full rounded-xl bg-white py-2.5 text-sm font-semibold text-[#5c4d3d] transition hover:bg-stone-100"
            >
                Live Preview &gt;&gt;
            </button>
        </div>
    );
}

export default function VendorProductForm({
    product,
    categories,
    listingLimit,
    shopName,
    materialTagOptions,
    marketplaceName,
    clothingSizeOptions = [],
    categoriesRequiringSize = [],
    categoryBrands = {},
    conditionOptions = [],
    minImages = 3,
    maxImages = 8,
    imageRequirements = { minWidth: 800, minHeight: 800 },
}) {
    const isEdit = product !== null;

    const { data, setData, post, processing, errors, delete: destroy, transform } = useForm({
        title: product?.title ?? '',
        description: product?.description ?? '',
        category: product?.category ?? '',
        brand: product?.brand ?? '',
        condition: product?.condition ?? 'new',
        clothing_size: product?.clothing_size ?? '',
        compare_at_price: product?.compare_at_price ?? '',
        price: product?.price ?? '',
        stock_quantity: product?.stock_quantity ?? 1,
        status: product?.status ?? 'draft',
        existing_images: product?.image_urls ?? [],
        images: [],
        material_tags: product?.material_tags ?? [],
        allows_customization: product?.allows_customization ?? false,
    });

    const [customTag, setCustomTag] = useState('');
    const [brandIsOther, setBrandIsOther] = useState(() =>
        Boolean(product?.brand) && !categoryHasBrand(categoryBrands, product?.category, product?.brand),
    );
    const [customBrand, setCustomBrand] = useState(() => {
        if (product?.brand && !categoryHasBrand(categoryBrands, product?.category, product?.brand)) {
            return product.brand;
        }

        return '';
    });
    const [discountPercent, setDiscountPercent] = useState(() => {
        const original = parseFloat(product?.compare_at_price ?? '');
        const sale = parseFloat(product?.price ?? '');

        if (original > sale && sale > 0) {
            return String(Math.round((1 - sale / original) * 100));
        }

        return '';
    });
    const [previewOpen, setPreviewOpen] = useState(false);
    const [livePreviewOpen, setLivePreviewOpen] = useState(false);
    const [serviceFeeModalOpen, setServiceFeeModalOpen] = useState(false);
    const [clientError, setClientError] = useState(null);
    const [imageQuality, setImageQuality] = useState({
        allPassed: true,
        checking: false,
        failedMessages: [],
    });

    const categoryLabel =
        categories.find((c) => c.value === data.category)?.label ?? '';

    const requiresClothingSize = categoriesRequiringSize.includes(data.category);

    const availableBrands = useMemo(() => {
        const brands = (categoryBrands[data.category] ?? []).filter(
            (brand) => brand.value !== OTHER_BRAND_VALUE && brand.label?.toLowerCase() !== 'other',
        );

        return [...brands, { value: OTHER_BRAND_VALUE, label: 'Other' }];
    }, [categoryBrands, data.category]);

    const brandSelectValue = brandIsOther ? OTHER_BRAND_VALUE : data.brand;

    const clothingSizeLabel =
        clothingSizeOptions.find((o) => o.value === data.clothing_size)?.label ?? '';

    const handleCategoryChange = (value) => {
        setData('category', value);
        setData('brand', '');
        setBrandIsOther(false);
        setCustomBrand('');
        if (!categoriesRequiringSize.includes(value)) {
            setData('clothing_size', '');
        }
    };

    const handleBrandSelectChange = (value) => {
        if (value === OTHER_BRAND_VALUE) {
            setBrandIsOther(true);
            setData('brand', customBrand.trim());
            return;
        }

        setBrandIsOther(false);
        setCustomBrand('');
        setData('brand', value);
    };

    const handleCustomBrandChange = (value) => {
        setCustomBrand(value);
        setData('brand', value);
    };

    const handleOriginalPriceChange = (value) => {
        setData('compare_at_price', value);

        const original = parseFloat(value);
        const percent = parseFloat(discountPercent);

        if (original > 0 && percent > 0 && percent < 100) {
            setData('price', (original * (1 - percent / 100)).toFixed(2));
        }
    };

    const handleDiscountPercentChange = (value) => {
        const sanitized = value.replace(/[^\d]/g, '').slice(0, 2);
        setDiscountPercent(sanitized);

        const original = parseFloat(data.compare_at_price);
        const percent = parseFloat(sanitized);

        if (original > 0 && percent > 0 && percent < 100) {
            setData('price', (original * (1 - percent / 100)).toFixed(2));
        }
    };

    const handleSalePriceChange = (value) => {
        setData('price', value);

        const original = parseFloat(data.compare_at_price);
        const sale = parseFloat(value);

        if (original > sale && sale > 0) {
            setDiscountPercent(String(Math.round((1 - sale / original) * 100)));
        } else if (!value || !data.compare_at_price) {
            setDiscountPercent('');
        }
    };

    const toggleMaterialTag = (tag) => {
        const current = data.material_tags ?? [];
        if (current.includes(tag)) {
            setData(
                'material_tags',
                current.filter((t) => t !== tag)
            );
        } else {
            setData('material_tags', [...current, tag]);
        }
    };

    const addCustomTag = () => {
        const label = customTag.trim();
        if (!label) {
            return;
        }
        const key = `custom:${label.toLowerCase().replace(/\s+/g, '_')}`;
        if (!(data.material_tags ?? []).includes(key)) {
            setData('material_tags', [...(data.material_tags ?? []), key]);
        }
        setCustomTag('');
    };

    const tagLabel = (value) => {
        if (value.startsWith('custom:')) {
            return value.replace('custom:', '').replace(/_/g, ' ');
        }
        return materialTagOptions.find((o) => o.value === value)?.label ?? value;
    };

    const previewBlobUrls = useMemo(
        () => (data.images ?? []).map((file) => URL.createObjectURL(file)),
        [data.images]
    );

    useEffect(() => {
        return () => {
            previewBlobUrls.forEach((url) => URL.revokeObjectURL(url));
        };
    }, [previewBlobUrls]);

    const previewGallery = useMemo(
        () => [...(data.existing_images ?? []), ...previewBlobUrls],
        [data.existing_images, previewBlobUrls]
    );

    const previewImage = previewGallery[0] ?? null;

    const brandLabel = brandIsOther
        ? (customBrand.trim() || data.brand || 'Other')
        : (availableBrands.find((b) => b.value === data.brand)?.label ?? data.brand);
    const conditionLabel =
        conditionOptions.find((option) => option.value === data.condition)?.label ?? 'New';

    const previewMaterialTags = (data.material_tags ?? []).map((tag) => tagLabel(tag));

    const openLivePreview = useCallback(() => {
        setLivePreviewOpen(true);
    }, []);

    const shopPreviewUrl =
        isEdit && product?.id && product?.status === 'active'
            ? route('shop.show', product.id)
            : null;

    const validateForm = useCallback(() => {
        if (!data.title?.trim()) {
            return 'Product name is required.';
        }
        if (data.title.trim().length > 30) {
            return 'Product name must be 30 characters or fewer.';
        }
        if (!data.category) {
            return 'Category is required.';
        }
        if (brandIsOther) {
            if (!customBrand.trim()) {
                return 'Please enter the brand name.';
            }
        } else if (!data.brand) {
            return 'Please select a brand.';
        }
        if (!data.condition) {
            return 'Please select the item condition.';
        }
        if (categoriesRequiringSize.includes(data.category) && !data.clothing_size) {
            return 'Please select a clothing size.';
        }
        if (data.description.trim().length < 20) {
            return 'Product description is required (at least 20 characters).';
        }
        if (!data.material_tags?.length) {
            return 'Select at least one material tag.';
        }
        if (!data.price || parseFloat(data.price) < 0.01) {
            return 'Sale price is required (minimum GHS 0.01).';
        }
        if (data.compare_at_price) {
            const original = parseFloat(data.compare_at_price);
            const sale = parseFloat(data.price);
            if (!Number.isFinite(original) || original < 0.01) {
                return 'Original price must be at least GHS 0.01.';
            }
            if (original <= sale) {
                return 'Original price must be higher than the sale price.';
            }
        }
        if (!data.stock_quantity || parseInt(data.stock_quantity, 10) < 1) {
            return 'Stock quantity is required (minimum 1).';
        }
        const imageCount = (data.existing_images?.length ?? 0) + (data.images?.length ?? 0);
        if (imageCount < minImages) {
            return `Upload at least ${minImages} product images.`;
        }
        if ((data.images?.length ?? 0) > 0) {
            if (imageQuality.checking) {
                return 'Still checking photo quality. Please wait a moment.';
            }
            if (!imageQuality.allPassed) {
                return 'Replace blurry or low-resolution photos before saving.';
            }
        }
        return null;
    }, [brandIsOther, customBrand, data, imageQuality, minImages]);

    const submitWithStatus = useCallback(
        (status) => {
            const message = validateForm();
            if (message) {
                setClientError(message);
                return;
            }
            setClientError(null);

            const hasNewImages = (data.images?.length ?? 0) > 0;

            transform((formData) => {
                const payload = {
                    ...formData,
                    brand: String(formData.brand ?? '').trim(),
                    status,
                    allows_customization: formData.allows_customization ? 1 : 0,
                };

                if (isEdit) {
                    payload._method = 'put';
                }

                return payload;
            });

            const options = {
                preserveScroll: true,
                forceFormData: hasNewImages,
                onFinish: () => transform((formData) => formData),
            };

            if (isEdit) {
                post(route('vendor.inventory.update', product.id), options);
            } else {
                post(route('vendor.inventory.store'), {
                    ...options,
                    forceFormData: true,
                });
            }
        },
        [isEdit, post, product, transform, validateForm, data.images]
    );

    const handleDelete = () => {
        if (!window.confirm('Remove this product from your inventory?')) {
            return;
        }
        destroy(route('vendor.inventory.destroy', product.id));
    };

    const allMaterialOptions = [
        ...materialTagOptions,
        ...(data.material_tags ?? [])
            .filter((t) => t.startsWith('custom:'))
            .map((t) => ({ value: t, label: tagLabel(t) })),
    ];

    const seen = new Set();
    const materialOptions = allMaterialOptions.filter((o) => {
        if (seen.has(o.value)) {
            return false;
        }
        seen.add(o.value);
        return true;
    });

    return (
        <VendorLayout>
            <Head title={isEdit ? 'Edit product' : 'Create new product'} />

            {/* Page header */}
            <div className="mb-8 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <Link
                        href={route('vendor.dashboard')}
                        className="text-sm font-medium text-[#5c4d3d] hover:underline"
                    >
                        &lt; Back to Dashboard
                    </Link>
                    <h1 className="mt-3 font-serif text-3xl font-bold tracking-tight text-stone-900 sm:text-4xl">
                        {isEdit ? 'Edit Product' : 'Create New Product'}
                    </h1>
                    <p className="mt-2 max-w-xl text-sm text-stone-600 sm:text-base">
                        Let&apos;s showcase your craft to the {marketplaceName} community.
                    </p>
                    {!isEdit && listingLimit?.max != null && (
                        <p className="mt-2 text-sm text-amber-800">
                            {listingLimit.remaining} of {listingLimit.max} listing slots remaining while your
                            application is reviewed.
                        </p>
                    )}
                </div>
                <div className="flex shrink-0 flex-wrap items-center gap-3">
                    <button
                        type="button"
                        disabled={processing || imageQuality.checking}
                        onClick={() => submitWithStatus('draft')}
                        className={outlineBtn}
                    >
                        {processing && data.status === 'draft' ? 'Saving…' : 'Save Draft'}
                    </button>
                    <button
                        type="button"
                        disabled={processing || imageQuality.checking}
                        onClick={() => submitWithStatus('active')}
                        className={peachBtn}
                    >
                        {processing && data.status === 'active'
                            ? 'Publishing…'
                            : isEdit
                              ? 'Save & Publish'
                              : 'Publish Product'}
                    </button>
                </div>
            </div>

            {clientError && !errors.images && (
                <div className="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="alert">
                    {clientError}
                </div>
            )}

            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    submitWithStatus(data.status || 'draft');
                }}
                className="grid gap-6 lg:grid-cols-[1fr_340px] xl:grid-cols-[1fr_380px]"
            >
                {/* Left column */}
                <div className="space-y-6">
                    <SectionCard icon={<IconTag />} title="Basic Details">
                        <div className="space-y-5">
                            <div>
                                <label htmlFor="title" className="text-sm font-medium text-stone-700">
                                    Product Name
                                    <RequiredMark />
                                </label>
                                <input
                                    id="title"
                                    type="text"
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    className={inputClass}
                                    placeholder="e.g. Organic cotton baby rattle"
                                    maxLength={30}
                                    required
                                />
                                <p className="mt-1 text-xs text-stone-500">
                                    Keep it short and clear. Put extra detail in the description (max 30 characters).
                                </p>
                                <InputError message={errors.title} className="mt-1" />
                            </div>
                            <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                                <div>
                                    <label htmlFor="category" className="text-sm font-medium text-stone-700">
                                        Category
                                        <RequiredMark />
                                    </label>
                                    <select
                                        id="category"
                                        value={data.category}
                                        onChange={(e) => handleCategoryChange(e.target.value)}
                                        className={inputClass}
                                        required
                                    >
                                        <option value="">Select category</option>
                                        {categories.map((cat) => (
                                            <option key={cat.value} value={cat.value}>
                                                {cat.label}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.category} className="mt-1" />
                                </div>
                                <div>
                                    <label htmlFor="brand" className="text-sm font-medium text-stone-700">
                                        Brand
                                        <RequiredMark />
                                    </label>
                                    <select
                                        id="brand"
                                        value={brandSelectValue}
                                        onChange={(e) => handleBrandSelectChange(e.target.value)}
                                        className={inputClass}
                                        disabled={!data.category}
                                        required
                                    >
                                        <option value="">
                                            {data.category ? 'Select brand' : 'Select a category first'}
                                        </option>
                                        {availableBrands.map((brand) => (
                                            <option key={brand.value} value={brand.value}>
                                                {brand.label}
                                            </option>
                                        ))}
                                    </select>
                                    {brandIsOther ? (
                                        <div className="mt-3">
                                            <label htmlFor="custom_brand" className="text-sm font-medium text-stone-700">
                                                Brand name
                                                <RequiredMark />
                                            </label>
                                            <input
                                                id="custom_brand"
                                                type="text"
                                                value={customBrand}
                                                onChange={(e) => handleCustomBrandChange(e.target.value)}
                                                className={inputClass}
                                                placeholder="Enter brand name"
                                                maxLength={120}
                                                required
                                            />
                                        </div>
                                    ) : null}
                                    <InputError message={errors.brand} className="mt-1" />
                                </div>
                                <div>
                                    <label htmlFor="condition" className="text-sm font-medium text-stone-700">
                                        Item condition
                                        <RequiredMark />
                                    </label>
                                    <select
                                        id="condition"
                                        value={data.condition}
                                        onChange={(e) => setData('condition', e.target.value)}
                                        className={inputClass}
                                        required
                                    >
                                        {conditionOptions.map((option) => (
                                            <option key={option.value} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.condition} className="mt-1" />
                                </div>
                            </div>

                            <div className="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <label htmlFor="sku" className="text-sm font-medium text-stone-700">
                                        SKU / Identifier
                                    </label>
                                    <div
                                        id="sku"
                                        className={`${inputClass} cursor-not-allowed bg-stone-50 text-stone-600`}
                                    >
                                        {isEdit && product?.sku
                                            ? product.sku
                                            : 'Auto-generated when you publish a product'}
                                    </div>
                                    <p className="mt-1 text-xs text-stone-500">
                                        A unique code is assigned automatically and cannot be changed.
                                    </p>
                                </div>
                                <div className="hidden sm:block" aria-hidden="true" />
                            </div>

                            {requiresClothingSize && (
                                <div className="mt-5 rounded-xl border border-[#5c4d3d]/15 bg-[#5c4d3d]/5 p-4">
                                    <label htmlFor="clothing_size" className="text-sm font-medium text-stone-700">
                                        Clothing size
                                        <RequiredMark />
                                    </label>
                                    <select
                                        id="clothing_size"
                                        value={data.clothing_size}
                                        onChange={(e) => setData('clothing_size', e.target.value)}
                                        className={inputClass}
                                        required
                                    >
                                        <option value="">Select size</option>
                                        {clothingSizeOptions.map((size) => (
                                            <option key={size.value} value={size.value}>
                                                {size.label}
                                            </option>
                                        ))}
                                    </select>
                                    <p className="mt-1.5 text-xs text-stone-600">
                                        Choose the size buyers should expect for this item.
                                    </p>
                                    <InputError message={errors.clothing_size} className="mt-1" />
                                </div>
                            )}
                        </div>
                    </SectionCard>

                    <SectionCard icon={<IconBook />} title="Story & Description">
                        <p className="mb-2 text-sm font-medium text-stone-700">
                            Product Description
                            <RequiredMark />
                        </p>
                        <textarea
                            id="description"
                            value={data.description}
                            inputMode="text"
                            autoComplete="off"
                            onBeforeInput={(e) => {
                                if (e.data && /\d/.test(e.data)) {
                                    e.preventDefault();
                                }
                            }}
                            onChange={(e) => setData('description', stripDigits(e.target.value))}
                            onKeyDown={(e) => {
                                if (e.ctrlKey || e.metaKey || e.altKey) {
                                    return;
                                }
                                if (isDigitInput(e.key, e.code)) {
                                    e.preventDefault();
                                }
                            }}
                            onPaste={(e) => {
                                e.preventDefault();
                                const pasted = stripDigits(e.clipboardData.getData('text/plain'));
                                if (!pasted) {
                                    return;
                                }
                                const el = e.target;
                                const start = el.selectionStart ?? 0;
                                const end = el.selectionEnd ?? 0;
                                setData(
                                    'description',
                                    stripDigits(data.description.slice(0, start) + pasted + data.description.slice(end))
                                );
                            }}
                            rows={6}
                            className={`${inputClass} min-h-[160px] resize-y`}
                            placeholder="Tell the story of how this product was made and why it's special..."
                            required
                        />
                        <p className="mt-1.5 text-xs text-stone-500">Letters and punctuation only — numbers are not allowed.</p>
                        <InputError message={errors.description} className="mt-1" />
                    </SectionCard>

                    <SectionCard icon={<IconShield />} title="Sustainability & Materials">
                        <p className="mb-3 text-sm font-medium text-stone-700">
                            Material Category
                            <RequiredMark />
                        </p>
                        <div className="flex flex-wrap gap-2">
                            {materialOptions.map((opt) => {
                                const selected = (data.material_tags ?? []).includes(opt.value);
                                return (
                                    <button
                                        key={opt.value}
                                        type="button"
                                        onClick={() => toggleMaterialTag(opt.value)}
                                        className={`inline-flex items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-sm font-medium transition ${
                                            selected
                                                ? 'border-emerald-600 bg-emerald-50 text-emerald-900'
                                                : 'border-stone-200 bg-white text-stone-700 hover:border-stone-300'
                                        }`}
                                    >
                                        {selected && (
                                            <svg className="h-4 w-4 text-emerald-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden>
                                                <path
                                                    fillRule="evenodd"
                                                    d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z"
                                                    clipRule="evenodd"
                                                />
                                            </svg>
                                        )}
                                        {opt.label}
                                    </button>
                                );
                            })}
                        </div>
                        <div className="mt-4 flex flex-wrap gap-2">
                            <input
                                type="text"
                                value={customTag}
                                onChange={(e) => setCustomTag(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && (e.preventDefault(), addCustomTag())}
                                placeholder="Custom tag name"
                                className="min-w-[140px] flex-1 rounded-xl border border-dashed border-stone-300 bg-stone-50/50 px-3 py-2 text-sm focus:border-[#5c4d3d] focus:outline-none focus:ring-1 focus:ring-[#5c4d3d]"
                            />
                            <button
                                type="button"
                                onClick={addCustomTag}
                                className="rounded-xl border border-dashed border-stone-300 px-4 py-2 text-sm font-medium text-stone-600 transition hover:border-[#5c4d3d] hover:text-[#5c4d3d]"
                            >
                                + Add Custom Tag
                            </button>
                        </div>
                        <InputError message={errors.material_tags} className="mt-2" />

                        <div className="mt-5 flex gap-3 rounded-xl border border-emerald-100 bg-emerald-50/80 px-4 py-3">
                            <IconShield />
                            <p className="text-sm leading-relaxed text-emerald-900">
                                Your Merchant Badge will be automatically applied to this listing based on your
                                shop&apos;s sustainability credentials.
                            </p>
                        </div>
                    </SectionCard>

                    {isEdit && (
                        <div className="flex justify-end border-t border-stone-200 pt-4">
                            <button
                                type="button"
                                onClick={handleDelete}
                                disabled={processing}
                                className="text-sm font-medium text-red-600 hover:text-red-800"
                            >
                                Delete product
                            </button>
                        </div>
                    )}
                </div>

                {/* Right column */}
                <div className="space-y-6 lg:sticky lg:top-20 lg:self-start">
                    <SectionCard icon={<IconImage />} title="Product Images">
                        <ProductImagesUpload
                            existingImages={data.existing_images ?? []}
                            newFiles={data.images ?? []}
                            onExistingImagesChange={(urls) => setData('existing_images', urls)}
                            onNewFilesChange={(files) => setData('images', files)}
                            minImages={minImages}
                            maxImages={maxImages}
                            imageRequirements={imageRequirements}
                            onQualityChange={setImageQuality}
                            error={errors.images || errors['images.0']}
                        />
                    </SectionCard>

                    <SectionCard icon={<IconCalculator />} title="Pricing & Inventory">
                        <div className="space-y-4">
                            <div className="rounded-xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm leading-relaxed text-stone-700">
                                <p className="font-semibold text-stone-900">How pricing works</p>
                                <ul className="mt-2 list-disc space-y-1.5 pl-5">
                                    <li>
                                        <span className="font-medium text-stone-900">Sale price</span> is required. This is
                                        what the buyer pays.
                                    </li>
                                    <li>
                                        <span className="font-medium text-stone-900">Original price</span> is optional. Use it
                                        only if the item is on sale. Shoppers will see it crossed out next to the sale price
                                        (for example{' '}
                                        <span className="whitespace-nowrap">
                                            <span className="text-stone-500 line-through">₵100</span> ₵80
                                        </span>
                                        ).
                                    </li>
                                    <li>
                                        If you set an original price, it must be{' '}
                                        <span className="font-medium text-stone-900">higher</span> than the sale price.
                                    </li>
                                    <li>
                                        <span className="font-medium text-stone-900">Discount %</span> is optional. Enter an
                                        original price first, then a percent, and we will calculate the sale price for you.
                                    </li>
                                    <li>If the product is not on sale, leave original price and discount % blank.</li>
                                </ul>
                            </div>
                            <div className="grid gap-4 sm:grid-cols-3 sm:items-start">
                                <div className="flex flex-col">
                                    <label
                                        htmlFor="compare_at_price"
                                        className="flex min-h-10 items-end text-sm font-medium text-stone-700"
                                    >
                                        Original price (GHS)
                                    </label>
                                    <div className="relative mt-1.5">
                                        <span className="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-stone-500">
                                            ₵
                                        </span>
                                        <input
                                            id="compare_at_price"
                                            type="number"
                                            min="0.01"
                                            step="0.01"
                                            value={data.compare_at_price}
                                            onChange={(e) => handleOriginalPriceChange(e.target.value)}
                                            className={`${inputClass} pl-8`}
                                            placeholder="Optional"
                                        />
                                    </div>
                                    <p className="mt-1 text-xs text-stone-500">
                                        The higher &quot;was&quot; price. Leave blank if the product is not on sale.
                                    </p>
                                    <InputError message={errors.compare_at_price} className="mt-1" />
                                </div>
                                <div className="flex flex-col">
                                    <label
                                        htmlFor="discount_percent"
                                        className="flex min-h-10 items-end text-sm font-medium text-stone-700"
                                    >
                                        Discount %
                                    </label>
                                    <div className="relative mt-1.5">
                                        <input
                                            id="discount_percent"
                                            type="number"
                                            min="1"
                                            max="99"
                                            step="1"
                                            value={discountPercent}
                                            onChange={(e) => handleDiscountPercentChange(e.target.value)}
                                            className={`${inputClass} pr-8`}
                                            placeholder="Optional"
                                            disabled={!data.compare_at_price}
                                        />
                                        <span className="pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2 text-sm text-stone-500">
                                            %
                                        </span>
                                    </div>
                                    <p className="mt-1 text-xs text-stone-500">Auto-calculates the sale price.</p>
                                </div>
                                <div className="flex flex-col">
                                    <label
                                        htmlFor="price"
                                        className="flex min-h-10 items-end text-sm font-medium text-stone-700"
                                    >
                                        Sale price (GHS)
                                        <RequiredMark />
                                    </label>
                                    <div className="relative mt-1.5">
                                        <span className="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-stone-500">
                                            ₵
                                        </span>
                                        <input
                                            id="price"
                                            type="number"
                                            min="0.01"
                                            step="0.01"
                                            value={data.price}
                                            onChange={(e) => handleSalePriceChange(e.target.value)}
                                            className={`${inputClass} pl-8`}
                                            placeholder="0.00"
                                            required
                                        />
                                    </div>
                                    <p className="mt-1 text-xs text-stone-500">What the buyer pays.</p>
                                    <InputError message={errors.price} className="mt-1" />
                                </div>
                            </div>
                            <div className="rounded-xl border border-amber-200 bg-amber-50/80 px-4 py-3">
                                <div className="flex items-start gap-2">
                                    <p className="min-w-0 flex-1 text-sm leading-relaxed text-amber-900">
                                        <span className="font-semibold">Service fee notice:</span> Mummish applies a flat
                                        10% service fee on the final sale price of every successfully completed
                                        transaction. This fee is automatically deducted from the payout balance
                                        transferred to the vendor.
                                    </p>
                                    <button
                                        type="button"
                                        onClick={() => setServiceFeeModalOpen(true)}
                                        className="shrink-0 rounded-full p-1 text-amber-800/80 transition hover:bg-amber-100 hover:text-amber-950"
                                        aria-label="View service fee example"
                                    >
                                        <svg
                                            className="h-5 w-5"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            strokeWidth="1.75"
                                            aria-hidden
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label htmlFor="stock_quantity" className="text-sm font-medium text-stone-700">
                                    Stock Quantity
                                    <RequiredMark />
                                </label>
                                <input
                                    id="stock_quantity"
                                    type="number"
                                    min="1"
                                    step="1"
                                    value={data.stock_quantity}
                                    onChange={(e) =>
                                        setData('stock_quantity', parseInt(e.target.value, 10) || 0)
                                    }
                                    className={inputClass}
                                    required
                                />
                                <InputError message={errors.stock_quantity} className="mt-1" />
                            </div>
                            <div className="flex items-start justify-between gap-4 rounded-xl border border-stone-100 bg-stone-50/80 px-4 py-3">
                                <div>
                                    <p className="text-sm font-semibold text-stone-900">
                                        Enable Customization
                                        <RequiredMark />
                                    </p>
                                    <p className="mt-0.5 text-xs text-stone-600">
                                        Allow customers to request personalized changes to this product.
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    role="switch"
                                    aria-checked={data.allows_customization}
                                    onClick={() => setData('allows_customization', !data.allows_customization)}
                                    className={`relative h-7 w-12 shrink-0 rounded-full transition ${
                                        data.allows_customization ? 'bg-emerald-600' : 'bg-stone-300'
                                    }`}
                                >
                                    <span
                                        className={`absolute top-0.5 left-0.5 h-6 w-6 rounded-full bg-white shadow transition ${
                                            data.allows_customization ? 'translate-x-5' : ''
                                        }`}
                                    />
                                </button>
                            </div>
                        </div>
                    </SectionCard>

                    <div className="hidden lg:block">
                        <MarketplacePreview
                            title={data.title}
                            price={data.price}
                            imageUrl={previewImage}
                            shopName={shopName}
                            categoryLabel={categoryLabel}
                            sizeLabel={requiresClothingSize ? clothingSizeLabel : ''}
                            onLivePreview={openLivePreview}
                        />
                    </div>

                    <div className="lg:hidden">
                        <button
                            type="button"
                            onClick={() => setPreviewOpen((o) => !o)}
                            className="w-full rounded-xl border border-stone-200 bg-white py-3 text-sm font-semibold text-[#5c4d3d] shadow-sm"
                        >
                            {previewOpen ? 'Hide preview' : 'Show marketplace preview'}
                        </button>
                        {previewOpen && (
                            <div className="mt-4">
                                <MarketplacePreview
                                    title={data.title}
                                    price={data.price}
                                    imageUrl={previewImage}
                                    shopName={shopName}
                                    categoryLabel={categoryLabel}
                                    sizeLabel={requiresClothingSize ? clothingSizeLabel : ''}
                                    onLivePreview={openLivePreview}
                                />
                            </div>
                        )}
                    </div>
                </div>
            </form>

            <ProductLivePreviewModal
                open={livePreviewOpen}
                onClose={() => setLivePreviewOpen(false)}
                title={data.title}
                price={data.price}
                description={data.description}
                categoryLabel={categoryLabel}
                brandLabel={brandLabel}
                conditionLabel={conditionLabel}
                sizeLabel={requiresClothingSize ? clothingSizeLabel : ''}
                shopName={shopName}
                materialTags={previewMaterialTags}
                allowsCustomization={data.allows_customization}
                images={previewGallery}
                shopUrl={shopPreviewUrl}
            />

            <Modal
                show={serviceFeeModalOpen}
                onClose={() => setServiceFeeModalOpen(false)}
                maxWidth="md"
            >
                <div className="px-6 py-6">
                    <h2 className="text-lg font-bold text-stone-900">Example</h2>
                    <p className="mt-3 text-sm leading-relaxed text-stone-600">
                        If a vendor sells a Fairly New stroller for GHS 500, Mummish&apos;s 10% commission equals GHS
                        50. The remaining balance of GHS 450 is released to the seller&apos;s wallet.
                    </p>
                    <div className="mt-6 flex justify-end">
                        <button
                            type="button"
                            onClick={() => setServiceFeeModalOpen(false)}
                            className="rounded-lg bg-[#5c4d3d] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#4a3e32]"
                        >
                            Got it
                        </button>
                    </div>
                </div>
            </Modal>
        </VendorLayout>
    );
}
