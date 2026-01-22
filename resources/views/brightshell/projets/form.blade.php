@extends('brightshell.layout')

@section('title', $projet ? 'Modifier le projet' : 'Nouveau projet')

@section('content')
<div class="card" style="max-width: 800px;">
    <form action="{{ $projet ? route('brightshell.projets.update', $projet->id) : route('brightshell.projets.store') }}" method="POST">
        @csrf
        @if($projet) @method('PUT') @endif
        
        <div class="form-group">
            <label class="form-label">Nom du projet *</label>
            <input type="text" name="nom" class="form-input" value="{{ old('nom', $projet->nom ?? '') }}" required>
        </div>
        
        <div class="grid grid-2">
            <div class="form-group">
                <label class="form-label">Client</label>
                <select name="client_id" class="form-input">
                    <option value="">Aucun client</option>
                    @foreach($clients as $client)
                    <option value="{{ $client->id }}" {{ old('client_id', $projet->client_id ?? '') == $client->id ? 'selected' : '' }}>
                        {{ $client->nom }} {{ $client->societe ? "({$client->societe})" : '' }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Statut *</label>
                <select name="statut" class="form-input" required>
                    <option value="en_attente" {{ old('statut', $projet->statut ?? '') === 'en_attente' ? 'selected' : '' }}>En attente</option>
                    <option value="en_cours" {{ old('statut', $projet->statut ?? '') === 'en_cours' ? 'selected' : '' }}>En cours</option>
                    <option value="termine" {{ old('statut', $projet->statut ?? '') === 'termine' ? 'selected' : '' }}>Terminé</option>
                    <option value="annule" {{ old('statut', $projet->statut ?? '') === 'annule' ? 'selected' : '' }}>Annulé</option>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-textarea">{{ old('description', $projet->description ?? '') }}</textarea>
        </div>
        
        <div class="grid grid-3">
            <div class="form-group">
                <label class="form-label">Date de début</label>
                <input type="date" name="date_debut" class="form-input" value="{{ old('date_debut', $projet->date_debut ?? '') }}">
            </div>
            <div class="form-group">
                <label class="form-label">Date de fin prévue</label>
                <input type="date" name="date_fin_prevue" class="form-input" value="{{ old('date_fin_prevue', $projet->date_fin_prevue ?? '') }}">
            </div>
            <div class="form-group">
                <label class="form-label">Budget (€)</label>
                <input type="number" name="budget" class="form-input" value="{{ old('budget', $projet->budget ?? '') }}" step="0.01" min="0">
            </div>
        </div>
        
        <div class="flex gap-2" style="margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary">{{ $projet ? 'Mettre à jour' : 'Créer le projet' }}</button>
            <a href="{{ route('brightshell.projets') }}" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>
@endsection
