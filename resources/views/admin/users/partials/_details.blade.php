<div class="space-y-8">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-8 border border-slate-100 dark:border-slate-700 shadow-sm">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-6 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-green-500"></span>
                Identité & Contact
            </h3>
            <dl class="space-y-6">
                <div>
                    <dt class="text-[10px] font-bold text-slate-400 uppercase mb-1">Nom complet</dt>
                    <dd class="text-lg font-bold text-slate-900 dark:text-white">{{ $user->name }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] font-bold text-slate-400 uppercase mb-1">Adresse Email</dt>
                    <dd class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        {{ $user->email }}
                        @if($user->email_verified_at)
                            <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded text-[10px] uppercase">Vérifié</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-3xl p-8 border border-slate-100 dark:border-slate-700 shadow-sm">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-6 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                Système
            </h3>
            <dl class="space-y-6">
                <div>
                    <dt class="text-[10px] font-bold text-slate-400 uppercase mb-1">Date d'inscription</dt>
                    <dd class="text-lg font-bold text-slate-900 dark:text-white">{{ $user->created_at->format('d F Y') }} <span class="text-xs font-normal text-slate-400">à {{ $user->created_at->format('H:i') }}</span></dd>
                </div>
                <div>
                    <dt class="text-[10px] font-bold text-slate-400 uppercase mb-1">Identifiant Unique</dt>
                    <dd class="text-sm font-mono text-slate-500 bg-slate-50 dark:bg-slate-900 px-2 py-1 rounded inline-block">#{{ str_pad($user->id, 6, '0', STR_PAD_LEFT) }}</dd>
                </div>
            </dl>
        </div>
    </div>
</div>
