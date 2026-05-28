<form action="{{ route('admin.echeances.manual.pay', $echeance) }}" method="POST" class="flex flex-wrap gap-3 items-end">
    @csrf
    <div>
        <label class="block text-xs font-medium text-green-700 dark:text-green-300 mb-1">Montant payé (€)</label>
        <input type="number" name="paid_amount" step="0.01" min="0.01" required value="{{ number_format((float)($echeance->montant_final ?? $echeance->montant_du), 2, '.', '') }}" class="w-32 px-3 py-2 border border-green-300 dark:border-green-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
    </div>
    <div>
        <label class="block text-xs font-medium text-green-700 dark:text-green-300 mb-1">Date de paiement</label>
        <input type="datetime-local" name="paid_at" required value="{{ now()->format('Y-m-d\\TH:i') }}" class="px-3 py-2 border border-green-300 dark:border-green-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
    </div>
    <div>
        <label class="block text-xs font-medium text-green-700 dark:text-green-300 mb-1">Mode</label>
        <select name="payment_mode" class="px-3 py-2 border border-green-300 dark:border-green-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
            <option value="cash">Espèces</option>
            <option value="bank_transfer">Virement</option>
            <option value="card_terminal">TPE</option>
            <option value="other">Autre</option>
        </select>
    </div>
    <div class="flex-1 min-w-[220px]">
        <label class="block text-xs font-medium text-green-700 dark:text-green-300 mb-1">Note</label>
        <input type="text" name="note" maxlength="1000" placeholder="Paiement validé manuellement..." class="w-full px-3 py-2 border border-green-300 dark:border-green-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
    </div>
    <button type="submit" class="ui-btn-simple px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition text-sm">
        Valider le paiement
    </button>
</form>
