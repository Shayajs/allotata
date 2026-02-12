@extends('brightshell.layout')

@section('title', 'Facture ' . $facture->numero)

@section('actions')
<div class="flex gap-2 flex-wrap">
    <a href="{{ route('brightshell.factures') }}" class="btn btn-secondary">← Retour</a>
    @if($facture->statut !== 'payee')
    <a href="{{ route('brightshell.factures.edit', $facture->id) }}" class="btn btn-secondary">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
        <span>Modifier</span>
    </a>
    @endif
    <a href="{{ route('brightshell.factures.pdf', $facture->id) }}" class="btn btn-primary" target="_blank">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        <span>PDF</span>
    </a>
    @if($facture->statut !== 'payee')
        @if(!$facture->paiement_echelonne)
        <button type="button" onclick="document.getElementById('plan-paiement-card').style.display = 'block'; document.getElementById('plan-paiement-card').scrollIntoView({behavior: 'smooth'})" class="btn btn-info">
            📅 Proposer paiement
        </button>
        @endif
        <form action="{{ route('brightshell.factures.paid', $facture->id) }}" method="POST" class="flex gap-2">
            @csrf
            <select name="mode_paiement" class="form-input" style="height: 38px; width: auto; min-width: 140px;">
                <option value="Virement bancaire">Virement</option>
                <option value="Chèque">Chèque</option>
                <option value="Carte bleue">CB</option>
            </select>
            <button type="submit" class="btn btn-success">Payée</button>
        </form>
    @endif
    <form action="{{ route('brightshell.factures.avoir', $facture->id) }}" method="POST" onsubmit="return confirm('Créer un avoir pour annuler cette facture ?')">
        @csrf
        <button type="submit" class="btn btn-danger">Avoir</button>
    </form>
</div>
@endsection

