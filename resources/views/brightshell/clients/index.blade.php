@extends('brightshell.layout')

@section('title', 'Clients')

@section('actions')
<a href="{{ route('brightshell.clients.create') }}" class="btn btn-primary">
    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
    Nouveau client
</a>
@endsection

@section('content')
@if(count($clients) > 0)
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Société</th>
                    <th>Email</th>
                    <th>Téléphone</th>
                    <th>Ville</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($clients as $client)
                <tr>
                    <td data-label="Client">
                        <div class="font-bold">{{ $client->nom }} {{ $client->prenom }}</div>
                    </td>
                    <td data-label="Société">{{ $client->societe ?? '-' }}</td>
                    <td data-label="Email">{{ $client->email ?? '-' }}</td>
                    <td data-label="Téléphone">{{ $client->telephone ?? '-' }}</td>
                    <td data-label="Ville">{{ $client->ville ?? '-' }}</td>
                    <td data-label="Actions">
                        <div class="flex gap-2" style="justify-content: flex-end;">
                            <a href="{{ route('brightshell.clients.edit', $client->id) }}" class="btn btn-secondary btn-sm">Modifier</a>
                            <form action="{{ route('brightshell.clients.delete', $client->id) }}" method="POST" onsubmit="return confirm('Supprimer ce client ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Supprimer</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="empty-state" style="padding: 2rem; border: 1px dashed var(--bs-border); border-radius: 12px; text-align: center;">
        <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-bottom: 1rem; color: var(--bs-text-muted);"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        <p style="color: var(--bs-text-muted);">Vous n'avez pas encore ajouté de clients manuellement.</p>
    </div>
@endif

@if(isset($potentiels) && count($potentiels) > 0)
    <div style="margin-top: 3rem;">
        <h3 style="font-size: 1.125rem; font-weight: 600; color: var(--bs-text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            Clients Potentiels (Allotata)
        </h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Entreprise</th>
                        <th>Propriétaire</th>
                        <th>Email</th>
                        <th>Ville</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($potentiels as $p)
                    <tr>
                        <td data-label="Entreprise">
                            <div class="font-bold">{{ $p->nom }}</div>
                            <div class="text-xs text-muted">{{ $p->siren }}</div>
                        </td>
                        <td data-label="Propriétaire">{{ $p->owner_name ?? '-' }}</td>
                        <td data-label="Email">{{ $p->owner_email ?? '-' }}</td>
                        <td data-label="Ville">{{ $p->ville ?? '-' }}</td>
                        <td data-label="Actions">
                            <form action="{{ route('brightshell.clients.store') }}" method="POST" style="display: flex; justify-content: flex-end;">
                                @csrf
                                <input type="hidden" name="nom" value="{{ $p->owner_name ?? $p->nom }}">
                                <input type="hidden" name="prenom" value="">
                                <input type="hidden" name="societe" value="{{ $p->nom }}">
                                <input type="hidden" name="email" value="{{ $p->owner_email }}">
                                <input type="hidden" name="telephone" value="{{ $p->telephone }}">
                                <input type="hidden" name="adresse" value="{{ $p->adresse_rue }}">
                                <input type="hidden" name="code_postal" value="{{ $p->code_postal }}">
                                <input type="hidden" name="ville" value="{{ $p->ville }}">
                                <button type="submit" class="btn btn-secondary btn-sm" title="Importer dans mes clients">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    Importer
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
