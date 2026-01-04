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
                        {{ $user->email }}
                        @if($user->email_verified_at)
                            <span class="inline-block ml-2 px-2.5 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-md text-[10px] font-bold uppercase tracking-wider border border-green-200 dark:border-green-800">Vérifié</span>
                        @endif
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
