import { hostChipHtml, bindHostChip, getHosts } from '../js/config.js';
import { clock, dayLine, esc } from '../js/ui.js';

const VERIFY_EMAIL = 'pocket_verify_email';

export async function renderVerify(app) {
    await getHosts();
    const email = sessionStorage.getItem(VERIFY_EMAIL) || '';
    app.innerHTML = `
        <div class="auth">
            <div class="auth-hero rise">
                <p class="auth-date">${dayLine()}</p>
                <p class="auth-now" id="auth-now">${clock()}</p>
                <h1>Vérifiez<br>votre e-mail</h1>
                <p class="auth-lead">${email
                    ? `Un message a été envoyé à <strong>${esc(email)}</strong>.`
                    : 'Ouvrez le message Allotata, puis revenez ici.'}</p>
            </div>
            <div class="auth-card rise-late">
                <button class="btn primary tap" type="button" data-go="#/login">J’ai vérifié</button>
                <button class="auth-link" type="button" data-go="#/login">Retour à la connexion</button>
            </div>
            ${hostChipHtml()}
        </div>`;
    const now = document.getElementById('auth-now');
    const id = window.setInterval(() => {
        if (!document.getElementById('auth-now')) {
            window.clearInterval(id);
            return;
        }
        now.textContent = clock();
    }, 10000);
    bindHostChip(() => renderVerify(app));
}