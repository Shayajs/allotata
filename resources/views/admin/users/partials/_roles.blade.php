<div class="max-w-2xl">
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
                        <span class="block text-xs text-red-500/70 mt-1 font-medium">⚠️ ATTENTION : Accès total et illimité à l'ensemble du panel d'administration.</span>
                    </div>
                </label>
            </div>
            
            <div class="pt-6">
                <button type="submit" class="w-full px-8 py-5 bg-gradient-to-r from-slate-900 to-slate-800 dark:from-white dark:to-slate-200 dark:text-slate-900 text-white font-bold rounded-2xl shadow-xl transition-all hover:scale-[1.02] active:scale-95 flex items-center justify-center gap-3">
                    <span>⚙️</span> Enregistrer les privilèges
                </button>
            </div>
        </form>
    </div>
</div>
