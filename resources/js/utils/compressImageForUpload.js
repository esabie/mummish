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

    // Already small enough — skip work (and avoid re-encoding PNG logos badly).
    const maxBytes = options.maxBytes ?? 1_500_000;
    if (file.size <= maxBytes && file.type !== 'image/png') {
        return file;
    }

    const maxDimension = options.maxDimension ?? 2000;
    let quality = options.quality ?? 0.82;

    const bitmap = await loadBitmap(file);
    try {
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

        let blob = await canvasToBlob(canvas, 'image/jpeg', quality);
        // If still large, step quality down once more.
        if (blob && blob.size > maxBytes && quality > 0.6) {
            quality = 0.7;
            blob = await canvasToBlob(canvas, 'image/jpeg', quality);
        }

        if (!blob || blob.size >= file.size) {
            return file;
        }

        const baseName = file.name.replace(/\.[^.]+$/, '') || 'product';
        return new File([blob], `${baseName}.jpg`, {
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
 * @param {File[]} files
 * @returns {Promise<File[]>}
 */
export async function compressImagesForUpload(files) {
    const results = [];
    for (const file of files) {
        results.push(await compressImageForUpload(file));
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
