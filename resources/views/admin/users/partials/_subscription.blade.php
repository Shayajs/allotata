@php
    $subscription = $user->subscription('default');
    $hasActiveSubscription = $user->aAbonnementActif();
    $hasActiveStripeSubscription = $subscription && $subscription->valid() && !$subscription->onGracePeriod();
@endphp

<div class="space-y-8">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Stripe Status -->
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-8 border border-slate-200 dark:border-slate-700 shadow-sm relative overflow-hidden group">
            <div class="absolute right-[-20px] top-[-20px] opacity-[0.03] grayscale group-hover:grayscale-0 transition-all duration-500">
                <img src="https://static-00.iconduck.com/assets.00/stripe-icon-2048x2048-shf6eb3s.png" class="w-32 h-32" alt="Stripe">
            </div>
            
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-6 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                Abonnement Stripe (Automatique)
            </h3>
            
            @if($subscription && $subscription->valid())
                <div class="space-y-6">
                    <div class="flex items-center gap-3">
                        <div class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-[10px] font-bold uppercase tracking-wider animate-pulse">● Actif</div>
                        @if($subscription->onGracePeriod())
                            <span class="px-3 py-1 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold uppercase tracking-wider">Fin programmée</span>
                        @endif
                    </div>
                    
                    @if($subscription->asStripeSubscription() && isset($subscription->asStripeSubscription()->current_period_end))
                        <div>
                            <dt class="text-[10px] font-bold text-slate-400 uppercase mb-1">Prochaine échéance</dt>
                            <dd class="text-lg font-bold text-slate-900 dark:text-white">{{ \Carbon\Carbon::createFromTimestamp($subscription->asStripeSubscription()->current_period_end)->format('d/m/Y') }}</dd>
                        </div>
                    @endif

                    @if(!$subscription->onGracePeriod())
                        <form action="{{ route('admin.users.subscription.cancel-stripe', $user) }}" method="POST" onsubmit="return confirm('Annuler l\'abonnement ? Il restera actif jusqu\'à la fin du mois déjà payé.');">
                            @csrf
                            <button type="submit" class="w-full px-4 py-3 bg-red-50 text-red-600 hover:bg-red-100 text-xs font-bold rounded-2xl transition-all border border-red-100 dark:bg-red-900/10 dark:border-red-900/30">
                                Stopper le renouvellement
                            </button>
                        </form>
                    @else
                        <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-2xl border border-amber-100 dark:border-amber-900/30">
                            <p class="text-[10px] font-bold text-amber-800 dark:text-amber-500 uppercase tracking-wider mb-1">Désactivation prévue le</p>
                            <p class="text-lg font-bold text-amber-900 dark:text-amber-400">{{ $subscription->ends_at->format('d/m/Y') }}</p>
                        </div>
                    @endif
                </div>
            @else
                <div class="p-8 text-center bg-slate-50 dark:bg-slate-900/30 rounded-2xl border border-dashed border-slate-200 dark:border-slate-700">
                    <p class="text-sm text-slate-400 italic font-medium">Aucun abonnement Stripe en cours.</p>
                </div>
            @endif
        </div>

        <!-- Manuel Status -->
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-8 border border-slate-200 dark:border-slate-700 shadow-sm">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-6 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                Abonnement Manuel (Offert / Direct)
            </h3>
            
            @if($user->abonnement_manuel && $user->abonnement_manuel_actif_jusqu)
                <div class="space-y-6">
                    <div class="flex items-center gap-3">
                        <div class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-[10px] font-bold uppercase tracking-wider">● Actif</div>
                    </div>
                    
                    <div>
                        <dt class="text-[10px] font-bold text-slate-400 uppercase mb-1">Valable jusqu'au</dt>
                        <dd class="text-lg font-bold text-slate-900 dark:text-white">{{ $user->abonnement_manuel_actif_jusqu->format('d/m/Y') }}</dd>
                    </div>

                    @if($user->abonnement_manuel_type_renouvellement)
                        <div class="p-4 bg-slate-50 dark:bg-slate-900/50 rounded-2xl text-xs space-y-2">
                            <div class="flex justify-between">
                                <span class="text-slate-400 uppercase font-bold text-[9px]">Cycle :</span>
                                <span class="font-bold text-slate-900 dark:text-white">{{ $user->abonnement_manuel_type_renouvellement === 'mensuel' ? 'Mensuel' : 'Annuel' }} (le {{ $user->abonnement_manuel_jour_renouvellement }})</span>
                            </div>
                            @if($user->abonnement_manuel_montant)
                                <div class="flex justify-between">
                                    <span class="text-slate-400 uppercase font-bold text-[9px]">Tarif :</span>
                                    <span class="font-bold text-slate-900 dark:text-white">{{ number_format($user->abonnement_manuel_montant, 2, ',', ' ') }}€</span>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @else
                <div class="p-8 text-center bg-slate-50 dark:bg-slate-900/30 rounded-2xl border border-dashed border-slate-200 dark:border-slate-700">
                    <p class="text-sm text-slate-400 italic font-medium">Pas d'abonnement manuel actif.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Configuration Form -->
    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="p-8 border-b border-slate-100 dark:border-slate-700 bg-slate-50/30 dark:bg-slate-900/20">
            <h4 class="text-xl font-bold text-slate-900 dark:text-white">{{ $user->abonnement_manuel ? '✒️ Modifier' : '🚀 Activer' }} l'abonnement Manuel</h4>
            <p class="text-sm text-slate-500 mt-1">Forcez l'activation du mode Premium pour cet utilisateur sans passer par Stripe.</p>
        </div>
        
        <div class="p-8">
            @if($hasActiveStripeSubscription)
                <div class="mb-8 p-6 bg-amber-50 dark:bg-amber-900/20 rounded-2xl border border-amber-200 dark:border-amber-800/30 flex gap-4">
                    <span class="text-2xl">⚠️</span>
                    <div>
                        <p class="text-sm font-bold text-amber-800 dark:text-amber-400">Action restreinte</p>
                        <p class="text-xs text-amber-700 dark:text-amber-500 mt-1">L'abonnement Stripe est en cours. Annulez-le d'abord pour pouvoir configurer un accès manuel.</p>
                    </div>
                </div>
            @endif

            <form action="{{ route('admin.users.subscription.toggle-manual', $user) }}" method="POST" class="space-y-8">
                @csrf
                <input type="hidden" name="activer" value="1">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Rythme de facturation</label>
                        <select 
                            name="type_renouvellement" 
                            required
                            class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-green-500 outline-none transition-all appearance-none cursor-pointer"
                            @if($hasActiveStripeSubscription) disabled @endif
                        >
                            <option value="mensuel" {{ $user->abonnement_manuel_type_renouvellement === 'mensuel' ? 'selected' : '' }}>Mensuel</option>
                            <option value="annuel" {{ $user->abonnement_manuel_type_renouvellement === 'annuel' ? 'selected' : '' }}>Annuel</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Jour de renouvellement</label>
                        <input 
                            type="number" 
                            name="jour_renouvellement" 
                            required
                            min="1"
                            max="31"
                            value="{{ $user->abonnement_manuel_jour_renouvellement ?? date('d') }}"
                            class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-green-500 outline-none transition-all"
                            @if($hasActiveStripeSubscription) disabled @endif
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Date de début</label>
                        <input 
                            type="date" 
                            name="date_debut" 
                            required
                            value="{{ $user->abonnement_manuel_date_debut ? $user->abonnement_manuel_date_debut->format('Y-m-d') : date('Y-m-d') }}"
                            class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-green-500 outline-none transition-all"
                            @if($hasActiveStripeSubscription) disabled @endif
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Expiration de l'accès</label>
                        <input 
                            type="date" 
                            name="date_fin" 
                            required
                            value="{{ $user->abonnement_manuel_actif_jusqu ? $user->abonnement_manuel_actif_jusqu->format('Y-m-d') : '' }}"
                            class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-green-500 outline-none transition-all"
                            @if($hasActiveStripeSubscription) disabled @endif
                        >
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Tarif mensuel (€)</label>
                        <div class="relative">
                            <span class="absolute left-5 top-5 text-slate-400 font-bold">€</span>
                            <input 
                                type="number" 
                                name="montant" 
                                step="0.01"
                                min="0"
                                required
                                value="{{ $user->abonnement_manuel_montant ?? '0.00' }}"
                                class="w-full pl-10 pr-5 py-4 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-green-500 outline-none transition-all font-bold"
                                @if($hasActiveStripeSubscription) disabled @endif
                            >
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Notes & Justificatif</label>
                        <textarea 
                            name="notes" 
                            rows="2"
                            placeholder="Pourquoi ce mode manuel ?..."
                            class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-green-500 outline-none transition-all text-sm"
                            @if($hasActiveStripeSubscription) disabled @endif
                        >{{ $user->abonnement_manuel_notes ?? '' }}</textarea>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-4">
                    <button 
                        type="submit" 
                        class="flex-1 px-8 py-5 bg-slate-900 dark:bg-white dark:text-slate-900 text-white font-bold rounded-2xl shadow-xl transition-all hover:scale-[1.02] active:scale-95 disabled:opacity-50 disabled:grayscale"
                        @if($hasActiveStripeSubscription) disabled @endif
                    >
                        {{ $user->abonnement_manuel ? 'Mettre à jour l\'accès' : 'Valider l\'activation manuelle' }}
                    </button>
                    
                    @if($user->abonnement_manuel)
                        <button type="submit" form="form-disable" class="px-8 py-5 bg-red-50 text-red-600 font-bold rounded-2xl hover:bg-red-100 transition-colors border border-red-100">
                            Révoquer l'accès
                        </button>
                    @endif
                </div>
            </form>

            @if($user->abonnement_manuel)
                <form id="form-disable" action="{{ route('admin.users.subscription.toggle-manual', $user) }}" method="POST" onsubmit="return confirm('Désactiver l\'accès manuel ?');">
                    @csrf
                </form>
            @endif
        </div>
    </div>
</div>
