@extends('brightshell.layout')

@section('title', 'Documents Administratifs')

@section('actions')
<a href="{{ route('brightshell.legals.create') }}" class="btn btn-primary">
    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
    Nouveau document
</a>
@endsection

@section('content')
<div class="card">
    @if(count($legals) > 0)
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Titre</th>
                <th>Destinataire</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($legals as $doc)
            <tr>
                <td style="color: var(--bs-text-muted);">{{ \Carbon\Carbon::parse($doc->date_document)->format('d/m/Y') }}</td>
                <td>
                    <span class="badge badge-info">{{ ucfirst($doc->type) }}</span>
                </td>
                <td class="font-bold">
                    <a href="{{ route('brightshell.legals.show', $doc->id) }}" style="color: inherit; text-decoration: none;">
                        {{ $doc->titre }}
                    </a>
                </td>
                <td>
                    @if($doc->client_nom)
                    <a href="{{ route('brightshell.clients.show', $doc->client_id) }}" style="color: var(--bs-accent);">
                        {{ $doc->client_societe ?? $doc->client_nom . ' ' . $doc->client_prenom }}
                    </a>
                    @else
                    {{ $doc->destinataire_nom ?? '-' }}
                    @endif
                </td>
                <td>
                    <div class="flex gap-2">
                        <a href="{{ route('brightshell.legals.pdf', $doc->id) }}" class="btn btn-secondary btn-sm" target="_blank" title="PDF">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        </a>
                        <a href="{{ route('brightshell.legals.edit', $doc->id) }}" class="btn btn-secondary btn-sm" title="Modifier">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        </a>
                        <form action="{{ route('brightshell.legals.delete', $doc->id) }}" method="POST" onsubmit="return confirm('Supprimer ce document ?');" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" title="Supprimer">×</button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="empty-state">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <p>Aucun document créé.</p>
        <a href="{{ route('brightshell.legals.create') }}" class="btn btn-primary mt-4">Créer un document</a>
    </div>
    @endif
</div>
@endsection
