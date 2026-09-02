export function startOfDay(date) {
    const d = new Date(date);
    d.setHours(0, 0, 0, 0);
    return d;
}

export function sameDay(isoOrDate, ref) {
    if (!isoOrDate) {
        return false;
    }
    const a = startOfDay(isoOrDate instanceof Date ? isoOrDate : new Date(isoOrDate));
    return a.getTime() === ref.getTime();
}

export function hm(iso) {
    if (!iso) {
        return '—';
    }
    return new Date(iso).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
}

export function dayLabel(date) {
    const today = startOfDay(new Date());
    if (date.getTime() === today.getTime()) {
        return 'Aujourd’hui';
    }
    return date.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' });
}

export function statusClass(statut) {
    if (statut === 'en_attente') {
        return 'wait';
    }
    if (statut === 'annulee') {
        return 'no';
    }
    return 'ok';
}

export function weekDays(anchor) {
    const start = startOfDay(anchor);
    return Array.from({ length: 7 }, (_, i) => new Date(start.getTime() + i * 86400000));
}

export function esc(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

export function prenom(compte) {
    const nom = String(compte?.nom || '').trim();
    return nom.split(/\s+/)[0] || 'toi';
}

export function clock() {
    return new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
}

export function dayLine() {
    return new Date().toLocaleDateString('fr-FR', {
        weekday: 'long', day: 'numeric', month: 'long',
    });
}

export function nav(active) {
    const items = [
        ['home', 'Accueil', '⌂'],
        ['reservations', 'Réserv.', '▦'],
        ['plus', 'Plus', '···'],
    ];
    return `<nav class="nav">${items.map(([key, label, ico]) => `
        <button class="${active === key ? 'on' : ''}" data-go="#/${key}">
            <span class="ico">${ico}</span>
            ${label}
        </button>`).join('')}</nav>`;
}

export async function pill() {
    const { meta } = await import('./db.js');
    const { pendingCount } = await import('./outbox.js');
    const pending = await pendingCount();
    const syncAt = await meta('sync_at');
    if (pending) {
        return `<span class="pill warn">${pending} en attente</span>`;
    }
    if (!syncAt) {
        return `<span class="pill">Hors ligne</span>`;
    }
    const t = new Date(syncAt).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
    return `<span class="pill">À jour · ${t}</span>`;
}

export function weekStrip(anchor, selected) {
    return `<div class="week">${weekDays(anchor).map((d) => {
        const on = d.getTime() === selected.getTime();
        const letter = d.toLocaleDateString('fr-FR', { weekday: 'narrow' });
        return `<button class="${on ? 'on' : ''}" data-jump="${d.getTime()}">
            <span>${letter}</span><strong>${d.getDate()}</strong>
        </button>`;
    }).join('')}</div>`;
}
