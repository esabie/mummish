/**
 * Downscale/compress a browser File for upload so phone photos don't trip nginx 413s.
 * Keeps enough resolution for product quality checks (min ~300px).
 *
 * @param {File} file
 * @param {{ maxDimension?: number, maxBytes?: number, quality?: number }} [options]
 * @returns {Promise<File>}
 */
export async function compressImageForUpload(file, options = {}) {
    if (!file?.type?.startsWith('image/')) {
        return file;
    }

    const maxBytes = options.maxBytes ?? 1_500_000;
    // Already small enough — skip work (and avoid re-encoding PNG logos badly).
    if (file.size <= maxBytes && file.type !== 'image/png') {
        return file;
    }

    let maxDimension = options.maxDimension ?? 2000;
    let quality = options.quality ?? 0.82;

    const bitmap = await loadBitmap(file);
    try {
        let bestBlob = null;

        for (let pass = 0; pass < 4; pass += 1) {
            const scale = Math.min(1, maxDimension / Math.max(bitmap.width, bitmap.height));
            const width = Math.max(1, Math.round(bitmap.width * scale));
            const height = Math.max(1, Math.round(bitmap.height * scale));

            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            const ctx = canvas.getContext('2d');
            if (!ctx) {
                return file;
            }
            ctx.drawImage(bitmap, 0, 0, width, height);

            while (quality >= 0.5) {
                const blob = await canvasToBlob(canvas, 'image/jpeg', quality);
                if (blob && (!bestBlob || blob.size < bestBlob.size)) {
                    bestBlob = blob;
                }
                if (blob && blob.size <= maxBytes) {
                    bestBlob = blob;
                    break;
                }
                quality = Math.round((quality - 0.08) * 100) / 100;
            }

            if (bestBlob && bestBlob.size <= maxBytes) {
                break;
            }

            // Still too large — shrink dimensions and try again.
            maxDimension = Math.round(maxDimension * 0.75);
            quality = Math.min(quality, 0.72);
        }

        if (!bestBlob || bestBlob.size >= file.size) {
            return file;
        }

        const baseName = file.name.replace(/\.[^.]+$/, '') || 'product';
        return new File([bestBlob], `${baseName}.jpg`, {
            type: 'image/jpeg',
            lastModified: Date.now(),
        });
    } finally {
        if (typeof bitmap.close === 'function') {
            bitmap.close();
        }
    }
}

/**
 * Product gallery uploads: keep each file small enough that several fit under a ~2 MB nginx limit.
 *
 * @param {File[]} files
 * @returns {Promise<File[]>}
 */
export async function compressImagesForUpload(files) {
    const results = [];
    for (const file of files) {
        results.push(
            await compressImageForUpload(file, {
                maxDimension: 1600,
                maxBytes: 550_000,
                quality: 0.8,
            }),
        );
    }
    return results;
}

/**
 * @param {File} file
 * @returns {Promise<ImageBitmap|HTMLImageElement>}
 */
async function loadBitmap(file) {
    if (typeof createImageBitmap === 'function') {
        return createImageBitmap(file);
    }

    const url = URL.createObjectURL(file);
    try {
        const image = await new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = () => resolve(img);
            img.onerror = () => reject(new Error('Could not read image'));
            img.src = url;
        });
        return image;
    } finally {
        URL.revokeObjectURL(url);
    }
}

/**
 * @param {HTMLCanvasElement} canvas
 * @param {string} type
 * @param {number} quality
 * @returns {Promise<Blob|null>}
 */
function canvasToBlob(canvas, type, quality) {
    return new Promise((resolve) => {
        canvas.toBlob((blob) => resolve(blob), type, quality);
    });
}
