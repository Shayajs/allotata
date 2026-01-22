@extends('brightshell.layout')

@section('title', 'Projets')

@section('actions')
<a href="{{ route('brightshell.projets.create') }}" class="btn btn-primary">
    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
    Nouveau projet
</a>
@endsection

@section('content')
@if(count($projets) > 0)
    <div class="grid grid-3">
        @foreach($projets as $projet)
        <div class="card">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold">{{ $projet->nom }}</h3>
                @switch($projet->statut)
                    @case('en_attente')
                        <span class="badge badge-info">En attente</span>
                        @break
                    @case('en_cours')
                        <span class="badge badge-warning">En cours</span>
                        @break
                    @case('termine')
                        <span class="badge badge-success">Terminé</span>
                        @break
                    @case('annule')
                        <span class="badge badge-danger">Annulé</span>
                        @break
                @endswitch
            </div>
            @if($projet->client_nom)
            <p class="text-muted text-sm mb-2">Client: {{ $projet->client_societe ?? $projet->client_nom }}</p>
            @endif
            @if($projet->description)
            <p class="text-sm" style="margin-bottom: 1rem;">{{ Str::limit($projet->description, 100) }}</p>
            @endif
            @if($projet->budget)
            <p class="text-accent font-bold">Budget: {{ number_format($projet->budget, 2, ',', ' ') }} €</p>
            @endif
            <p class="text-muted text-xs mt-2">Créé le {{ \Carbon\Carbon::parse($projet->created_at)->format('d/m/Y') }}</p>
        </div>
        @endforeach
    </div>
@else
    <div class="empty-state">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">Aucun projet</h3>
        <p style="margin-bottom: 1.5rem;">Créez votre premier projet.</p>
        <a href="{{ route('brightshell.projets.create') }}" class="btn btn-primary">Créer un projet</a>
    </div>
@endif
@endsection
