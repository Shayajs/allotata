<div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">💳 Abonnements et options</h2>
    @if($entreprise->user_id === auth()->id() && !request()->routeIs('entreprise.subscriptions.modal'))
        <a href="{{ route('settings.index', ['tab' => 'subscription']) }}" class="px-4 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-semibold rounded-lg transition text-sm flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            Gérer l'abonnement
        </a>
    @endif
</div>

@php
    $abonnementSiteWeb = $entreprise->abonnementSiteWeb();
    $abonnementMultiPersonnes = $entreprise->abonnementMultiPersonnes();
    $aSiteWebActif = $entreprise->aSiteWebActif();
    $aGestionMultiPersonnes = $entreprise->aGestionMultiPersonnes();
@endphp

<div class="space-y-6">
    <!-- Site Web Vitrine -->
    <div class="border border-slate-200 dark:border-slate-700 rounded-xl p-6 {{ $aSiteWebActif ? 'bg-green-50 dark:bg-green-900/20' : '' }}">
        <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                    <span class="text-3xl">🌐</span>
                    <div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white">Site Web Vitrine</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-400">Créez une page vitrine personnalisée pour votre entreprise</p>
                    </div>
                </div>
                @if($aSiteWebActif)
                    <div class="mt-4 p-4 bg-white dark:bg-slate-800 rounded-lg border border-green-200 dark:border-green-800">
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="font-semibold text-green-800 dark:text-green-400">Abonnement actif</span>
                        </div>
                        @if(!empty($entreprise->slug_web))
                            <div class="mb-3">
                                <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">URL de votre site :</p>
                                <a href="{{ route('site-web.show', ['slug' => $entreprise->slug_web]) }}" target="_blank" class="text-green-600 dark:text-green-400 hover:underline font-medium">
                                    {{ url('/w/' . $entreprise->slug_web) }}
                                </a>
                            </div>
                        @endif
                        <div class="flex items-center gap-3">
                            @if(!empty($entreprise->slug_web))
                                <a href="{{ route('site-web.show', ['slug' => $entreprise->slug_web]) }}" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition text-sm">
                                    Gérer le site
                                </a>
                            @else
                                <a href="{{ route('site-web.show', ['slug' => $entreprise->slug]) }}" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition text-sm">
                                    Configurer le site
                                </a>
                            @endif
                            @if($abonnementSiteWeb && !$abonnementSiteWeb->est_manuel)
                                <form action="{{ route('entreprise.subscriptions.cancel', [$entreprise->slug, 'site_web']) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition text-sm">
                                        Gérer sur Stripe
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                                        @else
                                            <div class="mt-4 p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <p class="font-semibold text-slate-900 dark:text-white mb-1">Fonctionnalités incluses :</p>
                                                        <ul class="text-sm text-slate-600 dark:text-slate-400 space-y-1 list-disc list-inside">
                                                            <li>Page vitrine personnalisée accessible via /w/{slug}</li>
                                                            <li>Logo et phrase d'accroche</li>
                                                            <li>Photos de réalisations</li>
                                                            <li>Sections configurables</li>
                                                        </ul>
                                                    </div>
                                                    <div class="text-right">
                                                        <div class="text-3xl font-bold text-green-600 dark:text-green-400 mb-1">2€</div>
                                                        <div class="text-sm text-slate-600 dark:text-slate-400">/mois</div>
                                                    </div>
                                                </div>
                                                <div class="mt-4 space-y-3">
                                                    <form action="{{ route('entreprise.subscriptions.checkout', $entreprise->slug) }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="type" value="site_web">
                                                        <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                                                            S'abonner à Site Web Vitrine
                                                        </button>
                                                    </form>
                                                    @if(auth()->user()->is_admin)
                                                        <div class="border-t border-slate-300 dark:border-slate-600 pt-3">
                                                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-2 text-center">Ou activer manuellement (admin) :</p>
                                                            <a href="{{ route('admin.entreprises.options', $entreprise) }}#tab-abonnements" class="block w-full px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-all text-center">
                                                                Activer l'option (Admin)
                                                            </a>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
            </div>
        </div>
    </div>

    <!-- Gestion Multi-Personnes -->
    <div class="border border-slate-200 dark:border-slate-700 rounded-xl p-6 {{ $aGestionMultiPersonnes ? 'bg-green-50 dark:bg-green-900/20' : '' }}">
        <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                    <span class="text-3xl">👥</span>
                    <div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white">Gestion Multi-Personnes</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-400">Gérez plusieurs personnes pour votre entreprise</p>
                    </div>
                </div>
                @if($aGestionMultiPersonnes)
                    <div class="mt-4 p-4 bg-white dark:bg-slate-800 rounded-lg border border-green-200 dark:border-green-800">
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="font-semibold text-green-800 dark:text-green-400">Abonnement actif</span>
                        </div>
                        <div class="mb-3">
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-2">Fonctionnalités disponibles :</p>
                            <ul class="text-sm text-slate-700 dark:text-slate-300 space-y-1 list-disc list-inside">
                                <li>Ajouter des administrateurs et membres</li>
                                <li>Statistiques avancées</li>
                                <li>Gestion de plusieurs établissements</li>
                            </ul>
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'equipe']) }}" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition text-sm">
                                Gérer les membres
                            </a>
                            @if($abonnementMultiPersonnes && !$abonnementMultiPersonnes->est_manuel)
                                <form action="{{ route('entreprise.subscriptions.cancel', [$entreprise->slug, 'multi_personnes']) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition text-sm">
                                        Gérer sur Stripe
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                                        @else
                                            <div class="mt-4 p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <p class="font-semibold text-slate-900 dark:text-white mb-1">Fonctionnalités incluses :</p>
                                                        <ul class="text-sm text-slate-600 dark:text-slate-400 space-y-1 list-disc list-inside">
                                                            <li>Ajouter des administrateurs (personnes avec compte existant)</li>
                                                            <li>Statistiques avancées détaillées</li>
                                                            <li>Gestion de plusieurs établissements</li>
                                                            <li>Collaboration en équipe</li>
                                                        </ul>
                                                    </div>
                                                    <div class="text-right">
                                                        <div class="text-3xl font-bold text-green-600 dark:text-green-400 mb-1">20€</div>
                                                        <div class="text-sm text-slate-600 dark:text-slate-400">/mois</div>
                                                    </div>
                                                </div>
                                                <div class="mt-4 space-y-3">
                                                    <form action="{{ route('entreprise.subscriptions.checkout', $entreprise->slug) }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="type" value="multi_personnes">
                                                        <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                                                            S'abonner à Gestion Multi-Personnes
                                                        </button>
                                                    </form>
                                                    @if(auth()->user()->is_admin)
                                                        <div class="border-t border-slate-300 dark:border-slate-600 pt-3">
                                                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-2 text-center">Ou activer manuellement (admin) :</p>
                                                            <a href="{{ route('admin.entreprises.options', $entreprise) }}#tab-abonnements" class="block w-full px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-all text-center">
                                                                Activer l'option (Admin)
                                                            </a>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
            </div>
        </div>
    </div>

    <!-- Site Web Externe (Gratuit) -->
    <div class="border border-slate-200 dark:border-slate-700 rounded-xl p-6 bg-slate-50 dark:bg-slate-700/50">
        <div class="flex items-start gap-3 mb-4">
            <span class="text-3xl">🔗</span>
            <div class="flex-1">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white">Lier un site web externe</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400">Si vous avez déjà un site web, vous pouvez le lier à votre entreprise (gratuit)</p>
            </div>
            <span class="px-3 py-1 text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 rounded-full">
                Gratuit
            </span>
        </div>
        <form action="{{ route('settings.entreprise.update', $entreprise->slug) }}" method="POST" class="mt-4">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        URL de votre site web
                    </label>
                    <input 
                        type="url" 
                        name="site_web_externe" 
                        value="{{ old('site_web_externe', $entreprise->site_web_externe) }}"
                        placeholder="https://votre-site.com"
                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        L'URL sera visible sur votre profil public.
                    </p>
                </div>
                <button type="submit" class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                    Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>
