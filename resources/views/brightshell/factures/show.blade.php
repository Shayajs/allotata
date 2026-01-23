@extends('brightshell.layout')

@section('title', 'Facture ' . $facture->numero)

@section('actions')
<a href="{{ route('brightshell.factures') }}" class="btn btn-secondary">← Retour</a>
<a href="{{ route('brightshell.factures.pdf', $facture->id) }}" class="btn btn-primary" target="_blank">
    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
    Télécharger PDF
</a>
@if($facture->statut !== 'payee')
    @if(!$facture->paiement_echelonne)
    <button type="button" onclick="document.getElementById('plan-paiement-card').style.display = 'block'; document.getElementById('plan-paiement-card').scrollIntoView({behavior: 'smooth'})" class="btn btn-info">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        Proposer paiement
    </button>
    @endif
    <form action="{{ route('brightshell.factures.paid', $facture->id) }}" method="POST" style="display: flex; gap: 0.5rem; align-items: center;">
        @csrf
        <select name="mode_paiement" class="form-input" style="height: 38px; width: 160px; padding: 4px 8px;">
            <option value="Virement bancaire">Virement bancaire</option>
            <option value="Chèque">Chèque</option>
            <option value="Carte bleue">Carte bleue</option>
        </select>
        <button type="submit" class="btn btn-success">Marquer payée</button>
    </form>
@endif
<form action="{{ route('brightshell.factures.avoir', $facture->id) }}" method="POST" onsubmit="return confirm('Créer un avoir pour annuler cette facture ? Cela générera un nouveau document avec des montants négatifs.')">
    @csrf
    <button type="submit" class="btn btn-danger">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15L12 19L8 15M12 19V5"/></svg>
        Générer un avoir
    </button>
</form>
@endsection

@section('content')
<div class="grid grid-2" style="gap: 2rem; max-width: 1400px;">
    <!-- Facture -->
    <div class="card" style="background: white; color: #1a1a1a;">
        <!-- En-tête -->
        <div style="display: flex; justify-content: space-between; margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 2px solid #e5e7eb;">
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
                <p style="color: #6b7280; margin-top: 1rem;">Date: {{ \Carbon\Carbon::parse($facture->created_at)->format('d/m/Y') }}</p>
                @if($facture->paiement_echelonne)
                <p style="color: #f59e0b; font-weight: 600; margin-top: 0.5rem;">📅 Paiement en {{ $facture->nombre_echeances }}x</p>
                @else
                <p style="color: #6b7280;">Échéance: {{ \Carbon\Carbon::parse($facture->created_at)->addDays($facture->echeance_jours)->format('d/m/Y') }}</p>
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
                        <td style="padding: 0.75rem; color: #0a0e1a; {{ $hasSousLignes ? 'font-weight: 600;' : '' }}">
                            {{ $ligne['description'] }}
                            @if(!empty($ligne['details']))
                                <div style="font-size: 0.8rem; color: #6b7280; font-style: italic; margin-top: 0.25rem;">{{ $ligne['details'] }}</div>
                            @endif
                        </td>
                        <td style="padding: 0.75rem; text-align: right; color: #6b7280;">{{ $ligne['quantite'] }}</td>
                        <td style="padding: 0.75rem; text-align: right; color: #6b7280;">{{ number_format($prixUnitaire, 2, ',', ' ') }} €</td>
                        <td style="padding: 0.75rem; text-align: right; font-weight: 600; color: #0a0e1a;">{{ number_format($ligneTotal, 2, ',', ' ') }} €</td>
                    </tr>
                    
                    @if($hasSousLignes)
                        @foreach($ligne['sous_lignes'] as $sousLigne)
                            @php
                                $slTotal = ($sousLigne['quantite'] ?? 0) * ($sousLigne['prix_unitaire'] ?? 0);
                            @endphp
                            <tr style="border-bottom: 1px dashed #e5e7eb; background: #f9fafb;">
                                <td style="padding: 0.5rem 0.75rem 0.5rem 2rem; color: #6b7280; font-size: 0.85rem;">↳ {{ $sousLigne['description'] }}</td>
                                <td style="padding: 0.5rem 0.75rem; text-align: right; color: #9ca3af; font-size: 0.85rem;">{{ $sousLigne['quantite'] }}</td>
                                <td style="padding: 0.5rem 0.75rem; text-align: right; color: #9ca3af; font-size: 0.85rem;">{{ number_format($sousLigne['prix_unitaire'], 2, ',', ' ') }} €</td>
                                <td style="padding: 0.5rem 0.75rem; text-align: right; color: #6b7280; font-size: 0.85rem;">{{ number_format($slTotal, 2, ',', ' ') }} €</td>
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
            <div class="card-header">
                <h3 class="card-title">Plan de paiement ({{ $facture->nombre_echeances }}x)</h3>
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
                        <td>{{ $echeance->numero }}/{{ $facture->nombre_echeances }}</td>
                        <td>
                            {{ \Carbon\Carbon::parse($echeance->date_echeance)->format('d/m/Y') }}
                            @if(!$echeance->est_payee && \Carbon\Carbon::parse($echeance->date_echeance)->isPast())
                            <span class="badge badge-danger" style="margin-left: 0.5rem;">En retard</span>
                            @endif
                        </td>
                        <td style="font-weight: 600;">{{ number_format($echeance->montant, 2, ',', ' ') }} €</td>
                        <td>
                            @if($echeance->est_payee)
                            <span class="badge badge-success">✓ Payée</span>
                            @if($echeance->date_paiement)
                            <span class="text-muted text-xs" style="display: block;">le {{ \Carbon\Carbon::parse($echeance->date_paiement)->format('d/m/Y') }}</span>
                            @endif
                            @else
                            <span class="badge badge-warning">En attente</span>
                            @endif
                        </td>
                        <td>
                            @if(!$echeance->est_payee)
                            <form action="{{ route('brightshell.factures.echeances.paid', [$facture->id, $echeance->id]) }}" method="POST" style="display: flex; gap: 0.5rem; align-items: center;">
                                @csrf
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
        <div class="card">
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
    </div>
</div>
@endsection
