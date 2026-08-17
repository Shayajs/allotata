import { CHECKOUT_URL, DASH_URL, SETTINGS_URL } from './config.js';

export async function hideSplash() {
    try {
        await window.Capacitor?.Plugins?.SplashScreen?.hide?.();
    } catch {
        // splash déjà masqué
    }
}

export async function openWeb(url) {
    const Browser = window.Capacitor?.Plugins?.Browser;
    if (Browser?.open) {
        await Browser.open({ url });
        return;
    }
    window.open(url, '_blank');
}

export function call(phone) {
    if (phone) {
        window.location.href = `tel:${phone}`;
    }
}

export function sms(phone) {
    if (phone) {
        window.location.href = `sms:${phone}`;
    }
}

export function maps(query) {
    if (query) {
        window.location.href = `geo:0,0?q=${encodeURIComponent(query)}`;
    }
}

export async function snapshotNextRdv(reservations) {
    const next = (reservations || [])
        .filter((r) => r.statut === 'confirmee' && r.date_debut && new Date(r.date_debut) >= new Date())
        .sort((a, b) => new Date(a.date_debut) - new Date(b.date_debut))[0];

    const payload = next
        ? {
            titre: next.client?.nom || next.service?.nom || 'Rendez-vous',
            quand: new Date(next.date_debut).toLocaleString('fr-FR', {
                weekday: 'short',
                day: 'numeric',
                month: 'short',
                hour: '2-digit',
                minute: '2-digit',
            }),
            lieu: next.lieu || next.entreprise_nom || '',
        }
        : { titre: 'Aucun rendez-vous', quand: '', lieu: '' };

    try {
        await window.Capacitor?.Plugins?.PocketSnapshot?.saveNextRdv?.(payload);
    } catch {
        // plugin optionnel
    }
}

export const webLinks = {
    dash: DASH_URL,
    settings: SETTINGS_URL,
    checkout: CHECKOUT_URL,
};

let fcmBound = false;

export async function registerFcm() {
    const Push = window.Capacitor?.Plugins?.PushNotifications;
    if (!Push?.register) {
        return;
    }
    try {
        if (!fcmBound) {
            fcmBound = true;
            Push.addListener('registration', async ({ value }) => {
                const { post } = await import('./api.js');
                await post('/device/fcm', { token: value, device: 'android' });
            });
        }
        await Push.requestPermissions();
        await Push.register();
    } catch {
        // FCM optionnel sans google-services.json
    }
}
