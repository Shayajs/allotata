<h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">💳 Gestion de l'abonnement</h2>

@php
    $echeancesAPayer = \App\Models\Echeance::where('user_id', $user->id)
        ->whereIn('statut', [\App\Models\Echeance::STATUT_A_PAYER, \App\Models\Echeance::STATUT_EN_ATTENTE])
        ->requiringUserPayment($user)
        ->count();
@endphp
@if($echeancesAPayer > 0)
    <div class="mb-6 p-4 sm:p-5 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center justify-between gap-4">
        <p class="text-amber-800 dark:text-amber-400 font-medium">
            Vous avez {{ $echeancesAPayer }} échéance(s) à régler.
        </p>
        <a href="{{ route('checkout.index') }}" class="inline-flex items-center justify-center px-5 py-3 sm:py-2.5 min-h-[44px] bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl transition touch-manipulation">
            Payer maintenant →
        </a>
    </div>
@endif

@php
    $upcomingEcheances = $upcomingEcheances ?? collect();
    $lastPayments = $lastPayments ?? collect();
@endphp

{{-- Cartes bleues --}}
<div class="mb-6 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 sm:p-6">
    <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-slate-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
        Carte(s) enregistrée(s)
    </h3>
    @if($user->stripe_payment_method_id)
        <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center justify-between gap-4">
            <p class="text-slate-700 dark:text-slate-300">
                <span class="font-medium">{{ ucfirst($user->pm_type ?? 'carte') }}</span>
                <span class="tabular-nums">•••• {{ $user->pm_last_four ?? '****' }}</span>
            </p>
            <a href="{{ route('checkout.index', ['change_card' => 1]) }}" class="inline-flex items-center justify-center min-h-[44px] px-5 py-3 sm:py-2.5 bg-slate-200 dark:bg-slate-600 hover:bg-slate-300 dark:hover:bg-slate-500 text-slate-800 dark:text-slate-200 font-semibold rounded-xl transition text-sm touch-manipulation w-full sm:w-auto">
                Modifier la carte
            </a>
        </div>
    @else
        <p class="text-slate-600 dark:text-slate-400 mb-4 text-sm sm:text-base">Aucune carte enregistrée. Ajoutez une carte pour régler vos échéances ou pour les prélèvements automatiques.</p>
        <a href="{{ route('checkout.index') }}" class="inline-flex items-center justify-center gap-2 min-h-[44px] px-5 py-3 w-full sm:w-auto bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl transition text-sm touch-manipulation">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Ajouter une carte
        </a>
    @endif
</div>

@php
    $hasActiveSubscription = $user->aAbonnementActif();
    $essaiPremium = $user->essaiActif('premium');
    $peutEssayerPremium = $user->peutDemarrerEssai('premium');
    $hasTrialSubscription = $essaiPremium && $essaiPremium->estEnCours();
@endphp

