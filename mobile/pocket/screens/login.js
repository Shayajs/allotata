import { publicPost } from '../js/api.js';
import { setToken } from '../js/auth.js';

function form(title, fields, submitLabel, extra = '') {
    return `
        <div class="auth">
            <p class="auth-date" id="auth-date"></p>
            <h1>${title}</h1>
            <form id="auth-form">
                ${fields}
                <p class="auth-err" id="auth-err" hidden></p>
                <button class="btn primary" type="submit">${submitLabel}</button>
            </form>
            ${extra}
        </div>`;
}

function showErr(message) {
    const el = document.getElementById('auth-err');
    if (!el) {
        return;
    }
    el.hidden = !message;
    el.textContent = message || '';
}

function stampDate() {
    const el = document.getElementById('auth-date');
    if (el) {
        el.textContent = new Date().toLocaleDateString('fr-FR', {
            weekday: 'long', day: 'numeric', month: 'long',
        });
    }
}

export async function renderLogin(app, { onReady }) {
    app.innerHTML = form(
        'Votre journée,<br>même sans réseau.',
        `<label>E-mail</label>
         <input name="email" type="email" autocomplete="username" required>
         <label>Mot de passe</label>
         <input name="password" type="password" autocomplete="current-password" required>`,
        'Entrer dans le carnet'
    );
    stampDate();

    document.getElementById('auth-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const data = new FormData(event.target);
        showErr('');
        event.target.querySelector('button').disabled = true;
        try {
            const res = await publicPost('/auth/login', {
                email: data.get('email'),
                password: data.get('password'),
            });
            if (res.jeton) {
                await setToken(res.jeton);
                await onReady();
                return;
            }
        } catch (error) {
            if (error.code === 'a2f_requis') {
                return renderTwoFactor(app, { onReady, challenge: error.data.challenge, methode: error.data.methode });
            }
            showErr(error.message);
        }
        event.target.querySelector('button').disabled = false;
    });
}

async function renderTwoFactor(app, { onReady, challenge, methode }) {
    const hint = methode === 'totp'
        ? 'Code de l’application d’authentification'
        : 'Code reçu par e-mail ou SMS';
    app.innerHTML = form(
        'Un dernier geste.',
        `<label>${hint}</label>
         <input name="code" inputmode="numeric" autocomplete="one-time-code" required maxlength="20">`,
        'Valider',
        methode !== 'totp' ? '<button type="button" class="auth-link" id="auth-resend">Renvoyer le code</button>' : ''
    );
    stampDate();

    document.getElementById('auth-resend')?.addEventListener('click', async () => {
        try {
            await publicPost('/auth/2fa/renvoyer', { challenge });
            showErr('');
        } catch (error) {
            showErr(error.message);
        }
    });

    document.getElementById('auth-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const data = new FormData(event.target);
        showErr('');
        event.target.querySelector('button').disabled = true;
        try {
            const res = await publicPost('/auth/2fa', {
                challenge,
                code: data.get('code'),
            });
            await setToken(res.jeton);
            await onReady();
        } catch (error) {
            showErr(error.message);
            event.target.querySelector('button').disabled = false;
        }
    });
}
