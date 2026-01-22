@extends('brightshell.layout')

@section('title', 'Documents (Fichiers)')

@section('content')
<div class="grid grid-3" style="align-items: start;">
    <!-- Liste des documents -->
    <div class="card" style="grid-column: span 2;">
        <h3 class="card-title mb-4">Fichiers enregistrés</h3>
        @if(count($documents) > 0)
        <table>
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Catégorie</th>
                    <th>Client</th>
                    <th>Taille</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($documents as $doc)
                <tr>
                    <td>
                        <div class="flex items-center gap-2">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            <a href="{{ asset('media/brightshell/docs/' . $doc->fichier) }}" target="_blank" style="color: inherit; font-weight: 500;">
                                {{ $doc->nom }}
                            </a>
                        </div>
                    </td>
                    <td><span class="badge badge-secondary">{{ $doc->categorie }}</span></td>
                    <td>{{ $doc->client_societe ?? $doc->client_nom ?? '-' }}</td>
                    <td class="text-muted text-xs">{{ number_format($doc->taille / 1024, 1) }} KB</td>
                    <td>
                        <form action="{{ route('brightshell.documents.destroy', $doc->id) }}" method="POST" onsubmit="return confirm('Supprimer ce fichier ?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">×</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="empty-state">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <p>Aucun fichier uploadé.</p>
        </div>
        @endif
    </div>

    <!-- Formulaire d'upload -->
    <div class="card">
        <h3 class="card-title mb-4">Uploader un fichier</h3>
        <form action="{{ route('brightshell.documents.upload') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label class="form-label">Fichier (Max 10MB)</label>
                <input type="file" name="fichier" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Nom d'affichage (optionnel)</label>
                <input type="text" name="nom" class="form-input" placeholder="Ex: Contrat de prestation">
            </div>
            <div class="form-group">
                <label class="form-label">Catégorie</label>
                <select name="categorie" class="form-input" required>
                    <option value="contrat">Contrat</option>
                    <option value="identite">Pièce d'identité</option>
                    <option value="rib">RIB</option>
                    <option value="autre" selected>Autre</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Lier à un client (optionnel)</label>
                <select name="client_id" class="form-input">
                    <option value="">Aucun</option>
                    @foreach(DB::table('brightshell_clients')->orderBy('nom')->get() as $client)
                    <option value="{{ $client->id }}">{{ $client->nom }} {{ $client->prenom }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-full mt-4">Uploader</button>
        </form>
    </div>
</div>
@endsection
