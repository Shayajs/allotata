import { getAll } from '../js/db.js';
import { pendingCount } from '../js/outbox.js';
import { nav } from '../js/ui.js';

export async function renderFactures(app) {
    const factures = await getAll('factures');
    app.innerHTML = `
        <header class="top"><button data-go="#/actions">‹</button><h1>Factures</h1></header>
        <div class="list">${factures.map((f) => `
            <button data-pdf="${f.id}"><strong>${f.numero}</strong><br><span class="meta">${f.montant_ttc ?? ''} € · ${f.date_facture || ''}</span></button>
        `).join('') || '<p class="empty">Aucune facture en cache.</p>'}</div>
        ${nav('actions', await pendingCount())}`;
}
