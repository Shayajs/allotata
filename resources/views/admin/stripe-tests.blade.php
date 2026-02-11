@extends('admin.layout')

@section('title', 'Tests Stripe')
@section('header', 'Tests Stripe & Paiements')
@section('subheader', 'Tests inline pour v&eacute;rifier toute la cha&icirc;ne de paiement')

@section('content')
<div x-data="stripeTests()" x-cloak>

    {{-- ═══════════════ Barre d'onglets ═══════════════ --}}
    <div class="border-b border-slate-200 dark:border-slate-700 mb-6 -mt-2 overflow-x-auto">
        <nav class="flex gap-1 min-w-max" role="tablist">
            <template x-for="tab in tabs" :key="tab.id">
                <button
                    @click="activeTab = tab.id"
                    :class="activeTab === tab.id
                        ? 'border-green-500 text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20'
                        : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'"
                    class="px-4 py-3 text-sm font-medium border-b-2 rounded-t-lg transition whitespace-nowrap"
                    x-text="tab.label"
                ></button>
            </template>
        </nav>
    </div>

    {{-- ═══════════════ Barre d'actions globale ═══════════════ --}}
    <div class="flex flex-wrap items-center gap-3 mb-6">
        <button @click="runAllInTab()" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Lancer tous les tests de cet onglet
        </button>
        <button @click="cleanupAll()" class="inline-flex items-center gap-2 px-4 py-2 bg-red-100 dark:bg-red-900/30 hover:bg-red-200 dark:hover:bg-red-900/50 text-red-700 dark:text-red-400 font-semibold rounded-lg transition text-sm border border-red-200 dark:border-red-800">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            Nettoyer les donn&eacute;es de test
        </button>
        <div class="ml-auto flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
            <span x-text="passCount + '/' + totalCount + ' test(s)'"></span>
            <span x-show="passCount === totalCount && totalCount > 0" class="text-green-600 dark:text-green-400 font-semibold">Tout OK</span>
        </div>
    </div>

    {{-- ═══════════════ Contenu des onglets ═══════════════ --}}
    <template x-for="tab in tabs" :key="tab.id">
        <div x-show="activeTab === tab.id" x-transition.opacity>
            <div class="grid gap-3">
                <template x-for="test in tab.tests" :key="test.id">
                    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden transition-all"
                         :class="results[test.id]?.ok === true ? 'ring-1 ring-green-200 dark:ring-green-800' : (results[test.id]?.ok === false ? 'ring-1 ring-red-200 dark:ring-red-800' : '')">
                        <div class="flex items-center gap-3 px-5 py-3.5">
                            {{-- Icône statut --}}
                            <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center transition-colors"
                                 :class="{
                                    'bg-slate-100 dark:bg-slate-700 text-slate-400': !results[test.id],
                                    'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400': results[test.id]?.ok === true,
                                    'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400': results[test.id]?.ok === false,
                                    'bg-amber-100 dark:bg-amber-900/30 text-amber-600': results[test.id]?.running,
                                 }">
                                <svg x-show="!results[test.id] && !results[test.id]?.running" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/></svg>
                                <svg x-show="results[test.id]?.running" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                <svg x-show="results[test.id]?.ok === true && !results[test.id]?.running" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <svg x-show="results[test.id]?.ok === false && !results[test.id]?.running" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </div>

                            {{-- Infos --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-baseline gap-2">
                                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white" x-text="test.label"></h3>
                                    <span x-show="results[test.id]?.elapsed_ms" class="text-xs text-slate-400" x-text="results[test.id]?.elapsed_ms + ' ms'"></span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5" x-text="test.desc"></p>
                                <p x-show="results[test.id]?.message" class="text-xs font-medium mt-1"
                                   :class="results[test.id]?.ok ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                                   x-text="results[test.id]?.message"></p>
                            </div>

                            {{-- Actions --}}
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <button @click="toggleDetails(test.id)" x-show="results[test.id]?.details"
                                    class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition rounded">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </button>
                                <button @click="runTest(test.id)" :disabled="results[test.id]?.running"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 font-medium rounded-lg transition text-xs disabled:opacity-50">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/></svg>
                                    Lancer
                                </button>
                            </div>
                        </div>

                        {{-- D&eacute;tails (toggle) --}}
                        <div x-show="expandedTest === test.id && results[test.id]?.details" x-transition
                             class="px-5 pb-4 border-t border-slate-100 dark:border-slate-700">
                            <pre class="mt-3 p-3 bg-slate-50 dark:bg-slate-900 rounded-lg text-xs text-slate-700 dark:text-slate-300 overflow-x-auto max-h-64 overflow-y-auto whitespace-pre-wrap break-words"
                                 x-text="JSON.stringify(results[test.id]?.details, null, 2)"></pre>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </template>

    {{-- ═══════════════ Log en direct ═══════════════ --}}
    <div class="mt-8" x-show="logs.length > 0">
        <h3 class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-2 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            Journal des tests
        </h3>
        <div class="bg-slate-900 rounded-xl p-4 max-h-48 overflow-y-auto font-mono text-xs">
            <template x-for="(log, i) in logs" :key="i">
                <div class="flex gap-2" :class="log.ok ? 'text-green-400' : (log.ok === false ? 'text-red-400' : 'text-slate-400')">
                    <span x-text="log.time" class="text-slate-500 flex-shrink-0"></span>
                    <span x-text="log.ok ? '&check;' : (log.ok === false ? '&cross;' : '&bull;')" class="flex-shrink-0"></span>
                    <span x-text="log.text"></span>
                </div>
            </template>
        </div>
    </div>
</div>

@push('scripts')
<script>
function stripeTests() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    return {
        activeTab: 'config',
        results: {},
        expandedTest: null,
        logs: [],

        get tabs() {
            return [
                {
                    id: 'config',
                    label: 'Configuration',
                    tests: [
                        { id: 'api_connection', label: 'Connexion API Stripe', desc: 'V\u00e9rifie la cl\u00e9 secr\u00e8te et r\u00e9cup\u00e8re le solde.' },
                        { id: 'config_check', label: 'V\u00e9rification config', desc: 'Cl\u00e9s, webhook, devises, tables DB.' },
                        { id: 'stripe_products', label: 'Produits & Prix Stripe', desc: 'Liste les produits/prix actifs.' },
                        { id: 'stripe_customer_service', label: 'StripeCustomerService', desc: 'V\u00e9rifie ensureCustomer().' },
                        { id: 'audit_log', label: 'PaymentAuditLog', desc: 'Cr\u00e9e un log de test.' },
                        { id: 'calcul_montant_service', label: 'CalculMontantDuService', desc: 'Calcule un montant fictif.' },
                    ]
                },
                {
                    id: 'customer',
                    label: 'Customer & Cartes',
                    tests: [
                        { id: 'create_customer', label: 'Cr\u00e9er un Customer test', desc: 'Cr\u00e9e un customer Stripe jetable.' },
                        { id: 'attach_test_card', label: 'Attacher carte Visa test', desc: 'Attache pm_card_visa (4242).' },
                        { id: 'list_payment_methods', label: 'Lister les cartes', desc: 'Liste les cartes du customer test.' },
                    ]
                },
                {
                    id: 'payments',
                    label: 'PaymentIntents',
                    tests: [
                        { id: 'payment_success', label: 'Paiement r\u00e9ussi (5\u20ac)', desc: 'PaymentIntent off_session avec carte Visa test.' },
                        { id: 'payment_declined', label: 'Paiement refus\u00e9', desc: 'Carte d\u00e9clin\u00e9e (tok_chargeDeclined).' },
                        { id: 'payment_insufficient_funds', label: 'Fonds insuffisants', desc: 'Carte avec solde insuffisant.' },
                        { id: 'payment_3ds', label: '3DS requis', desc: 'Carte n\u00e9cessitant authentification.' },
                    ]
                },
                {
                    id: 'echeances',
                    label: '\u00c9ch\u00e9ances',
                    tests: [
                        { id: 'echeance_create', label: 'Cr\u00e9er \u00e9ch\u00e9ance test', desc: 'Cr\u00e9e une \u00e9ch\u00e9ance a_payer (5\u20ac).' },
                        { id: 'echeance_auto_charge', label: 'Auto-charge', desc: 'D\u00e9bite l\u2019\u00e9ch\u00e9ance via Stripe.' },
                        { id: 'echeance_retraction', label: 'R\u00e9traction (session)', desc: 'Ajoute en session \u2192 annule \u2192 rien en DB.' },
                        { id: 'echeance_fail_and_retry', label: '\u00c9chec + 3 retries \u2192 annul\u00e9', desc: 'Simule 3 \u00e9checs \u2192 annulation auto.' },
                        { id: 'echeance_cancel_after_7_days', label: 'Annulation apr\u00e8s 7j', desc: '\u00c9ch\u00e9ance vieille de 8j \u2192 annul\u00e9e.' },
                    ]
                },
                {
                    id: 'cashier',
                    label: 'Cashier',
                    tests: [
                        { id: 'cashier_setup', label: '\u00c9tat Cashier', desc: 'V\u00e9rifie Billable, stripe_id, abonnement.' },
                        { id: 'cashier_portal_url', label: 'Portail Client Stripe', desc: 'G\u00e9n\u00e8re une URL de portail.' },
                    ]
                },
                {
                    id: 'cron',
                    label: 'Commandes CRON',
                    tests: [
                        { id: 'process_payments_command', label: 'process-payments (dry-run)', desc: 'Lance la commande auto-charge en mode simulation.' },
                        { id: 'reconcile_command', label: 'reconcile-echeances', desc: 'Lance la r\u00e9conciliation Stripe.' },
                        { id: 'check_echeances_command', label: 'check-echeances', desc: 'V\u00e9rifie les \u00e9ch\u00e9ances mensuelles.' },
                    ]
                },
            ];
        },

        get passCount() {
            return Object.values(this.results).filter(r => r.ok === true).length;
        },
        get totalCount() {
            return Object.values(this.results).filter(r => r.ok !== undefined).length;
        },

        toggleDetails(testId) {
            this.expandedTest = this.expandedTest === testId ? null : testId;
        },

        addLog(ok, text) {
            const now = new Date();
            const time = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            this.logs.unshift({ ok, text, time });
            if (this.logs.length > 50) this.logs.pop();
        },

        async runTest(testId) {
            this.results[testId] = { running: true, ok: null, message: 'En cours\u2026', details: null, elapsed_ms: null };
            this.addLog(null, `Lancement : ${testId}`);

            try {
                const res = await fetch('{{ route("admin.stripe-tests.run") }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ test: testId }),
                });
                const data = await res.json();
                this.results[testId] = { ...data, running: false };
                this.addLog(data.ok, `${testId} \u2192 ${data.message} (${data.elapsed_ms}ms)`);
            } catch (err) {
                this.results[testId] = { ok: false, running: false, message: err.message, details: null, elapsed_ms: null };
                this.addLog(false, `${testId} \u2192 ERREUR : ${err.message}`);
            }
        },

        async runAllInTab() {
            const tab = this.tabs.find(t => t.id === this.activeTab);
            if (!tab) return;
            for (const test of tab.tests) {
                await this.runTest(test.id);
            }
        },

        async cleanupAll() {
            this.addLog(null, 'Nettoyage en cours\u2026');
            try {
                const res = await fetch('{{ route("admin.stripe-tests.cleanup") }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: '{}',
                });
                const data = await res.json();
                this.addLog(data.ok, 'Nettoyage : ' + data.message);
                this.results = {};
            } catch (err) {
                this.addLog(false, 'Nettoyage \u00e9chou\u00e9 : ' + err.message);
            }
        },
    };
}
</script>
@endpush
@endsection