<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 sm:p-6 mb-6">
    <div class="text-center mb-6">
        <h3 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white mb-2">
            Abonnement Premium
        </h3>
        <div class="flex items-baseline justify-center gap-2 mb-4 flex-wrap">
            @php
                $defaultPrice = \App\Models\Tarif::displayForUser($user, 'default');
                $currentPriceAmount = $defaultPrice['amount'] ?? 0;
            @endphp
            @if($currentPriceAmount > 0)
                <span class="text-4xl sm:text-5xl font-bold text-green-600 dark:text-green-400">{{ $defaultPrice['formatted'] }}</span>
            @else
                <span class="text-4xl sm:text-5xl font-bold text-green-600 dark:text-green-400">-</span>
            @endif
            <span class="text-lg sm:text-xl text-slate-600 dark:text-slate-400">/mois</span>
        </div>
        <p class="text-slate-600 dark:text-slate-400 text-sm sm:text-base">
            Accès complet à toutes les fonctionnalités • Sans engagement • Annulation à tout moment
        </p>
    </div>

    @if($hasActiveSubscription)
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4 sm:p-6 mb-6">
            <div class="flex items-center gap-3 mb-4">
                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h3 class="text-xl font-bold {{ $hasTrialSubscription ? 'text-orange-600 dark:text-orange-400' : 'text-green-800 dark:text-green-400' }}">
                    @if($hasTrialSubscription)
                        Essai gratuit actif
                    @else
                        Abonnement actif
                    @endif
                </h3>
            </div>
            
            @if($hasTrialSubscription)
                <div class="mb-4 p-4 bg-orange-50 dark:bg-orange-900/10 rounded-lg border border-orange-200 dark:border-orange-800">
                    <p class="text-sm text-orange-800 dark:text-orange-400 mb-3">
                        <strong>Votre essai expire le {{ $essaiPremium->date_fin->format('d/m/Y à H:i') }}</strong>
                        ({{ $essaiPremium->joursRestants() }} jour(s) restant(s)).<br>
                        Abonnez-vous maintenant pour continuer à profiter de toutes les fonctionnalités.
                    </p>
                    <form action="{{ route('subscription.checkout') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full min-h-[44px] px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-xl transition-all touch-manipulation">
                            @if($currentPriceAmount > 0)
                                S'abonner maintenant ({{ $defaultPrice['formatted'] }}/mois)
                            @else
                                S'abonner maintenant
                            @endif
                        </button>
                    </form>
                </div>
            @elseif($subscription && $subscription->valid())
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-slate-600 dark:text-slate-400 mb-1">Type</p>
                            <p class="font-semibold text-slate-900 dark:text-white">Abonnement Stripe</p>
                        </div>
                        <div>
                            <p class="text-slate-600 dark:text-slate-400 mb-1">Statut</p>
                            @if($subscription->onGracePeriod())
                                <p class="font-semibold text-yellow-600 dark:text-yellow-400">Annulé - Actif jusqu'au {{ $subscription->ends_at->format('d/m/Y') }}</p>
                            @else
                                <p class="font-semibold text-green-600 dark:text-green-400">Actif</p>
                            @endif
                        </div>
                        @php
                            $stripeSubscription = null;
                            try {
                                $stripeSubscription = $subscription->asStripeSubscription();
                            } catch (\Exception $e) {
                                // Ignorer l'erreur si l'abonnement n'existe plus chez Stripe
                            }
                        @endphp
                        
                        @if(!$stripeSubscription && $subscription->valid())
                             <div class="col-span-1 md:col-span-2 mt-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                                <h4 class="font-bold text-red-800 dark:text-red-400 mb-2">⚠️ Problème de synchronisation</h4>
                                <p class="text-sm text-red-700 dark:text-red-300 mb-4">
                                    Votre abonnement semble avoir été supprimé de la plateforme de paiement mais est toujours affiché comme actif ici.
                                </p>
                                <form action="{{ route('subscription.purge', $subscription->id) }}" method="POST" onsubmit="return confirm('Nettoyer la base de données ?');">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition text-sm">
                                        Corriger le problème (Supprimer l'abonnement fantôme)
                                    </button>
                                </form>
                            </div>
                        @else
                        @if($stripeSubscription)
                            @if(isset($stripeSubscription->current_period_end))
                                <div>
                                    <p class="text-slate-600 dark:text-slate-400 mb-1">Prochain paiement</p>
                                    <p class="font-semibold text-slate-900 dark:text-white">
                                        {{ \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end)->format('d/m/Y') }}
                                    </p>
                                </div>
                            @endif
                            @if(isset($stripeSubscription->current_period_start) && isset($stripeSubscription->current_period_end))
                                <div>
                                    <p class="text-slate-600 dark:text-slate-400 mb-1">Période actuelle</p>
                                    <p class="font-semibold text-slate-900 dark:text-white">
                                        Du {{ \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_start)->format('d/m/Y') }}
                                        au {{ \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end)->format('d/m/Y') }}
                                    </p>
                                </div>
                            @endif
                        @endif
                    @endif
                    </div>
                    
                    <div class="space-y-4 mt-6">
                        @if($subscription->onGracePeriod())
                            <div class="flex flex-col gap-3">
                                <form action="{{ route('subscription.resume') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg shadow-sm transition-all transform hover:scale-[1.01]">
                                        🚀 Réactiver mon abonnement Premium
                                    </button>
                                </form>
                                <p class="text-sm text-center text-yellow-600 dark:text-yellow-400 font-medium italic">
                                    Votre accès Premium restera valide jusqu'au {{ $subscription->ends_at->format('d/m/Y') }}
                                </p>
                            </div>
                        @else
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <form action="{{ route('subscription.cancel') }}" method="POST" onsubmit="return confirm('Voulez-vous vraiment annuler votre abonnement Premium ? Vous garderez vos accès jusqu\'au prochain renouvellement.');">
                                    @csrf
                                    <button type="submit" class="w-full min-h-[44px] px-4 py-3 bg-white dark:bg-slate-800 border border-red-200 dark:border-red-900/50 text-red-600 dark:text-red-400 font-semibold rounded-xl hover:bg-red-50 dark:hover:bg-red-900/20 transition-all touch-manipulation">
                                        🛑 Annuler l'abonnement
                                    </button>
                                </form>

                                <form action="{{ route('subscription.manage') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full min-h-[44px] px-4 py-3 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold rounded-xl hover:bg-slate-200 dark:hover:bg-slate-600 transition-all touch-manipulation">
                                        💳 Gérer le paiement
                                    </button>
                                </form>
                            </div>
                            <p class="text-xs text-center text-slate-500 dark:text-slate-400 italic">
                                L'annulation prend effet à la fin de la période facturée. Vous ne serez plus débité.
                            </p>
                        @endif
                    </div>
                </div>
            @elseif($user->abonnement_manuel && $user->abonnement_manuel_actif_jusqu)
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-slate-600 dark:text-slate-400 mb-1">Type</p>
                            <p class="font-semibold text-slate-900 dark:text-white">Abonnement manuel (géré par l'administrateur)</p>
                        </div>
                        <div>
                            <p class="text-slate-600 dark:text-slate-400 mb-1">Actif jusqu'au</p>
                            <p class="font-semibold text-slate-900 dark:text-white">{{ $user->abonnement_manuel_actif_jusqu->format('d/m/Y') }}</p>
                        </div>
                        @if($user->abonnement_manuel_notes)
                            <div class="md:col-span-2">
                                <p class="text-slate-600 dark:text-slate-400 mb-1">Note</p>
                                <p class="font-semibold text-slate-900 dark:text-white">{{ $user->abonnement_manuel_notes }}</p>
                            </div>
                        @endif
                    </div>
                    <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                        <p class="text-sm text-blue-800 dark:text-blue-400">
                            ℹ️ Vous avez un abonnement manuel actif. Vous ne pouvez pas souscrire à un abonnement Stripe tant que l'abonnement manuel est actif.
                        </p>
                    </div>
                </div>
            @endif
        </div>

    @else
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4 sm:p-6 mb-6">
            <div class="mb-4">
                <p class="text-yellow-800 dark:text-yellow-400 font-semibold mb-2">
                    ⚠️ Vous n'avez pas d'abonnement actif
                </p>
                <p class="text-sm text-yellow-700 dark:text-yellow-500">
                    Sans abonnement actif, vos entreprises ne seront pas visibles en ligne. Souscrivez maintenant pour accéder à toutes les fonctionnalités.
                </p>
            </div>
            <div class="space-y-4">
                @if($peutEssayerPremium)
                    <form action="{{ route('essai-gratuit.utilisateur') }}" method="POST">
                        @csrf
                        <input type="hidden" name="source" value="page_paiement">
                        <button type="submit" class="w-full min-h-[44px] px-6 py-3 bg-gradient-to-r from-orange-500 to-yellow-500 hover:from-orange-600 hover:to-yellow-600 text-white font-semibold rounded-xl transition-all touch-manipulation">
                            Essayer gratuitement pendant 7 jours
                        </button>
                    </form>
                    <p class="text-center text-xs text-slate-500 dark:text-slate-400">Sans engagement • Sans carte bancaire</p>
                    <div class="relative flex items-center justify-center py-1">
                        <span class="absolute inset-x-0 h-px bg-yellow-200 dark:bg-yellow-800"></span>
                        <span class="relative px-4 bg-yellow-50 dark:bg-yellow-900/20 text-xs text-slate-500 dark:text-slate-400">ou</span>
                    </div>
                @else
                    <p class="text-center text-sm text-slate-600 dark:text-slate-400">
                        Vous avez déjà utilisé votre essai gratuit. Un nouvel essai n'est plus possible.
                    </p>
                @endif
                <form action="{{ route('subscription.checkout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full min-h-[44px] px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-xl transition-all touch-manipulation">
                        @if($currentPriceAmount > 0)
                            Souscrire à l'abonnement ({{ $defaultPrice['formatted'] }}/mois)
                        @else
                            Souscrire à l'abonnement
                        @endif
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>

{{-- Factures (Stripe) --}}
@if(isset($invoices) && $invoices->isNotEmpty())
    <div class="mt-6 sm:mt-8 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 sm:p-6">
        <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-slate-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            Factures
        </h3>
        <div class="space-y-3">
            @foreach($invoices->take(10) as $invoice)
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 bg-slate-50 dark:bg-slate-700/50 rounded-xl border border-slate-200 dark:border-slate-600">
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-900 dark:text-white truncate">
                            @if(isset($invoice->created))
                                Facture du {{ \Carbon\Carbon::createFromTimestamp($invoice->created)->format('d/m/Y') }}
                            @else
                                Facture
                            @endif
                        </p>
                        <p class="text-sm text-slate-600 dark:text-slate-400">
                            {{ number_format($invoice->amount_paid / 100, 2, ',', ' ') }} €
                            @if($invoice->status === 'paid')
                                <span class="ml-2 text-green-600 dark:text-green-400">✓ Payée</span>
                            @elseif($invoice->status === 'open')
                                <span class="ml-2 text-yellow-600 dark:text-yellow-400">En attente</span>
                            @else
                                <span class="ml-2 text-red-600 dark:text-red-400">Impayée</span>
                            @endif
                        </p>
                    </div>
                    <a href="{{ route('subscription.invoice.download', $invoice->id) }}" class="inline-flex items-center justify-center min-h-[44px] flex-shrink-0 px-5 py-3 sm:py-2.5 w-full sm:w-auto bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl transition text-sm touch-manipulation">📥 Télécharger</a>
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- Derniers paiements --}}
@if($lastPayments->isNotEmpty())
    <div class="mt-6 sm:mt-8 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 sm:p-6">
        <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-slate-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Derniers paiements
        </h3>
        <div class="space-y-3">
            @foreach($lastPayments->take(10) as $p)
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 py-3 border-b border-slate-200 dark:border-slate-700 last:border-0">
                    <div class="min-w-0">
                        <p class="font-medium text-slate-900 dark:text-white truncate">{{ $p->label }}</p>
                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ $p->date ? \Carbon\Carbon::parse($p->date)->format('d/m/Y H:i') : '' }}</p>
                    </div>
                    <p class="font-semibold text-slate-900 dark:text-white tabular-nums flex-shrink-0">{{ number_format($p->amount, 2, ',', ' ') }} {{ strtoupper($p->currency ?? 'eur') }}</p>
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- Prochains paiements --}}
@if($upcomingEcheances->isNotEmpty())
    <div class="mt-6 sm:mt-8 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 sm:p-6">
        <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-slate-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            Prochains paiements
        </h3>
        <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">Échéances Stripe à régler ou à annuler.</p>
        <div class="space-y-4">
            @foreach($upcomingEcheances->filter(fn ($e) => $e->requiresUserPayment($user)) as $e)
                @php
                    $montant = (float) ($e->montant_final ?? $e->montant_du ?? 0);
                @endphp
                <div class="flex flex-col sm:flex-row sm:flex-wrap sm:items-center justify-between gap-4 p-4 rounded-xl bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600">
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-900 dark:text-white">{{ $e->libelle() }}</p>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            {{ $e->periode_debut->format('d/m/Y') }} → {{ $e->periode_fin->format('d/m/Y') }}
                            @if($e->statut === \App\Models\Echeance::STATUT_EN_ATTENTE)
                                <span class="ml-2 text-amber-600 dark:text-amber-400">(en attente)</span>
                            @endif
                        </p>
                    </div>
                    <div class="flex flex-col-reverse sm:flex-row sm:items-center gap-3 flex-wrap">
                        <p class="font-bold text-slate-900 dark:text-white tabular-nums text-lg sm:text-base">{{ number_format($montant, 2, ',', ' ') }} €</p>
                        <div class="flex gap-2 flex-wrap">
                            <a href="{{ route('checkout.index') }}" class="inline-flex items-center justify-center min-h-[44px] flex-1 sm:flex-none px-4 py-3 sm:py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl transition text-sm touch-manipulation">Régler</a>
                            <form action="{{ route('subscription.echeance.annuler', $e) }}" method="POST" class="inline flex-1 sm:flex-none" onsubmit="return confirm('Annuler cette échéance ? Vous ne serez pas débité pour cette période.');">
                                @csrf
                                <button type="submit" class="w-full sm:w-auto min-h-[44px] px-4 py-3 sm:py-2.5 bg-slate-200 dark:bg-slate-600 hover:bg-slate-300 dark:hover:bg-slate-500 text-slate-800 dark:text-slate-200 font-semibold rounded-xl transition text-sm touch-manipulation">Annuler</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-4">
            <a href="{{ route('checkout.index') }}" class="inline-flex items-center justify-center gap-2 min-h-[44px] w-full sm:w-auto px-5 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl transition text-sm touch-manipulation">
                Voir toutes les échéances et payer →
            </a>
        </div>
    </div>
