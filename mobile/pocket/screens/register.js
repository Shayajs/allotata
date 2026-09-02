import { publicGet, publicPost } from '../js/api.js';
import { paintAuth, readyAuth, setBusy, showErr } from '../js/auth-ui.js';
import { getHosts } from '../js/config.js';
import { webLinks } from '../js/native.js';
import { esc } from '../js/ui.js';

const VERIFY_EMAIL = 'pocket_verify_email';

function vide() {
    return {
        name: '',
        surname: '',
        email: '',
        password: '',
        password_confirmation: '',
        date_naissance: '',
        telephone: '',
        adresse: '',
        ville: '',
        code_postal: '',
        latitude: '',
        longitude: '',
        cgu_accepted: false,
        cgv_accepted: false,
        confidentialite_accepted: false,
    };
}

let draft = vide();
let etape = 1;

function lire(form) {
    const data = new FormData(form);
    for (const [cle, valeur] of data.entries()) {
        if (cle.endsWith('_accepted')) {
            draft[cle] = true;
        } else {
            draft[cle] = String(valeur);
        }
    }
    if (etape === 3) {
        draft.cgu_accepted = Boolean(form.querySelector('[name="cgu_accepted"]')?.checked);
        draft.cgv_accepted = Boolean(form.querySelector('[name="cgv_accepted"]')?.checked);
        draft.confidentialite_accepted = Boolean(form.querySelector('[name="confidentialite_accepted"]')?.checked);
    }
}

function val(cle) {
    return esc(draft[cle] || '');
}

function hier() {
    const d = new Date();
    d.setDate(d.getDate() - 1);
    return d.toISOString().slice(0, 10);
}

function dots() {
    return `<div class="wizard-dots" aria-hidden="true">${[1, 2, 3].map((n) => `<i class="${n === etape ? 'on' : ''}"></i>`).join('')}</div>`;
}

