@extends('brightshell.layout')

@section('title', 'Fournisseurs')

@section('actions')
<a href="{{ route('brightshell.fournisseurs.create') }}" class="btn btn-primary">
    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
    Nouveau fournisseur
</a>
@endsection

@section('content')
@if(count($fournisseurs) > 0)
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Nom</th>
                <th>Email</th>
                <th>Téléphone</th>
                <th>SIRET</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($fournisseurs as $f)
            <tr>
                <td class="font-bold">{{ $f->nom }}</td>
                <td>{{ $f->email ?? '-' }}</td>
                <td>{{ $f->telephone ?? '-' }}</td>
                <td class="text-muted">{{ $f->siret ?? '-' }}</td>
                <td>
                    <form action="{{ route('brightshell.fournisseurs.delete', $f->id) }}" method="POST" onsubmit="return confirm('Supprimer ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">Supprimer</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@else
<div class="empty-state">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
    <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">Aucun fournisseur</h3>
    <p style="margin-bottom: 1.5rem;">Ajoutez vos fournisseurs et contacts professionnels.</p>
    <a href="{{ route('brightshell.fournisseurs.create') }}" class="btn btn-primary">Ajouter un fournisseur</a>
</div>
@endif
@endsection
