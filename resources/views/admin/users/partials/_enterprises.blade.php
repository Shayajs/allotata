<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h3 class="text-xl font-bold text-slate-900 dark:text-white">Entreprises rattachées</h3>
        <span class="px-3 py-1 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 rounded-full text-xs font-bold">{{ $user->entreprises->count() }} au total</span>
    </div>

    @if($user->entreprises->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($user->entreprises as $entreprise)
                <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm hover:border-green-300 dark:hover:border-green-800 transition-all group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-900 flex items-center justify-center text-2xl">
                                🏢
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-900 dark:text-white group-hover:text-green-600 transition-colors">{{ $entreprise->nom }}</h4>
                                <p class="text-xs text-slate-500">{{ $entreprise->type_activite }}</p>
                            </div>
                        </div>
                        <span class="px-2 py-1 {{ $entreprise->est_valide ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }} rounded text-[10px] font-bold uppercase tracking-wider">
                            {{ $entreprise->est_valide ? 'Validée' : 'En attente' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between pt-4 border-t border-slate-50 dark:border-slate-700">
                        <span class="text-[10px] text-slate-400 italic">Créée le {{ $entreprise->created_at->format('d/m/Y') }}</span>
                        <a href="{{ route('admin.entreprises.show', $entreprise) }}" class="text-xs font-bold text-green-600 hover:underline">Accéder à la fiche →</a>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="bg-slate-50 dark:bg-slate-900/50 rounded-3xl p-12 text-center border-2 border-dashed border-slate-200 dark:border-slate-700">
            <span class="text-4xl mb-4 block">🏢</span>
            <p class="text-slate-500 font-medium">Cet utilisateur ne possède aucune entreprise pour le moment.</p>
        </div>
    @endif
</div>
