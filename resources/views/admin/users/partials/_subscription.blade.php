@php
    $subscription = $user->subscription('default');
    $hasActiveSubscription = $user->aAbonnementActif();
    $hasActiveStripeSubscription = $subscription && $subscription->valid() && !$subscription->onGracePeriod();
@endphp

<div class="space-y-6 lg:space-y-8">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8">
        <!-- Stripe Status -->
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 lg:p-8 border border-slate-200 dark:border-slate-700 shadow-sm relative overflow-hidden group">
            <div class="absolute right-[-20px] top-[-20px] opacity-[0.03] grayscale group-hover:grayscale-0 transition-all duration-500">
                <img src="https://static-00.iconduck.com/assets.00/stripe-icon-2048x2048-shf6eb3s.png" class="w-24 lg:w-32 h-24 lg:h-32" alt="Stripe">
            </div>
            
            <h3 class="text-[10px] lg:text-xs font-bold text-slate-400 uppercase tracking-widest mb-6 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                Abonnement Stripe
            </h3>
            
            @if($subscription && $subscription->valid())
                <div class="space-y-6">
                    <div class="flex flex-wrap items-center gap-2 lg:gap-3">
                        <div class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-[10px] font-bold uppercase tracking-wider animate-pulse">● Actif</div>
                        @if($subscription->onGracePeriod())
                            <span class="px-3 py-1 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold uppercase tracking-wider">Fin programmée</span>
                        @endif
                    </div>
                    
                    @if($subscription->asStripeSubscription() && isset($subscription->asStripeSubscription()->current_period_end))
                        <div>
                            <dt class="text-[10px] font-bold text-slate-400 uppercase mb-1">Prochaine échéance</dt>
                            <dd class="text-base lg:text-lg font-bold text-slate-900 dark:text-white">{{ \Carbon\Carbon::createFromTimestamp($subscription->asStripeSubscription()->current_period_end)->format('d/m/Y') }}</dd>
                        </div>
                    @endif

                    @if(!$subscription->onGracePeriod())
                        <form action="{{ route('admin.users.subscription.cancel-stripe', $user) }}" method="POST" onsubmit="return confirm('Annuler l\'abonnement ? Il restera actif jusqu\'à la fin du mois déjà payé.');">
                            @csrf
                            <button type="submit" class="w-full px-4 py-3 bg-red-50 text-red-600 hover:bg-red-100 text-[10px] lg:text-xs font-bold rounded-2xl transition-all border border-red-100 dark:bg-red-900/10 dark:border-red-900/30">
                                Stopper le renouvellement
                            </button>
                        </form>
                    @else
                        <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-2xl border border-amber-100 dark:border-amber-900/30">
                            <p class="text-[10px] font-bold text-amber-800 dark:text-amber-500 uppercase tracking-wider mb-1">Désactivation prévue le</p>
                            <p class="text-base lg:text-lg font-bold text-amber-900 dark:text-amber-400">{{ $subscription->ends_at->format('d/m/Y') }}</p>
                        </div>
                    @endif
                </div>
            @else
                <div class="p-8 text-center bg-slate-50 dark:bg-slate-900/30 rounded-2xl border border-dashed border-slate-200 dark:border-slate-700">
                    <p class="text-sm text-slate-400 italic font-medium">Aucun abonnement Stripe.</p>
                </div>
            @endif
        </div>

        <!-- Manuel Status -->
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 lg:p-8 border border-slate-200 dark:border-slate-700 shadow-sm">
            <h3 class="text-[10px] lg:text-xs font-bold text-slate-400 uppercase tracking-widest mb-6 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                Abonnement Manuel
            </h3>
            
            @if($user->abonnement_manuel && $user->abonnement_manuel_actif_jusqu)
                <div class="space-y-6">
                    <div class="flex items-center gap-3">
                        <div class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-[10px] font-bold uppercase tracking-wider">● Actif</div>
                    </div>
                    
                    <div>
                        <dt class="text-[10px] font-bold text-slate-400 uppercase mb-1">Valable jusqu'au</dt>
                        <dd class="text-base lg:text-lg font-bold text-slate-900 dark:text-white">{{ $user->abonnement_manuel_actif_jusqu->format('d/m/Y') }}</dd>
                    </div>

                    @if($user->abonnement_manuel_type_renouvellement)
                        <div class="p-4 bg-slate-50 dark:bg-slate-900/50 rounded-2xl text-[10px] lg:text-xs space-y-2">
                            <div class="flex justify-between">
                                <span class="text-slate-400 uppercase font-bold text-[9px]">Cycle :</span>
                                <span class="font-bold text-slate-900 dark:text-white">{{ $user->abonnement_manuel_type_renouvellement === 'mensuel' ? 'Mensuel' : 'Annuel' }}</span>
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
                    <p class="text-sm text-slate-400 italic font-medium">Pas d'accès manuel.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Configuration Form -->
    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="p-6 lg:p-8 border-b border-slate-100 dark:border-slate-700 bg-slate-50/30 dark:bg-slate-900/20">
            <h4 class="text-lg lg:text-xl font-bold text-slate-900 dark:text-white">{{ $user->abonnement_manuel ? '✒️ Modifier' : '🚀 Activer' }} l'abonnement Manuel</h4>
        </div>
        
        <div class="p-6 lg:p-8">
            {{-- AJOUT : Affichage des erreurs de validation --}}
            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
                    <div class="flex items-center gap-3 mb-2">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <h5 class="font-bold text-red-800 dark:text-red-400 text-sm">Erreur de validation</h5>
                    </div>
                    <ul class="list-disc list-inside text-xs text-red-700 dark:text-red-300 ml-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($hasActiveStripeSubscription)
                <div class="mb-8 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-2xl border border-amber-200 dark:border-amber-800/30 flex gap-4">
                    <span class="text-xl">⚠️</span>
                    <div>
                        <p class="text-[10px] font-bold text-amber-800 dark:text-amber-400 uppercase">Action restreinte</p>
                        <p class="text-xs text-amber-700 dark:text-amber-500 mt-1">L'abonnement Stripe est en cours. Annulez-le d'abord.</p>
                    </div>
                </div>
            @endif

            <form action="{{ route('admin.users.subscription.toggle-manual', $user) }}" method="POST" class="space-y-6 lg:space-y-8">
                @csrf
                <input type="hidden" name="activer" value="1">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Rythme</label>
                        <select 
                            name="type_renouvellement" 
                            required
                            class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-green-500 outline-none transition-all text-sm @if($hasActiveStripeSubscription) opacity-50 @endif"
                            @if($hasActiveStripeSubscription) disabled @endif
                        >
                            <option value="mensuel" {{ old('type_renouvellement', $user->abonnement_manuel_type_renouvellement) === 'mensuel' ? 'selected' : '' }}>Mensuel</option>
                            <option value="annuel" {{ old('type_renouvellement', $user->abonnement_manuel_type_renouvellement) === 'annuel' ? 'selected' : '' }}>Annuel</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Jour de renouvellement</label>
                        <input 
                            type="number" 
                            name="jour_renouvellement" 
                            required
                            min="1"
                            max="31"
                            value="{{ old('jour_renouvellement', $user->abonnement_manuel_jour_renouvellement ?? date('j')) }}"
                            class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-green-500 outline-none transition-all text-sm"
                            @if($hasActiveStripeSubscription) disabled @endif
                        >
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Date de début</label>
                        <input 
                            type="date" 
                            name="date_debut" 
                            required
                            value="{{ old('date_debut', $user->abonnement_manuel_date_debut ? $user->abonnement_manuel_date_debut->format('Y-m-d') : date('Y-m-d')) }}"
                            class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-green-500 outline-none transition-all text-sm"
                            @if($hasActiveStripeSubscription) disabled @endif
                        >
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Expiration</label>
                        <input 
                            type="date" 
                            name="date_fin" 
                            required
                            value="{{ old('date_fin', $user->abonnement_manuel_actif_jusqu ? $user->abonnement_manuel_actif_jusqu->format('Y-m-d') : '') }}"
                            class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-green-500 outline-none transition-all text-sm"
                            @if($hasActiveStripeSubscription) disabled @endif
                        >
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Tarif mensuel (€)</label>
                        <div class="relative">
                            <span class="absolute left-5 top-[18px] text-slate-400 font-bold">€</span>
                            <input 
                                type="number" 
                                name="montant" 
                                step="0.01"
                                min="0"
                                required
                                value="{{ old('montant', $user->abonnement_manuel_montant ?? '0.00') }}"
                                class="w-full pl-10 pr-5 py-4 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-green-500 outline-none transition-all font-bold text-sm"
                                @if($hasActiveStripeSubscription) disabled @endif
                            >
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Notes & Justificatif</label>
                        <textarea 
                            name="notes" 
                            rows="2"
                            placeholder="Pourquoi ce mode manuel ?..."
                            class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-green-500 outline-none transition-all text-sm"
                            @if($hasActiveStripeSubscription) disabled @endif
                        >{{ old('notes', $user->abonnement_manuel_notes ?? '') }}</textarea>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-4">
                    <button 
                        type="submit" 
                        class="flex-1 px-8 py-5 bg-slate-900 dark:bg-white dark:text-slate-900 text-white font-bold rounded-2xl shadow-xl transition-all hover:scale-[1.02] active:scale-95 disabled:opacity-50 text-sm lg:text-base"
                        @if($hasActiveStripeSubscription) disabled @endif
                    >
                        {{ $user->abonnement_manuel ? 'Mettre à jour' : 'Activer l\'accès' }}
                    </button>
                    
                    @if($user->abonnement_manuel)
                        <button type="submit" form="form-disable" class="px-8 py-5 bg-red-50 text-red-600 font-bold rounded-2xl hover:bg-red-100 transition-colors border border-red-100 text-sm lg:text-base">
                            Révoquer
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
