@extends('brightshell.layout')

@section('title', 'Devis ' . $devis->numero)

@section('actions')
<a href="{{ route('brightshell.devis') }}" class="btn btn-secondary">← Retour</a>
<a href="{{ route('brightshell.devis.pdf', $devis->id) }}" class="btn btn-primary" target="_blank">
    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
    Télécharger PDF
</a>
@if($devis->statut !== 'accepte')
<form action="{{ route('brightshell.devis.convert', $devis->id) }}" method="POST" style="display: inline;">
    @csrf
    <button type="submit" class="btn btn-success">Convertir en facture</button>
</form>
@endif
@endsection

@section('content')
<div class="card" style="max-width: 900px; background: white; color: #1a1a1a;" id="devis-content">
    <!-- En-tête -->
    <div style="display: flex; justify-content: space-between; margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 2px solid #e5e7eb;">
        <div>
            @php
                $logoPath = public_path('media/brightshell/logo.png');
                $hasLogo = file_exists($logoPath);
            @endphp
            @if($hasLogo)
                <img src="{{ asset('media/brightshell/logo.png') }}" alt="Logo" style="height: 50px; margin-bottom: 0.5rem;">
            @endif
            <h2 style="font-size: 1.5rem; font-weight: 700; color: #0a0e1a; margin-bottom: 0.5rem;">{{ $entreprise['nom'] }}</h2>
            <p style="color: #6b7280; font-size: 0.875rem;">{{ $entreprise['forme_juridique'] }}</p>
            <p style="color: #6b7280; font-size: 0.875rem;">SIRET: {{ $entreprise['siret'] }}</p>
            <p style="color: #6b7280; font-size: 0.875rem;">{{ $entreprise['email'] }}</p>
            <p style="color: #6b7280; font-size: 0.875rem;">{{ $entreprise['telephone'] }}</p>
        </div>
        <div style="text-align: right;">
            <h1 style="font-size: 2rem; font-weight: 700; color: #5bbce4;">DEVIS</h1>
            <p style="font-size: 1.25rem; font-weight: 600; color: #0a0e1a;">{{ $devis->numero }}</p>
            <p style="color: #6b7280; margin-top: 1rem;">Date: {{ \Carbon\Carbon::parse($devis->created_at)->format('d/m/Y') }}</p>
            <p style="color: #6b7280;">Validité: {{ $devis->validite_jours }} jours</p>
            <p style="margin-top: 0.5rem;">
                @php
                    $statutColors = [
                        'brouillon' => '#6b7280',
                        'envoye' => '#f59e0b',
                        'accepte' => '#10b981',
                        'refuse' => '#ef4444',
                        'expire' => '#6b7280',
                    ];
                    $statutLabels = [
                        'brouillon' => 'Brouillon',
                        'envoye' => 'Envoyé',
                        'accepte' => 'Accepté',
                        'refuse' => 'Refusé',
                        'expire' => 'Expiré',
                    ];
                @endphp
                <span style="display: inline-block; padding: 0.25rem 0.75rem; background: {{ $statutColors[$devis->statut] ?? '#6b7280' }}20; color: {{ $statutColors[$devis->statut] ?? '#6b7280' }}; border-radius: 9999px; font-size: 0.875rem; font-weight: 500;">
                    {{ $statutLabels[$devis->statut] ?? $devis->statut }}
                </span>
            </p>
        </div>
    </div>
    
    <!-- Client -->
    <div style="background: #f9fafb; padding: 1rem; border-radius: 8px; margin-bottom: 2rem;">
        <p style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; margin-bottom: 0.5rem;">Client</p>
        <p style="font-weight: 600; color: #0a0e1a;">{{ $devis->client_societe ?? $devis->client_nom . ' ' . $devis->client_prenom }}</p>
        @if($devis->client_adresse)
        <p style="color: #6b7280; font-size: 0.875rem;">{{ $devis->client_adresse }}</p>
        <p style="color: #6b7280; font-size: 0.875rem;">{{ $devis->client_cp }} {{ $devis->client_ville }}</p>
        @endif
        @if($devis->client_siret)
        <p style="color: #6b7280; font-size: 0.875rem;">SIRET: {{ $devis->client_siret }}</p>
        @endif
    </div>
    
    <!-- Objet -->
    <div style="margin-bottom: 2rem;">
        <p style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; margin-bottom: 0.5rem;">Objet</p>
        <p style="font-weight: 600; color: #0a0e1a;">{{ $devis->objet }}</p>
    </div>
    
    <!-- Lignes -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem;">
        <thead>
            <tr style="background: #0a0e1a; color: white;">
                <th style="padding: 0.75rem; text-align: left; font-size: 0.75rem; text-transform: uppercase;">Description</th>
                <th style="padding: 0.75rem; text-align: right; font-size: 0.75rem; text-transform: uppercase;">Qté</th>
                <th style="padding: 0.75rem; text-align: right; font-size: 0.75rem; text-transform: uppercase;">Prix unit.</th>
                <th style="padding: 0.75rem; text-align: right; font-size: 0.75rem; text-transform: uppercase;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($devis->lignes as $ligne)
            <tr style="border-bottom: 1px solid #e5e7eb;">
                <td style="padding: 0.75rem; color: #0a0e1a;">{{ $ligne['description'] }}</td>
                <td style="padding: 0.75rem; text-align: right; color: #6b7280;">{{ $ligne['quantite'] }}</td>
                <td style="padding: 0.75rem; text-align: right; color: #6b7280;">{{ number_format($ligne['prix_unitaire'], 2, ',', ' ') }} €</td>
                <td style="padding: 0.75rem; text-align: right; font-weight: 600; color: #0a0e1a;">{{ number_format($ligne['quantite'] * $ligne['prix_unitaire'], 2, ',', ' ') }} €</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            @php
                $modeTva = $devis->mode_tva ?? 'non_assujetti';
                $tauxTva = $devis->taux_tva ?? 20;
                $montantHt = $devis->montant_ht ?? 0;
                $montantTva = $devis->montant_tva ?? 0;
                $montantTotal = $devis->montant_total ?? $montantHt;
            @endphp
            
            @if($modeTva === 'non_assujetti')
                <tr>
                    <td colspan="3" style="padding: 0.75rem; text-align: right; font-weight: 600; color: #0a0e1a;">Total HT</td>
                    <td style="padding: 0.75rem; text-align: right; font-size: 1.25rem; font-weight: 700; color: #5bbce4;">{{ number_format($montantHt, 2, ',', ' ') }} €</td>
                </tr>
                <tr>
                    <td colspan="4" style="padding: 0.5rem; text-align: right; color: #6b7280; font-size: 0.875rem; font-style: italic;">
                        TVA non applicable, art. 293 B du CGI
                    </td>
                </tr>
            @elseif($modeTva === 'ht')
                <tr>
                    <td colspan="3" style="padding: 0.75rem; text-align: right; font-weight: 500; color: #6b7280;">Total HT</td>
                    <td style="padding: 0.75rem; text-align: right; font-weight: 600; color: #0a0e1a;">{{ number_format($montantHt, 2, ',', ' ') }} €</td>
                </tr>
                <tr>
                    <td colspan="3" style="padding: 0.5rem; text-align: right; color: #6b7280;">TVA ({{ number_format($tauxTva, 1) }}%)</td>
                    <td style="padding: 0.5rem; text-align: right; color: #6b7280;">{{ number_format($montantTva, 2, ',', ' ') }} €</td>
                </tr>
                <tr style="border-top: 2px solid #e5e7eb;">
                    <td colspan="3" style="padding: 0.75rem; text-align: right; font-weight: 600; color: #0a0e1a;">Total TTC</td>
                    <td style="padding: 0.75rem; text-align: right; font-size: 1.25rem; font-weight: 700; color: #5bbce4;">{{ number_format($montantTotal, 2, ',', ' ') }} €</td>
                </tr>
            @elseif($modeTva === 'ttc')
                <tr>
                    <td colspan="3" style="padding: 0.75rem; text-align: right; font-weight: 500; color: #6b7280;">Total TTC</td>
                    <td style="padding: 0.75rem; text-align: right; font-weight: 600; color: #0a0e1a;">{{ number_format($montantTotal, 2, ',', ' ') }} €</td>
                </tr>
                <tr>
                    <td colspan="3" style="padding: 0.5rem; text-align: right; color: #6b7280; font-size: 0.875rem;">
                        dont TVA ({{ number_format($tauxTva, 1) }}%)
                    </td>
                    <td style="padding: 0.5rem; text-align: right; color: #6b7280; font-size: 0.875rem;">{{ number_format($montantTva, 2, ',', ' ') }} €</td>
                </tr>
            @endif
        </tfoot>
    </table>
    
    <!-- Notes -->
    @if($devis->notes)
    <div style="background: #f9fafb; padding: 1rem; border-radius: 8px;">
        <p style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; margin-bottom: 0.5rem;">Notes & Conditions</p>
        <p style="color: #0a0e1a; white-space: pre-line;">{{ $devis->notes }}</p>
    </div>
    @endif