export async function renderRegister(app, { go, reset = false } = {}) {
    if (reset) {
        draft = vide();
        etape = 1;
    }
    await getHosts();
    const links = await webLinks();
    const ecrans = {
        1: {
            title: 'Qui êtes-vous ?',
            lead: 'Les mêmes infos que sur le site.',
            submit: 'Continuer',
            fields: `${dots()}
                <label>Prénom</label>
                <input name="name" autocomplete="given-name" required value="${val('name')}">
                <label>Nom</label>
                <input name="surname" autocomplete="family-name" required value="${val('surname')}">
                <label>E-mail</label>
                <input name="email" type="email" autocomplete="email" required value="${val('email')}">
                <label>Mot de passe</label>
                <input name="password" type="password" autocomplete="new-password" required minlength="8" value="${val('password')}">
                <label>Confirmation</label>
                <input name="password_confirmation" type="password" autocomplete="new-password" required minlength="8" value="${val('password_confirmation')}">
                <label>Date de naissance</label>
                <input name="date_naissance" type="date" required max="${hier()}" value="${val('date_naissance')}">`,
            extra: `<button type="button" class="auth-link" data-go="#/">Retour</button>`,
        },
        2: {
            title: 'Où vous écrire ?',
            lead: 'Téléphone et adresse, comme sur le web.',
            submit: 'Continuer',
            fields: `${dots()}
                <label>Téléphone</label>
                <input name="telephone" type="tel" autocomplete="tel" required maxlength="20" value="${val('telephone')}">
                <label>Adresse</label>
                <input name="adresse" id="reg-adresse" autocomplete="street-address" required value="${val('adresse')}">
                <div class="suggest" id="reg-suggest" hidden></div>
                <label>Ville</label>
                <input name="ville" id="reg-ville" autocomplete="address-level2" required value="${val('ville')}">
                <label>Code postal</label>
                <input name="code_postal" id="reg-cp" autocomplete="postal-code" required maxlength="10" value="${val('code_postal')}">
                <input type="hidden" name="latitude" id="reg-lat" value="${val('latitude')}">
                <input type="hidden" name="longitude" id="reg-lng" value="${val('longitude')}">`,
            extra: `<button type="button" class="auth-link" id="reg-back">Retour</button>`,
        },
        3: {
            title: 'Dernière étape.',
            lead: 'À accepter pour créer le compte. Pas de connexion automatique.',
            submit: 'Créer mon compte',
            fields: `${dots()}
                <label class="check">
                    <input type="checkbox" name="cgu_accepted" value="1" ${draft.cgu_accepted ? 'checked' : ''}>
                    <span>J’accepte les <button type="button" class="inline" data-web="${links.cgu}">CGU</button></span>
                </label>
                <label class="check">
                    <input type="checkbox" name="cgv_accepted" value="1" ${draft.cgv_accepted ? 'checked' : ''}>
                    <span>J’accepte les <button type="button" class="inline" data-web="${links.cgv}">CGV</button></span>
                </label>
                <label class="check">
                    <input type="checkbox" name="confidentialite_accepted" value="1" ${draft.confidentialite_accepted ? 'checked' : ''}>
                    <span>J’accepte la <button type="button" class="inline" data-web="${links.confidentialite}">confidentialité</button></span>
                </label>`,
            extra: `<button type="button" class="auth-link" id="reg-back">Retour</button>`,
        },
    };
    const ecran = ecrans[etape];
    paintAuth(app, ecran);
    const form = document.getElementById('auth-form');
    form.querySelector('.auth-go').dataset.label = ecran.submit;
    await readyAuth(() => renderRegister(app, { go }));

    document.getElementById('reg-back')?.addEventListener('click', () => {
        lire(form);
        etape -= 1;
        renderRegister(app, { go });
    });

    if (etape === 2) {
        bindAdresse();
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        lire(form);
        showErr('');
        if (etape === 1 && draft.password !== draft.password_confirmation) {
            showErr('Les mots de passe ne correspondent pas.');
            return;
        }
        if (etape < 3) {
            etape += 1;
            renderRegister(app, { go });
            return;
        }
        if (!draft.cgu_accepted || !draft.cgv_accepted || !draft.confidentialite_accepted) {
            showErr('Acceptez les CGU, CGV et la confidentialité.');
            return;
        }
        setBusy(form, true, 'Création du compte…');
        try {
            const res = await publicPost('/auth/register', {
                name: draft.name,
                surname: draft.surname,
                email: draft.email,
                password: draft.password,
                password_confirmation: draft.password_confirmation,
                date_naissance: draft.date_naissance,
                telephone: draft.telephone,
                adresse: draft.adresse,
                ville: draft.ville,
                code_postal: draft.code_postal,
                latitude: draft.latitude || null,
                longitude: draft.longitude || null,
                cgu_accepted: true,
                cgv_accepted: true,
                confidentialite_accepted: true,
            });
            sessionStorage.setItem(VERIFY_EMAIL, res.email || draft.email);
            draft = vide();
            etape = 1;
            go('#/verify');
        } catch (error) {
            showErr(error.message);
            setBusy(form, false);
        }
    });
}

function bindAdresse() {
    const input = document.getElementById('reg-adresse');
    const box = document.getElementById('reg-suggest');
    if (!input || !box) {
        return;
    }
    let timer = 0;
    input.addEventListener('input', () => {
        window.clearTimeout(timer);
        const q = input.value.trim();
        if (q.length < 3) {
            box.hidden = true;
            box.innerHTML = '';
            return;
        }
        timer = window.setTimeout(async () => {
            try {
                const data = await publicGet(`/address/search?q=${encodeURIComponent(q)}&limit=5`);
                const rows = data.results || [];
                if (!rows.length) {
                    box.hidden = true;
                    box.innerHTML = '';
                    return;
                }
                box.hidden = false;
                box.innerHTML = rows.map((row) => `<button type="button" class="suggest-item" data-label="${esc(row.label)}" data-city="${esc(row.city)}" data-cp="${esc(row.postcode)}" data-lat="${row.latitude ?? ''}" data-lng="${row.longitude ?? ''}">${esc(row.label)}</button>`).join('');
            } catch {
                box.hidden = true;
            }
        }, 300);
    });
    box.addEventListener('click', (event) => {
        const item = event.target.closest('.suggest-item');
        if (!item) {
            return;
        }
        input.value = item.dataset.label || '';
        document.getElementById('reg-ville').value = item.dataset.city || '';
        document.getElementById('reg-cp').value = item.dataset.cp || '';
        document.getElementById('reg-lat').value = item.dataset.lat || '';
        document.getElementById('reg-lng').value = item.dataset.lng || '';
        draft.adresse = input.value;
        draft.ville = item.dataset.city || '';
        draft.code_postal = item.dataset.cp || '';
        draft.latitude = item.dataset.lat || '';
        draft.longitude = item.dataset.lng || '';
        box.hidden = true;
        box.innerHTML = '';
    });
}
