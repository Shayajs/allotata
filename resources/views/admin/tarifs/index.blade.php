@extends('admin.layout')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Tarifs</h1>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
            Vous définissez ici les prix. Ils sont stockés en base et affichés partout (abonnements, options entreprise, checkout). Plus de connexion Stripe ni .env pour les montants.
        </p>
        <div class="mt-3 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
            <p class="text-sm text-amber-800 dark:text-amber-400">
                <strong>Prix bloqué :</strong> Quand un abonné paie, son prix est fixé (hausse ou baisse ultérieure ne le concerne pas). S'il <strong>annule</strong> son abonnement puis se réabonne, il sera soumis aux <strong>nouveaux</strong> tarifs affichés ici.
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
            <p class="text-sm text-green-800 dark:text-green-400">{{ session('success') }}</p>
        </div>
    @endif
    @if(session('stripe_test_ok'))
        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg flex items-center gap-2">
            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <p class="text-sm text-green-800 dark:text-green-400">{{ session('stripe_test_ok') }}</p>
        </div>
    @endif
    @if(session('stripe_test_error'))
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg flex items-center gap-2">
            <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <p class="text-sm text-red-800 dark:text-red-400">{{ session('stripe_test_error') }}</p>
        </div>
    @endif
    @if($errors->any())
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
            <ul class="text-sm text-red-800 dark:text-red-400">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="space-y-6">
        @foreach($tarifs as $tarif)
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ $tarif->label ?? $tarif->type }}</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            @if($tarif->type === 'default')
                                Abonnement Premium (utilisateur)
                            @elseif($tarif->type === 'site_web')
                                Site Web Vitrine
                            @else
                                Gestion Multi-Personnes
                            @endif
                        </p>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $tarif->formatted }}</span>
                        <span class="text-slate-500 dark:text-slate-400">/mois</span>
                    </div>
                </div>
                <form action="{{ route('admin.stripe-prices.update', $tarif->type) }}" method="POST" class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Montant (€)</label>
                        <input type="number" name="amount" step="0.01" min="0" value="{{ old('amount', $tarif->amount) }}" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Devise</label>
                        <select name="currency" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                            <option value="eur" {{ ($tarif->currency ?? 'eur') === 'eur' ? 'selected' : '' }}>EUR (€)</option>
                            <option value="usd" {{ ($tarif->currency ?? '') === 'usd' ? 'selected' : '' }}>USD ($)</option>
                            <option value="gbp" {{ ($tarif->currency ?? '') === 'gbp' ? 'selected' : '' }}>GBP (£)</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Libellé (optionnel)</label>
                        <input type="text" name="label" value="{{ old('label', $tarif->label) }}" placeholder="Ex. Abonnement Premium" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white" maxlength="255">
                    </div>
                    <div class="md:col-span-4 flex justify-end">
                        <button type="submit" class="ui-btn-simple px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">Enregistrer</button>
                    </div>
                </form>
            </div>
        @endforeach
    </div>

    {{-- Test Stripe : clés, Setup Intent, débit API --}}
    <div class="mt-12 pt-8 border-t border-slate-200 dark:border-slate-700">
        <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Test Stripe</h2>
        <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
            <strong>Setup Intent</strong> : enregistrer une carte sans débiter (X jours gratuits, etc.). Si le Setup fonctionne, le <strong>débit API</strong> fonctionnera. Flux recommandé : 1) Test Setup → 2) Test débit API (0,50 €, sans redirection).
        </p>
        <div class="flex flex-wrap gap-4 items-center">
            <form action="{{ route('admin.stripe-prices.verify-keys') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="ui-btn-simple inline-flex items-center gap-2 px-4 py-2.5 bg-slate-700 hover:bg-slate-600 dark:bg-slate-600 dark:hover:bg-slate-500 text-white font-medium rounded-xl transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                    Vérifier les clés Stripe
                </button>
            </form>
            <a href="{{ route('admin.stripe-prices.test-setup') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                Test Setup (enregistrer carte)
            </a>
            <form action="{{ route('admin.stripe-prices.test-debit-api') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="ui-btn-simple inline-flex items-center gap-2 px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white font-medium rounded-xl transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                    Test débit API (0,50 €)
                </button>
            </form>
            <form action="{{ route('admin.stripe-prices.test-payment') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="ui-btn-simple inline-flex items-center gap-2 px-4 py-2.5 bg-slate-500 hover:bg-slate-600 text-white font-medium rounded-xl transition">
                    Paiement test Checkout (0,50 €, redirection)
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
