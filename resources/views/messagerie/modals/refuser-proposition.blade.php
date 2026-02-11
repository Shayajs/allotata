<div id="modal-refuser-{{ $propositionActive->id }}" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 hidden p-4">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-md w-full p-6">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-500 to-pink-500 flex items-center justify-center text-white text-2xl">
                    ✗
                </div>
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">Refuser la proposition</h2>
            </div>
            <button onclick="document.getElementById('modal-refuser-{{ $propositionActive->id }}').classList.add('hidden')" class="w-10 h-10 flex items-center justify-center text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form action="{{ $isGerant ? route('messagerie.refuser-proposition-gerant', [$entreprise->slug, $conversation->id, $propositionActive->id]) : route('messagerie.refuser-proposition', [$entreprise->slug, $propositionActive->id]) }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Raison (optionnel)</label>
                    <textarea name="raison" rows="3" maxlength="500" placeholder="Expliquez pourquoi vous refusez cette proposition..." class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent bg-white dark:bg-slate-700 text-slate-900 dark:text-white resize-none"></textarea>
                </div>
                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input 
                            type="checkbox" 
                            name="creer_contre_proposition" 
                            value="1"
                            id="creer-contre-proposition-{{ $propositionActive->id }}"
                            class="w-5 h-5 text-blue-600 border-slate-300 rounded focus:ring-blue-500"
                        >
                        <span class="text-sm font-medium text-slate-900 dark:text-white">
                            Créer une contre-proposition après le refus
                        </span>
                    </label>
                    <p class="text-xs text-slate-600 dark:text-slate-400 mt-1 ml-7">
                        Si coché, vous pourrez proposer une nouvelle modification juste après avoir refusé cette proposition.
                    </p>
                </div>
                <div class="flex gap-3 justify-end pt-4 border-t border-slate-200 dark:border-slate-700">
                    <button type="button" onclick="document.getElementById('modal-refuser-{{ $propositionActive->id }}').classList.add('hidden')" class="px-6 py-3 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 transition font-semibold">
                        Annuler
                    </button>
                    <button type="submit" class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-xl transition shadow-lg">
                        Refuser
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

