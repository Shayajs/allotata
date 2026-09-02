import { clearToken } from '../js/auth.js';
import { api } from '../js/api.js';
import { bindHostChip, getHosts, hostChipHtml } from '../js/config.js';
import { esc, nav } from '../js/ui.js';

export async function renderPlus(app, { go, onAuthError }) {
    await getHosts();
    let compte = {};
    try {
        const moi = await api('/moi');
        compte = moi.compte || {};
    } catch (error) {
        if (error.message === 'jeton_invalide') {
            await onAuthError();
            return;
        }
    }
    app.innerHTML = `
        <header class="top"><h1>Plus</h1></header>
        <div class="sheet">
            <p class="plus-name">${esc(compte.nom || 'Compte')}</p>
            <p class="meta">${esc(compte.email || '')}</p>
            <p class="plus-role">Espace membre</p>
            ${hostChipHtml()}
            <button class="btn danger tap" type="button" id="logout">Déconnexion</button>
        </div>
        ${nav('plus')}`;
    bindHostChip(() => renderPlus(app, { go, onAuthError }));
    document.getElementById('logout')?.addEventListener('click', async () => {
        await clearToken();
        go('#/');
    });
}
