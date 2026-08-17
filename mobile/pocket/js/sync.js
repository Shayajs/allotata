import { api } from './api.js';
import { getAll, meta, putAll, putMany } from './db.js';
import { snapshotNextRdv } from './native.js';

function withId(rows, key = 'id') {
    return (rows || []).map((row) => ({ ...row, id: row[key] ?? row.cle ?? row.id }));
}

async function mergeMesReservations() {
    try {
        const mine = await api('/mes-reservations?par_page=100');
        const extra = withId(mine.donnees || []);
        if (!extra.length) {
            return;
        }
        const existing = await getAll('reservations');
        const map = new Map((existing || []).map((row) => [row.id, row]));
        extra.forEach((row) => map.set(row.id, row));
        await putAll('reservations', [...map.values()]);
    } catch {
        // endpoint client optionnel
    }
}

async function prefetchMessages(conversations) {
    if (!navigator.onLine) {
        return;
    }
    const rows = [];
    for (const conversation of (conversations || []).slice(0, 8)) {
        try {
            const data = await api(`/messagerie/conversations/${conversation.id}/messages`);
            (data.donnees || []).forEach((message) => {
                rows.push({ ...message, id: message.id, conversation_id: message.conversation_id || conversation.id });
            });
        } catch {
            // hors ligne ou conversation inaccessible
        }
    }
    if (rows.length) {
        await putMany('messages', rows);
    }
}

async function prefetchPdfs(factures) {
    if (!navigator.onLine) {
        return;
    }
    for (const facture of (factures || []).slice(0, 8)) {
        try {
            const blob = await api(`/factures/${facture.id}/pdf`);
            await putMany('pdfs', [{ id: facture.id, blob, at: Date.now() }]);
        } catch {
            // PDF optionnel
        }
    }
}

export async function pullSync() {
    const data = await api('/sync');
    await putAll('reservations', withId(data.reservations));
    await putAll('clients', withId(data.clients, 'cle'));
    await putAll('conversations', withId(data.conversations));
    await putAll('factures', withId(data.factures));
    await meta('compte', data.compte);
    await meta('entreprises', data.entreprises || []);
    await meta('sync_at', data.sync_at);
    await mergeMesReservations();
    await prefetchMessages(data.conversations || []);
    await prefetchPdfs(data.factures || []);
    const reservations = await getAll('reservations');
    await snapshotNextRdv(reservations || data.reservations || []);
    return data;
}
