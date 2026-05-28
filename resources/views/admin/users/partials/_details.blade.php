<div class="space-y-6 lg:space-y-8">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8">
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 lg:p-8 border border-slate-100 dark:border-slate-700 shadow-sm">
            <h3 class="text-[10px] lg:text-xs font-bold text-slate-400 uppercase tracking-widest mb-6 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-green-500"></span>
                Identité & Contact
            </h3>
            <dl class="space-y-8 mt-2">
                <div>
                    <dt class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Nom complet</dt>
                    <dd class="text-lg lg:text-xl font-bold text-slate-900 dark:text-white truncate leading-relaxed">{{ $user->name }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Adresse Email</dt>
                    <dd class="text-lg lg:text-xl font-bold text-slate-900 dark:text-white break-all leading-relaxed">
                        <div class="flex items-center gap-3 flex-wrap">
                            <span>{{ $user->email }}</span>
                            @if($user->hasVerifiedEmail())
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-md text-[10px] font-bold uppercase tracking-wider border border-green-200 dark:border-green-800">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Vérifié
                                </span>
                                @if($user->email_verified_at)
                                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">
                                        le {{ $user->email_verified_at->format('d/m/Y à H:i') }}
                                    </span>
                                @endif
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-md text-[10px] font-bold uppercase tracking-wider border border-red-200 dark:border-red-800">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    Non vérifié
                                </span>
                                <form action="{{ route('admin.email-logs.verify-user', $user) }}" method="POST" class="inline-block">
                                    @csrf
                                    <button type="submit" 
                                            onclick="return confirm('Êtes-vous sûr de vouloir vérifier manuellement cet email ?')"
                                            class="px-3 py-1.5 text-xs font-semibold bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Vérifier manuellement
                                    </button>
                                </form>
                            @endif
                        </div>
                    </dd>
                </div>
            </dl>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-3xl p-8 lg:p-10 border border-slate-100 dark:border-slate-700 shadow-sm">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-8 flex items-center gap-3">
                <span class="w-2.5 h-2.5 rounded-full bg-blue-500 shadow-sm shadow-blue-500/50"></span>
                Système
            </h3>
            <dl class="space-y-8 mt-2">
                <div>
                    <dt class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Statut du compte</dt>
                    <dd class="flex items-center gap-2">
                        @php
                            $statut = $user->statut_compte ?? 'normal';
                            $statutConfig = [
                                'normal' => ['label' => 'Normal', 'color' => 'green', 'icon' => '<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'],
                                'limite' => ['label' => 'Limité', 'color' => 'yellow', 'icon' => '<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>'],
                                'interdit' => ['label' => 'Interdit', 'color' => 'red', 'icon' => '<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>'],
                                'supprime' => ['label' => 'Supprimé', 'color' => 'gray', 'icon' => '<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>'],
                            ];
                            $config = $statutConfig[$statut] ?? $statutConfig['normal'];
                        @endphp
                        {!! $config['icon'] !!}
                        <span class="px-3 py-1.5 text-sm font-bold rounded-lg
                            @if($config['color'] === 'green') bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400
                            @elseif($config['color'] === 'yellow') bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400
                            @elseif($config['color'] === 'red') bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400
                            @else bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-400
                            @endif">
                            {{ $config['label'] }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Date d'inscription</dt>
                    <dd class="text-lg lg:text-xl font-bold text-slate-900 dark:text-white leading-relaxed">{{ $user->created_at->format('d/m/Y') }} <span class="text-sm font-medium text-slate-400 ml-1">à {{ $user->created_at->format('H:i') }}</span></dd>
                </div>
                <div>
                    <dt class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Identifiant Unique</dt>
                    <dd class="text-sm font-mono text-slate-500 bg-slate-50 dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-100 dark:border-slate-800 inline-block">#{{ str_pad($user->id, 6, '0', STR_PAD_LEFT) }}</dd>
                </div>
            </dl>
        </div>
    </div>
</div>
