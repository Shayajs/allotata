import { clearToken, getToken, setToken, tokenFromHandoff } from './auth.js';
import { api } from './api.js';
import { get, getAll, meta } from './db.js';
import { replayOutbox } from './outbox.js';
import { call, hideSplash, maps, openWeb, registerFcm, sms } from './native.js';
import { renderLogin } from '../screens/login.js';
import { pullSync } from './sync.js';
import { startOfDay } from './ui.js';
import { renderAgenda } from '../screens/agenda.js';
import { renderRdv } from '../screens/rdv.js';
import { renderClient, renderClients } from '../screens/client.js';
import { renderOutbox } from '../screens/outbox.js';
import { renderFactures } from '../screens/factures.js';
import { renderMessages } from '../screens/messages.js';
import { renderPlus } from '../screens/plus.js';

const app = document.getElementById('app');
let day = startOfDay(new Date());
let weekMode = false;
let welcomeDone = false;

function route() {
    const hash = (location.hash || '#/').replace(/^#/, '');
    const parts = hash.split('/').filter(Boolean);
    return { name: parts[0] || 'today', id: parts[1] || null };
}

function go(path) {
    location.hash = path;
}

async function render() {
    const { name, id } = route();
    const ctx = { go, tryReplay, trySync, render, day, weekMode };
    if (name === 'rdv') {
        return renderRdv(app, id, ctx);
    }
    if (name === 'clients') {
        return renderClients(app);
    }
    if (name === 'client') {
        return renderClient(app, id, ctx);
    }
    if (name === 'actions') {
        return renderOutbox(app);
    }
    if (name === 'factures') {
        return renderFactures(app);
    }
    if (name === 'messages') {
        return renderMessages(app, id);
    }
    if (name === 'plus') {
        return renderPlus(app, ctx);
    }
    if (name === 'login') {
        return renderLogin(app, {
            onReady: async () => {
                await trySync();
                await registerFcm();
                go('#/today');
                await render();
            },
        });
    }
    return renderAgenda(app, { day, weekMode });
}

async function trySync() {
    if (!navigator.onLine) {
        return;
    }
    try {
        await pullSync();
        await replayOutbox();
    } catch (error) {
        if (error.message === 'jeton_invalide') {
            await clearToken();
            go('#/login');
            await render();
        }
    }
}

async function tryReplay() {
    try {
        await replayOutbox();
    } catch {
        // reste en file
    }
}

function bind() {
    app.addEventListener('click', (event) => {
        const goBtn = event.target.closest('[data-go]');
        if (goBtn) {
            go(goBtn.dataset.go);
            return;
        }
        const dayBtn = event.target.closest('[data-day]');
        if (dayBtn) {
            day = startOfDay(new Date(day.getTime() + Number(dayBtn.dataset.day) * 86400000));
            render();
            return;
        }
        const jumpBtn = event.target.closest('[data-jump]');
        if (jumpBtn) {
            day = startOfDay(new Date(Number(jumpBtn.dataset.jump)));
            weekMode = false;
            render();
            return;
        }
        const modeBtn = event.target.closest('[data-mode]');
        if (modeBtn) {
            weekMode = modeBtn.dataset.mode === 'semaine';
            render();
            return;
        }
        const fab = event.target.closest('[data-fab]');
        if (fab) {
            const today = startOfDay(new Date());
            if (day.getTime() !== today.getTime() || weekMode) {
                day = today;
                weekMode = false;
                render();
                return;
            }
            app.querySelector('.slot.now')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
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
    const cached = await get('pdfs', Number(id));
    if (cached?.blob) {
        const url = URL.createObjectURL(cached.blob);
        window.open(url, '_blank');
        return;
    }
    if (!navigator.onLine) {
        return;
    }
    try {
        const blob = await api(`/factures/${id}/pdf`);
        const url = URL.createObjectURL(blob);
        window.open(url, '_blank');
    } catch {
        // ignore
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
    await hideSplash();
    window.addEventListener('hashchange', render);
    window.addEventListener('online', () => trySync().then(render));

    const App = window.Capacitor?.Plugins?.App;
    App?.addListener?.('appUrlOpen', async ({ url }) => {
        const token = tokenFromHandoff(url);
        if (token) {
            await setToken(token);
            setBootStatus('On prépare votre carnet…');
            await trySync();
            go('#/today');
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
        go('#/login');
        await render();
        await hideBoot();
        return;
    }

    setBootStatus('Lecture du carnet…');
    const cached = await getAll('reservations');
    const firstOpen = !(await meta('welcomed'));

    if (firstOpen && !welcomeDone) {
        welcomeDone = true;
        setBootStatus('On prépare votre carnet…');
        await trySync();
        await meta('welcomed', true);
        await registerFcm();
        go('#/today');
        await render();
        await hideBoot();
        return;
    }

    if (cached.length) {
        await render();
        await hideBoot();
        trySync().then(render);
        registerFcm();
        return;
    }

    setBootStatus(navigator.onLine ? 'On prépare votre carnet…' : 'Aucun rendez-vous en cache.');
    await trySync();
    await registerFcm();
    await render();
    await hideBoot();
}

boot();
