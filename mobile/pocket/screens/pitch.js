import { bindHostChip, getHosts, hostChipHtml } from '../js/config.js';
import { clock, dayLine } from '../js/ui.js';

export async function renderPitch(app) {
    await getHosts();
    app.innerHTML = `
        <div class="pitch">
            <div class="pitch-hero rise">
                <p class="auth-date">${dayLine()}</p>
                <p class="auth-now pitch-now" id="auth-now">${clock()}</p>
                <h1 class="pitch-title">Allotata</h1>
                <p class="pitch-tag">votre gestionnaire de PME et EI</p>
            </div>
            <div class="pitch-actions rise-late">
                <button class="btn primary tap" type="button" data-go="#/login">Se connecter</button>
                <button class="btn ghost tap" type="button" data-go="#/register">S’enregistrer</button>
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
    bindHostChip(() => renderPitch(app));
}
