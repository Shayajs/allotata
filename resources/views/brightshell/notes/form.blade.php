@extends('brightshell.layout')

@section('title', $note ? 'Modifier la note' : 'Nouvelle note')

@section('content')
<div class="card" style="max-width: 800px;">
    <form action="{{ $note ? route('brightshell.notes.update', $note->id) : route('brightshell.notes.store') }}" method="POST">
        @csrf
        @if($note) @method('PUT') @endif
        
        <div class="form-group">
            <label class="form-label">Titre</label>
            <input type="text" name="titre" class="form-input" value="{{ old('titre', $note->titre ?? '') }}" required>
        </div>
        
        <div class="form-group">
            <label class="form-label">Contenu</label>
            <textarea name="contenu" class="form-textarea" style="min-height: 300px;">{{ old('contenu', $note->contenu ?? '') }}</textarea>
        </div>
        
        <div class="form-group">
            <label class="form-label">Couleur</label>
            <div style="display: flex; gap: 0.5rem;">
                @foreach(['default' => '#1a1a2e', 'jaune' => '#fbbf24', 'vert' => '#22c55e', 'bleu' => '#3b82f6', 'rose' => '#ec4899', 'violet' => '#8b5cf6'] as $key => $hex)
                <label style="cursor: pointer;">
                    <input type="radio" name="couleur" value="{{ $key }}" {{ old('couleur', $note->couleur ?? 'default') === $key ? 'checked' : '' }} style="display: none;">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: {{ $hex }}; border: 3px solid {{ old('couleur', $note->couleur ?? 'default') === $key ? '#5bbce4' : 'transparent' }};"></div>
                </label>
                @endforeach
            </div>
        </div>
        
        <div class="flex gap-2" style="margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary">{{ $note ? 'Mettre à jour' : 'Créer la note' }}</button>
            <a href="{{ route('brightshell.notes') }}" class="btn btn-secondary">Annuler</a>
            @if($note)
            <form action="{{ route('brightshell.notes.delete', $note->id) }}" method="POST" style="margin-left: auto;" onsubmit="return confirm('Supprimer cette note ?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Supprimer</button>
            </form>
            @endif
        </div>
    </form>
</div>
@endsection
