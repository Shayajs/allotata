<div class="max-w-2xl space-y-6">
    <!-- Gestion du statut du compte -->
    <div class="bg-white dark:bg-slate-800 rounded-3xl p-8 border border-slate-100 dark:border-slate-700 shadow-sm">
        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-6">État du compte</h3>
        
        <form action="{{ route('admin.users.status.update', $user) }}" method="POST" class="space-y-6">
            @csrf
            @method('POST')
            
            <!-- Affichage du statut actuel -->
            <div class="mb-6 p-5 bg-slate-50 dark:bg-slate-900/30 rounded-2xl border border-slate-100 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Statut actuel</p>
                        <div class="flex items-center gap-3">
                            @php
                                $currentStatus = $user->statut_compte ?? 'normal';
                                $statusConfig = [
                                    'normal' => ['label' => 'Normal', 'color' => 'green', 'icon' => '<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'],
                                    'limite' => ['label' => 'Limité', 'color' => 'yellow', 'icon' => '<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>'],
                                    'interdit' => ['label' => 'Interdit', 'color' => 'red', 'icon' => '<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>'],
                                    'supprime' => ['label' => 'Supprimé', 'color' => 'gray', 'icon' => '<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>'],
                                ];
                                $config = $statusConfig[$currentStatus] ?? $statusConfig['normal'];
                            @endphp
                            {!! $config['icon'] !!}
                            <span class="text-lg font-bold text-slate-900 dark:text-white">{{ $config['label'] }}</span>
                            <span class="px-3 py-1 text-xs font-bold rounded-full
                                @if($config['color'] === 'green') bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400
                                @elseif($config['color'] === 'yellow') bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400
                                @elseif($config['color'] === 'red') bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400
                                @else bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-400
                                @endif">
                                {{ $config['label'] }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sélecteur de statut -->
            <div>
                <label class="block text-sm font-bold text-slate-900 dark:text-white mb-3">
                    Changer l'état du compte
                </label>
                <select 
                    name="statut_compte" 
                    class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-700 rounded-2xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-semibold text-base"
                >
                    <option value="normal" {{ $currentStatus === 'normal' ? 'selected' : '' }}>Normal - Accès complet</option>
                    <option value="limite" {{ $currentStatus === 'limite' ? 'selected' : '' }}>Limité - Accès restreint</option>
                    <option value="interdit" {{ $currentStatus === 'interdit' ? 'selected' : '' }}>Interdit - Connexion bloquée</option>
                </select>
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                    <strong>Normal :</strong> L'utilisateur a un accès complet à toutes les fonctionnalités autorisées par ses rôles.<br>
                    <strong>Limité :</strong> L'utilisateur a un accès restreint mais peut toujours se connecter.<br>
                    <strong>Interdit :</strong> L'utilisateur ne peut plus se connecter. Les tentatives de connexion seront bloquées.
                </p>
            </div>
            
            <div class="pt-4">
                <button type="submit" class="ui-btn-simple w-full px-8 py-5 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-bold rounded-2xl shadow-xl transition-all hover:scale-[1.02] active:scale-95 flex items-center justify-center gap-3">
                    <span>💾</span> Enregistrer l'état du compte
                </button>
            </div>
        </form>
    </div>

    <!-- Gestion des permissions -->
    <div class="bg-white dark:bg-slate-800 rounded-3xl p-8 border border-slate-100 dark:border-slate-700 shadow-sm">
        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-6">Gestion des permissions</h3>
        
        <form action="{{ route('admin.users.update', $user) }}" method="POST" class="space-y-6">
            @csrf
            <div class="space-y-4">
                <label class="flex items-start p-5 border border-slate-100 dark:border-slate-700 rounded-2xl cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-900/30 transition-all group">
                    <div class="mt-1">
                        <input type="checkbox" name="est_client" value="1" {{ $user->est_client ? 'checked' : '' }} class="w-6 h-6 rounded-lg border-slate-300 text-green-600 focus:ring-green-500 transition-all">
                    </div>
                    <div class="ml-4">
                        <span class="block text-base font-bold text-slate-900 dark:text-white group-hover:text-green-600 transition-colors">Client Utilisateur</span>
                        <span class="block text-xs text-slate-500 mt-1">Permet à l'utilisateur de prendre des rendez-vous et d'accéder à son espace personnel.</span>
                    </div>
                </label>

                <label class="flex items-start p-5 border border-slate-100 dark:border-slate-700 rounded-2xl cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-900/30 transition-all group">
                    <div class="mt-1">
                        <input type="checkbox" name="est_gerant" value="1" {{ $user->est_gerant ? 'checked' : '' }} class="w-6 h-6 rounded-lg border-slate-300 text-green-600 focus:ring-green-500 transition-all">
                    </div>
                    <div class="ml-4">
                        <span class="block text-base font-bold text-slate-900 dark:text-white group-hover:text-green-600 transition-colors">Gérant d'Établissement</span>
                        <span class="block text-xs text-slate-500 mt-1">Autorise la création d'entreprises et l'accès au tableau de bord professionnel.</span>
                    </div>
                </label>

                <label class="flex items-start p-5 border-2 border-red-50 dark:border-red-900/20 rounded-2xl cursor-pointer hover:bg-red-50/30 dark:hover:bg-red-900/10 transition-all group">
                    <div class="mt-1">
                        <input type="checkbox" name="is_admin" value="1" {{ $user->is_admin ? 'checked' : '' }} class="w-6 h-6 rounded-lg border-red-200 text-red-600 focus:ring-red-500 transition-all">
                    </div>
                    <div class="ml-4">
                        <span class="block text-base font-bold text-red-600 dark:text-red-400">Administrateur Système</span>
                        <span class="block text-xs text-red-500/70 mt-1 font-medium flex items-center gap-1">
                            <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            ATTENTION : Accès total et illimité à l'ensemble du panel d'administration.
                        </span>
                    </div>
                </label>
            </div>
            
            <div class="pt-6">
                <button type="submit" class="ui-btn-simple w-full px-8 py-5 bg-gradient-to-r from-slate-900 to-slate-800 dark:from-white dark:to-slate-200 dark:text-slate-900 text-white font-bold rounded-2xl shadow-xl transition-all hover:scale-[1.02] active:scale-95 flex items-center justify-center gap-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Enregistrer les privilèges
                </button>
            </div>
        </form>
    </div>
</div>
