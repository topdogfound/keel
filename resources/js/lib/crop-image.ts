export type PixelCrop = {
    x: number;
    y: number;
    width: number;
    height: number;
};

function loadImage(src: string): Promise<HTMLImageElement> {
    return new Promise((resolve, reject) => {
        const image = new Image();
        image.addEventListener('load', () => resolve(image));
        image.addEventListener('error', reject);
        image.crossOrigin = 'anonymous';
        image.src = src;
    });
}

function toRadians(degrees: number): number {
    return (degrees * Math.PI) / 180;
}

/**
 * The rotated image's bounding box: rotating a w×h rectangle by `rotation`
 * degrees needs a canvas at least this big to avoid clipping the corners.
 */
function rotatedSize(width: number, height: number, rotation: number) {
    const radians = toRadians(rotation);

    return {
        width:
            Math.abs(Math.cos(radians) * width) +
            Math.abs(Math.sin(radians) * height),
        height:
            Math.abs(Math.sin(radians) * width) +
            Math.abs(Math.cos(radians) * height),
    };
}

/**
 * Renders the source image rotated onto an oversized canvas, then extracts
 * just the cropped pixel rectangle onto a second canvas and exports it as a
 * JPEG blob — the standard two-pass recipe for combining rotation with
 * react-easy-crop's pixel crop, since the crop rectangle is computed in the
 * rotated image's coordinate space.
 */
export async function getCroppedImageBlob(
    imageSrc: string,
    crop: PixelCrop,
    rotation: number,
): Promise<Blob> {
    const image = await loadImage(imageSrc);

    const { width: rotatedWidth, height: rotatedHeight } = rotatedSize(
        image.width,
        image.height,
        rotation,
    );

    const rotationCanvas = document.createElement('canvas');
    rotationCanvas.width = rotatedWidth;
    rotationCanvas.height = rotatedHeight;

    const rotationContext = rotationCanvas.getContext('2d');
    if (!rotationContext) {
        throw new Error('Canvas 2D context is not available.');
    }

    rotationContext.translate(rotatedWidth / 2, rotatedHeight / 2);
    rotationContext.rotate(toRadians(rotation));
    rotationContext.drawImage(image, -image.width / 2, -image.height / 2);

    const outputCanvas = document.createElement('canvas');
    outputCanvas.width = crop.width;
    outputCanvas.height = crop.height;

    const outputContext = outputCanvas.getContext('2d');
    if (!outputContext) {
        throw new Error('Canvas 2D context is not available.');
    }

    outputContext.drawImage(
        rotationCanvas,
        crop.x,
        crop.y,
        crop.width,
        crop.height,
        0,
        0,
        crop.width,
        crop.height,
    );

    return new Promise((resolve, reject) => {
        outputCanvas.toBlob(
            (blob) =>
                blob
                    ? resolve(blob)
                    : reject(new Error('Failed to export cropped image.')),
            'image/jpeg',
            0.92,
        );
    });
}
