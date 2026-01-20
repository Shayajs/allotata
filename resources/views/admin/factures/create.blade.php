@extends('admin.layout')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <svg class="w-8 h-8 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Créer une facture manuelle
        </h1>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
            Générez une facture pour une entreprise ou un membre.
        </p>
    </div>

    @if($errors->any())
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
            <ul class="text-sm text-red-800 dark:text-red-400">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.factures.store') }}" method="POST" class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6">
        @csrf

        <div class="space-y-6">
            <!-- Type de facture -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Type de facture *</label>
                <select name="type_facture" id="type_facture" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    <option value="reservation">Réservation</option>
                    <option value="abonnement_manuel">Abonnement manuel</option>
                    <option value="abonnement_entreprise">Abonnement entreprise (Allotata → Entreprise)</option>
                </select>
            </div>

            <!-- Entreprise -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Entreprise</label>
                <select name="entreprise_id" id="entreprise_id" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    <option value="">Sélectionner une entreprise</option>
                    @foreach($entreprises as $entreprise)
                        <option value="{{ $entreprise->id }}">{{ $entreprise->nom }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Utilisateur -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Membre/Client</label>
                <select name="user_id" id="user_id" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    <option value="">Sélectionner un membre</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Au moins une entreprise ou un membre doit être sélectionné</p>
            </div>

            <!-- Abonnement (optionnel) -->
            <div id="subscription_field" style="display: none;">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Abonnement</label>
                <select name="entreprise_subscription_id" id="entreprise_subscription_id" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    <option value="">Aucun</option>
                    @foreach($subscriptions as $subscription)
                        <option value="{{ $subscription->id }}" data-entreprise="{{ $subscription->entreprise_id }}">
                            {{ $subscription->entreprise->nom }} - {{ $subscription->type === 'site_web' ? 'Site Web' : 'Multi-Personnes' }} ({{ $subscription->est_manuel ? 'Manuel' : 'Stripe' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Montant HT -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Montant HT (€) *</label>
                <input type="number" name="montant_ht" step="0.01" min="0.01" required value="{{ old('montant_ht') }}" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
            </div>

            <!-- Taux TVA -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Taux TVA (%)</label>
                <input type="number" name="taux_tva" step="0.01" min="0" max="100" value="{{ old('taux_tva', 0) }}" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
            </div>

            <!-- Date facture -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Date de facture *</label>
                <input type="date" name="date_facture" required value="{{ old('date_facture', now()->format('Y-m-d')) }}" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
            </div>

            <!-- Date échéance -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Date d'échéance</label>
                <input type="date" name="date_echeance" value="{{ old('date_echeance', now()->addDays(30)->format('Y-m-d')) }}" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
            </div>

            <!-- Statut -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Statut</label>
                <select name="statut" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    <option value="emise" {{ old('statut', 'emise') === 'emise' ? 'selected' : '' }}>Émise</option>
                    <option value="brouillon" {{ old('statut') === 'brouillon' ? 'selected' : '' }}>Brouillon</option>
                    <option value="payee" {{ old('statut') === 'payee' ? 'selected' : '' }}>Payée</option>
                    <option value="annulee" {{ old('statut') === 'annulee' ? 'selected' : '' }}>Annulée</option>
                </select>
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Notes</label>
                <textarea name="notes" rows="4" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">{{ old('notes') }}</textarea>
            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <button type="submit" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
                Créer la facture
            </button>
            <a href="{{ route('admin.factures.index') }}" class="px-6 py-3 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 font-semibold rounded-lg transition">
                Annuler
            </a>
        </div>
    </form>
</div>

<script>
document.getElementById('type_facture').addEventListener('change', function() {
    const subscriptionField = document.getElementById('subscription_field');
    if (this.value === 'abonnement_entreprise' || this.value === 'abonnement_manuel') {
        subscriptionField.style.display = 'block';
    } else {
        subscriptionField.style.display = 'none';
    }
});

// Filtrer les abonnements selon l'entreprise sélectionnée
document.getElementById('entreprise_id').addEventListener('change', function() {
    const entrepriseId = this.value;
    const subscriptionSelect = document.getElementById('entreprise_subscription_id');
    const options = subscriptionSelect.querySelectorAll('option');
    
    options.forEach(option => {
        if (option.value === '') {
            option.style.display = 'block';
            return;
        }
        const optionEntreprise = option.getAttribute('data-entreprise');
        if (entrepriseId && optionEntreprise !== entrepriseId) {
            option.style.display = 'none';
        } else {
            option.style.display = 'block';
        }
    });
});
</script>
@endsection
