@extends('brightshell.layout')

@section('title', 'Devis ' . $devis->numero)

@section('actions')
<div class="flex gap-2 flex-wrap">
    <a href="{{ route('brightshell.devis') }}" class="btn btn-secondary">← Retour</a>
    <a href="{{ route('brightshell.devis.pdf', $devis->id) }}" class="btn btn-primary" target="_blank">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        <span>Télécharger PDF</span>
    </a>
    @if($devis->statut !== 'accepte')
    <form action="{{ route('brightshell.devis.convert', $devis->id) }}" method="POST" style="display: inline;">
        @csrf
        <button type="submit" class="btn btn-success">Convertir en facture</button>
    </form>
    @endif
</div>
@endsection

@push('styles')
<style>
    .devis-show-wrapper {
        max-width: 900px;
        margin: 0 auto;
    }
    
    .document-preview-card {
        background: white;
        color: #1a1a1a;
        padding: 2.5rem;
    }
    
    .document-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 2rem;
        padding-bottom: 2rem;
        border-bottom: 2px solid #e5e7eb;
        gap: 2rem;
    }
    
    .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .document-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 2rem;
        min-width: 600px;
    }
    
    @media (max-width: 768px) {
        .document-preview-card {
            padding: 1.5rem;
        }
        
        .document-header {
            flex-direction: column;
            gap: 1.5rem;
            text-align: center;
        }
        
        .document-header > div:last-child {
            text-align: center !important;
            border-top: 1px dashed #e5e7eb;
            padding-top: 1.5rem;
        }
        
        .document-header .preview-numero {
            justify-content: center;
        }
        
        .document-header img {
            margin: 0 auto 1rem !important;
        }
    }
</style>
@endpush

@section('content')
<div class="devis-show-wrapper">
    <div class="card document-preview-card" id="devis-content">
        <!-- En-tête -->
        <div class="document-header">
            <div>
                @php
                    $logoPath = public_path('media/brightshell/logo.png');
                    $hasLogo = file_exists($logoPath);
                @endphp
                @if($hasLogo)
                    <img src="{{ asset('media/brightshell/logo.png') }}" alt="Logo" style="height: 50px; margin-bottom: 0.5rem; display: block;">
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
                <p style="color: #6b7280; margin-top: 1rem;">Date: {{ \Carbon\Carbon::parse($devis->date_devis ?? $devis->created_at)->format('d/m/Y') }}</p>
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
        <div class="table-responsive">
            <table class="document-table">
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
                        @php
                            $hasSousLignes = !empty($ligne['sous_lignes']) && count($ligne['sous_lignes']) > 0;
                            $ligneTotal = 0;
                            $prixUnitaire = $ligne['prix_unitaire'] ?? 0;
                            
                            if ($hasSousLignes) {
                                $sousTotal = 0;
                                foreach ($ligne['sous_lignes'] as $sl) {
                                    $sousTotal += ($sl['quantite'] ?? 0) * ($sl['prix_unitaire'] ?? 0);
                                }
                                $prixUnitaire = $sousTotal;
                                $ligneTotal = $sousTotal * ($ligne['quantite'] ?? 1);
                            } else {
                                $ligneTotal = ($ligne['quantite'] ?? 0) * ($ligne['prix_unitaire'] ?? 0);
                            }
                        @endphp
                        
                        <tr style="border-bottom: 1px solid #e5e7eb; {{ $hasSousLignes ? 'background: #fafbfc;' : '' }}">
                            <td data-label="Description" style="padding: 0.75rem; color: #0a0e1a; {{ $hasSousLignes ? 'font-weight: 600;' : '' }}">
                                {{ $ligne['description'] }}
                                @if(!empty($ligne['details']))
                                    <div style="font-size: 0.8rem; color: #6b7280; font-style: italic; margin-top: 0.25rem;">{{ $ligne['details'] }}</div>
                                @endif
                            </td>
                            <td data-label="Qté" style="padding: 0.75rem; text-align: right; color: #6b7280;">{{ $ligne['quantite'] }}</td>
                            <td data-label="Prix unit." style="padding: 0.75rem; text-align: right; color: #6b7280;">{{ number_format($prixUnitaire, 2, ',', ' ') }} €</td>
                            <td data-label="Total" style="padding: 0.75rem; text-align: right; font-weight: 600; color: #0a0e1a;">{{ number_format($ligneTotal, 2, ',', ' ') }} €</td>
                        </tr>
                        
                        @if($hasSousLignes)
                            @foreach($ligne['sous_lignes'] as $sousLigne)
                                @php
                                    $slTotal = ($sousLigne['quantite'] ?? 0) * ($sousLigne['prix_unitaire'] ?? 0);
                                @endphp
                                <tr style="border-bottom: 1px dashed #e5e7eb; background: #f9fafb;">
                                    <td data-label="Description" style="padding: 0.5rem 0.75rem 0.5rem 2rem; color: #6b7280; font-size: 0.85rem;">↳ {{ $sousLigne['description'] }}</td>
                                    <td data-label="Qté" style="padding: 0.5rem 0.75rem; text-align: right; color: #9ca3af; font-size: 0.85rem;">{{ $sousLigne['quantite'] }}</td>
                                    <td data-label="Prix unit." style="padding: 0.5rem 0.75rem; text-align: right; color: #9ca3af; font-size: 0.85rem;">{{ number_format($sousLigne['prix_unitaire'], 2, ',', ' ') }} €</td>
                                    <td data-label="Total" style="padding: 0.5rem 0.75rem; text-align: right; color: #6b7280; font-size: 0.85rem;">{{ number_format($slTotal, 2, ',', ' ') }} €</td>
                                </tr>
                            @endforeach
                        @endif
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
        </div>
        
        <!-- Notes -->
        @if($devis->notes)
        <div style="background: #f9fafb; padding: 1rem; border-radius: 8px;">
            <p style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; margin-bottom: 0.5rem;">Notes & Conditions</p>
            <p style="color: #0a0e1a; white-space: pre-line;">{{ $devis->notes }}</p>
        </div>
        @endif
    </div>
    
    <!-- Actions supplémentaires -->
    <div class="card" style="margin-top: 1.5rem;">
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
</div>
@endsection
