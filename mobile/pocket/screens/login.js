import { publicPost } from '../js/api.js';
import { setToken } from '../js/auth.js';
import { paintAuth, readyAuth, setBusy, showErr } from '../js/auth-ui.js';
import { getHosts } from '../js/config.js';
import { esc } from '../js/ui.js';

const VERIFY_EMAIL = 'pocket_verify_email';

export async function renderLogin(app, { onReady, go }) {
    await getHosts();
    const email = sessionStorage.getItem(VERIFY_EMAIL) || '';
    paintAuth(app, {
        title: 'Heureux de<br>vous revoir.',
        lead: 'Votre espace membre Allotata.',
        fields: `<label>E-mail</label>
            <input name="email" type="email" autocomplete="username" required value="${esc(email)}">
            <label>Mot de passe</label>
            <input name="password" type="password" autocomplete="current-password" required>`,
        submit: 'Se connecter',
        extra: `<button type="button" class="auth-link" data-go="#/register">S’enregistrer</button>
            <button type="button" class="auth-link" data-go="#/">Retour</button>`,
    });
    const form = document.getElementById('auth-form');
    form.querySelector('.auth-go').dataset.label = 'Se connecter';
    await readyAuth(() => renderLogin(app, { onReady, go }));

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const data = new FormData(form);
        showErr('');
        setBusy(form, true, 'On ouvre le carnet…');
        try {
            const res = await publicPost('/auth/login', {
                email: data.get('email'),
                password: data.get('password'),
            });
            if (res.jeton) {
                setBusy(form, true, 'C’est bon.');
                sessionStorage.removeItem(VERIFY_EMAIL);
                await setToken(res.jeton);
                await onReady();
                return;
            }
        } catch (error) {
            if (error.code === 'a2f_requis') {
                return renderTwoFactor(app, { onReady, go, challenge: error.data.challenge, methode: error.data.methode });
            }
            if (error.code === 'email_non_verifie') {
                sessionStorage.setItem(VERIFY_EMAIL, String(data.get('email') || ''));
                go('#/verify');
                return;
            }
            showErr(error.message);
        }
        setBusy(form, false);
    });
}

async function renderTwoFactor(app, { onReady, go, challenge, methode }) {
    await getHosts();
    const hint = methode === 'totp'
        ? 'Code de l’application d’authentification'
        : 'Code reçu par e-mail ou SMS';
    paintAuth(app, {
        title: 'Un dernier geste.',
        lead: 'Juste le code, puis votre espace.',
        fields: `<label>${hint}</label>
            <input name="code" inputmode="numeric" autocomplete="one-time-code" required maxlength="20">`,
        submit: 'Valider',
        extra: methode !== 'totp' ? '<button type="button" class="auth-link" id="auth-resend">Renvoyer le code</button>' : '',
    });
    const form = document.getElementById('auth-form');
    form.querySelector('.auth-go').dataset.label = 'Valider';
    await readyAuth(() => renderTwoFactor(app, { onReady, go, challenge, methode }));

    document.getElementById('auth-resend')?.addEventListener('click', async () => {
        try {
            await publicPost('/auth/2fa/renvoyer', { challenge });
            showErr('');
        } catch (error) {
            showErr(error.message);
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const data = new FormData(form);
        showErr('');
        setBusy(form, true, 'Vérification…');
        try {
            const res = await publicPost('/auth/2fa', { challenge, code: data.get('code') });
            setBusy(form, true, 'C’est bon.');
            await setToken(res.jeton);
            await onReady();
        } catch (error) {
            showErr(error.message);
            setBusy(form, false);
        }
    });
}
