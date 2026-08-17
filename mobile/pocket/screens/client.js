import { get, getAll } from '../js/db.js';
import { pendingCount } from '../js/outbox.js';
import { nav, pill } from '../js/ui.js';

export async function renderClients(app) {
    const clients = await getAll('clients');
    app.innerHTML = `
        <header class="top"><h1>Clients</h1>${await pill()}</header>
        <div class="list">${clients.map((c) => `
            <button data-go="#/client/${encodeURIComponent(c.id)}">
                <strong>${c.nom || 'Sans nom'}</strong><br>
                <span class="meta">${c.telephone || c.email || ''} · ${c.reservations || 0} RDV</span>
            </button>`).join('') || '<p class="empty">Aucun client en cache.</p>'}
        </div>
        ${nav('clients', await pendingCount())}`;
}

export async function renderClient(app, id, { go }) {
    const c = await get('clients', decodeURIComponent(id));
    if (!c) {
        go('#/clients');
        return;
    }
    app.innerHTML = `
        <header class="top"><button data-go="#/clients">‹</button>${await pill()}</header>
        <div class="sheet">
            <h2>${c.nom || 'Client'}</h2>
            <p class="meta">${c.email || ''}<br>${c.telephone || ''}</p>
            <div class="actions">
                <button data-call="${c.telephone || ''}">Appeler</button>
                <button data-sms="${c.telephone || ''}">SMS</button>
                <button data-map="${c.nom || ''}">Y aller</button>
            </div>
        </div>
        ${nav('clients', await pendingCount())}`;
}
