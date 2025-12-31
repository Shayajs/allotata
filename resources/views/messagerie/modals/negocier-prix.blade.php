<div id="modal-negocier-{{ $propositionActive->id }}" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 hidden p-4">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-md w-full p-6">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-orange-500 to-yellow-500 flex items-center justify-center text-white text-2xl">
                    💰
                </div>
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">Négocier le prix</h2>
            </div>
            <button onclick="document.getElementById('modal-negocier-{{ $propositionActive->id }}').classList.add('hidden')" class="w-10 h-10 flex items-center justify-center text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form action="{{ route('messagerie.negocier-prix', [$entreprise->slug, $propositionActive->id]) }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div class="bg-slate-100 dark:bg-slate-700 rounded-xl p-4">
                    <label class="block text-sm font-medium text-slate-600 dark:text-slate-400 mb-1">Prix actuel</label>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($propositionActive->prix_propose, 2, ',', ' ') }} €</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Votre proposition (€) *</label>
                    <input type="number" name="nouveau_prix" required min="0" step="0.01" value="{{ $propositionActive->prix_final ?? $propositionActive->prix_propose }}" class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                </div>
                <div class="flex gap-3 justify-end pt-4 border-t border-slate-200 dark:border-slate-700">
                    <button type="button" onclick="document.getElementById('modal-negocier-{{ $propositionActive->id }}').classList.add('hidden')" class="px-6 py-3 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 transition font-semibold">
                        Annuler
                    </button>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-orange-600 to-orange-500 hover:from-orange-700 hover:to-orange-600 text-white font-semibold rounded-xl transition-all shadow-lg">
                        Proposer
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

