/**
 * Admin – Test Setup (Stripe Setup Intent + Elements).
 * Enregistre une carte sans débiter. Si OK, le débit API fonctionnera.
 */
import { loadStripe } from '@stripe/stripe-js';

const stripePk = document.querySelector('meta[name="stripe-publishable-key"]')?.getAttribute('content');
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
const form = document.getElementById('admin-test-setup-form');
const container = document.getElementById('admin-test-setup-element');
const base = window.location.origin;

function getQuery(name) {
    return new URLSearchParams(window.location.search).get(name);
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
    const res = await fetch(base + '/admin/stripe-prices/save-test-pm', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ payment_method: pmId }),
    });
    const data = await res.json();
    if (!data.success) return true;
    window.location.href = base + '/admin/stripe-prices/test-setup-success';
    return true;
}

if (!stripePk || !csrfToken || !form || !container) {
    if (container) container.innerHTML = '<p class="text-red-600 text-sm">Configuration manquante.</p>';
} else {
    (async () => {
        if (await finishAfterRedirect()) return;
        const r = await fetch(base + '/admin/stripe-prices/test-setup-intent', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: '{}',
        });
        const data = await r.json();
        if (!data.client_secret) {
            container.innerHTML = '<p class="text-red-600 dark:text-red-400 text-sm">Impossible de créer le SetupIntent.</p>';
            return;
        }

        const stripe = await loadStripe(stripePk);
        if (!stripe) {
            container.innerHTML = '<p class="text-red-600 dark:text-red-400 text-sm">Stripe introuvable.</p>';
            return;
        }

        const isDark = document.documentElement.classList.contains('dark');
        const elements = stripe.elements({
            clientSecret: data.client_secret,
            appearance: { theme: isDark ? 'night' : 'stripe', variables: { borderRadius: '12px' } },
        });
        const paymentElement = elements.create('payment');
        paymentElement.mount(container);

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = form.querySelector('button[type="submit"]');
            const errEl = document.getElementById('admin-test-setup-error');
            if (errEl) errEl.textContent = '';
            if (btn) { btn.disabled = true; btn.textContent = 'Enregistrement…'; }

            try {
                const { setupIntent, error } = await stripe.confirmSetup({
                    elements,
                    confirmParams: { return_url: base + '/admin/stripe-prices/test-setup' },
                });
                if (error) {
                    if (errEl) errEl.textContent = error.message || 'Erreur';
                    if (btn) { btn.disabled = false; btn.textContent = 'Enregistrer la carte (test)'; }
                    return;
                }
                const pmId = setupIntent?.payment_method;
                if (!pmId) { window.location.reload(); return; }

                const saveRes = await fetch(base + '/admin/stripe-prices/save-test-pm', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ payment_method: pmId }),
                });
                const saveData = await saveRes.json();
                if (!saveData.success) {
                    if (errEl) errEl.textContent = saveData.error || 'Erreur enregistrement';
                    if (btn) { btn.disabled = false; btn.textContent = 'Enregistrer la carte (test)'; }
                    return;
                }
                window.location.href = base + '/admin/stripe-prices/test-setup-success';
            } catch (err) {
                if (errEl) errEl.textContent = err.message || 'Erreur';
                if (btn) { btn.disabled = false; btn.textContent = 'Enregistrer la carte (test)'; }
            }
        });
    })();
}
