function isNativeAndroid() {
    const cap = window.Capacitor;
    if (!cap) {
        return false;
    }
    if (typeof cap.getPlatform === 'function') {
        return cap.getPlatform() === 'android';
    }
    return /android/i.test(navigator.userAgent || '');
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function playUrl(path) {
    return path;
}

function notify(message, type = 'error') {
    if (typeof window.showToast === 'function') {
        window.showToast(message, type);
        return;
    }
    window.alert(message);
}

async function verifyPurchase(result, productId, entrepriseId) {
    const response = await fetch(playUrl('/play-billing/verify'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Capacitor': '1',
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            product_id: result.productId || productId,
            purchase_token: result.purchaseToken,
            order_id: result.orderId || null,
            entreprise_id: entrepriseId || null,
        }),
    });

    const data = await response.json().catch(() => ({}));
    if (!response.ok || !data.ok) {
        throw new Error(data.message || 'Vérification Google Play impossible.');
    }

    return data;
}

export async function purchasePlayProduct(productId, entrepriseId = null) {
    if (!isNativeAndroid()) {
        throw new Error('Google Play Billing est disponible uniquement dans l’application Android.');
    }

    const plugin = window.Capacitor?.Plugins?.PlayBilling;
    if (!plugin?.purchase) {
        throw new Error('Le module de paiement Google Play n’est pas disponible.');
    }

    const result = await plugin.purchase({
        productId,
        productType: 'subs',
    });

    return verifyPurchase(result, productId, entrepriseId);
}

function bindCheckoutForms() {
    if (!isNativeAndroid()) {
        return;
    }

    document.querySelectorAll('form.js-play-billing-form').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const grant = form.dataset.playGrant || form.querySelector('input[name="type"]')?.value;
            const productId = form.dataset.playProduct || window.playProducts?.[grant]?.id;
            const entrepriseId = form.dataset.entrepriseId || null;
            if (!productId) {
                form.submit();
                return;
            }
            const button = form.querySelector('button[type="submit"]');
            if (button) {
                button.disabled = true;
            }

            try {
                await purchasePlayProduct(productId, entrepriseId);
                notify('Abonnement Google Play activé.', 'success');
                window.location.reload();
            } catch (error) {
                notify(error.message || 'Paiement Google Play annulé.');
            } finally {
                if (button) {
                    button.disabled = false;
                }
            }
        });
    });
}

function persistNativeCookie() {
    if (!window.Capacitor) {
        return;
    }

    document.documentElement.classList.add('is-capacitor');
    const host = window.location.hostname.replace(/^[^.]+\./, '');
    const domain = host.includes('.') ? `; domain=.${host}` : '';
    document.cookie = `allotata_native=1; path=/${domain}; max-age=31536000; SameSite=Lax`;
}

async function initNativeChrome() {
    if (!window.Capacitor) {
        return;
    }

    persistNativeCookie();

    try {
        await window.Capacitor.Plugins?.StatusBar?.setBackgroundColor?.({ color: '#0f172a' });
    } catch {
        // Plugin optionnel
    }
}

document.addEventListener('DOMContentLoaded', () => {
    initNativeChrome();
    bindCheckoutForms();
});

window.purchasePlayProduct = purchasePlayProduct;