@endif

<!-- Abonnements des entreprises -->
@if($entreprises->count() > 0)
    <div class="mt-6 sm:mt-8 border-t border-slate-200 dark:border-slate-700 pt-6 sm:pt-8">
        <h3 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white mb-6">📦 Abonnements de vos entreprises</h3>
        
        <div class="space-y-4">
            @foreach($entreprises as $entreprise)
                @php
                    $abonnementSiteWeb = $entreprise->abonnementSiteWeb();
                    $abonnementMultiPersonnes = $entreprise->abonnementMultiPersonnes();
                    $aSiteWebActif = $entreprise->aSiteWebActif();
                    $aGestionMultiPersonnes = $entreprise->aGestionMultiPersonnes();
                @endphp
                
                <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl border border-slate-200 dark:border-slate-700 p-4 sm:p-6">
                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-4">
                        <div class="min-w-0">
                            <h4 class="text-base sm:text-lg font-semibold text-slate-900 dark:text-white">{{ $entreprise->nom }}</h4>
                            <p class="text-sm text-slate-600 dark:text-slate-400">{{ $entreprise->type_activite }}</p>
                        </div>
                        <a href="{{ route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'abonnements']) }}" class="inline-flex items-center justify-center min-h-[44px] flex-shrink-0 w-full sm:w-auto px-4 py-3 sm:py-2.5 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-semibold rounded-xl transition text-sm touch-manipulation">
                            Voir détails
                        </a>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Site Web Vitrine -->
                        <div class="p-4 border border-slate-200 dark:border-slate-600 rounded-lg {{ $aSiteWebActif ? 'bg-green-50 dark:bg-green-900/20' : '' }}">
                            <div class="flex items-center justify-between mb-2">
                                <h5 class="font-semibold text-slate-900 dark:text-white">🌐 Site Web Vitrine</h5>
                                @if($aSiteWebActif)
                                    <span class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-full">Actif</span>
                                @endif
                            </div>
                            @php $siteWebPrice = \App\Models\Tarif::displayForEntreprise($entreprise, 'site_web'); @endphp
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-3">{{ $siteWebPrice['formatted'] }}/mois</p>
                            
                            @if($aSiteWebActif)
                                @if($abonnementSiteWeb && !$abonnementSiteWeb->est_manuel)
                                    @if($abonnementSiteWeb->ends_at && $abonnementSiteWeb->ends_at->isFuture())
                                        <form action="{{ route('entreprise.subscriptions.resume', [$entreprise->slug, 'site_web']) }}" method="POST" class="mb-2">
                                            @csrf
                                            <button type="submit" class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition text-sm">
                                                Réactiver l'abonnement
                                            </button>
                                        </form>
                                        <div class="text-xs text-center text-yellow-600 dark:text-yellow-400 mb-2">
                                            Fin de l'accès le {{ $abonnementSiteWeb->ends_at->format('d/m/Y') }}
                                        </div>
                                    @else
                                        <form action="{{ route('entreprise.subscriptions.cancel-direct', [$entreprise->slug, 'site_web']) }}" method="POST" class="mb-2" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler ? L\'accès restera valide jusqu\'à la fin de la période payée.');">
                                            @csrf
                                            <button type="submit" class="w-full px-4 py-2 bg-red-50 text-red-600 border border-red-200 hover:bg-red-100 dark:bg-red-900/20 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-900/30 font-semibold rounded-lg transition text-sm">
                                                Annuler l'abonnement
                                            </button>
                                        </form>
                                    @endif

                                    <form action="{{ route('entreprise.subscriptions.cancel', [$entreprise->slug, 'site_web']) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="w-full px-4 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-semibold rounded-lg transition text-sm">
                                            Gérer sur Stripe
                                        </button>
                                    </form>
                                @else
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Abonnement manuel</p>
                                @endif
                            @else
                                <form action="{{ route('entreprise.subscriptions.checkout', $entreprise->slug) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="type" value="site_web">
                                    <button type="submit" class="w-full px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition text-sm">
                                        S'abonner
                                    </button>
                                </form>
                            @endif
                        </div>

                        <!-- Gestion Multi-Personnes -->
                        <div class="p-4 border border-slate-200 dark:border-slate-600 rounded-lg {{ $aGestionMultiPersonnes ? 'bg-green-50 dark:bg-green-900/20' : '' }}">
                            <div class="flex items-center justify-between mb-2">
                                <h5 class="font-semibold text-slate-900 dark:text-white">👥 Gestion Multi-Personnes</h5>
                                @if($aGestionMultiPersonnes)
                                    <span class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-full">Actif</span>
                                @endif
                            </div>
                            @php $multiPrice = \App\Models\Tarif::displayForEntreprise($entreprise, 'multi_personnes'); @endphp
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-3">{{ $multiPrice['formatted'] }}/mois</p>
                            
                            @if($aGestionMultiPersonnes)
                                @if($abonnementMultiPersonnes && !$abonnementMultiPersonnes->est_manuel)
                                    @if($abonnementMultiPersonnes->ends_at && $abonnementMultiPersonnes->ends_at->isFuture())
                                        <form action="{{ route('entreprise.subscriptions.resume', [$entreprise->slug, 'multi_personnes']) }}" method="POST" class="mb-2">
                                            @csrf
                                            <button type="submit" class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition text-sm">
                                                Réactiver l'abonnement
                                            </button>
                                        </form>
                                        <div class="text-xs text-center text-yellow-600 dark:text-yellow-400 mb-2">
                                            Fin de l'accès le {{ $abonnementMultiPersonnes->ends_at->format('d/m/Y') }}
                                        </div>
                                    @else
                                        <form action="{{ route('entreprise.subscriptions.cancel-direct', [$entreprise->slug, 'multi_personnes']) }}" method="POST" class="mb-2" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler ? L\'accès restera valide jusqu\'à la fin de la période payée.');">
                                            @csrf
                                            <button type="submit" class="w-full px-4 py-2 bg-red-50 text-red-600 border border-red-200 hover:bg-red-100 dark:bg-red-900/20 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-900/30 font-semibold rounded-lg transition text-sm">
                                                Annuler l'abonnement
                                            </button>
                                        </form>
                                    @endif

                                    <form action="{{ route('entreprise.subscriptions.cancel', [$entreprise->slug, 'multi_personnes']) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="w-full px-4 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-semibold rounded-lg transition text-sm">
                                            Gérer sur Stripe
                                        </button>
                                    </form>
                                @else
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Abonnement manuel</p>
                                @endif
                            @else
                                <form action="{{ route('entreprise.subscriptions.checkout', $entreprise->slug) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="type" value="multi_personnes">
                                    <button type="submit" class="w-full px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition text-sm">
                                        S'abonner
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
