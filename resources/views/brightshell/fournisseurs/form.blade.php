@extends('brightshell.layout')

@section('title', 'Nouveau fournisseur')

@section('content')
<div class="card" style="max-width: 700px;">
    <form action="{{ route('brightshell.fournisseurs.store') }}" method="POST">
        @csrf
        
        <div class="form-group">
            <label class="form-label">Nom / Société *</label>
            <input type="text" name="nom" class="form-input" required>
        </div>
        
        <div class="grid grid-2">
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">Téléphone</label>
                <input type="text" name="telephone" class="form-input">
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Adresse</label>
            <input type="text" name="adresse" class="form-input">
        </div>
        
        <div class="form-group">
            <label class="form-label">SIRET</label>
            <input type="text" name="siret" class="form-input" maxlength="14">
        </div>
        
        <div class="form-group">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-textarea"></textarea>
        </div>
        
        <div class="flex gap-2" style="margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary">Ajouter</button>
            <a href="{{ route('brightshell.fournisseurs') }}" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>
@endsection
