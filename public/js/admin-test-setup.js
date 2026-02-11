/**
 * Admin – Test Setup (Stripe Setup Intent + CardElement).
 * Script autonome (CDN Stripe v3), pas de Vite. Enregistre une carte sans débiter.
 */
(function () {
    'use strict';
    var stripePk = document.querySelector('meta[name="stripe-publishable-key"]') && document.querySelector('meta[name="stripe-publishable-key"]').getAttribute('content');
    var csrfToken = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var form = document.getElementById('admin-test-setup-form');
    var container = document.getElementById('admin-test-setup-element');
    var base = window.location.origin;

    function getQuery(name) {
        return new URLSearchParams(window.location.search).get(name);
    }

    function finishAfterRedirect() {
        var secret = getQuery('setup_intent_client_secret');
        var status = getQuery('redirect_status');
        if (!secret || status !== 'succeeded') return Promise.resolve(false);
        var stripe = window.Stripe && window.Stripe(stripePk);
        if (!stripe) return Promise.resolve(true);
        return stripe.retrieveSetupIntent(secret).then(function (r) {
            var pmId = r.setupIntent && r.setupIntent.payment_method;
            if (!pmId) return true;
            return fetch(base + '/admin/stripe-prices/save-test-pm', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ payment_method: pmId })
            }).then(function (res) { return res.json(); }).then(function (data) {
                if (data.success) window.location.href = base + '/admin/stripe-prices/test-setup-success';
                return true;
            });
        });
    }

    if (!stripePk || !csrfToken || !form || !container) {
        if (container) container.innerHTML = '<p class="text-red-600 dark:text-red-400 text-sm">Configuration manquante.</p>';
        return;
    }

    finishAfterRedirect().then(function (done) {
        if (done) return;
        fetch(base + '/admin/stripe-prices/test-setup-intent', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: '{}'
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (!data.client_secret) {
                container.innerHTML = '<p class="text-red-600 dark:text-red-400 text-sm">Impossible de créer le SetupIntent.</p>';
                return;
            }
            var stripe = window.Stripe && window.Stripe(stripePk);
            if (!stripe) {
                container.innerHTML = '<p class="text-red-600 dark:text-red-400 text-sm">Stripe introuvable.</p>';
                return;
            }
            var isDark = document.documentElement.classList.contains('dark');
            var appearance = {
                theme: isDark ? 'night' : 'stripe',
                variables: {
                    borderRadius: '12px',
                    colorPrimary: '#059669',
                    colorBackground: isDark ? '#334155' : '#f8fafc',
                    colorText: isDark ? '#f1f5f9' : '#1e293b',
                    colorSecondaryText: isDark ? '#94a3b8' : '#64748b',
                    colorBorder: isDark ? '#475569' : '#e2e8f0',
                    colorDanger: isDark ? '#f87171' : '#dc2626'
                }
            };
            var elements = stripe.elements({ appearance: appearance });
            var card = elements.create('card', { style: { base: { fontSize: '16px' } } });
            card.mount(container);

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var btn = form.querySelector('button[type="submit"]');
                var errEl = document.getElementById('admin-test-setup-error');
                if (errEl) errEl.textContent = '';
                if (btn) { btn.disabled = true; btn.textContent = 'Enregistrement…'; }

                stripe.confirmCardSetup(data.client_secret, {
                    payment_method: { card: card },
                    return_url: base + '/admin/stripe-prices/test-setup'
                }).then(function (result) {
                    if (result.error) {
                        if (errEl) errEl.textContent = result.error.message || 'Erreur';
                        if (btn) { btn.disabled = false; btn.textContent = 'Enregistrer la carte (test)'; }
                        return;
                    }
                    var pmId = result.setupIntent && result.setupIntent.payment_method;
                    if (!pmId) { window.location.reload(); return; }
                    return fetch(base + '/admin/stripe-prices/save-test-pm', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                        body: JSON.stringify({ payment_method: pmId })
                    }).then(function (res) { return res.json(); }).then(function (saveData) {
                        if (!saveData.success) {
                            if (errEl) errEl.textContent = saveData.error || 'Erreur enregistrement';
                            if (btn) { btn.disabled = false; btn.textContent = 'Enregistrer la carte (test)'; }
                            return;
                        }
                        window.location.href = base + '/admin/stripe-prices/test-setup-success';
                    });
                }).catch(function (err) {
                    if (errEl) errEl.textContent = (err && err.message) || 'Erreur';
                    if (btn) { btn.disabled = false; btn.textContent = 'Enregistrer la carte (test)'; }
                });
            });
        }).catch(function () {
            if (container) container.innerHTML = '<p class="text-red-600 dark:text-red-400 text-sm">Impossible de créer le SetupIntent.</p>';
        });
    });
})();
