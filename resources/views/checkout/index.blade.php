<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="stripe-publishable-key" content="{{ config('services.stripe.key') }}">
        <title>Espace Paiement – Allo Tata</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/checkout.js'])
        @include('partials.theme-script')
    </head>
    <body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">
        <nav class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 shadow-sm">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <a href="{{ route('dashboard') }}" class="text-xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">Allo Tata</a>
                    <div class="flex items-center gap-3">
                        <span class="hidden sm:inline text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Espace Paiement</span>
                        <a href="{{ route('settings.index', ['tab' => 'subscription']) }}" class="px-3 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700">Abonnement</a>
                        <a href="{{ route('dashboard') }}" class="px-3 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700">Dashboard</a>
                    </div>
                </div>
            </div>
        </nav>

        {{-- Toast JS (succès / erreur) --}}
        <div id="checkout-toast" class="hidden fixed top-20 left-1/2 -translate-x-1/2 z-50 max-w-md w-full mx-4 px-4 py-3 rounded-xl shadow-lg border text-center font-medium" role="alert"></div>

        <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            {{-- En-tête : positionnement "espace de gestion" --}}
            <header class="mb-8">
                <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white tracking-tight">Espace Paiement</h1>
                <p class="mt-2 text-slate-600 dark:text-slate-400 text-lg max-w-2xl">
                    Gestion de vos tarifs et règlements. Vous consultez ici les montants dus, appliquez vos codes promo et réglez en toute transparence.
                </p>
                <div class="mt-4 flex flex-wrap items-center gap-4 text-sm text-slate-500 dark:text-slate-400">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        Paiement sécurisé
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                        Prix fixés au moment du règlement
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        @if($hasPaymentMethod ?? false) Carte enregistrée •••• {{ $user->pm_last_four ?? '****' }} @else Aucune carte enregistrée @endif
                    </span>
                </div>
            </header>

            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl flex items-center gap-3">
                    <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <p class="text-green-800 dark:text-green-400 font-medium">{{ session('success') }}</p>
                </div>
            @endif
            @if(session('error'))
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl flex items-center gap-3">
                    <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <p class="text-red-800 dark:text-red-400 font-medium">{{ session('error') }}</p>
                </div>
            @endif
            @if(session('info'))
                <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl flex items-center gap-3">
                    <svg class="w-5 h-5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <p class="text-blue-800 dark:text-blue-400 font-medium">{{ session('info') }}</p>
                </div>
            @endif
            @if($errors->any())
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
                    @foreach($errors->all() as $err)
                        <p class="text-red-800 dark:text-red-400 font-medium">{{ $err }}</p>
                    @endforeach
                </div>
            @endif

            @if(!($hasPaymentMethod ?? false))
                <section class="mb-8 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Moyen de paiement</h2>
                        <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Enregistrez une carte pour régler vos échéances. Aucun débit immédiat.</p>
                    </div>
                    <div class="p-6">
                        <form id="checkout-save-card-form">
                            <div id="checkout-payment-element" class="min-h-[200px]"></div>
                            <p id="checkout-card-error" class="mt-2 text-sm text-red-600 dark:text-red-400" role="alert"></p>
                            <button type="submit" class="mt-4 px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl transition">Enregistrer ma carte</button>
                        </form>
                    </div>
                </section>
            @else
                <div class="mb-6 p-4 bg-slate-100 dark:bg-slate-800 rounded-xl flex items-center gap-3">
                    <span class="text-slate-600 dark:text-slate-400">Carte enregistrée</span>
                    <span class="font-medium text-slate-900 dark:text-white">•••• {{ $user->pm_last_four ?? '****' }}</span>
                </div>
            @endif

            @if($echeances->isEmpty())
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                    <div class="p-10 sm:p-14 text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 mb-6">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Tout est à jour</h2>
                        <p class="text-slate-600 dark:text-slate-400 mb-6 max-w-sm mx-auto">Vous n'avez aucune échéance à régler pour le moment. Vos prochains montants apparaîtront ici lorsqu'ils seront dus.</p>
                        <a href="{{ route('settings.index', ['tab' => 'subscription']) }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-800 dark:text-slate-200 font-semibold rounded-xl transition">Voir mon abonnement</a>
                    </div>
                </div>
            @else
                @php
                    $totalTTC = 0;
                    foreach ($echeances as $e) {
                        $c = $calculs[$e->id] ?? [];
                        $totalTTC += (float) ($c['montant_final'] ?? $e->montant_final ?? 0);
                    }
                @endphp

                {{-- Récapitulatif global --}}
                <section class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm mb-8 overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Récapitulatif</h2>
                    </div>
                    <div class="p-6 sm:p-8">
                        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-6">
                            <div class="space-y-1">
                                <p class="text-slate-600 dark:text-slate-400">
                                    <strong class="text-slate-900 dark:text-white">{{ $echeances->count() }}</strong> échéance{{ $echeances->count() > 1 ? 's' : '' }} à régler
                                </p>
                                <p class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">
                                    {{ number_format($totalTTC, 2, ',', ' ') }} <span class="text-lg font-semibold text-slate-500 dark:text-slate-400">€</span>
                                </p>
                            </div>
                            <div class="sm:text-right">
                                @if($codePromo)
                                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 mb-3">
                                        <span class="font-medium">Code appliqué :</span>
                                        <code class="font-semibold">{{ $codePromo }}</code>
                                        <form action="{{ route('checkout.retirer-promo') }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="text-green-600 dark:text-green-500 hover:underline text-sm">Retirer</button>
                                        </form>
                                    </div>
                                @else
                                    <form action="{{ route('checkout.appliquer-promo') }}" method="POST" class="flex flex-wrap gap-2 sm:justify-end">
                                        @csrf
                                        <input type="text" name="code" placeholder="Code promo" class="px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-slate-900 dark:text-white w-full sm:w-48" maxlength="64">
                                        <button type="submit" class="px-4 py-2.5 bg-slate-200 dark:bg-slate-600 hover:bg-slate-300 dark:hover:bg-slate-500 text-slate-800 dark:text-slate-200 font-semibold rounded-xl transition w-full sm:w-auto">Appliquer</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Échéances : cartes type facture --}}
                <section>
                    <h2 class="sr-only">Échéances à régler</h2>
                    <div class="space-y-5">
                        @foreach($echeances as $e)
                            @php $calc = $calculs[$e->id] ?? []; $montantFinal = $calc['montant_final'] ?? $e->montant_final ?? 0; @endphp
                            <article class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                                <div class="p-6 sm:p-8">
                                    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex flex-wrap items-baseline gap-2">
                                                <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ $e->libelle() }}</h3>
                                                <span class="text-sm text-slate-500 dark:text-slate-400">
                                                    {{ $e->periode_debut->format('d/m/Y') }} → {{ $e->periode_fin->format('d/m/Y') }}
                                                </span>
                                            </div>
                                            @if(!empty($calc['lignes']))
                                                <dl class="mt-4 space-y-1.5 text-sm">
                                                    @foreach($calc['lignes'] as $ligne)
                                                        <div class="flex justify-between gap-4">
                                                            <dt class="text-slate-600 dark:text-slate-400">{{ $ligne['label'] }}</dt>
                                                            <dd class="font-medium text-slate-900 dark:text-white tabular-nums">{{ number_format($ligne['montant'], 2, ',', ' ') }} €</dd>
                                                        </div>
                                                    @endforeach
                                                </dl>
                                            @endif
                                            @if(($calc['reduction_promo'] ?? 0) > 0)
                                                <p class="mt-2 text-sm text-green-600 dark:text-green-400 font-medium">− {{ number_format($calc['reduction_promo'], 2, ',', ' ') }} € (code promo)</p>
                                            @endif
                                            @if(($e->reduction_manuel ?? 0) > 0)
                                                <p class="mt-1 text-sm text-amber-600 dark:text-amber-400 font-medium">− {{ number_format($e->reduction_manuel, 2, ',', ' ') }} € (réduction)</p>
                                            @endif
                                        </div>
                                        <div class="lg:text-right lg:flex-shrink-0 flex flex-col sm:flex-row lg:flex-col items-start sm:items-center lg:items-end gap-4">
                                            <div>
                                                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-0.5">Total à régler</p>
                                                <p class="text-2xl font-bold text-slate-900 dark:text-white tabular-nums">{{ number_format($montantFinal, 2, ',', ' ') }} €</p>
                                            </div>
                                            <button type="button" class="checkout-regler-btn inline-flex items-center justify-center gap-2 w-full sm:w-auto lg:w-full px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl transition shadow-sm hover:shadow disabled:opacity-60 disabled:cursor-not-allowed" data-echeance-id="{{ $e->id }}" @if($codePromo) data-code-promo="{{ $codePromo }}" @endif>
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                                <span class="checkout-regler-label">Régler cette échéance</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif
        </main>
    </body>
</html>
