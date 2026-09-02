/**
 * Géolocalisation pour la recherche.
 * Demande explicite (clic) — jamais de prompt silencieux.
 * Si la permission est déjà accordée, réutilise la position sans re-demander.
 */

const OPT_OUT_KEY = 'allotata_geo_opt_out';
const BANNER_DISMISS_KEY = 'allotata_geo_banner_dismissed';

const GEO_OPTIONS = {
    enableHighAccuracy: true,
    timeout: 12000,
    maximumAge: 120000,
};

function isOptedOut() {
    try {
        return localStorage.getItem(OPT_OUT_KEY) === '1';
    } catch {
        return false;
    }
}

function setOptedOut(value) {
    try {
        if (value) {
            localStorage.setItem(OPT_OUT_KEY, '1');
        } else {
            localStorage.removeItem(OPT_OUT_KEY);
        }
    } catch {
        // quota / mode privé
    }
}

function isBannerDismissed() {
    try {
        return localStorage.getItem(BANNER_DISMISS_KEY) === '1';
    } catch {
        return false;
    }
}

function dismissBanner() {
    try {
        localStorage.setItem(BANNER_DISMISS_KEY, '1');
    } catch {
        // ignore
    }
}

function geoPlugin() {
    return window.Capacitor?.Plugins?.Geolocation || null;
}

function isSupported() {
    return Boolean(geoPlugin()?.getCurrentPosition) || Boolean(navigator.geolocation);
}

async function permissionState() {
    const Geo = geoPlugin();
    if (Geo?.checkPermissions) {
        try {
            const result = await Geo.checkPermissions();
            return result.location || result.coarseLocation || 'prompt';
        } catch {
            // fallback navigateur
        }
    }

    if (navigator.permissions?.query) {
        try {
            const status = await navigator.permissions.query({ name: 'geolocation' });
            return status.state;
        } catch {
            // Safari / WebView
        }
    }

    return 'prompt';
}

function getCurrentPosition() {
    const Geo = geoPlugin();
    if (Geo?.getCurrentPosition) {
        return Geo.getCurrentPosition(GEO_OPTIONS).then((pos) => ({
            lat: pos.coords.latitude,
            lng: pos.coords.longitude,
        }));
    }

    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject(new Error('unsupported'));
            return;
        }
        navigator.geolocation.getCurrentPosition(
            (pos) => resolve({
                lat: pos.coords.latitude,
                lng: pos.coords.longitude,
            }),
            reject,
            GEO_OPTIONS,
        );
    });
}

async function requestPosition() {
    const Geo = geoPlugin();
    if (Geo?.requestPermissions) {
        const perm = await Geo.requestPermissions();
        const state = perm.location || perm.coarseLocation;
        if (state && state !== 'granted') {
            const error = new Error('denied');
            error.code = 1;
            throw error;
        }
    }

    return getCurrentPosition();
}

function searchForms() {
    return [...document.querySelectorAll('[data-search-geo-form]')];
}

function ensureHidden(form, name, value) {
    let input = form.querySelector(`input[name="${name}"]`);
    if (!input) {
        input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        form.appendChild(input);
    }
    input.value = value == null ? '' : String(value);
}

function attachToForms(lat, lng) {
    searchForms().forEach((form) => {
        ensureHidden(form, 'user_lat', lat);
        ensureHidden(form, 'user_lng', lng);
        form.querySelectorAll('[name="forget_geo"]').forEach((el) => el.remove());
    });
}

function currentSource() {
    return document.querySelector('[data-search-geo-root]')?.dataset.geoSource || '';
}

function isSearchPage() {
    return Boolean(document.getElementById('search-form-results'))
        || /\/search\/?$/.test(window.location.pathname);
}

function setStatus(message, isError = false) {
    document.querySelectorAll('[data-geo-status]').forEach((el) => {
        el.textContent = message || '';
        el.classList.toggle('hidden', !message);
        el.classList.toggle('text-red-600', isError);
        el.classList.toggle('dark:text-red-400', isError);
        el.classList.toggle('text-slate-500', !isError);
    });
}

function setLocating(locating) {
    document.querySelectorAll('[data-geo-enable]').forEach((btn) => {
        btn.disabled = locating;
        btn.classList.toggle('opacity-60', locating);
    });
}

function syncUi({ active, denied, unsupported }) {
    document.querySelectorAll('[data-geo-label]').forEach((el) => {
        if (unsupported) {
            el.textContent = 'Position indisponible';
        } else if (denied) {
            el.textContent = 'Position bloquée';
        } else if (active) {
            el.textContent = 'Position activée';
        } else {
            el.textContent = 'Utiliser ma position';
        }
    });

    document.querySelectorAll('[data-geo-enable]').forEach((btn) => {
        btn.classList.toggle('ring-2', active);
        btn.classList.toggle('ring-green-500', active);
        btn.classList.toggle('text-green-700', active);
        btn.classList.toggle('dark:text-green-300', active);
        btn.disabled = Boolean(unsupported || denied);
    });

    document.querySelectorAll('[data-geo-disable]').forEach((btn) => {
        btn.classList.toggle('hidden', !active);
    });

    document.querySelectorAll('[data-geo-banner]').forEach((banner) => {
        const show = !active && !denied && !unsupported && !isBannerDismissed();
        banner.classList.toggle('hidden', !show);
    });
}

