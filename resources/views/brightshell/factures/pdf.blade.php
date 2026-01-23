<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture {{ $facture->numero }}</title>
    @php
        // Couleurs par défaut ou personnalisées
        $couleurs = $couleurs ?? [
            'primary' => '#5bbce4',
            'secondary' => '#0a0e1a',
            'text' => '#1a1a1a',
            'muted' => '#6b7280',
            'background' => '#f9fafb',
            'border' => '#e5e7eb',
            'success' => '#10b981',
        ];
    @endphp
    <style>
        @page {
            margin: 10mm 15mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: {{ $couleurs['text'] }};
            background: white;
        }
        
        .container {
            width: 100%;
            padding: 0;
        }
        
        .header {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid {{ $couleurs['border'] }};
        }
        
        .header-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        
        .header-right {
            display: table-cell;
            width: 50%;
            text-align: right;
            vertical-align: top;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: {{ $couleurs['secondary'] }};
            margin-bottom: 5px;
        }
        
        .company-info {
            color: {{ $couleurs['muted'] }};
            font-size: 11px;
        }
        
        .facture-title {
            font-size: 32px;
            font-weight: bold;
            color: {{ $couleurs['primary'] }};
        }
        
        .facture-numero {
            font-size: 16px;
            font-weight: 600;
            color: {{ $couleurs['secondary'] }};
            margin-top: 5px;
        }
        
        .facture-meta {
            color: {{ $couleurs['muted'] }};
            margin-top: 15px;
            font-size: 11px;
        }
        
        .paid-stamp {
            display: inline-block;
            padding: 5px 15px;
            background: {{ $couleurs['success'] ?? '#10b981' }};
            color: white;
            font-weight: bold;
            border-radius: 5px;
            margin-top: 10px;
        }
        
        .section {
            margin-bottom: 15px;
        }
        
        .section-title {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: {{ $couleurs['muted'] }};
            margin-bottom: 3px;
        }
        
        .client-box {
            background: {{ $couleurs['background'] }};
            padding: 10px;
            border-radius: 4px;
        }
        
        .client-name {
            font-weight: 600;
            color: {{ $couleurs['secondary'] }};
            font-size: 14px;
        }
        
        .client-info {
            color: {{ $couleurs['muted'] }};
            font-size: 11px;
        }
        
        .objet {
            font-weight: 600;
            color: {{ $couleurs['secondary'] }};
            font-size: 14px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        th {
            background: {{ $couleurs['secondary'] }};
            color: white;
            padding: 10px;
            font-size: 10px;
            text-transform: uppercase;
            text-align: left;
        }
        
        th:not(:first-child) {
            text-align: right;
        }
        
        td {
            padding: 10px;
            border-bottom: 1px solid {{ $couleurs['border'] }};
            color: {{ $couleurs['text'] }};
        }
        
        td:not(:first-child) {
            text-align: right;
        }
        
        .ligne-total {
            font-weight: 600;
        }
        
        /* Ligne parente (avec sous-lignes) */
        tr.ligne-parent td {
            font-weight: 600;
            background: {{ $couleurs['background'] }};
            border-bottom: none;
        }
        
        /* Sous-lignes */
        tr.sous-ligne td {
            padding: 6px 10px;
            font-size: 11px;
            color: {{ $couleurs['muted'] }};
            border-bottom: 1px dashed {{ $couleurs['border'] }};
        }
        
        tr.sous-ligne td:first-child {
            padding-left: 25px;
        }
        
        tr.sous-ligne:last-of-type td {
            border-bottom: 1px solid {{ $couleurs['border'] }};
        }
        
        /* Description détaillée */
        .ligne-details-text {
            font-size: 10px;
            color: {{ $couleurs['muted'] }};
            font-style: italic;
            margin-top: 3px;
            white-space: pre-line;
        }
        
        .totaux {
            text-align: right;
            margin-top: 20px;
        }
        
        .totaux-row {
            padding: 5px 0;
            font-size: 12px;
            color: {{ $couleurs['muted'] }};
        }
        
        .totaux-row.final {
            font-size: 18px;
            font-weight: bold;
            color: {{ $couleurs['primary'] }};
            padding-top: 10px;
            margin-top: 10px;
            border-top: 1px solid {{ $couleurs['border'] }};
        }
        
        .mention-tva {
            text-align: right;
            font-size: 11px;
            color: {{ $couleurs['muted'] }};
            font-style: italic;
            margin-top: 10px;
        }
        
        .paiement-box {
            background: {{ $couleurs['background'] }};
            padding: 15px;
            border-radius: 5px;
            font-size: 10px;
            margin-top: 30px;
        }

        .penalites-mention {
            margin-top: 10px;
            font-size: 9px;
            color: {{ $couleurs['muted'] }};
            text-align: justify;
            border-top: 1px dashed {{ $couleurs['border'] }};
            padding-top: 5px;
        }
        
        .notes {
            background: {{ $couleurs['background'] }};
            padding: 15px;
            border-radius: 5px;
            font-size: 11px;
            color: {{ $couleurs['text'] }};
            white-space: pre-line;
            margin-top: 15px;
        }
        
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid {{ $couleurs['border'] }};
            text-align: center;
            color: {{ $couleurs['muted'] }};
            font-size: 10px;
        }
        
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- En-tête -->
        <div class="header">
            <div class="header-left">
                @if(isset($logo) && $logo)
                    @php
                        $type = pathinfo($logo, PATHINFO_EXTENSION);
                        $data = file_get_contents($logo);
                        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                    @endphp
                    <img src="{{ $base64 }}" alt="Logo" style="max-height: 60px; margin-bottom: 10px; display: block;">
                @endif
                <div class="company-name">{{ $entreprise['nom'] }}</div>
                <div class="company-info">{{ $entreprise['forme_juridique'] }}</div>
                <div class="company-info">SIRET: {{ $entreprise['siret'] }}</div>
                <div class="company-info">{{ $entreprise['email'] }}</div>
                <div class="company-info">{{ $entreprise['telephone'] }}</div>
            </div>
            <div class="header-right">
                <div class="facture-title">{{ str_starts_with($facture->numero, 'AVO') ? 'AVOIR' : 'FACTURE' }}</div>
                <div class="facture-numero">{{ $facture->numero }}</div>
                <div class="facture-meta">
                    <p>Date: {{ \Carbon\Carbon::parse($facture->date_facture ?? $facture->created_at)->format('d/m/Y') }}</p>
                    <p>Échéance: {{ \Carbon\Carbon::parse($facture->date_facture ?? $facture->created_at)->addDays($facture->echeance_jours)->format('d/m/Y') }}</p>
                </div>
                @if($facture->statut === 'payee')
                <div class="paid-stamp">[OK] PAYEE</div>
                @endif
            </div>
        </div>
        
        <!-- Client -->
        <div class="section">
            <div class="section-title">Client</div>
            <div class="client-box">
                <div class="client-name">{{ $facture->client_societe ?? $facture->client_nom . ' ' . $facture->client_prenom }}</div>
                @if($facture->client_adresse)
                <div class="client-info">{{ $facture->client_adresse }}</div>
                <div class="client-info">{{ $facture->client_cp }} {{ $facture->client_ville }}</div>
                @endif
                @if($facture->client_siret)
                <div class="client-info">SIRET: {{ $facture->client_siret }}</div>
                @endif
            </div>
        </div>
        
        <!-- Objet -->
        <div class="section">
            <div class="section-title">Objet</div>
            <div class="objet">{{ $facture->objet }}</div>
        </div>
        
        <!-- Lignes -->
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Qté</th>
                    <th>Prix unit.</th>
                    <th>Total</th>
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
                    
                    <tr class="{{ $hasSousLignes ? 'ligne-parent' : '' }}">
                        <td>
                            {{ $ligne['description'] }}
                            @if(!empty($ligne['details']))
                                <div class="ligne-details-text">{{ $ligne['details'] }}</div>
                            @endif
                        </td>
                        <td>{{ $ligne['quantite'] }}</td>
                        <td>{{ number_format($prixUnitaire, 2, ',', ' ') }} €</td>
                        <td class="ligne-total">{{ number_format($ligneTotal, 2, ',', ' ') }} €</td>
                    </tr>
                    
                    @if($hasSousLignes)
                        @foreach($ligne['sous_lignes'] as $sousLigne)
                            @php
                                $slTotal = ($sousLigne['quantite'] ?? 0) * ($sousLigne['prix_unitaire'] ?? 0);
                            @endphp
                            <tr class="sous-ligne">
                                <td>↳ {{ $sousLigne['description'] }}</td>
                                <td>{{ $sousLigne['quantite'] }}</td>
                                <td>{{ number_format($sousLigne['prix_unitaire'], 2, ',', ' ') }} €</td>
                                <td>{{ number_format($slTotal, 2, ',', ' ') }} €</td>
                            </tr>
                        @endforeach
                    @endif
                @endforeach
            </tbody>
        </table>
        
        <!-- Totaux -->
        @php
            $modeTva = $facture->mode_tva ?? 'non_assujetti';
            $tauxTva = $facture->taux_tva ?? 20;
            $montantTotal = $facture->montant_total ?? 0;
            $montantTva = $facture->montant_tva ?? 0;
        @endphp
        
        <div class="totaux">
            @if($modeTva === 'non_assujetti')
                <div class="totaux-row final">
                    Total HT = Total TTC: {{ number_format($montantTotal, 2, ',', ' ') }} €
                </div>
                <div class="mention-tva">
                    TVA non applicable, art. 293 B du CGI
                </div>
            @elseif($modeTva === 'ht')
                @php
                    $montantHt = $montantTotal - $montantTva;
                @endphp
                <div class="totaux-row">
                    Total HT: {{ number_format($montantHt, 2, ',', ' ') }} €
                </div>
                <div class="totaux-row">
                    TVA ({{ number_format($tauxTva, 1) }}%): {{ number_format($montantTva, 2, ',', ' ') }} €
                </div>
                <div class="totaux-row final">
                    Total TTC: {{ number_format($montantTotal, 2, ',', ' ') }} €
                </div>
            @else
                <div class="totaux-row final">
                    Total: {{ number_format($montantTotal, 2, ',', ' ') }} €
                </div>
            @endif
        </div>
        
        <!-- Modalités de paiement -->
        <div class="paiement-box">
            <div class="section-title">Modalités de paiement</div>
            <p style="color: {{ $couleurs['text'] }}; margin-top: 5px;">
                Moyen de paiement : <strong>{{ $facture->mode_paiement ?? 'Virement bancaire' }}</strong>
            </p>
            
            @if(isset($echeances) && count($echeances) > 0)
                <p style="margin-top: 10px; font-weight: 600; text-decoration: underline;">Échéancier de paiement (Facilités de paiement) :</p>
                <table style="margin-top: 5px; font-size: 10px;">
                    <thead>
                        <tr style="background: transparent; color: {{ $couleurs['text'] }}; border-bottom: 1px solid {{ $couleurs['border'] }};">
                            <th style="padding: 5px; color: {{ $couleurs['text'] }}; background: transparent; border-bottom: 1px solid {{ $couleurs['border'] }};">Échéance</th>
                            <th style="padding: 5px; color: {{ $couleurs['text'] }}; background: transparent; border-bottom: 1px solid {{ $couleurs['border'] }};">Date</th>
                            <th style="padding: 5px; color: {{ $couleurs['text'] }}; background: transparent; border-bottom: 1px solid {{ $couleurs['border'] }}; text-align: right;">Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($echeances as $e)
                            <tr>
                                <td style="padding: 5px; border-bottom: 1px solid {{ $couleurs['border'] }}; text-align: left;">{{ $e->numero }} / {{ count($echeances) }}</td>
                                <td style="padding: 5px; border-bottom: 1px solid {{ $couleurs['border'] }}; text-align: left;">{{ \Carbon\Carbon::parse($e->date_echeance)->format('d/m/Y') }}</td>
                                <td style="padding: 5px; border-bottom: 1px solid {{ $couleurs['border'] }}; text-align: right; font-weight: 600;">{{ number_format($e->montant, 2, ',', ' ') }} €</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @elseif($facture->echeance_jours)
                <p style="margin-top: 5px;">Date limite de règlement : {{ \Carbon\Carbon::parse($facture->created_at)->addDays($facture->echeance_jours)->format('d/m/Y') }}</p>
            @endif

            @if($facture->statut === 'payee' && $facture->date_paiement)
            <p style="color: {{ $couleurs['success'] ?? '#10b981' }}; font-weight: 600; margin-top: 10px;">
                Facture acquittée le {{ \Carbon\Carbon::parse($facture->date_paiement)->format('d/m/Y') }}
            </p>
            @endif

            <div class="penalites-mention">
                En cas de retard de paiement, une indemnité forfaitaire pour frais de recouvrement de 40 euros est due de plein droit (Art. L. 441-10 II du Code de commerce). Pénalités de retard : taux de refinancement de la BCE majoré de 10 points. Pas d'escompte pour paiement anticipé.
            </div>
        </div>
        
        <!-- Notes -->
        @if($facture->notes)
        <div class="section" style="margin-top: 15px;">
            <div class="section-title">Notes</div>
            <div class="notes">{{ $facture->notes }}</div>
        </div>
        @endif

        <!-- Signature -->
        <div style="margin-top: 30px; text-align: right; padding-right: 20px;">
            <p style="font-size: 11px; color: {{ $couleurs['muted'] }}; margin-bottom: 5px;">Pour {{ $entreprise['nom'] }}, {{ $entreprise['responsable'] }}</p>
            @if(isset($signature) && $signature)
                @php
                    $sType = pathinfo($signature, PATHINFO_EXTENSION);
                    $sData = file_get_contents($signature);
                    $sBase64 = 'data:image/' . $sType . ';base64,' . base64_encode($sData);
                @endphp
                <img src="{{ $sBase64 }}" alt="Signature" style="max-height: 50px; filter: grayscale(1);">
            @endif
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>{{ $entreprise['nom'] }} - {{ $entreprise['forme_juridique'] }} - SIRET {{ $entreprise['siret'] }}</p>
            <p>{{ $entreprise['email'] }} - {{ $entreprise['telephone'] }}</p>
        </div>
    </div>
</body>
</html>
