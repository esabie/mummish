/**
 * Draw a cropped region of an image to a canvas and return a JPEG File.
 * Used with react-easy-crop's pixel crop area.
 *
 * @param {string} imageSrc
 * @param {{ x: number, y: number, width: number, height: number }} pixelCrop
 * @param {{ fileName?: string, mimeType?: string, quality?: number, maxDimension?: number }} [options]
 * @returns {Promise<File>}
 */
export async function getCroppedImageFile(imageSrc, pixelCrop, options = {}) {
    const image = await loadImage(imageSrc);
    const mimeType = options.mimeType ?? 'image/jpeg';
    const quality = options.quality ?? 0.9;
    const maxDimension = options.maxDimension ?? 1200;
    const fileName = options.fileName ?? 'profile.jpg';

    const scale = Math.min(1, maxDimension / Math.max(pixelCrop.width, pixelCrop.height));
    const outputWidth = Math.max(1, Math.round(pixelCrop.width * scale));
    const outputHeight = Math.max(1, Math.round(pixelCrop.height * scale));

    const canvas = document.createElement('canvas');
    canvas.width = outputWidth;
    canvas.height = outputHeight;
    const ctx = canvas.getContext('2d');

    if (!ctx) {
        throw new Error('Could not crop image');
    }

    ctx.drawImage(
        image,
        pixelCrop.x,
        pixelCrop.y,
        pixelCrop.width,
        pixelCrop.height,
        0,
        0,
        outputWidth,
        outputHeight,
    );

    const blob = await new Promise((resolve, reject) => {
        canvas.toBlob(
            (result) => {
                if (!result) {
                    reject(new Error('Could not create image file'));
                    return;
                }
                resolve(result);
            },
            mimeType,
            quality,
        );
    });

    return new File([blob], fileName, {
        type: mimeType,
        lastModified: Date.now(),
    });
}

/**
 * @param {string} src
 * @returns {Promise<HTMLImageElement>}
 */
function loadImage(src) {
    return new Promise((resolve, reject) => {
        const image = new Image();
        image.addEventListener('load', () => resolve(image));
        image.addEventListener('error', () => reject(new Error('Could not load image')));
        image.crossOrigin = 'anonymous';
        image.src = src;
    });
}
