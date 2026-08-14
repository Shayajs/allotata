/**
 * Compression d'images dans le navigateur, avant l'upload.
 * Réduit le côté max à 1920 px et le poids si le fichier est trop lourd.
 * Ignore GIF (animation), SVG et les non-images.
 */

const MAX_EDGE = 1920;
const MAX_BYTES = 450 * 1024;
const JPEG_QUALITY = 0.82;
const SKIP_TYPES = new Set([
    'image/svg+xml',
    'image/gif',
    'image/x-icon',
    'image/vnd.microsoft.icon',
]);

function canvasToBlob(canvas, mime, quality) {
    return new Promise((resolve) => {
        canvas.toBlob((blob) => resolve(blob), mime, quality);
    });
}

function replaceExtension(name, ext) {
    return name.replace(/\.[^.]+$/, '') + '.' + ext;
}

function sampleHasAlpha(ctx, width, height) {
    const stepX = Math.max(1, Math.floor(width / 48));
    const stepY = Math.max(1, Math.floor(height / 48));
    const data = ctx.getImageData(0, 0, width, height).data;
    for (let y = 0; y < height; y += stepY) {
        for (let x = 0; x < width; x += stepX) {
            if (data[(y * width + x) * 4 + 3] < 250) {
                return true;
            }
        }
    }
    return false;
}

export async function compressImageFile(file, options = {}) {
    if (!(file instanceof Blob) || !file.type.startsWith('image/')) {
        return file;
    }
    if (SKIP_TYPES.has(file.type)) {
        return file;
    }

    const maxEdge = Number(options.maxEdge) || MAX_EDGE;
    const maxBytes = Number(options.maxBytes) || MAX_BYTES;

    let bitmap;
    try {
        bitmap = await createImageBitmap(file);
    } catch {
        return file;
    }

    const needsResize = bitmap.width > maxEdge || bitmap.height > maxEdge;
    const needsShrink = file.size > maxBytes;
    if (!needsResize && !needsShrink) {
        bitmap.close?.();
        return file;
    }

    const scale = Math.min(1, maxEdge / Math.max(bitmap.width, bitmap.height));
    const width = Math.max(1, Math.round(bitmap.width * scale));
    const height = Math.max(1, Math.round(bitmap.height * scale));

    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d', { alpha: true });
    if (!ctx) {
        bitmap.close?.();
        return file;
    }

    ctx.drawImage(bitmap, 0, 0, width, height);
    bitmap.close?.();

    const keepPng = file.type === 'image/png' && sampleHasAlpha(ctx, width, height);
    const mime = keepPng ? 'image/png' : (file.type === 'image/webp' ? 'image/webp' : 'image/jpeg');
    const quality = keepPng ? undefined : JPEG_QUALITY;

    if (!keepPng) {
        ctx.globalCompositeOperation = 'destination-over';
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, width, height);
        ctx.globalCompositeOperation = 'source-over';
    }

    let blob = await canvasToBlob(canvas, mime, quality);
    if (!blob) {
        return file;
    }

    if (!needsResize && blob.size >= file.size) {
        return file;
    }

    const ext = mime === 'image/png' ? 'png' : (mime === 'image/webp' ? 'webp' : 'jpg');
    const name = file.name ? replaceExtension(file.name, ext) : `image.${ext}`;

    return new File([blob], name, { type: mime, lastModified: Date.now() });
}

function inputWantsImages(input) {
    const accept = (input.getAttribute('accept') || '').toLowerCase();
    if (!accept) {
        return true;
    }
    if (accept.includes('image') || accept.includes('.png') || accept.includes('.jpg') || accept.includes('.jpeg') || accept.includes('.webp')) {
        return true;
    }
    return false;
}

function showOverlay(input) {
    const host = input.closest('form') || input.closest('.file-upload-zone') || input.parentElement || document.body;
    const overlay = document.createElement('div');
    overlay.className = 'image-compress-overlay';
    overlay.setAttribute('role', 'status');
    overlay.innerHTML = '<span>Compression de l’image sur cet appareil…</span>';
    overlay.style.cssText = [
        'position:absolute',
        'inset:0',
        'display:flex',
        'align-items:center',
        'justify-content:center',
        'background:rgba(15,23,42,.55)',
        'color:#fff',
        'font-size:13px',
        'font-weight:600',
        'border-radius:inherit',
        'z-index:40',
        'pointer-events:all',
        'text-align:center',
        'padding:12px',
    ].join(';');

    const prev = host.style.position;
    if (!prev || prev === 'static') {
        host.style.position = 'relative';
        overlay.dataset.resetPosition = '1';
    }
    host.appendChild(overlay);
    return () => {
        overlay.remove();
        if (overlay.dataset.resetPosition) {
            host.style.position = prev;
        }
    };
}

export function installImageCompressHook() {
    if (window.__imageCompressHookInstalled) {
        return;
    }
    window.__imageCompressHookInstalled = true;

    document.addEventListener('change', async (event) => {
        const input = event.target;
        if (!(input instanceof HTMLInputElement) || input.type !== 'file') {
            return;
        }
        if (input.hasAttribute('data-no-compress')) {
            return;
        }
        if (input.dataset.compressSkip === '1') {
            return;
        }
        if (!input.files || input.files.length === 0) {
            return;
        }
        if (!inputWantsImages(input)) {
            return;
        }

        const files = Array.from(input.files);
        const hasImage = files.some((file) => file.type.startsWith('image/') && !SKIP_TYPES.has(file.type));
        if (!hasImage) {
            return;
        }

        event.stopImmediatePropagation();
        event.preventDefault();

        const maxEdge = Number(input.dataset.compressMaxEdge) || MAX_EDGE;
        const maybeHeavy = files.some((file) => file.type.startsWith('image/') && file.size > 200 * 1024);
        const removeOverlay = maybeHeavy ? showOverlay(input) : () => {};

        try {
            const transfer = new DataTransfer();
            for (const file of files) {
                transfer.items.add(await compressImageFile(file, { maxEdge }));
            }
            input.dataset.compressSkip = '1';
            input.files = transfer.files;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        } catch (error) {
            console.warn('Compression image ignorée', error);
            input.dataset.compressSkip = '1';
            input.dispatchEvent(new Event('change', { bubbles: true }));
        } finally {
            delete input.dataset.compressSkip;
            removeOverlay();
        }
    }, true);
}

window.compressImageFile = compressImageFile;
