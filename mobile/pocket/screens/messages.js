import { api } from '../js/api.js';
import { getAll, putMany } from '../js/db.js';
import { pendingCount } from '../js/outbox.js';
import { nav } from '../js/ui.js';

export async function renderMessages(app, id) {
    if (!id) {
        const convs = await getAll('conversations');
        app.innerHTML = `
            <header class="top"><button data-go="#/actions">‹</button><h1>Messages</h1></header>
            <div class="list">${convs.map((c) => `
                <button data-go="#/messages/${c.id}"><strong>${c.entreprise_nom || c.client_nom || 'Conversation'}</strong><br><span class="meta">${c.dernier_message || ''}</span></button>
            `).join('') || '<p class="empty">Aucun message en cache.</p>'}</div>
            ${nav('actions', await pendingCount())}`;
        return;
    }

    let messages = (await getAll('messages')).filter((m) => String(m.conversation_id) === String(id));
    if (!messages.length && navigator.onLine) {
        try {
            const data = await api(`/messagerie/conversations/${id}/messages`);
            messages = data.donnees || [];
            await putMany('messages', messages.map((m) => ({ ...m, id: m.id, conversation_id: m.conversation_id || Number(id) })));
        } catch {
            // offline
        }
    }

    app.innerHTML = `
        <header class="top"><button data-go="#/messages">‹</button><h1>Conversation</h1></header>
        ${messages.map((m) => `<div class="msg ${m.user_id ? '' : ''}">${m.contenu || ''}</div>`).join('') || '<p class="empty">Pas de messages.</p>'}
        ${nav('actions', await pendingCount())}`;
}
