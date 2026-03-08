<div class="mb-6 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-4">
    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Ajouter une dette manuelle</h2>
    <form action="{{ route('admin.echeances.manual.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-8 gap-3 items-end">
        @csrf
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Membre</label>
            <select name="user_id" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                <option value="">Sélectionner...</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Type</label>
            <select name="subscription_type" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                <option value="default">Premium</option>
                <option value="site_web">Site Web</option>
                <option value="multi_personnes">Multi-Personnes</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Période début</label>
            <input type="date" name="periode_debut" required value="{{ now()->startOfMonth()->format('Y-m-d') }}" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Période fin</label>
            <input type="date" name="periode_fin" required value="{{ now()->endOfMonth()->format('Y-m-d') }}" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Jour facturation</label>
            <input type="number" min="1" max="31" name="jour_facturation" value="{{ now()->day }}" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Montant (€)</label>
            <input type="number" step="0.01" min="0.01" required name="montant_du" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Note interne</label>
            <input type="text" name="note" maxlength="1000" placeholder="Paiement direct / accord commercial..." class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
        </div>
        <div class="md:col-span-8">
            <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">Créer la dette</button>
        </div>
    </form>
</div>
