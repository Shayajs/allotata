import { api } from '../js/api.js';
import { esc, nav } from '../js/ui.js';

export async function renderFactures(app, { onAuthError }) {
    app.innerHTML = `
        <header class="top"><button data-go="#/home">‹</button><h1>Factures</h1></header>
        <p class="empty"><span class="dot"></span>Chargement…</p>
        ${nav('home')}`;
    try {
        const data = await api('/factures?par_page=50');
        const rows = data.donnees || [];
        app.innerHTML = `
            <header class="top"><button data-go="#/home">‹</button><h1>Factures</h1></header>
            <div class="list">${rows.map((f) => `
                <button type="button" data-pdf="${f.id}">
                    <strong>${esc(f.numero || 'Facture')}</strong>
                    <span class="meta">${esc(f.montant_ttc != null ? `${f.montant_ttc} €` : '')}${f.date_facture ? ` · ${esc(f.date_facture)}` : ''}</span>
                </button>`).join('') || '<p class="empty">Aucune facture pour le moment.</p>'}</div>
            ${nav('home')}`;
    } catch (error) {
        if (error.message === 'jeton_invalide') {
            await onAuthError();
            return;
        }
        app.innerHTML = `
            <header class="top"><button data-go="#/home">‹</button><h1>Factures</h1></header>
            <p class="empty">${esc(error.message)}</p>
            ${nav('home')}`;
    }
}