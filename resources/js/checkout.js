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

function escapeHtml(s) {
    if (s == null) return '';
    const div = document.createElement('div');
    div.textContent = String(s);
    return div.innerHTML;
}

function showToast(message, type = 'error') {
    const el = document.getElementById('checkout-toast');
    if (!el) return;
    el.textContent = message;
    el.className = 'fixed top-20 left-4 right-4 sm:left-1/2 sm:right-auto sm:-translate-x-1/2 sm:max-w-md z-50 px-4 py-3 rounded-xl shadow-lg border text-center font-medium ' +
        (type === 'error' ? 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-800 dark:text-red-400' : 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 text-green-800 dark:text-green-400');
    el.classList.remove('hidden');
    setTimeout(() => el.classList.add('hidden'), 5000);
}

/**
 * Mapper les codes d'erreur Stripe vers des messages français clairs
 * C'est crucial pour l'UX : le client doit comprendre que c'est SA banque qui refuse
 */
function mapStripeErrorToUserMessage(errorCode, rawMessage) {
    const errorMessages = {
        // Erreurs de carte courantes
        'insufficient_funds': 'Solde insuffisant sur cette carte. Vérifiez votre compte bancaire ou utilisez une autre carte.',
        'card_declined': 'Votre banque a refusé le paiement. Contactez votre banque pour connaître la raison ou utilisez une autre carte.',
        'expired_card': 'Cette carte a expiré. Veuillez utiliser une autre carte ou mettre à jour vos informations de paiement.',
        'incorrect_cvc': 'Le code de sécurité (CVC) est incorrect. Vérifiez les 3 chiffres au dos de votre carte.',
        'incorrect_number': 'Le numéro de carte est incorrect. Vérifiez les 16 chiffres de votre carte.',
        'processing_error': 'Votre banque a rencontré une erreur lors du traitement. Réessayez dans quelques instants.',
        'generic_decline': 'Votre banque a refusé le paiement sans raison spécifique. Contactez votre banque ou utilisez une autre carte.',
        
        // Erreurs de carte avancées
        'lost_card': 'Cette carte a été signalée comme perdue. Utilisez une autre carte.',
        'stolen_card': 'Cette carte a été signalée comme volée. Utilisez une autre carte.',
        'pickup_card': 'Votre banque a demandé la récupération de cette carte. Contactez votre banque.',
        'restricted_card': 'Cette carte est restreinte. Contactez votre banque.',
        'security_violation': 'Votre banque a détecté une violation de sécurité. Contactez votre banque.',
        'service_not_allowed': 'Cette carte ne permet pas ce type de transaction. Contactez votre banque.',
        'stop_payment_order': 'Un ordre d\'arrêt de paiement a été émis pour cette carte. Contactez votre banque.',
        'withdrawal_count_limit_exceeded': 'Vous avez atteint la limite de retraits autorisés. Contactez votre banque.',
        
        // Erreurs de test
        'testmode_decline': 'Cette carte de test a été refusée. Utilisez une carte de test valide.',
        
        // Erreurs de limite
        'card_velocity_exceeded': 'Vous avez effectué trop de transactions récemment. Réessayez plus tard ou contactez votre banque.',
        'do_not_honor': 'Votre banque a refusé la transaction. Contactez votre banque pour plus d\'informations.',
        'do_not_try_again': 'Votre banque a refusé la transaction et demande de ne pas réessayer. Contactez votre banque.',
        'fraudulent': 'Cette transaction a été refusée pour suspicion de fraude. Contactez votre banque si vous pensez qu\'il s\'agit d\'une erreur.',
        'merchant_blacklist': 'Cette carte ne peut pas être utilisée pour cette transaction. Contactez le support.',
        'no_action_taken': 'Votre banque n\'a pas traité la transaction. Réessayez ou contactez votre banque.',
        'not_permitted': 'Cette transaction n\'est pas autorisée. Contactez votre banque.',
        'offline_pin_required': 'Cette carte nécessite une authentification supplémentaire. Utilisez une autre carte ou contactez votre banque.',
        'online_or_offline_pin_required': 'Cette carte nécessite un code PIN. Utilisez une autre carte ou contactez votre banque.',
        'pin_try_exceeded': 'Trop de tentatives de code PIN incorrect. Contactez votre banque.',
        'restricted_pin_try_exceeded': 'Trop de tentatives de code PIN incorrect. Votre carte est temporairement bloquée. Contactez votre banque.',
        'transaction_not_allowed': 'Cette transaction n\'est pas autorisée pour cette carte. Contactez votre banque.',
        'try_again_later': 'Votre banque demande de réessayer plus tard. Patientez quelques instants avant de réessayer.',
        'withdrawal_amount_limit_exceeded': 'Vous avez atteint la limite de montant autorisée. Contactez votre banque.',
    };

    // Si on a un code spécifique, utiliser le message correspondant
    if (errorCode && errorMessages[errorCode]) {
        return errorMessages[errorCode];
    }

    // Sinon, analyser le message brut pour détecter des mots-clés
    const lowerMessage = (rawMessage || '').toLowerCase();
    
    if (lowerMessage.includes('insufficient') || lowerMessage.includes('fond') || lowerMessage.includes('solde')) {
        return 'Solde insuffisant sur cette carte. Vérifiez votre compte bancaire ou utilisez une autre carte.';
    }
    
    if (lowerMessage.includes('declined') || lowerMessage.includes('refus') || lowerMessage.includes('refused')) {
        return 'Votre banque a refusé le paiement. Contactez votre banque pour connaître la raison ou utilisez une autre carte.';
    }
    
    if (lowerMessage.includes('expired') || lowerMessage.includes('expir') || lowerMessage.includes('expiration')) {
        return 'Cette carte a expiré. Veuillez utiliser une autre carte ou mettre à jour vos informations de paiement.';
    }
    
    if (lowerMessage.includes('cvc') || lowerMessage.includes('security code') || lowerMessage.includes('cvv')) {
        return 'Le code de sécurité (CVC) est incorrect. Vérifiez les 3 chiffres au dos de votre carte.';
    }
    
    if (lowerMessage.includes('fraud') || lowerMessage.includes('fraude')) {
        return 'Cette transaction a été refusée pour suspicion de fraude. Contactez votre banque si vous pensez qu\'il s\'agit d\'une erreur.';
    }
    
    if (lowerMessage.includes('limit') || lowerMessage.includes('limite')) {
        return 'Vous avez atteint une limite autorisée. Contactez votre banque ou utilisez une autre carte.';
    }
    
    if (lowerMessage.includes('pin') || lowerMessage.includes('code pin')) {
        return 'Cette carte nécessite une authentification supplémentaire. Utilisez une autre carte ou contactez votre banque.';
    }
    
    if (lowerMessage.includes('blocked') || lowerMessage.includes('bloqué') || lowerMessage.includes('restricted')) {
        return 'Cette carte est bloquée ou restreinte. Contactez votre banque.';
    }

    // Message générique mais qui indique que c'est la banque
    return 'Votre banque a refusé le paiement. Contactez votre banque pour connaître la raison ou utilisez une autre carte.';
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

function loadingHtml() {
    return '<div class="flex items-center justify-center gap-2 py-12 text-slate-500 dark:text-slate-400" role="status"><svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Chargement du formulaire…</div>';
}

async function initSaveCard() {
    if (!form || !container) return;
    if (form.dataset.saveCardInit === '1') return;
    form.dataset.saveCardInit = '1';

    let stripe = null;
    let elements = null;
    let clientSecret = null;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!stripe || !elements || !clientSecret) return;
        const submitBtn = form.querySelector('button[type="submit"]');
        const submitLabel = submitBtn?.textContent?.trim() || 'Enregistrer ma carte';
        const errEl = document.getElementById('checkout-card-error');
        if (errEl) errEl.textContent = '';
        if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Enregistrement…'; }
        try {
            const { setupIntent, error } = await stripe.confirmSetup({
                elements,
                confirmParams: {
                    return_url: window.location.origin + '/checkout',
                },
            });
            if (error) {
                if (errEl) errEl.textContent = error.message || 'Erreur inconnue';
                if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = submitLabel; }
                return;
            }
            const pmId = setupIntent?.payment_method;
            if (!pmId) { window.location.replace(window.location.origin + '/checkout'); return; }
            const saveRes = await fetch(window.location.origin + '/checkout/save-payment-method', {
                method: 'POST', headers: headers(), body: JSON.stringify({ payment_method: pmId }),
            });
            const saveData = await saveRes.json();
            if (!saveData.success) {
                if (errEl) errEl.textContent = saveData.error || 'Erreur lors de l\'enregistrement.';
                if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = submitLabel; }
                return;
            }
            window.location.replace(window.location.origin + '/checkout');
        } catch (err) {
            if (errEl) errEl.textContent = err.message || 'Erreur inconnue.';
            if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = submitLabel; }
        }
    });

    function showLoading() {
        container.innerHTML = loadingHtml();
    }

    function showError(msg, offerRetry) {
        const retry = offerRetry ? '<p class="mt-3"><button type="button" id="checkout-retry-btn" class="px-4 py-2 rounded-xl bg-green-600 hover:bg-green-700 dark:bg-green-600 dark:hover:bg-green-700 text-white font-medium text-sm transition">Réessayer</button></p>' : '';
        container.innerHTML = '<p class="text-red-600 dark:text-red-400 text-sm">' + escapeHtml(msg) + '</p>' + retry;
        if (offerRetry) {
            const btn = document.getElementById('checkout-retry-btn');
            if (btn) btn.onclick = () => loadForm();
        }
    }

    async function loadForm() {
        showLoading();
        let data = {};
        try {
            const r = await fetch(window.location.origin + '/checkout/setup-intent', { method: 'POST', headers: headers(), body: '{}' });
            data = await r.json().catch(() => ({}));
            if (!r.ok) {
                const err = data.error || data.message || `Erreur ${r.status}`;
                showError('Impossible de préparer le formulaire. ' + err, true);
                return;
            }
        } catch (e) {
            showError('Impossible de joindre le serveur. Vérifiez votre connexion.', true);
            return;
        }
        if (!data.client_secret) {
            const err = data.error || data.message || 'Réessayez.';
            showError('Impossible de préparer le formulaire. ' + err, true);
            return;
        }
        clientSecret = data.client_secret;
        stripe = await loadStripe(stripePk);
        if (!stripe) {
            showError('Stripe n\'a pas pu être chargé.', true);
            return;
        }
        const isDark = document.documentElement.classList.contains('dark');
        elements = stripe.elements({
            clientSecret,
            appearance: { theme: isDark ? 'night' : 'stripe', variables: { borderRadius: '12px' } },
        });
        const paymentElement = elements.create('payment', {
            fields: { billingDetails: { address: 'if_required' } },
        });
        container.innerHTML = '';
        paymentElement.mount(container);
    }

    await loadForm();
}

async function initRegler() {
    const btns = document.querySelectorAll('.checkout-regler-btn');
    if (!btns.length) return;
    const stripe = await loadStripe(stripePk);

    btns.forEach((btn) => {
        btn.addEventListener('click', async () => {
            const echeanceId = btn.dataset.echeanceId;
            const pendingKey = btn.dataset.pendingKey;
            const codePromo = btn.dataset.codePromo || '';
            const label = btn.querySelector('.checkout-regler-label');
            btn.disabled = true;
            if (label) label.textContent = 'Paiement…';

            try {
                const body = {};
                if (pendingKey) {
                    body.pending_key = pendingKey;
                } else {
                    body.echeance_id = parseInt(echeanceId, 10);
                }
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
                    // Le serveur envoie déjà un message clair, mais on peut améliorer si on a un error_code
                    let errorMessage = json.error || 'Erreur de paiement.';
                    if (json.error_code) {
                        errorMessage = mapStripeErrorToUserMessage(json.error_code, json.error || '');
                    }
                    showToast(errorMessage);
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
                        // Mapper les erreurs Stripe vers des messages français clairs
                        const userMessage = mapStripeErrorToUserMessage(error.code, error.message);
                        showToast(userMessage);
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
