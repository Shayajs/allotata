import { api } from '../js/api.js';
import { esc, nav } from '../js/ui.js';

export async function renderMessages(app, id, { onAuthError }) {
    if (id) {
        return renderThread(app, id, { onAuthError });
    }
    app.innerHTML = `
        <header class="top"><button data-go="#/home">‹</button><h1>Messages</h1></header>
        <p class="empty"><span class="dot"></span>Chargement…</p>
        ${nav('home')}`;
    try {
        const data = await api('/messagerie/conversations');
        const rows = data.donnees || [];
        app.innerHTML = `
            <header class="top"><button data-go="#/home">‹</button><h1>Messages</h1></header>
            <div class="list">${rows.map((c) => `
                <button type="button" data-go="#/messages/${c.id}">
                    <strong>${esc(c.entreprise_nom || c.client_nom || 'Conversation')}</strong>
                    <span class="meta">${esc(c.dernier_message || '')}</span>
                </button>`).join('') || '<p class="empty">Aucun message pour le moment.</p>'}</div>
            ${nav('home')}`;
    } catch (error) {
        if (error.message === 'jeton_invalide') {
            await onAuthError();
            return;
        }
        app.innerHTML = `
            <header class="top"><button data-go="#/home">‹</button><h1>Messages</h1></header>
            <p class="empty">${esc(error.message)}</p>
            ${nav('home')}`;
    }
}

async function renderThread(app, id, { onAuthError }) {
    app.innerHTML = `
        <header class="top"><button data-go="#/messages">‹</button><h1>Conversation</h1></header>
        <p class="empty"><span class="dot"></span>Chargement…</p>
        ${nav('home')}`;
    try {
        const data = await api(`/messagerie/conversations/${id}/messages`);
        const rows = data.donnees || [];
        app.innerHTML = `
            <header class="top"><button data-go="#/messages">‹</button><h1>Conversation</h1></header>
            ${rows.map((m) => `<div class="msg">${esc(m.contenu || '')}</div>`).join('') || '<p class="empty">Pas de messages.</p>'}
            ${nav('home')}`;
    } catch (error) {
        if (error.message === 'jeton_invalide') {
            await onAuthError();
            return;
        }
        app.innerHTML = `
            <header class="top"><button data-go="#/messages">‹</button><h1>Conversation</h1></header>
            <p class="empty">${esc(error.message)}</p>
            ${nav('home')}`;
    }
}
