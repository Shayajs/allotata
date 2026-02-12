@extends('brightshell.layout')

@section('title', 'Nouvel achat')

@section('content')
<div class="card" style="max-width: 700px;">
    <form action="{{ route('brightshell.achats.store') }}" method="POST">
        @csrf
        
        <div class="grid grid-2">
            <div class="form-group">
                <label class="form-label">Date *</label>
                <input type="date" name="date" class="form-input" value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Montant (€) *</label>
                <input type="number" name="montant" class="form-input" step="0.01" min="0" required>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Description *</label>
            <input type="text" name="description" class="form-input" placeholder="Ex: Abonnement Figma, Achat matériel..." required>
        </div>
        
        <div class="grid grid-2">
            <div class="form-group">
                <label class="form-label">Fournisseur</label>
                @if(count($fournisseurs) > 0)
                <select name="fournisseur_id" class="form-input">
                    <option value="">Sélectionner...</option>
                    @foreach($fournisseurs as $f)
                    <option value="{{ $f->id }}">{{ $f->nom }}</option>
                    @endforeach
                </select>
                @else
                <input type="text" name="fournisseur_nom" class="form-input" placeholder="Nom du fournisseur">
                @endif
            </div>
            <div class="form-group">
                <label class="form-label">Mode de paiement</label>
                <select name="mode_paiement" class="form-input">
                    <option value="cb">Carte bancaire</option>
                    <option value="virement">Virement</option>
                    <option value="especes">Espèces</option>
                    <option value="prelevement">Prélèvement</option>
                    <option value="paypal">PayPal</option>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Référence / N° facture</label>
            <input type="text" name="reference" class="form-input" placeholder="Optionnel">
        </div>
        
        <div style="background: var(--bs-bg-dark); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
            <p class="text-muted text-sm">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: inline; vertical-align: middle; margin-right: 0.5rem;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Cet achat sera enregistré dans le <strong>registre des achats</strong> pour votre comptabilité micro-entreprise.
            </p>
        </div>
        
        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary">Enregistrer l'achat</button>
            <a href="{{ route('brightshell.comptabilite') }}" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>
@endsection
