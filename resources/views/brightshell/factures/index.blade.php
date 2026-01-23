@extends('brightshell.layout')

@section('title', 'Factures')

@section('actions')
<a href="{{ route('brightshell.factures.create') }}" class="btn btn-primary">
    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
    Nouvelle facture
</a>
@endsection

@section('content')
@if(count($factures) > 0)
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Numéro</th>
                    <th>Client</th>
                    <th>Objet</th>
                    <th>Montant</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($factures as $facture)
                <tr>
                    <td data-label="Numéro" class="font-bold">{{ $facture->numero }}</td>
                    <td data-label="Client">{{ $facture->client_societe ?? $facture->client_nom }}</td>
                    <td data-label="Objet">{{ Str::limit($facture->objet, 40) }}</td>
                    <td data-label="Montant" class="font-bold">{{ number_format($facture->montant_total, 2, ',', ' ') }} €</td>
                    <td data-label="Statut">
                        @if(str_starts_with($facture->numero, 'AVO'))
                            <span class="badge" style="background: #8b5cf6; color: white;">Avoir</span>
                        @endif
                        @switch($facture->statut)
                            @case('brouillon')
                                <span class="badge badge-info">Brouillon</span>
                                @break
                            @case('envoyee')
                                <span class="badge badge-warning">En attente</span>
                                @break
                            @case('payee')
                                <span class="badge badge-success">Payée</span>
                                @break
                            @case('annulee')
                                <span class="badge badge-danger">Annulée</span>
                                @break
                        @endswitch
                    </td>
                    <td data-label="Date" class="text-muted">{{ \Carbon\Carbon::parse($facture->created_at)->format('d/m/Y') }}</td>
                    <td data-label="Actions">
                        <div class="flex gap-2" style="justify-content: flex-end;">
                            <a href="{{ route('brightshell.factures.show', $facture->id) }}" class="btn btn-secondary btn-sm">Voir</a>
                            @if($facture->statut !== 'payee')
                            <form action="{{ route('brightshell.factures.paid', $facture->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm">Payée</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="empty-state">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">Aucune facture</h3>
        <p style="margin-bottom: 1.5rem;">Créez votre première facture.</p>
        <a href="{{ route('brightshell.factures.create') }}" class="btn btn-primary">Créer une facture</a>
    </div>
@endif
@endsection
