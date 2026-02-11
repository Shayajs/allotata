@extends('brightshell.layout')

@section('title', $document->titre)

@section('actions')
<a href="{{ route('brightshell.legals') }}" class="btn btn-secondary">← Retour</a>
<a href="{{ route('brightshell.legals.pdf', $document->id) }}" class="btn btn-primary" target="_blank">
    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
    Télécharger PDF
</a>
@endsection

@section('content')
<div class="grid grid-3" style="align-items: start;">
    <!-- Aperçu du document -->
    <div class="card" style="grid-column: span 2; min-height: 800px; background: white; color: black; font-family: 'DejaVu Sans', sans-serif;">
        <!-- En-tête simplifié pour l'aperçu -->
        <div style="display: flex; justify-content: space-between; margin-bottom: 2rem; border-bottom: 1px solid #eee; padding-bottom: 1rem;">
            <div style="max-width: 50%;">
                <h2 style="font-size: 1.25rem; font-weight: bold;">BrightShell EI</h2>
                <p class="text-xs text-muted">Entrepreneur Individuel</p>
            </div>
            <div style="text-align: right;">
                <p style="font-weight: bold;">{{ $document->lieu }}, le {{ \Carbon\Carbon::parse($document->date_document)->format('d/m/Y') }}</p>
            </div>
        </div>
        
        <!-- Destinataire -->
        <div style="margin-left: 50%; margin-bottom: 4rem; background: #f9fafb; padding: 1rem; border-radius: 4px;">
            <p><strong>{{ $document->destinataire_nom }}</strong></p>
            <p style="white-space: pre-line;">{{ $document->destinataire_adresse }}</p>
        </div>
        
        <!-- Titre -->
        <h1 style="text-align: center; font-size: 1.5rem; font-weight: bold; margin-bottom: 3rem; text-decoration: underline;">{{ $document->titre }}</h1>
        
        <!-- Contenu -->
        <div class="document-content" style="line-height: 1.6; text-align: justify;">
            {!! $document->contenu !!}
        </div>
        
        <!-- Signature -->
        <div style="margin-top: 4rem; text-align: right;">
            <p>Lucas Espinar</p>
            <p class="text-muted text-xs">BrightShell EI</p>
        </div>
    </div>
    
    <!-- Infos -->
    <div class="card">
        <h3 class="card-title mb-4">Informations</h3>
        
        <div class="grid grid-2 mb-4" style="gap: 1rem;">
            <div>
                <p class="text-xs text-muted">Type</p>
                <span class="badge badge-info">{{ ucfirst($document->type) }}</span>
            </div>
            <div>
                <p class="text-xs text-muted">Date</p>
                <p class="font-bold">{{ \Carbon\Carbon::parse($document->date_document)->format('d/m/Y') }}</p>
            </div>
        </div>
        
        @if($document->client_id)
        <div class="mb-4">
            <p class="text-xs text-muted">Lié au client</p>
            <a href="{{ route('brightshell.clients.show', $document->client_id) }}">
                {{ $document->client_societe ?? $document->client_nom . ' ' . $document->client_prenom }}
            </a>
        </div>
        @endif
        
        <hr style="margin: 1rem 0; border: 0; border-top: 1px solid var(--bs-border);">
        
        <form action="{{ route('brightshell.legals.delete', $document->id) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce document ?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger w-full">Supprimer le document</button>
        </form>
    </div>
</div>
@endsection
