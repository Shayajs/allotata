import { clearToken, getToken, setToken, tokenFromHandoff } from './auth.js';
import { api } from './api.js';
import { hideSplash, maps, openWeb, call, sms } from './native.js';
import { getHosts } from './config.js';
import { renderPitch } from '../screens/pitch.js';
import { renderLogin } from '../screens/login.js';
import { renderRegister } from '../screens/register.js';
import { renderVerify } from '../screens/verify.js';
import { renderHome } from '../screens/home.js';
import { renderReservations } from '../screens/reservations.js';
import { renderPlus } from '../screens/plus.js';
import { renderFactures } from '../screens/factures.js';
import { renderMessages } from '../screens/messages.js';

const app = document.getElementById('app');
const GUEST = new Set(['', 'pitch', 'login', 'register', 'verify']);
const MEMBER = new Set(['home', 'reservations', 'plus', 'messages', 'factures']);
let previousRoute = '';

function route() {
    const hash = (location.hash || '#/').replace(/^#/, '');
    const parts = hash.split('/').filter(Boolean);
    return { name: parts[0] || '', id: parts[1] || null };
}

function go(path) {
    location.hash = path;
}

function setGuest(on) {
    document.body.classList.toggle('guest', on);
}

async function onAuthError() {
    await clearToken();
    go('#/');
    await render();
}

async function render() {
    const token = await getToken();
    const { name, id } = route();
    const from = previousRoute;
    previousRoute = name || (token ? 'home' : 'pitch');
    const ctx = { go, onAuthError };

    if (!token) {
        setGuest(true);
        if (name === 'login') {
            return renderLogin(app, {
                go,
                onReady: async () => {
                    go('#/home');
                    await render();
                },
            });
        }
        if (name === 'register') {
            return renderRegister(app, { go, reset: from !== 'register' });
        }
        if (name === 'verify') {
            return renderVerify(app);
        }
        if (name && !GUEST.has(name)) {
            go('#/');
        }
        return renderPitch(app);
    }

    setGuest(false);
    if (!name || GUEST.has(name)) {
        go('#/home');
        return renderHome(app, ctx);
    }
    if (name === 'reservations') {
        return renderReservations(app, ctx);
    }
    if (name === 'plus') {
        return renderPlus(app, ctx);
    }
    if (name === 'messages') {
        return renderMessages(app, id, ctx);
    }
    if (name === 'factures') {
        return renderFactures(app, ctx);
    }
    if (name === 'home' || MEMBER.has(name)) {
        return renderHome(app, ctx);
    }
    go('#/home');
    return renderHome(app, ctx);
}

function bind() {
    app.addEventListener('click', (event) => {
        const goBtn = event.target.closest('[data-go]');
        if (goBtn) {
            go(goBtn.dataset.go);
            return;
        }
        const callBtn = event.target.closest('[data-call]');
        if (callBtn) {
            call(callBtn.dataset.call);
            return;
        }
        const smsBtn = event.target.closest('[data-sms]');
        if (smsBtn) {
            sms(smsBtn.dataset.sms);
            return;
        }
        const mapBtn = event.target.closest('[data-map]');
        if (mapBtn) {
            maps(mapBtn.dataset.map);
            return;
        }
        const webBtn = event.target.closest('[data-web]');
        if (webBtn) {
            event.preventDefault();
            event.stopPropagation();
            openWeb(webBtn.dataset.web);
            return;
        }
        const pdfBtn = event.target.closest('[data-pdf]');
        if (pdfBtn) {
            openPdf(pdfBtn.dataset.pdf);
        }
    });
}

async function openPdf(id) {
    if (!navigator.onLine) {
        return;
    }
    try {
        const blob = await api(`/factures/${id}/pdf`);
        const url = URL.createObjectURL(blob);
        window.open(url, '_blank');
    } catch (error) {
        if (error.message === 'jeton_invalide') {
            await onAuthError();
        }
    }
}

function setBootStatus(text) {
    const el = document.getElementById('boot-status');
    if (el) {
        el.textContent = text;
    }
}

async function hideBoot() {
    document.getElementById('boot')?.classList.add('out');
    await hideSplash();
    window.setTimeout(() => document.getElementById('boot')?.remove(), 400);
}

async function boot() {
    bind();
    await getHosts();
    await hideSplash();
    window.addEventListener('hashchange', render);

    const App = window.Capacitor?.Plugins?.App;
    App?.addListener?.('appUrlOpen', async ({ url }) => {
        const token = tokenFromHandoff(url);
        if (token) {
            await setToken(token);
            setBootStatus('C’est bon.');
            go('#/home');
            await render();
            await hideBoot();
        }
    });

    const launch = await App?.getLaunchUrl?.();
    if (launch?.url) {
        const token = tokenFromHandoff(launch.url);
        if (token) {
            await setToken(token);
        }
    }

    const token = await getToken();
    if (!token) {
        if (!GUEST.has(route().name)) {
            go('#/');
        }
        await render();
        await hideBoot();
        return;
    }

    setBootStatus('Ouverture de votre espace…');
    if (!MEMBER.has(route().name)) {
        go('#/home');
    }
    await render();
    await hideBoot();
}

boot().catch(async (error) => {
    console.error(error);
    try {
        go('#/');
        await render();
        await hideBoot();
    } catch {
        await hideSplash();
        app.innerHTML = `<div class="auth"><h1>Allotata n’a pas pu s’ouvrir.</h1><p class="auth-lead">Relance l’app. Si ça continue, réinstalle l’APK.</p></div>`;
    }
});