@push('styles')
<style>
    .facture-show-wrapper {
        max-width: 1400px;
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
    
    @media (max-width: 1024px) {
        .facture-grid {
            grid-template-columns: 1fr !important;
        }
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
        
        .document-header img {
            margin: 0 auto 1rem !important;
        }
    }
</style>
@endpush

@section('content')
<div class="facture-show-wrapper">
    <div class="grid grid-2 facture-grid" style="gap: 2rem;">
        <!-- Facture -->
        <div class="card document-preview-card">
            <!-- En-tête -->
            <div class="document-header">
                <div>
                    <h2 style="font-size: 1.5rem; font-weight: 700; color: #0a0e1a; margin-bottom: 0.5rem;">{{ $entreprise['nom'] }}</h2>
                    <p style="color: #6b7280; font-size: 0.875rem;">{{ $entreprise['forme_juridique'] }}</p>
                    <p style="color: #6b7280; font-size: 0.875rem;">SIRET: {{ $entreprise['siret'] }}</p>
                    <p style="color: #6b7280; font-size: 0.875rem;">{{ $entreprise['email'] }}</p>
                    <p style="color: #6b7280; font-size: 0.875rem;">{{ $entreprise['telephone'] }}</p>
                </div>
                <div style="text-align: right;">
                    <h1 style="font-size: 2rem; font-weight: 700; color: #5bbce4;">{{ str_starts_with($facture->numero, 'AVO') ? 'AVOIR' : 'FACTURE' }}</h1>
                    <p style="font-size: 1.25rem; font-weight: 600; color: #0a0e1a;">{{ $facture->numero }}</p>
                    <p style="color: #6b7280; margin-top: 1rem;">Date: {{ \Carbon\Carbon::parse($facture->date_facture ?? $facture->created_at)->format('d/m/Y') }}</p>
                    @if($facture->paiement_echelonne)
                    <p style="color: #f59e0b; font-weight: 600; margin-top: 0.5rem;">📅 Paiement en {{ $facture->nombre_echeances }}x</p>
                    @else
                    <p style="color: #6b7280;">Échéance: {{ \Carbon\Carbon::parse($facture->date_facture ?? $facture->created_at)->addDays($facture->echeance_jours)->format('d/m/Y') }}</p>
                    @endif
                    @if($facture->statut === 'payee')
                    <p style="color: #10b981; font-weight: 600; margin-top: 0.5rem;">✓ PAYÉE</p>
                    @endif
                </div>
            </div>
            
            <!-- Client -->
            <div style="background: #f9fafb; padding: 1rem; border-radius: 8px; margin-bottom: 2rem;">
                <p style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; margin-bottom: 0.5rem;">Client</p>
                <p style="font-weight: 600; color: #0a0e1a;">{{ $facture->client_societe ?? $facture->client_nom . ' ' . $facture->client_prenom }}</p>
                @if($facture->client_adresse)
                <p style="color: #6b7280; font-size: 0.875rem;">{{ $facture->client_adresse }}</p>
                <p style="color: #6b7280; font-size: 0.875rem;">{{ $facture->client_cp }} {{ $facture->client_ville }}</p>
                @endif
                @if($facture->client_siret)
                <p style="color: #6b7280; font-size: 0.875rem;">SIRET: {{ $facture->client_siret }}</p>
                @endif
            </div>
            
            <!-- Objet -->
            <div style="margin-bottom: 2rem;">
                <p style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; margin-bottom: 0.5rem;">Objet</p>
                <p style="font-weight: 600; color: #0a0e1a;">{{ $facture->objet }}</p>
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
                        @foreach($facture->lignes as $ligne)
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
                        <tr>
                            <td colspan="3" style="padding: 0.75rem; text-align: right; font-weight: 600; color: #0a0e1a;">Total HT = Total TTC</td>
                            <td style="padding: 0.75rem; text-align: right; font-size: 1.25rem; font-weight: 700; color: #5bbce4;">{{ number_format($facture->montant_total, 2, ',', ' ') }} €</td>
                        </tr>
                        <tr>
                            <td colspan="4" style="padding: 0.5rem; text-align: right; color: #6b7280; font-size: 0.875rem;">
                                TVA non applicable, art. 293 B du CGI
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            @if($facture->notes)
            <div style="background: #f9fafb; padding: 1rem; border-radius: 8px;">
                <p style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; margin-bottom: 0.5rem;">Notes</p>
                <p style="color: #0a0e1a; white-space: pre-line;">{{ $facture->notes }}</p>
            </div>
            @endif
        </div>
    
    <!-- Panneau Paiement -->
    <div>
        @if($facture->paiement_echelonne && count($echeances) > 0)
        <!-- Échéances -->
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="card-title">Plan de paiement ({{ $facture->nombre_echeances }}x)</h3>
                @if($facture->statut !== 'payee')
                <form action="{{ route('brightshell.factures.echeances.delete', $facture->id) }}" method="POST" onsubmit="return confirm('Supprimer ce plan de paiement ?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm" style="padding: 2px 8px; font-size: 12px;">Supprimer</button>
                </form>
                @endif
            </div>
            
            @php
                $totalPaye = collect($echeances)->where('est_payee', true)->sum('montant');
                $totalRestant = collect($echeances)->where('est_payee', false)->sum('montant');
            @endphp
            
            <div class="grid grid-2 mb-4" style="gap: 1rem;">
                <div class="stat-card">
                    <div class="stat-label">Déjà payé</div>
                    <div class="stat-value success">{{ number_format($totalPaye, 2, ',', ' ') }} €</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Reste à payer</div>
                    <div class="stat-value warning">{{ number_format($totalRestant, 2, ',', ' ') }} €</div>
                </div>
            </div>
            
            <div class="progress mb-4">
                <div class="progress-bar" style="width: {{ $facture->montant_total > 0 ? ($totalPaye / $facture->montant_total) * 100 : 0 }}%"></div>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Échéance</th>
                        <th>Date</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($echeances as $echeance)
                    <tr>
                        <td data-label="Échéance">{{ $echeance->numero }}/{{ $facture->nombre_echeances }}</td>
                        <td data-label="Date">
                            {{ \Carbon\Carbon::parse($echeance->date_echeance)->format('d/m/Y') }}
                            @if(!$echeance->est_payee && \Carbon\Carbon::parse($echeance->date_echeance)->isPast())
                            <span class="badge badge-danger" style="margin-left: 0.5rem;">En retard</span>
                            @endif
                        </td>
                        <td data-label="Montant" style="font-weight: 600;">{{ number_format($echeance->montant, 2, ',', ' ') }} €</td>
                        <td data-label="Statut">
                            @if($echeance->est_payee)
                            <span class="badge badge-success">✓ Payée</span>
                            @if($echeance->date_paiement)
                            <span class="text-muted text-xs" style="display: block;">le {{ \Carbon\Carbon::parse($echeance->date_paiement)->format('d/m/Y') }}</span>
                            @endif
                            @else
                            <span class="badge badge-warning">En attente</span>
                            @endif
                        </td>
                        <td data-label="Action">
                            @if(!$echeance->est_payee)
                            <form action="{{ route('brightshell.factures.echeances.paid', [$facture->id, $echeance->id]) }}" method="POST" style="display: flex; gap: 0.5rem; align-items: center; justify-content: flex-end;">
                                @csrf
                                <input type="number" name="montant_paye" class="form-input" style="padding: 2px 8px; font-size: 11px; width: 70px; height: 28px;" value="{{ $echeance->montant }}" step="0.01">
                                <select name="mode_paiement" class="form-input" style="padding: 2px 8px; font-size: 11px; width: auto; height: 28px;">
                                    <option value="Virement bancaire" {{ ($facture->mode_paiement ?? '') === 'Virement bancaire' ? 'selected' : '' }}>Virement</option>
                                    <option value="Chèque" {{ ($facture->mode_paiement ?? '') === 'Chèque' ? 'selected' : '' }}>Chèque</option>
                                    <option value="Carte bleue" {{ ($facture->mode_paiement ?? '') === 'Carte bleue' ? 'selected' : '' }}>CB</option>
                                </select>
                                <button type="submit" class="btn btn-success btn-sm">Payé</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <!-- Créer un plan de paiement -->
        @if($facture->statut !== 'payee')
        <div class="card" id="plan-paiement-card" style="display: none;">
            <div class="card-header">
                <h3 class="card-title">Facilités de paiement</h3>
            </div>
            <p class="text-muted mb-4">Proposez un paiement échelonné à votre client.</p>
            
            <!-- Mode de calcul -->
            <div class="form-group mb-4">
                <label class="form-label">Mode de calcul</label>
                <div class="flex gap-2">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem 1rem; border-radius: 6px; border: 2px solid var(--bs-border);" id="mode-echeances-label">
                        <input type="radio" name="mode_calcul" value="echeances" checked onchange="toggleModeEchelonnage()"> Par nombre d'échéances
                    </label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem 1rem; border-radius: 6px; border: 2px solid var(--bs-border);" id="mode-mensualite-label">
                        <input type="radio" name="mode_calcul" value="mensualite" onchange="toggleModeEchelonnage()"> Par mensualité
                    </label>
                </div>
            </div>
            
            <form action="{{ route('brightshell.factures.echeances.create', $facture->id) }}" method="POST" id="form-echelonnage">
                @csrf
                <input type="hidden" name="montant_mensuel" id="hidden-montant-mensuel">
                
                <!-- Mode: Nombre d'échéances -->
                <div id="section-echeances">
                    <div class="form-group">
                        <label class="form-label">Nombre d'échéances</label>
                        <select name="nombre_echeances" class="form-input" id="select-echeances" onchange="calculerMensualite()">
                            <option value="2">2x</option>
                            <option value="3" selected>3x</option>
                            <option value="4">4x</option>
                            <option value="5">5x</option>
                            <option value="6">6x</option>
                            <option value="7">7x</option>
                            <option value="8">8x</option>
                            <option value="9">9x</option>
                            <option value="10">10x</option>
                            <option value="11">11x</option>
                            <option value="12">12x</option>
                        </select>
                        <p class="text-muted text-sm mt-1" id="info-mensualite">→ Mensualité: <strong>{{ number_format($facture->montant_total / 3, 2, ',', ' ') }} €</strong></p>
                    </div>
                </div>
                
                <!-- Mode: Mensualité souhaitée -->
                <div id="section-mensualite" style="display: none;">
                    <div class="form-group">
                        <label class="form-label">Mensualité souhaitée (€)</label>
                        <input type="number" class="form-input" id="input-mensualite" step="0.01" min="50" 
                               value="{{ number_format($facture->montant_total / 3, 2, '.', '') }}" onchange="calculerEcheances()">
                        <p class="text-muted text-sm mt-1" id="info-echeances">→ Nombre d'échéances: <strong>3</strong></p>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Date de la première échéance</label>
                    <input type="date" name="premiere_echeance" class="form-input" 
                           value="{{ now()->addMonth()->format('Y-m-d') }}" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Jour de prélèvement mensuel</label>
                    <select name="jour_echeance" class="form-input" required>
                        @for($j = 1; $j <= 28; $j++)
                        <option value="{{ $j }}" {{ $j === 5 ? 'selected' : '' }}>{{ $j }} de chaque mois</option>
                        @endfor
                    </select>
                    <p class="text-muted text-xs mt-1">Limité au 28 pour éviter les problèmes en février.</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Moyen de paiement prévu</label>
                    <select name="mode_paiement" class="form-input" required>
                        <option value="Virement bancaire">Virement bancaire</option>
                        <option value="Chèque">Chèque</option>
                        <option value="Carte bleue">Carte bleue</option>
                    </select>
                </div>
                
                <!-- Résumé -->
                <div style="background: var(--bs-bg-hover); padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <p class="text-sm text-muted mb-1">Montant total de la facture</p>
                    <p class="font-bold text-lg" style="color: var(--bs-accent);">{{ number_format($facture->montant_total, 2, ',', ' ') }} €</p>
                </div>
                
                <button type="submit" class="btn btn-primary">Créer le plan de paiement</button>
            </form>
        </div>
        
        <script>
        const montantTotal = {{ $facture->montant_total }};
        
        function toggleModeEchelonnage() {
            const modeEcheances = document.querySelector('input[value="echeances"]').checked;
            document.getElementById('section-echeances').style.display = modeEcheances ? 'block' : 'none';
            document.getElementById('section-mensualite').style.display = modeEcheances ? 'none' : 'block';
            
            // Styles des labels
            document.getElementById('mode-echeances-label').style.borderColor = modeEcheances ? 'var(--bs-accent)' : 'var(--bs-border)';
            document.getElementById('mode-mensualite-label').style.borderColor = modeEcheances ? 'var(--bs-border)' : 'var(--bs-accent)';
            
            // Synchroniser les valeurs au changement de mode
            if (modeEcheances) {
                calculerMensualite();
            } else {
                calculerEcheances();
            }
        }
        
        function calculerMensualite() {
            const select = document.getElementById('select-echeances');
            const nbEcheances = parseInt(select.value);
            const mensualite = (montantTotal / nbEcheances).toFixed(2);
            document.getElementById('info-mensualite').innerHTML = '→ Mensualité: <strong>' + new Intl.NumberFormat('fr-FR', {minimumFractionDigits: 2}).format(mensualite) + ' €</strong>';
            
            // Mettre à jour l'input de l'autre mode pour la cohérence
            document.getElementById('input-mensualite').value = mensualite;
            document.getElementById('hidden-montant-mensuel').value = ""; // Mode standard
        }
        
        function calculerEcheances() {
            const input = document.getElementById('input-mensualite');
            const mensualiteSpecifiee = parseFloat(input.value);
            if (mensualiteSpecifiee > 0) {
                let nbEcheances = Math.ceil(montantTotal / mensualiteSpecifiee);
                // On limite à 12 car c'est la limite du select et de la validation PHP
                nbEcheances = Math.max(2, Math.min(nbEcheances, 12));
                
                document.getElementById('info-echeances').innerHTML = '→ Nombre d\'échéances: <strong>' + nbEcheances + '</strong>';
                document.getElementById('select-echeances').value = nbEcheances;
                
                // Mettre à jour le champ caché pour le contrôleur
                document.getElementById('hidden-montant-mensuel').value = mensualiteSpecifiee;
                
                // Mettre à jour l'info de l'autre section (mensualité réelle peut différer pour la dernière)
                const mensualiteMoyenne = (montantTotal / nbEcheances).toFixed(2);
                document.getElementById('info-mensualite').innerHTML = '→ Mensualité: <strong>' + new Intl.NumberFormat('fr-FR', {minimumFractionDigits: 2}).format(mensualiteMoyenne) + ' € (Dernière: ' + new Intl.NumberFormat('fr-FR', {minimumFractionDigits: 2}).format(montantTotal - (mensualiteSpecifiee * (nbEcheances-1))) + ' €)</strong>';
            }
        }
        
        // Init
        toggleModeEchelonnage();
        </script>
        @else
        <div class="card mb-4">
            <div class="empty-state">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p>Cette facture a été payée intégralement.</p>
                @if($facture->date_paiement)
                <p class="text-success">Payée le {{ \Carbon\Carbon::parse($facture->date_paiement)->format('d/m/Y') }}</p>
                @endif
            </div>
        </div>
        @endif
        @endif

        <!-- Historique des paiements & Ajout manuel -->
        <div class="card mt-4">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="card-title">Historique des règlements</h3>
                @if($facture->statut !== 'payee')
                <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('form-add-payment').classList.toggle('d-none')">+ Ajouter un règlement</button>
                @endif
            </div>
            
            <div id="form-add-payment" class="d-none" style="padding: 1rem; background: var(--bs-bg-hover); border-bottom: 1px solid var(--bs-border);">
                <h4 style="font-size: 0.9rem; margin-bottom: 0.5rem;">Ajouter un règlement (rétroactif ou partiel)</h4>
                <form action="{{ route('brightshell.factures.add_payment', $facture->id) }}" method="POST">
                    @csrf
                    <div class="grid grid-2" style="gap: 1rem;">
                        <div class="form-group mb-0">
                            <label class="form-label">Date</label>
                            <input type="date" name="date" class="form-input" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">Montant (€)</label>
                            <input type="number" name="montant" class="form-input" step="0.01" min="0" required placeholder="0.00">
                        </div>
                    </div>
                    <div class="grid grid-2 mt-2" style="gap: 1rem;">
                        <div class="form-group mb-0">
                            <label class="form-label">Mode</label>
                            <select name="mode_paiement" class="form-input">
                                <option value="Virement bancaire">Virement bancaire</option>
                                <option value="Chèque">Chèque</option>
                                <option value="Carte bleue">Carte bleue</option>
                                <option value="Espèces">Espèces</option>
                            </select>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">Note (facultatif)</label>
                            <input type="text" name="note" class="form-input" placeholder="Ex: Acompte, Solde...">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm mt-3">Enregistrer le règlement</button>
                </form>
            </div>

            @if(isset($paiements) && count($paiements) > 0)
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Montant</th>
                            <th>Mode</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paiements as $p)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($p->date)->format('d/m/Y') }}</td>
                            <td class="font-bold text-success">+{{ number_format($p->montant, 2, ',', ' ') }} €</td>
                            <td>{{ $p->mode_reglement }}</td>
                            <td class="text-muted text-sm">{{ Str::after($p->nature, '(') ? Str::before(Str::after($p->nature, '('), ')') : '-' }}</td>
                        </tr>
                        @endforeach
                        <tr style="background: #fafbfc; font-weight: bold; border-top: 2px solid #e5e7eb;">
                            <td style="text-align: right;">Total réglé</td>
                            <td class="text-success">{{ number_format($paiements->sum('montant'), 2, ',', ' ') }} €</td>
                            <td colspan="2">
                                <span class="text-muted text-xs font-normal">
                                    sur {{ number_format($facture->montant_total, 2, ',', ' ') }} € 
                                    ({{ number_format(($paiements->sum('montant') / $facture->montant_total) * 100, 0) }}%)
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            @else
            <p class="text-muted text-center py-4">Aucun règlement enregistré pour cette facture.</p>
            @endif
        </div>
    </div>
</div>
@endsection