function hasCityFilter() {
    const params = new URLSearchParams(window.location.search);
    return Boolean(params.get('ville_filter'));
}

function alreadyHasGpsParams() {
    const params = new URLSearchParams(window.location.search);
    return Boolean(params.get('user_lat') && params.get('user_lng'));
}

function navigateWithPosition(lat, lng, { clearCity = false } = {}) {
    const form = document.getElementById('search-form-results') || document.getElementById('search-form');
    if (form) {
        attachToForms(lat, lng);
        if (clearCity) {
            const ville = form.querySelector('[name="ville_filter"]');
            if (ville) {
                ville.value = '';
            }
            ['ville_lat', 'ville_lng'].forEach((name) => {
                const input = form.querySelector(`[name="${name}"]`);
                if (input) {
                    input.value = '';
                }
            });
        }
        form.submit();
        return;
    }

    const url = new URL('/search', window.location.origin);
    url.searchParams.set('user_lat', String(lat));
    url.searchParams.set('user_lng', String(lng));
    window.location.assign(url.toString());
}

function geolocationErrorMessage(error) {
    if (error?.code === 1 || error?.message === 'denied') {
        return 'La géolocalisation est bloquée dans votre navigateur.';
    }
    if (error?.code === 3) {
        return 'La localisation a pris trop de temps. Réessayez.';
    }
    if (error?.message === 'unsupported') {
        return 'Votre appareil ne permet pas la géolocalisation.';
    }
    return 'Impossible d’obtenir votre position.';
}

async function enablePosition({ navigate }) {
    setOptedOut(false);
    setLocating(true);
    setStatus('Localisation…');
    try {
        const { lat, lng } = await requestPosition();
        attachToForms(lat, lng);
        setStatus('');
        syncUi({ active: true, denied: false, unsupported: false });
        if (navigate) {
            navigateWithPosition(lat, lng, { clearCity: true });
        }
    } catch (error) {
        const denied = error?.code === 1 || error?.message === 'denied';
        setStatus(geolocationErrorMessage(error), true);
        syncUi({
            active: false,
            denied,
            unsupported: error?.message === 'unsupported',
        });
    } finally {
        setLocating(false);
    }
}

function disablePosition() {
    setOptedOut(true);
    searchForms().forEach((form) => {
        ensureHidden(form, 'user_lat', '');
        ensureHidden(form, 'user_lng', '');
        ensureHidden(form, 'forget_geo', '1');
    });
    syncUi({ active: false, denied: false, unsupported: false });

    if (isSearchPage()) {
        const form = document.getElementById('search-form-results') || document.getElementById('search-form');
        if (form) {
            form.submit();
            return;
        }
        const url = new URL(window.location.href);
        url.searchParams.delete('user_lat');
        url.searchParams.delete('user_lng');
        url.searchParams.set('forget_geo', '1');
        window.location.assign(url.toString());
    }
}

function bindControls() {
    document.querySelectorAll('[data-geo-enable]').forEach((btn) => {
        btn.addEventListener('click', () => enablePosition({ navigate: true }));
    });
    document.querySelectorAll('[data-geo-disable]').forEach((btn) => {
        btn.addEventListener('click', disablePosition);
    });
    document.querySelectorAll('[data-geo-banner-activate]').forEach((btn) => {
        btn.addEventListener('click', () => enablePosition({ navigate: true }));
    });
    document.querySelectorAll('[data-geo-banner-dismiss]').forEach((btn) => {
        btn.addEventListener('click', () => {
            dismissBanner();
            document.querySelectorAll('[data-geo-banner]').forEach((banner) => {
                banner.classList.add('hidden');
            });
        });
    });
}

export async function initSearchGeolocation() {
    if (!document.querySelector('[data-search-geo-form], [data-search-geo-root]')) {
        return;
    }

    bindControls();

    const unsupported = !isSupported();
    if (unsupported) {
        syncUi({ active: false, denied: false, unsupported: true });
        return;
    }

    const root = document.querySelector('[data-search-geo-root]');
    const alreadyBrowser = currentSource() === 'browser';
    if (alreadyBrowser) {
        const lat = root?.dataset.geoLat || new URLSearchParams(window.location.search).get('user_lat');
        const lng = root?.dataset.geoLng || new URLSearchParams(window.location.search).get('user_lng');
        if (lat && lng) {
            attachToForms(lat, lng);
        }
        syncUi({ active: true, denied: false, unsupported: false });
        return;
    }

    const state = await permissionState();
    if (state === 'denied') {
        syncUi({ active: false, denied: true, unsupported: false });
        return;
    }

    if (state === 'granted' && !isOptedOut()) {
        setLocating(true);
        try {
            const { lat, lng } = await getCurrentPosition();
            attachToForms(lat, lng);
            syncUi({ active: true, denied: false, unsupported: false });
            if (isSearchPage() && !alreadyHasGpsParams() && !hasCityFilter()) {
                navigateWithPosition(lat, lng, { clearCity: false });
                return;
            }
        } catch (error) {
            const denied = error?.code === 1;
            syncUi({ active: false, denied, unsupported: false });
            if (denied) {
                setStatus(geolocationErrorMessage(error), true);
            }
        } finally {
            setLocating(false);
        }
        return;
    }

    syncUi({ active: false, denied: false, unsupported: false });
}

document.addEventListener('DOMContentLoaded', () => {
    initSearchGeolocation();
});