</div>

<!-- Actions supplémentaires -->
<div class="card" style="max-width: 900px; margin-top: 1.5rem;">
    <h3 class="card-title" style="margin-bottom: 1rem;">Actions</h3>
    <div class="flex gap-2" style="flex-wrap: wrap;">
        @if($devis->statut === 'brouillon')
        <form action="{{ route('brightshell.devis.status', $devis->id) }}" method="POST" style="display: inline;">
            @csrf
            <input type="hidden" name="statut" value="envoye">
            <button type="submit" class="btn btn-primary">Marquer comme envoyé</button>
        </form>
        @elseif($devis->statut === 'envoye')
        <form action="{{ route('brightshell.devis.status', $devis->id) }}" method="POST" style="display: inline;">
            @csrf
            <input type="hidden" name="statut" value="accepte">
            <button type="submit" class="btn btn-success">Marquer comme accepté</button>
        </form>
        <form action="{{ route('brightshell.devis.status', $devis->id) }}" method="POST" style="display: inline;">
            @csrf
            <input type="hidden" name="statut" value="refuse">
            <button type="submit" class="btn btn-danger">Marquer comme refusé</button>
        </form>
        @endif
        
        <a href="{{ route('brightshell.devis.edit', $devis->id) }}" class="btn btn-secondary">Modifier</a>
        
        <form action="{{ route('brightshell.devis.delete', $devis->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Supprimer ce devis ?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Supprimer</button>
        </form>
    </div>
</div>
@endsection
