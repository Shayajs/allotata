/**
 * Checkout – Stripe Elements (SetupIntent, charge PaymentIntent, 3DS).
 * Chargé uniquement sur la page /checkout.
 */
import { loadStripe } from '@stripe/stripe-js';

const stripePk = document.querySelector('meta[name="stripe-publishable-key"]')?.getAttribute('content');
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
const form = document.getElementById('checkout-save-card-form');
const container = document.getElementById('checkout-payment-element');

function getQuery(name) {
    return new URLSearchParams(window.location.search).get(name);
}

function showToast(message, type = 'error') {
    const el = document.getElementById('checkout-toast');
    if (!el) return;
    el.textContent = message;
    el.className = 'fixed top-20 left-1/2 -translate-x-1/2 z-50 max-w-md w-full mx-4 px-4 py-3 rounded-xl shadow-lg border text-center font-medium ';
    el.classList.add(type === 'error' ? 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-800 dark:text-red-400' : 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 text-green-800 dark:text-green-400');
    el.classList.remove('hidden');
    setTimeout(() => el.classList.add('hidden'), 5000);
}

async function finishAfterRedirect() {
    const secret = getQuery('setup_intent_client_secret');
    const status = getQuery('redirect_status');
    if (!secret || status !== 'succeeded') return false;
    const stripe = await loadStripe(stripePk);
    if (!stripe) return true;
    const { setupIntent } = await stripe.retrieveSetupIntent(secret);
    const pmId = setupIntent?.payment_method;
    if (!pmId) return true;
    await fetch(window.location.origin + '/checkout/save-payment-method', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ payment_method: pmId }),
    });
    window.location.replace(window.location.origin + '/checkout');
    return true;
}

const headers = () => ({ 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' });

async function initSaveCard() {
    if (!form || !container) return;
    let stripe = null;
    let elements = null;
    let clientSecret = null;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!stripe || !elements || !clientSecret) return;
        const submitBtn = form.querySelector('button[type="submit"]');
        const errEl = document.getElementById('checkout-card-error');
        if (errEl) errEl.textContent = '';
        if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Enregistrement…'; }
        try {
            const { setupIntent, error } = await stripe.confirmSetup({
                elements,
                confirmParams: { return_url: window.location.origin + '/checkout' },
            });
            if (error) {
                if (errEl) errEl.textContent = error.message || 'Erreur inconnue';
                if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Enregistrer ma carte'; }
                return;
            }
            const pmId = setupIntent?.payment_method;
            if (!pmId) { window.location.reload(); return; }
            const saveRes = await fetch(window.location.origin + '/checkout/save-payment-method', {
                method: 'POST', headers: headers(), body: JSON.stringify({ payment_method: pmId }),
            });
            const saveData = await saveRes.json();
            if (!saveData.success) {
                if (errEl) errEl.textContent = saveData.error || 'Erreur lors de l\'enregistrement.';
                if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Enregistrer ma carte'; }
                return;
            }
            window.location.reload();
        } catch (err) {
            if (errEl) errEl.textContent = err.message || 'Erreur inconnue.';
            if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Enregistrer ma carte'; }
        }
    });

    const r = await fetch(window.location.origin + '/checkout/setup-intent', { method: 'POST', headers: headers(), body: '{}' });
    const data = await r.json();
    if (!data.client_secret) {
        container.innerHTML = '<p class="text-red-600 dark:text-red-400 text-sm">Impossible de préparer le formulaire. Réessayez.</p>';
        return;
    }
    clientSecret = data.client_secret;
    stripe = await loadStripe(stripePk);
    if (!stripe) {
        container.innerHTML = '<p class="text-red-600 dark:text-red-400 text-sm">Stripe n\'a pas pu être chargé.</p>';
        return;
    }
    const isDark = document.documentElement.classList.contains('dark');
    elements = stripe.elements({
        clientSecret,
        appearance: { theme: isDark ? 'night' : 'stripe', variables: { borderRadius: '12px' } },
    });
    const paymentElement = elements.create('payment', {
        fields: {
            billingDetails: {
                address: 'never',
            },
        },
    });
    paymentElement.mount(container);
}

async function initRegler() {
    const btns = document.querySelectorAll('.checkout-regler-btn');
    if (!btns.length) return;
    const stripe = await loadStripe(stripePk);

    btns.forEach((btn) => {
        btn.addEventListener('click', async () => {
            const echeanceId = btn.dataset.echeanceId;
            const codePromo = btn.dataset.codePromo || '';
            const label = btn.querySelector('.checkout-regler-label');
            btn.disabled = true;
            if (label) label.textContent = 'Paiement…';

            try {
                const body = { echeance_id: parseInt(echeanceId, 10) };
                if (codePromo) body.code_promo = codePromo;
                const res = await fetch(window.location.origin + '/checkout/charge', {
                    method: 'POST',
                    headers: headers(),
                    body: JSON.stringify(body),
                });
                const json = await res.json();

                if (res.status === 409) {
                    showToast(json.error || 'Enregistrez d\'abord un moyen de paiement.');
                    btn.disabled = false;
                    if (label) label.textContent = 'Régler cette échéance';
                    return;
                }
                if (res.status === 422) {
                    showToast(json.error || 'Erreur de paiement.');
                    btn.disabled = false;
                    if (label) label.textContent = 'Régler cette échéance';
                    return;
                }
                if (res.status >= 500) {
                    showToast(json.error || 'Erreur serveur. Réessayez.');
                    btn.disabled = false;
                    if (label) label.textContent = 'Régler cette échéance';
                    return;
                }

                if (json.requires_action && json.client_secret && json.payment_intent_id) {
                    if (!stripe) {
                        showToast('Stripe n\'a pas pu être chargé.');
                        btn.disabled = false;
                        if (label) label.textContent = 'Régler cette échéance';
                        return;
                    }
                    const { error } = await stripe.handleCardAction(json.client_secret);
                    if (error) {
                        showToast(error.message || 'Authentification 3DS échouée.');
                        btn.disabled = false;
                        if (label) label.textContent = 'Régler cette échéance';
                        return;
                    }
                    const confirmRes = await fetch(window.location.origin + '/checkout/confirm-status', {
                        method: 'POST',
                        headers: headers(),
                        body: JSON.stringify({ payment_intent_id: json.payment_intent_id }),
                    });
                    const confirmData = await confirmRes.json();
                    if (!confirmData.success) {
                        showToast(confirmData.error || 'Paiement non confirmé.');
                        btn.disabled = false;
                        if (label) label.textContent = 'Régler cette échéance';
                        return;
                    }
                    showToast('Paiement enregistré. Merci !', 'success');
                    setTimeout(() => window.location.reload(), 1200);
                    return;
                }

                if (json.success) {
                    showToast('Paiement enregistré. Merci !', 'success');
                    setTimeout(() => window.location.reload(), 1200);
                    return;
                }

                showToast(json.error || 'Erreur inconnue.');
                btn.disabled = false;
                if (label) label.textContent = 'Régler cette échéance';
            } catch (err) {
                showToast(err.message || 'Erreur réseau.');
                btn.disabled = false;
                if (label) label.textContent = 'Régler cette échéance';
            }
        });
    });
}

if (stripePk && csrfToken) {
    (async () => {
        if (getQuery('setup_intent_client_secret') && getQuery('redirect_status') === 'succeeded') {
            if (await finishAfterRedirect()) return;
        }
        if (form && container) await initSaveCard();
        await initRegler();
    })();
}
