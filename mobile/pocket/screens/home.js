import { api } from '../js/api.js';
import { esc, nav, prenom } from '../js/ui.js';

export async function renderHome(app, { onAuthError }) {
    app.innerHTML = `
        <header class="top"><h1>Accueil</h1></header>
        <p class="empty"><span class="dot"></span>Chargement…</p>
        ${nav('home')}`;
    try {
        const [moi, rdv, msgs, facts] = await Promise.all([
            api('/moi'),
            api('/mes-reservations?par_page=1'),
            api('/messagerie/conversations'),
            api('/factures?par_page=1'),
        ]);
        const compte = moi.compte || {};
        const nRdv = rdv.pagination?.total ?? (rdv.donnees || []).length;
        const nMsg = (msgs.donnees || []).length;
        const nFac = facts.pagination?.total ?? (facts.donnees || []).length;
        app.innerHTML = `
            <header class="hello rise">
                <p class="hello-kicker">Espace membre</p>
                <h1>Bonjour, ${esc(prenom(compte))}</h1>
            </header>
            <div class="tiles rise-late">
                <button class="tile tap" type="button" data-go="#/reservations">
                    <span>Réservations</span>
                    <strong>${nRdv}</strong>
                </button>
                <button class="tile tap" type="button" data-go="#/messages">
                    <span>Messages</span>
                    <strong>${nMsg}</strong>
                </button>
                <button class="tile tap" type="button" data-go="#/factures">
                    <span>Factures</span>
                    <strong>${nFac}</strong>
                </button>
            </div>
            ${nav('home')}`;
    } catch (error) {
        if (error.message === 'jeton_invalide') {
            await onAuthError();
            return;
        }
        app.innerHTML = `
            <header class="hello"><h1>Accueil</h1></header>
            <p class="empty">${esc(error.message)}</p>
            ${nav('home')}`;
    }
}
