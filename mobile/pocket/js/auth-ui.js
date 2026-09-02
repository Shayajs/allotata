import { bindHostChip, getHosts, hostChipHtml } from './config.js';
import { clock, dayLine } from './ui.js';

export function paintAuth(app, { title, lead, fields, submit, extra = '' }) {
    app.innerHTML = `
        <div class="auth">
            <div class="auth-hero rise">
                <p class="auth-date">${dayLine()}</p>
                <p class="auth-now" id="auth-now">${clock()}</p>
                <h1>${title}</h1>
                <p class="auth-lead">${lead}</p>
            </div>
            <form id="auth-form" class="auth-card rise-late">
                ${fields}
                <p class="auth-err" id="auth-err" hidden></p>
                <button class="btn primary auth-go tap" type="submit">${submit}</button>
            </form>
            ${extra}
            ${hostChipHtml()}
        </div>`;
}

export function showErr(message) {
    const el = document.getElementById('auth-err');
    if (!el) {
        return;
    }
    el.hidden = !message;
    el.textContent = message || '';
}

export function setBusy(form, busy, label) {
    const btn = form.querySelector('.auth-go');
    if (!btn) {
        return;
    }
    btn.disabled = busy;
    btn.innerHTML = busy
        ? `<span class="dot"></span>${label}`
        : btn.dataset.label;
}

export function bindClock() {
    const el = document.getElementById('auth-now');
    if (!el) {
        return;
    }
    const id = window.setInterval(() => {
        if (!document.getElementById('auth-now')) {
            window.clearInterval(id);
            return;
        }
        el.textContent = clock();
    }, 10000);
}

export async function readyAuth(redraw) {
    await getHosts();
    bindClock();
    bindHostChip(redraw);
}
