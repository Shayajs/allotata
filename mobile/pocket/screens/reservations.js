import { api } from '../js/api.js';
import { esc, hm, nav, statusClass } from '../js/ui.js';

const STATUTS = {
    en_attente: 'En attente',
    confirmee: 'Confirmée',
    annulee: 'Annulée',
};

function quand(iso) {
    if (!iso) {
        return '—';
    }
    const d = new Date(iso);
    return `${d.toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric', month: 'short' })} · ${hm(iso)}`;
}

export async function renderReservations(app, { onAuthError }) {
    app.innerHTML = `
        <header class="top"><h1>Réservations</h1></header>
        <p class="empty"><span class="dot"></span>Chargement…</p>
        ${nav('reservations')}`;
    try {
        const data = await api('/mes-reservations?par_page=50');
        const rows = data.donnees || [];
        app.innerHTML = `
            <header class="top"><h1>Réservations</h1></header>
            <div class="list">${rows.map((r) => `
                <div class="card ${statusClass(r.statut)}">
                    <strong>${esc(r.entreprise_nom || r.service?.nom || 'Réservation')}</strong>
                    <span>${esc(quand(r.date_debut))}</span>
                    <span>${esc(STATUTS[r.statut] || r.statut || '')}${r.service?.nom ? ` · ${esc(r.service.nom)}` : ''}</span>
                </div>`).join('') || '<p class="empty">Aucune réservation pour le moment.</p>'}</div>
            ${nav('reservations')}`;
    } catch (error) {
        if (error.message === 'jeton_invalide') {
            await onAuthError();
            return;
        }
        app.innerHTML = `
            <header class="top"><h1>Réservations</h1></header>
            <p class="empty">${esc(error.message)}</p>
            ${nav('reservations')}`;
    }
}
