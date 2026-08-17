import { get, put } from '../js/db.js';
import { enqueue } from '../js/outbox.js';
import { pendingCount } from '../js/outbox.js';
import { hm, nav, pill } from '../js/ui.js';

export async function renderRdv(app, id, { go, tryReplay, render }) {
    const r = await get('reservations', Number(id));
    if (!r) {
        go('#/today');
        return;
    }
    const pending = r.statut === 'en_attente';
    app.innerHTML = `
        <header class="top"><button data-go="#/today">‹</button>${await pill()}</header>
        <div class="sheet">
            <h2>${r.client?.nom || 'Client'}</h2>
            <p class="meta">${hm(r.date_debut)}${r.date_fin ? ' – '+hm(r.date_fin) : ''}<br>${r.service?.nom || ''} · ${r.entreprise_nom || ''}<br>${r.lieu || ''}</p>
            <div class="actions">
                <button data-call="${r.client?.telephone || ''}">Appeler</button>
                <button data-sms="${r.client?.telephone || ''}">SMS</button>
                <button data-map="${r.lieu || r.entreprise_nom || ''}">Y aller</button>
            </div>
            ${pending ? `<div class="row">
                <button class="btn primary" data-act="accepter">Accepter</button>
                <button class="btn danger" data-act="refuser">Refuser</button>
            </div>` : `<p class="meta">Statut : ${r.statut}</p>`}
        </div>
        ${nav('today', await pendingCount())}`;

    app.querySelector('[data-act="accepter"]')?.addEventListener('click', async () => {
        await enqueue('accepter', r);
        r.statut = 'confirmee';
        await put('reservations', r);
        await tryReplay();
        render();
    });
    app.querySelector('[data-act="refuser"]')?.addEventListener('click', async () => {
        await enqueue('refuser', r);
        r.statut = 'annulee';
        await put('reservations', r);
        await tryReplay();
        render();
    });
}
