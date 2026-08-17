import { clearToken } from '../js/auth.js';
import { meta } from '../js/db.js';
import { pendingCount } from '../js/outbox.js';
import { webLinks } from '../js/native.js';
import { nav, pill } from '../js/ui.js';

export async function renderPlus(app, { trySync, render }) {
    const compte = await meta('compte');
    app.innerHTML = `
        <header class="top"><h1>Plus</h1>${await pill()}</header>
        <div class="list">
            <div>${compte?.nom || ''} · ${compte?.email || ''}</div>
            <button data-web="${webLinks.dash}">Tableau de bord (site)</button>
            <button data-web="${webLinks.settings}">Réglages (site)</button>
            <button data-web="${webLinks.checkout}">Paiement (site)</button>
            <button id="resync">Synchroniser</button>
            <button id="logout">Déconnexion</button>
        </div>
        ${nav('plus', await pendingCount())}`;
    document.getElementById('resync')?.addEventListener('click', async () => {
        await trySync();
        render();
    });
    document.getElementById('logout')?.addEventListener('click', async () => {
        await clearToken();
        location.hash = '#/login';
    });
}
