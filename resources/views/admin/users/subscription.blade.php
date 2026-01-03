@extends('admin.layout')

@section('title', 'Gestion abonnement - ' . $user->name)
@section('header', 'Gestion d\'Abonnement')
@section('subheader', $user->name . ' (' . $user->email . ')')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-8">
        <a href="{{ route('admin.users.show', $user) }}" class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400 transition-colors group">
            <span class="group-hover:-translate-x-1 transition-transform">←</span> Retour au profil de {{ $user->name }}
        </a>
    </div>

    <!-- Statut actuel -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        @php
            $subscription = $user->subscription('default');
            $hasActiveSubscription = $user->aAbonnementActif();
        @endphp

        <!-- Stripe Status -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 relative overflow-hidden">
            <div class="absolute right-[-20px] top-[-20px] opacity-[0.05] grayscale group-hover:grayscale-0 transition-all">
                <img src="https://static-00.iconduck.com/assets.00/stripe-icon-2048x2048-shf6eb3s.png" class="w-24 h-24" alt="Stripe">
            </div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                <span class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">💳</span>
                Stripe
            </h3>
            
            @if($subscription && $subscription->valid())
                <div class="space-y-4">
                    <div class="flex items-center gap-2 text-green-600 font-bold">
                        <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                        Abonnement ACTIF
                    </div>
                    @if($subscription->asStripeSubscription() && isset($subscription->asStripeSubscription()->current_period_end))
                        <p class="text-sm text-slate-600 dark:text-slate-400">
                            Prochaine échéance : <span class="font-medium text-slate-900 dark:text-white">{{ \Carbon\Carbon::createFromTimestamp($subscription->asStripeSubscription()->current_period_end)->format('d/m/Y') }}</span>
                        </p>
                    @endif
                    @if(!$subscription->onGracePeriod())
                        <form action="{{ route('admin.users.subscription.cancel-stripe', $user) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler l\'abonnement Stripe de cet utilisateur ? L\'abonnement restera actif jusqu\'à la fin de la période payée.');">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-sm font-bold rounded-xl border border-red-100 dark:border-red-900/30 hover:bg-red-100 transition-colors">
                                Annuler l'abonnement Stripe
                            </button>
                        </form>
                    @else
                        <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-100 dark:border-yellow-900/30 rounded-xl">
                            <p class="text-xs text-yellow-700 dark:text-yellow-500 font-medium">
                                ⚠️ Annulation programmée pour le {{ $subscription->ends_at->format('d/m/Y') }}
                            </p>
                        </div>
                    @endif
                </div>
            @else
                <p class="text-sm text-slate-500 italic">Aucun abonnement Stripe détecté.</p>
            @endif
        </div>

        <!-- Manuel Status -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                <span class="w-8 h-8 rounded-lg bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">✍️</span>
                Manuel
            </h3>
            
            @if($user->abonnement_manuel && $user->abonnement_manuel_actif_jusqu)
                <div class="space-y-3 font-medium">
                    <div class="flex items-center gap-2 text-green-600 font-bold">
                        <span class="w-2 h-2 rounded-full bg-green-500"></span>
                        Abonnement ACTIF
                    </div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        Valide jusqu'au : <span class="text-slate-900 dark:text-white">{{ $user->abonnement_manuel_actif_jusqu->format('d/m/Y') }}</span>
                    </p>
                    @if($user->abonnement_manuel_type_renouvellement)
                        <div class="text-xs text-slate-500 bg-slate-100 dark:bg-slate-700 p-2 rounded-lg">
                            Détails : {{ $user->abonnement_manuel_type_renouvellement === 'mensuel' ? 'Mensuel' : 'Annuel' }} (le {{ $user->abonnement_manuel_jour_renouvellement }})
                            @if($user->abonnement_manuel_montant)
                                - {{ number_format($user->abonnement_manuel_montant, 2, ',', ' ') }}€
                            @endif
                        </div>
                    @endif
                </div>
            @else
                <p class="text-sm text-slate-500 italic">Aucun abonnement manuel actif.</p>
            @endif
        </div>
    </div>

    <!-- Configuration Form -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="p-8 border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/20">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">
                {{ $user->abonnement_manuel ? '✒️ Modifier' : '🚀 Activer' }} l'abonnement Manuel
            </h2>
            <p class="text-sm text-slate-500 mt-1">Gérez les accès spécifiques, remises ou modes de paiements hors-Stripe.</p>
        </div>
        
        <div class="p-8">
            @php
                $hasActiveStripeSubscription = $subscription && $subscription->valid() && !$subscription->onGracePeriod();
            @endphp

            @if($hasActiveStripeSubscription)
                <div class="mb-8 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl flex gap-4">
                    <span class="text-2xl">🚧</span>
                    <div>
                        <h3 class="text-sm font-bold text-yellow-800 dark:text-yellow-400">Action impossible</h3>
                        <p class="text-sm text-yellow-700 dark:text-yellow-400/80 mt-1">
                            L'abonnement Stripe est actuellement prioritaire. Vous devez l'annuler ou attendre sa fin pour pouvoir passer en mode manuel.
                        </p>
                    </div>
                </div>
            @endif

            <form action="{{ route('admin.users.subscription.toggle-manual', $user) }}" method="POST" class="space-y-6">
                @csrf
                <input type="hidden" name="activer" value="1">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Type de cycle</label>
                        <select 
                            name="type_renouvellement" 
                            required
                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-green-500 transition-all"
                            @if($hasActiveStripeSubscription) disabled @endif
                        >
                            <option value="mensuel" {{ $user->abonnement_manuel_type_renouvellement === 'mensuel' ? 'selected' : '' }}>Mensuel (Standard)</option>
                            <option value="annuel" {{ $user->abonnement_manuel_type_renouvellement === 'annuel' ? 'selected' : '' }}>Annuel (Engagement)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Jour facturé</label>
                        <input 
                            type="number" 
                            name="jour_renouvellement" 
                            required
                            min="1"
                            max="31"
                            value="{{ $user->abonnement_manuel_jour_renouvellement ?? date('d') }}"
                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-green-500 transition-all"
                            @if($hasActiveStripeSubscription) disabled @endif
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Début de validité</label>
                        <input 
                            type="date" 
                            name="date_debut" 
                            required
                            value="{{ $user->abonnement_manuel_date_debut ? $user->abonnement_manuel_date_debut->format('Y-m-d') : date('Y-m-d') }}"
                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-green-500 transition-all"
                            @if($hasActiveStripeSubscription) disabled @endif
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Dernier jour valide</label>
                        <input 
                            type="date" 
                            name="date_fin" 
                            required
                            value="{{ $user->abonnement_manuel_actif_jusqu ? $user->abonnement_manuel_actif_jusqu->format('Y-m-d') : '' }}"
                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-green-500 transition-all"
                            @if($hasActiveStripeSubscription) disabled @endif
                        >
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Prix facturé (€)</label>
                    <div class="relative">
                        <span class="absolute left-4 top-3.5 text-slate-400">€</span>
                        <input 
                            type="number" 
                            name="montant" 
                            step="0.01"
                            min="0"
                            required
                            value="{{ $user->abonnement_manuel_montant ?? '0.00' }}"
                            class="w-full pl-10 pr-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-green-500 transition-all font-bold"
                            @if($hasActiveStripeSubscription) disabled @endif
                        >
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Notes & Justification</label>
                    <textarea 
                        name="notes" 
                        rows="3"
                        placeholder="Pourquoi ce mode manuel ? (Ex: Offert pour test, paiement chèque...)"
                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-green-500 transition-all"
                        @if($hasActiveStripeSubscription) disabled @endif
                    >{{ $user->abonnement_manuel_notes ?? '' }}</textarea>
                </div>

                <div class="flex flex-col sm:flex-row gap-4 pt-4">
                    <button 
                        type="submit" 
                        class="flex-1 px-8 py-4 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-bold rounded-2xl shadow-[0_10px_20px_-10px_rgba(22,163,74,0.5)] transition-all hover:scale-[1.01] disabled:opacity-50 disabled:grayscale disabled:cursor-not-allowed"
                        @if($hasActiveStripeSubscription) disabled @endif
                    >
                        {{ $user->abonnement_manuel ? '💾 Sauvegarder les modifications' : '✨ Activer maintenant' }}
                    </button>
                    
                    @if($user->abonnement_manuel)
                        <button type="submit" form="form-disable" class="px-8 py-4 border-2 border-red-100 dark:border-red-900/30 text-red-600 dark:text-red-400 font-bold rounded-2xl hover:bg-red-50 dark:hover:bg-red-900/10 transition-colors">
                            🛑 Désactiver totalement
                        </button>
                    @endif
                </div>
            </form>

            @if($user->abonnement_manuel)
                <form id="form-disable" action="{{ route('admin.users.subscription.toggle-manual', $user) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer les informations d\'abonnement manuel ?');">
                    @csrf
                </form>
            @endif
        </div>
    </div>
</div>
@endsection

