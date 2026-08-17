const KEY = 'pocket_token';

export async function getToken() {
    try {
        const Preferences = window.Capacitor?.Plugins?.Preferences;
        if (Preferences?.get) {
            const { value } = await Preferences.get({ key: KEY });
            return value || localStorage.getItem(KEY);
        }
    } catch {
        // fallback
    }
    return localStorage.getItem(KEY);
}

export async function setToken(token) {
    localStorage.setItem(KEY, token);
    try {
        await window.Capacitor?.Plugins?.Preferences?.set?.({ key: KEY, value: token });
    } catch {
        // ignore
    }
}

export async function clearToken() {
    localStorage.removeItem(KEY);
    try {
        await window.Capacitor?.Plugins?.Preferences?.remove?.({ key: KEY });
    } catch {
        // ignore
    }
}

export function tokenFromHandoff(url) {
    try {
        const parsed = new URL(url);
        if (parsed.protocol !== 'allotata:') {
            return null;
        }
        const hash = parsed.hash.startsWith('#') ? parsed.hash.slice(1) : parsed.hash;
        const params = new URLSearchParams(hash);
        return params.get('token');
    } catch {
        if (typeof url === 'string' && url.includes('token=')) {
            return decodeURIComponent(url.split('token=')[1].split('&')[0]);
        }
        return null;
    }
}
