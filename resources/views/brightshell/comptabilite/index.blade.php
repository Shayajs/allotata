@extends('brightshell.layout')

@section('title', 'Comptabilité')

@section('content')
<!-- Résumé -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Recettes {{ date('Y') }}</div>
        <div class="stat-value text-success">{{ number_format($stats['total_recettes'], 2, ',', ' ') }} €</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Achats {{ date('Y') }}</div>
        <div class="stat-value text-danger">{{ number_format($stats['total_achats'], 2, ',', ' ') }} €</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Bénéfice</div>
        <div class="stat-value {{ $stats['benefice'] >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($stats['benefice'], 2, ',', ' ') }} €</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Cotisations URSSAF estimées</div>
        <div class="stat-value text-warning">{{ number_format($stats['cotisations_estimees'], 2, ',', ' ') }} €</div>
    </div>
</div>

<!-- Seuils -->
<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Seuils Micro-Entreprise {{ date('Y') }}</h3>
    </div>
    <div class="grid grid-2">
        <div>
            <p class="text-muted text-sm mb-2">Franchise de TVA (36 800 €)</p>
            @php $pctTVA = $stats['seuil_tva'] > 0 ? min(100, ($stats['total_recettes'] / $stats['seuil_tva']) * 100) : 0; @endphp
            <div class="progress" style="margin-bottom: 0.5rem;">
                <div class="progress-bar {{ $pctTVA > 80 ? ($pctTVA > 95 ? 'danger' : 'warning') : '' }}" style="width: {{ $pctTVA }}%"></div>
            </div>
            <p class="text-xs text-muted">{{ number_format($stats['total_recettes'], 0, ',', ' ') }} € / {{ number_format($stats['seuil_tva'], 0, ',', ' ') }} € ({{ number_format($pctTVA, 1) }}%)</p>
        </div>
        <div>
            <p class="text-muted text-sm mb-2">Plafond Micro-Entreprise (77 700 €)</p>
            @php $pctMicro = $stats['seuil_micro'] > 0 ? min(100, ($stats['total_recettes'] / $stats['seuil_micro']) * 100) : 0; @endphp
            <div class="progress" style="margin-bottom: 0.5rem;">
                <div class="progress-bar {{ $pctMicro > 80 ? 'warning' : '' }}" style="width: {{ $pctMicro }}%"></div>
            </div>
            <p class="text-xs text-muted">{{ number_format($stats['total_recettes'], 0, ',', ' ') }} € / {{ number_format($stats['seuil_micro'], 0, ',', ' ') }} € ({{ number_format($pctMicro, 1) }}%)</p>
        </div>
    </div>
</div>

<!-- Livre des recettes -->
<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Livre des Recettes</h3>
    </div>
    @if(count($recettes) > 0)
    <div class="table-container" style="border: none; border-radius: 0;">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Mode</th>
                    <th>Montant</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recettes as $recette)
                <tr>
                    <td data-label="Date">{{ \Carbon\Carbon::parse($recette->date)->format('d/m/Y') }}</td>
                    <td data-label="Description">
                        <div class="font-bold">{{ $recette->nature }}</div>
                        <div class="text-xs text-muted">{{ $recette->reference }} - {{ $recette->client_nom }}</div>
                    </td>
                    <td data-label="Mode">{{ ucfirst($recette->mode_reglement ?? 'N/A') }}</td>
                    <td data-label="Montant" class="font-bold text-success">+{{ number_format($recette->montant, 2, ',', ' ') }} €</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <p class="text-muted text-center" style="padding: 2rem;">Aucune recette enregistrée.</p>
    @endif
</div>

<!-- Registre des achats -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Registre des Achats</h3>
    </div>
    @if(count($achats) > 0)
    <div class="table-container" style="border: none; border-radius: 0;">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Fournisseur</th>
                    <th>Montant</th>
                </tr>
            </thead>
            <tbody>
                @foreach($achats as $achat)
                <tr>
                    <td data-label="Date">{{ \Carbon\Carbon::parse($achat->date)->format('d/m/Y') }}</td>
                    <td data-label="Description">{{ $achat->description }}</td>
                    <td data-label="Fournisseur">{{ $achat->fournisseur ?? 'N/A' }}</td>
                    <td data-label="Montant" class="font-bold text-danger">-{{ number_format($achat->montant, 2, ',', ' ') }} €</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <p class="text-muted text-center" style="padding: 2rem;">Aucun achat enregistré.</p>
    @endif
</div>
@endsection
