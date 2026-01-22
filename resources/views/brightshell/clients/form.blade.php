@extends('brightshell.layout')

@section('title', $client ? 'Modifier le client' : 'Nouveau client')

@section('content')
<div class="card" style="max-width: 800px;">
    <form action="{{ $client ? route('brightshell.clients.update', $client->id) : route('brightshell.clients.store') }}" method="POST">
        @csrf
        @if($client) @method('PUT') @endif
        
        <div class="grid grid-2">
            <div class="form-group">
                <label class="form-label">Nom *</label>
                <input type="text" name="nom" class="form-input" value="{{ old('nom', $client->nom ?? '') }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Prénom</label>
                <input type="text" name="prenom" class="form-input" value="{{ old('prenom', $client->prenom ?? '') }}">
            </div>
        </div>
        
        <div class="grid grid-2">
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-input" value="{{ old('email', $client->email ?? '') }}">
            </div>
            <div class="form-group">
                <label class="form-label">Téléphone</label>
                <input type="text" name="telephone" class="form-input" value="{{ old('telephone', $client->telephone ?? '') }}">
            </div>
        </div>
        
        <div class="grid grid-2">
            <div class="form-group">
                <label class="form-label">Société</label>
                <input type="text" name="societe" class="form-input" value="{{ old('societe', $client->societe ?? '') }}">
            </div>
            <div class="form-group">
                <label class="form-label">SIRET</label>
                <input type="text" name="siret" class="form-input" value="{{ old('siret', $client->siret ?? '') }}" maxlength="14">
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Adresse</label>
            <input type="text" name="adresse" class="form-input" value="{{ old('adresse', $client->adresse ?? '') }}">
        </div>
        
        <div class="grid grid-2">
            <div class="form-group">
                <label class="form-label">Code postal</label>
                <input type="text" name="code_postal" class="form-input" value="{{ old('code_postal', $client->code_postal ?? '') }}" maxlength="10">
            </div>
            <div class="form-group">
                <label class="form-label">Ville</label>
                <input type="text" name="ville" class="form-input" value="{{ old('ville', $client->ville ?? '') }}">
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-textarea">{{ old('notes', $client->notes ?? '') }}</textarea>
        </div>
        
        <div class="flex gap-2" style="margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary">{{ $client ? 'Mettre à jour' : 'Créer le client' }}</button>
            <a href="{{ route('brightshell.clients') }}" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>
@endsection
