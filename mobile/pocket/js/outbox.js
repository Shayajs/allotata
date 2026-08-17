import { post } from './api.js';
import { getAll, put, remove } from './db.js';

export async function enqueue(action, reservation, notes = '') {
    const item = {
        id: `${action}-${reservation.id}-${Date.now()}`,
        action,
        reservation_id: reservation.id,
        slug: reservation.entreprise_slug,
        notes,
        idempotency_key: crypto.randomUUID(),
        created_at: new Date().toISOString(),
    };
    await put('outbox', item);
    return item;
}

export async function pendingCount() {
    return (await getAll('outbox')).length;
}

export async function replayOutbox() {
    if (!navigator.onLine) {
        return;
    }
    const items = await getAll('outbox');
    for (const item of items) {
        const path = `/entreprises/${item.slug}/reservations/${item.reservation_id}/${item.action}`;
        await post(path, {
            idempotency_key: item.idempotency_key,
            notes: item.notes || null,
        });
        await remove('outbox', item.id);
    }
}
