import { getAll } from '../js/db.js';
import { pendingCount } from '../js/outbox.js';
import { dayLabel, hm, nav, pill, sameDay, startOfDay, statusClass, weekDays, weekStrip } from '../js/ui.js';

function slotHtml(reservation, now) {
    const isNow = new Date(reservation.date_debut) <= now
        && (!reservation.date_fin || new Date(reservation.date_fin) >= now);
    return `<div class="slot ${isNow ? 'now' : ''}">
        <time>${hm(reservation.date_debut)}</time>
        <button class="card ${statusClass(reservation.statut)}" data-go="#/rdv/${reservation.id}">
            <strong>${reservation.client?.nom || 'Client'}</strong>
            <span>${reservation.service?.nom || ''} · ${reservation.entreprise_nom || ''}</span>
        </button>
    </div>`;
}

export async function renderAgenda(app, { day, weekMode }) {
    const reservations = await getAll('reservations');
    const now = Date.now();
    const weekStart = startOfDay(day);
    const badge = await pendingCount();

    let body;
    if (weekMode) {
        body = weekDays(weekStart).map((d) => {
            const items = reservations
                .filter((r) => sameDay(r.date_debut, d))
                .sort((a, b) => new Date(a.date_debut) - new Date(b.date_debut));
            return `<section class="week-day">
                <h2>${dayLabel(d)}</h2>
                ${items.map((r) => slotHtml(r, now)).join('') || '<p class="empty tight">Rien.</p>'}
            </section>`;
        }).join('');
    } else {
        const items = reservations
            .filter((r) => sameDay(r.date_debut, day))
            .sort((a, b) => new Date(a.date_debut) - new Date(b.date_debut));
        body = items.map((r) => slotHtml(r, now)).join('')
            || `<p class="empty">Rien de prévu ${dayLabel(day).toLowerCase()}.</p>`;
    }

    app.innerHTML = `
        <header class="top"><h1>${weekMode ? '7 jours' : dayLabel(day)}</h1>${await pill()}</header>
        <div class="day-nav">
            <button data-day="-1">‹</button>
            <div class="day-label">${day.toLocaleDateString('fr-FR')}</div>
            <button data-day="1">›</button>
        </div>
        ${weekStrip(weekStart, day)}
        <div class="mode-row">
            <button class="${weekMode ? '' : 'on'}" data-mode="jour">Jour</button>
            <button class="${weekMode ? 'on' : ''}" data-mode="semaine">7 jours</button>
        </div>
        <div class="timeline">${body}</div>
        <button class="fab" data-fab="now" aria-label="Maintenant">●</button>
        ${nav('today', badge)}`;
}
