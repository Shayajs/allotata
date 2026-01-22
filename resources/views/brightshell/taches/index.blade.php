@extends('brightshell.layout')

@section('title', 'Tâches')

@section('content')
<div class="grid grid-2">
    <!-- Formulaire ajout -->
    <div class="card">
        <h3 class="card-title mb-4">Nouvelle tâche</h3>
        <form action="{{ route('brightshell.taches.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <input type="text" name="titre" class="form-input" placeholder="Titre de la tâche" required>
            </div>
            <div class="form-group">
                <textarea name="description" class="form-textarea" rows="2" placeholder="Description (optionnel)"></textarea>
            </div>
            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label">Priorité</label>
                    <select name="priorite" class="form-input">
                        <option value="basse">Basse</option>
                        <option value="normale" selected>Normale</option>
                        <option value="haute">Haute</option>
                        <option value="urgente">Urgente</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Échéance</label>
                    <input type="date" name="echeance" class="form-input">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Ajouter</button>
        </form>
    </div>
    
    <!-- Stats -->
    <div class="card">
        <h3 class="card-title mb-4">Résumé</h3>
        @php
            $total = count($taches);
            $completed = collect($taches)->where('completed', true)->count();
            $pending = $total - $completed;
            $urgent = collect($taches)->where('priorite', 'urgente')->where('completed', false)->count();
        @endphp
        <div class="grid grid-2" style="gap: 1rem;">
            <div style="background: var(--bs-bg-dark); padding: 1rem; border-radius: 8px; text-align: center;">
                <p class="text-muted text-xs" style="text-transform: uppercase;">En cours</p>
                <p class="text-accent" style="font-size: 2rem; font-weight: 700;">{{ $pending }}</p>
            </div>
            <div style="background: var(--bs-bg-dark); padding: 1rem; border-radius: 8px; text-align: center;">
                <p class="text-muted text-xs" style="text-transform: uppercase;">Terminées</p>
                <p class="text-success" style="font-size: 2rem; font-weight: 700;">{{ $completed }}</p>
            </div>
        </div>
        @if($urgent > 0)
        <div style="background: rgba(239, 68, 68, 0.15); padding: 1rem; border-radius: 8px; margin-top: 1rem; text-align: center;">
            <p class="text-danger font-bold">{{ $urgent }} tâche(s) urgente(s)</p>
        </div>
        @endif
    </div>
</div>

<!-- Liste des tâches -->
<div class="card mt-4">
    <h3 class="card-title mb-4">Liste des tâches</h3>
    @if(count($taches) > 0)
    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
        @foreach($taches as $tache)
        <div class="tache-item" style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: var(--bs-bg-dark); border-radius: 8px; flex-wrap: wrap; {{ $tache->completed ? 'opacity: 0.6;' : '' }}">
            <form action="{{ route('brightshell.taches.toggle', $tache->id) }}" method="POST">
                @csrf
                @method('PUT')
                <button type="submit" style="width: 24px; height: 24px; border-radius: 50%; border: 2px solid {{ $tache->completed ? 'var(--bs-success)' : 'var(--bs-border)' }}; background: {{ $tache->completed ? 'var(--bs-success)' : 'transparent' }}; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                    @if($tache->completed)
                    <svg width="14" height="14" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    @endif
                </button>
            </form>
            <div style="flex: 1; min-width: 200px;">
                <p style="font-weight: 600; margin: 0; {{ $tache->completed ? 'text-decoration: line-through;' : '' }}">{{ $tache->titre }}</p>
                @if($tache->description)
                <p class="text-muted text-sm" style="margin: 0.25rem 0 0;">{{ Str::limit($tache->description, 80) }}</p>
                @endif
            </div>
            <div class="flex items-center gap-2" style="margin-left: auto;">
                @if($tache->echeance)
                <span class="text-muted text-sm">{{ \Carbon\Carbon::parse($tache->echeance)->format('d/m') }}</span>
                @endif
                <span class="badge {{ $tache->priorite === 'urgente' ? 'badge-danger' : ($tache->priorite === 'haute' ? 'badge-warning' : ($tache->priorite === 'basse' ? 'badge-info' : 'badge-success')) }}">
                    {{ ucfirst($tache->priorite ?? 'normale') }}
                </span>
                <form action="{{ route('brightshell.taches.delete', $tache->id) }}" method="POST" onsubmit="return confirm('Supprimer ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">×</button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <p class="text-muted text-center">Aucune tâche. Ajoutez-en une !</p>
    @endif
</div>
@endsection
