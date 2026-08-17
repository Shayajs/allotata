import { getAll } from '../js/db.js';
import { nav, pill } from '../js/ui.js';

export async function renderOutbox(app) {
    const items = await getAll('outbox');
    const factures = await getAll('factures');
    const convs = await getAll('conversations');
    app.innerHTML = `
        <header class="top"><h1>Actions</h1>${await pill()}</header>
        <div class="list">
            ${items.map((i) => `<div><strong>${i.action}</strong> · RDV #${i.reservation_id}</div>`).join('') || '<p class="empty">Aucune action en attente.</p>'}
            <button data-go="#/factures">Factures (${factures.length})</button>
            <button data-go="#/messages">Messages (${convs.length})</button>
        </div>
        ${nav('actions', items.length)}`;
}
