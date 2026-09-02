import './bootstrap';
import './address-autocomplete';
import './presence';
import { installImageCompressHook } from './image-compress';
import { isPushSupported, isPushSubscribed, subscribeToPush, sendSubscriptionToServer } from './push-notifications';
import './play-billing';
import './android-shell';
import './search-geolocation';

installImageCompressHook();

// ========================================
// Flatpickr — Dates en français
// ========================================
import flatpickr from 'flatpickr';
import { French } from 'flatpickr/dist/l10n/fr.js';

flatpickr.localize(French);

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('input[type="date"]').forEach(function (input) {
        // Remplissage programmatique (ex. agenda public) : garder le natif
        if (input.hasAttribute('data-no-flatpickr')) {
            return;
        }
        // Conserver la valeur existante
        const currentValue = input.value;
        // Changer le type pour éviter le picker natif
        input.type = 'text';
        input.setAttribute('autocomplete', 'off');
        // Initialiser Flatpickr
        flatpickr(input, {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/m/Y',
            allowInput: true,
            defaultDate: currentValue || null,
            // Respecter min/max natifs
            minDate: input.getAttribute('min') || undefined,
            maxDate: input.getAttribute('max') || undefined,
        });
    });
});

// Charger les scripts des blocs de cours uniquement sur les pages de cours
// Doit être fait après le DOMContentLoaded car le contenu est généré dynamiquement
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('[data-video-block-id], .course-text-content')) {
        import('./course-blocks.js');
    }
});

// ========================================
// Gestion du thème clair/foncé avec cookies
// ========================================

// Fonction pour lire un cookie
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

// Fonction pour définir un cookie (expire dans 1 an)
function setCookie(name, value) {
    const expires = new Date();
    expires.setFullYear(expires.getFullYear() + 1);
    document.cookie = `${name}=${value}; expires=${expires.toUTCString()}; path=/; SameSite=Lax`;
}

// Fonction pour appliquer le thème
function applyTheme() {
    const savedTheme = getCookie('theme');
    if (savedTheme === 'dark') {
        document.documentElement.classList.add('dark');
    } else if (savedTheme === 'light') {
        document.documentElement.classList.remove('dark');
    } else {
        // Mode auto : suivre les paramètres du système
        if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }
}

// Fonction pour basculer le thème
function toggleTheme() {
    const html = document.documentElement;
    html.classList.toggle('dark');

    // Sauvegarder la préférence dans un cookie
    const theme = html.classList.contains('dark') ? 'dark' : 'light';
    setCookie('theme', theme);
}

// ========================================
// Toggle visibilité mot de passe
// ========================================
function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    btn.querySelector('.eye-off').classList.toggle('hidden', isPassword);
    btn.querySelector('.eye-on').classList.toggle('hidden', !isPassword);
    btn.setAttribute('aria-label', isPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
}

// Exposer les fonctions globalement
window.toggleTheme = toggleTheme;
window.applyTheme = applyTheme;
window.togglePasswordVisibility = togglePasswordVisibility;

// Attacher les événements aux boutons de thème après le chargement du DOM
document.addEventListener('DOMContentLoaded', function () {
    // Sélectionner tous les boutons de thème (par classe ou par id)
    const themeButtons = document.querySelectorAll('.theme-toggle-btn, #theme-toggle');

    themeButtons.forEach(function (button) {
        button.addEventListener('click', toggleTheme);
    });
});

// Écouter les changements de préférence système (pour le mode auto)
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (e) {
    // Ne réagir que si aucune préférence n'est sauvegardée (mode auto)
    if (!getCookie('theme')) {
        if (e.matches) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }
});

// ========================================
// Push Notifications — Re-souscription silencieuse
// ========================================
(async function initPushResubscribe() {
    if (!isPushSupported() || !window.currentUserId || !window.VAPID_PUBLIC_KEY) return;
    if (Notification.permission !== 'granted') return;

    try {
        const alreadySubscribed = await isPushSubscribed();
        if (alreadySubscribed) return;

        const subscription = await subscribeToPush(window.VAPID_PUBLIC_KEY);
        if (subscription) {
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            if (csrfMeta) {
                await sendSubscriptionToServer(subscription, csrfMeta.content);
            }
        }
    } catch (e) {
        // Silencieux — ne pas bloquer le chargement de la page
    }
})();

// ========================================
// Détection PWA (display-mode: standalone)
// ========================================
const isPwa = window.matchMedia('(display-mode: standalone)').matches
    || window.navigator.standalone === true;
if (isPwa) {
    document.documentElement.classList.add('is-pwa');
}
window.matchMedia('(display-mode: standalone)').addEventListener('change', function (e) {
    if (e.matches) {
        document.documentElement.classList.add('is-pwa');
    } else {
        document.documentElement.classList.remove('is-pwa');
    }
});

// ========================================
// Détection online/offline
// ========================================
function setOfflineState(isOffline) {
    var html = document.documentElement;
    var banner = document.getElementById('offline-banner');
    var reconnectBanner = document.getElementById('reconnect-banner');

    if (isOffline) {
        html.classList.add('is-offline');
        if (banner) banner.classList.remove('hidden');
    } else {
        var wasOffline = html.classList.contains('is-offline');
        html.classList.remove('is-offline');
        if (banner) banner.classList.add('hidden');

        if (wasOffline && reconnectBanner) {
            reconnectBanner.classList.remove('hidden');
            setTimeout(function () {
                reconnectBanner.classList.add('hidden');
            }, 3000);
        }
    }
}

setOfflineState(!navigator.onLine);
window.addEventListener('online', function () { setOfflineState(false); });
window.addEventListener('offline', function () { setOfflineState(true); });

// ========================================
// Pre-cache des pages utilisateur pour l'offline
// ========================================
(function precacheUserPages() {
    if (!('serviceWorker' in navigator) || !navigator.onLine) return;
    var urls = window.offlinePrecacheUrls;
    if (!urls || !urls.length) return;

    navigator.serviceWorker.ready.then(function (registration) {
        if (!registration.active) return;
        registration.active.postMessage({
            type: 'PRECACHE_URLS',
            urls: urls,
        });
    });
})();
