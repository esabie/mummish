import { useCallback, useState } from 'react';
import Cropper from 'react-easy-crop';
import { getCroppedImageFile } from '@/utils/getCroppedImageFile';

/**
 * Modal to crop / adjust an image before upload.
 *
 * @param {{
 *   imageSrc: string,
 *   open: boolean,
 *   fileName?: string,
 *   aspect?: number,
 *   title?: string,
 *   description?: string,
 *   onCancel: () => void,
 *   onSave: (file: File, previewUrl: string) => void,
 * }} props
 */
export default function ImageCropModal({
    imageSrc,
    open,
    fileName = 'image.jpg',
    aspect = 1,
    title = 'Adjust image',
    description = 'Drag to reposition and use the slider to zoom. Save when it looks right.',
    onCancel,
    onSave,
}) {
    const [crop, setCrop] = useState({ x: 0, y: 0 });
    const [zoom, setZoom] = useState(1);
    const [croppedAreaPixels, setCroppedAreaPixels] = useState(null);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');

    const onCropComplete = useCallback((_croppedArea, pixels) => {
        setCroppedAreaPixels(pixels);
    }, []);

    if (!open || !imageSrc) {
        return null;
    }

    const handleSave = async () => {
        if (!croppedAreaPixels) {
            return;
        }

        setSaving(true);
        setError('');

        try {
            const file = await getCroppedImageFile(imageSrc, croppedAreaPixels, {
                fileName: fileName.replace(/\.[^.]+$/, '') + '.jpg',
                maxDimension: 1200,
                quality: 0.9,
            });
            const previewUrl = URL.createObjectURL(file);
            onSave(file, previewUrl);
        } catch {
            setError('Could not save the cropped image. Please try again.');
        } finally {
            setSaving(false);
        }
    };

    return (
        <div className="fixed inset-0 z-[80] flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <button
                type="button"
                className="absolute inset-0 bg-stone-900/60"
                aria-label="Close crop dialog"
                onClick={onCancel}
            />

            <div className="relative z-10 flex w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div className="border-b border-stone-200 px-5 py-4">
                    <h2 className="text-base font-bold text-stone-900">{title}</h2>
                    <p className="mt-1 text-xs text-stone-500">{description}</p>
                </div>

                <div className="relative h-72 w-full bg-stone-900 sm:h-80">
                    <Cropper
                        image={imageSrc}
                        crop={crop}
                        zoom={zoom}
                        aspect={aspect}
                        cropShape="rect"
                        showGrid
                        onCropChange={setCrop}
                        onZoomChange={setZoom}
                        onCropComplete={onCropComplete}
                        classes={{
                            containerClassName: 'rounded-none',
                        }}
                        style={{
                            cropAreaStyle: {
                                border: '2px solid #5c4d3d',
                                boxShadow: '0 0 0 9999px rgba(0, 0, 0, 0.45)',
                            },
                        }}
                    />
                </div>

                <div className="space-y-4 px-5 py-4">
                    <div>
                        <label htmlFor="crop-zoom" className="text-xs font-medium text-stone-600">
                            Zoom
                        </label>
                        <input
                            id="crop-zoom"
                            type="range"
                            min={1}
                            max={3}
                            step={0.05}
                            value={zoom}
                            onChange={(e) => setZoom(Number(e.target.value))}
                            className="mt-2 w-full accent-[#5c4d3d]"
                        />
                    </div>

                    {error ? <p className="text-sm text-red-600">{error}</p> : null}

                    <div className="flex items-center justify-end gap-3">
                        <button
                            type="button"
                            onClick={onCancel}
                            disabled={saving}
                            className="rounded-lg px-4 py-2 text-sm font-semibold text-stone-700 transition hover:bg-stone-100 disabled:opacity-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            onClick={handleSave}
                            disabled={saving || !croppedAreaPixels}
                            className="rounded-lg bg-[#5c4d3d] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#4a3e32] disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {saving ? 'Saving…' : 'Save'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
