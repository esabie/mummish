import InputError from '@/Components/InputError';
import axios from 'axios';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

function RequiredMark() {
    return <span className="text-red-600" aria-hidden="true"> *</span>;
}

function fileKey(file) {
    return `${file.name}-${file.size}-${file.lastModified}`;
}

function StatusBadge({ status }) {
    if (status === 'checking') {
        return (
            <span className="absolute inset-0 flex items-center justify-center bg-stone-900/40 text-[10px] font-medium text-white">
                Checking…
            </span>
        );
    }

    if (status === 'pass') {
        return (
            <span
                className="absolute bottom-0.5 right-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-emerald-600 text-[10px] text-white"
                aria-label="Image quality OK"
            >
                ✓
            </span>
        );
    }

    if (status === 'fail' || status === 'error') {
        return (
            <span
                className="absolute bottom-0.5 right-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-red-600 text-[10px] text-white"
                aria-label="Image needs improvement"
            >
                !
            </span>
        );
    }

    return null;
}

export default function ProductImagesUpload({
    existingImages,
    newFiles,
    onExistingImagesChange,
    onNewFilesChange,
    minImages,
    maxImages,
    imageRequirements,
    onQualityChange,
    error,
}) {
    const [dragOver, setDragOver] = useState(false);
    const [quality, setQuality] = useState({});
    const fileRef = useRef(null);
    const checkingRef = useRef(new Set());
    const startedRef = useRef(new Set());
    const total = existingImages.length + newFiles.length;
    const minWidth = imageRequirements?.minWidth ?? 800;
    const minHeight = imageRequirements?.minHeight ?? 800;

    const newPreviewUrls = useMemo(
        () => newFiles.map((file) => URL.createObjectURL(file)),
        [newFiles]
    );

    useEffect(() => {
        return () => {
            newPreviewUrls.forEach((url) => URL.revokeObjectURL(url));
        };
    }, [newPreviewUrls]);

    const previews = [
        ...existingImages.map((url) => ({ kind: 'existing', src: url })),
        ...newFiles.map((file, index) => ({ kind: 'new', src: newPreviewUrls[index], file })),
    ];

    const checkImage = useCallback(async (file, key) => {
        if (checkingRef.current.has(key)) {
            return;
        }

        checkingRef.current.add(key);

        setQuality((current) => ({
            ...current,
            [key]: { status: 'checking', messages: [] },
        }));

        const form = new FormData();
        form.append('image', file);

        try {
            const { data } = await axios.post(route('vendor.inventory.check-image'), form, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            setQuality((current) => ({
                ...current,
                [key]: {
                    status: data.pass ? 'pass' : 'fail',
                    messages: data.messages ?? [],
                    issues: data.issues ?? [],
                },
            }));
        } catch {
            setQuality((current) => ({
                ...current,
                [key]: {
                    status: 'error',
                    messages: ['Could not check this image. Please try uploading it again.'],
                },
            }));
        } finally {
            checkingRef.current.delete(key);
        }
    }, []);

    useEffect(() => {
        const activeKeys = new Set(newFiles.map((file) => fileKey(file)));

        startedRef.current.forEach((key) => {
            if (!activeKeys.has(key)) {
                startedRef.current.delete(key);
            }
        });

        newFiles.forEach((file) => {
            const key = fileKey(file);
            if (startedRef.current.has(key)) {
                return;
            }
            startedRef.current.add(key);
            checkImage(file, key);
        });
    }, [newFiles, checkImage]);

    useEffect(() => {
        const activeKeys = new Set(newFiles.map((file) => fileKey(file)));
        setQuality((current) => {
            const next = {};
            Object.entries(current).forEach(([key, value]) => {
                if (activeKeys.has(key)) {
                    next[key] = value;
                }
            });
            return next;
        });
    }, [newFiles]);

    useEffect(() => {
        if (!onQualityChange) {
            return;
        }

        if (newFiles.length === 0) {
            onQualityChange({ allPassed: true, checking: false, failedMessages: [] });
            return;
        }

        const keys = newFiles.map((file) => fileKey(file));
        const states = keys.map((key) => quality[key]).filter(Boolean);
        const checking = states.some((state) => state.status === 'checking') || states.length < keys.length;
        const allPassed =
            !checking &&
            states.length === keys.length &&
            states.every((state) => state.status === 'pass');
        const failedMessages = states
            .filter((state) => state.status === 'fail' || state.status === 'error')
            .flatMap((state) => state.messages ?? []);

        onQualityChange({ allPassed, checking, failedMessages });
    }, [newFiles, quality, onQualityChange]);

    const addFiles = (fileList) => {
        const incoming = Array.from(fileList || []).filter((f) => f.type?.startsWith('image/'));
        if (incoming.length === 0) {
            return;
        }
        const room = maxImages - total;
        if (room <= 0) {
            return;
        }
        onNewFilesChange([...newFiles, ...incoming.slice(0, room)]);
    };

    const removeAt = (index) => {
        if (index < existingImages.length) {
            onExistingImagesChange(existingImages.filter((_, i) => i !== index));
            return;
        }
        const fileIndex = index - existingImages.length;
        onNewFilesChange(newFiles.filter((_, i) => i !== fileIndex));
    };

    const mainPreview = previews[0];
    const failedEntries = newFiles
        .map((file) => ({ file, state: quality[fileKey(file)] }))
        .filter(({ state }) => state && (state.status === 'fail' || state.status === 'error'));

    return (
        <div>
            <p className="mb-2 text-sm text-stone-600">
                Upload at least {minImages} images
                <RequiredMark />
                <span className="text-stone-500">
                    {' '}
                    ({total}/{minImages} minimum, {maxImages} max)
                </span>
            </p>

            <div
                role="button"
                tabIndex={0}
                onClick={() => fileRef.current?.click()}
                onKeyDown={(e) => e.key === 'Enter' && fileRef.current?.click()}
                onDragOver={(e) => {
                    e.preventDefault();
                    setDragOver(true);
                }}
                onDragLeave={() => setDragOver(false)}
                onDrop={(e) => {
                    e.preventDefault();
                    setDragOver(false);
                    addFiles(e.dataTransfer.files);
                }}
                className={`relative flex min-h-[200px] cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed px-4 py-8 text-center transition ${
                    dragOver ? 'border-[#5c4d3d] bg-[#5c4d3d]/5' : 'border-stone-300 bg-stone-50/80 hover:border-stone-400'
                } ${total < minImages ? 'border-amber-300' : ''}`}
            >
                {mainPreview ? (
                    <img src={mainPreview.src} alt="" className="max-h-48 w-full rounded-lg object-contain" />
                ) : (
                    <>
                        <svg className="mb-3 h-10 w-10 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.25">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                        </svg>
                        <p className="text-sm font-medium text-stone-700">Drag & drop images here</p>
                        <p className="mt-1 text-xs text-stone-500">or click to browse (JPEG, PNG, WebP)</p>
                        <p className="mt-2 text-xs text-stone-400">
                            Min {minWidth} × {minHeight}px · clear, well-lit photos
                        </p>
                    </>
                )}
                <input
                    ref={fileRef}
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    multiple
                    className="sr-only"
                    onChange={(e) => {
                        addFiles(e.target.files);
                        e.target.value = '';
                    }}
                />
            </div>

            <div className="mt-3 flex flex-wrap gap-2">
                {previews.map((item, index) => {
                    const qualityKey = item.kind === 'new' ? fileKey(item.file) : null;
                    const qualityState = qualityKey ? quality[qualityKey] : null;

                    return (
                        <div
                            key={`${item.kind}-${qualityKey ?? item.src}-${index}`}
                            className="relative h-16 w-16 overflow-hidden rounded-lg border border-stone-200 bg-stone-100"
                        >
                            <img src={item.src} alt="" className="h-full w-full object-cover" />
                            {item.kind === 'new' && <StatusBadge status={qualityState?.status} />}
                            <button
                                type="button"
                                onClick={() => removeAt(index)}
                                className="absolute right-0.5 top-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-stone-900/70 text-xs text-white hover:bg-stone-900"
                                aria-label="Remove image"
                            >
                                ×
                            </button>
                        </div>
                    );
                })}
                {total < maxImages &&
                    Array.from({ length: Math.max(0, minImages - total) }).map((_, i) => (
                        <button
                            key={`empty-${i}`}
                            type="button"
                            onClick={() => fileRef.current?.click()}
                            className="flex h-16 w-16 items-center justify-center rounded-lg border border-dashed border-amber-300 bg-amber-50/50 text-amber-700"
                            aria-label="Add required image"
                        >
                            <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </button>
                    ))}
            </div>

            {failedEntries.length > 0 && (
                <div className="mt-3 space-y-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2">
                    <p className="text-sm font-semibold text-red-900">Some photos need to be retaken</p>
                    <ul className="space-y-2 text-xs text-red-800">
                        {failedEntries.map(({ file, state }) => (
                            <li key={fileKey(file)}>
                                <span className="font-medium">{file.name}:</span>{' '}
                                {(state.messages ?? []).join(' ')}
                            </li>
                        ))}
                    </ul>
                </div>
            )}

            {total < minImages && (
                <p className="mt-2 text-sm text-amber-800">
                    Add {minImages - total} more image{minImages - total === 1 ? '' : 's'} to continue.
                </p>
            )}

            <p className="mt-4 rounded-lg bg-stone-50 px-3 py-2 text-xs leading-relaxed text-stone-600">
                <span className="font-semibold text-stone-800">Tip:</span> Use bright, natural light and hold your
                phone steady. Each photo is checked for size and clarity as you upload.
            </p>
            <InputError message={error} className="mt-1" />
        </div>
    );
}
