<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devis {{ $devis->numero }}</title>
    @php
        // Couleurs par défaut ou personnalisées
        $couleurs = $couleurs ?? [
            'primary' => '#5bbce4',
            'secondary' => '#0a0e1a',
            'text' => '#1a1a1a',
            'muted' => '#6b7280',
            'background' => '#f9fafb',
            'border' => '#e5e7eb',
        ];
    @endphp
    <style>
        @page {
            margin: 20mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: {{ $couleurs['text'] }};
            background: white;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            display: table;
            width: 100%;
            margin-bottom: 30px;
            padding-bottom: 20px;
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
        
        .devis-title {
            font-size: 32px;
            font-weight: bold;
            color: {{ $couleurs['primary'] }};
        }
        
        .devis-numero {
            font-size: 16px;
            font-weight: 600;
            color: {{ $couleurs['secondary'] }};
            margin-top: 5px;
        }
        
        .devis-meta {
            color: {{ $couleurs['muted'] }};
            margin-top: 15px;
            font-size: 11px;
        }
        
        .section {
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: {{ $couleurs['muted'] }};
            margin-bottom: 5px;
        }
        
        .client-box {
            background: {{ $couleurs['background'] }};
            padding: 15px;
            border-radius: 5px;
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
        
        .notes {
            background: {{ $couleurs['background'] }};
            padding: 15px;
            border-radius: 5px;
            font-size: 11px;
            color: {{ $couleurs['text'] }};
            white-space: pre-line;
            margin-top: 30px;
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
                <div class="devis-title">DEVIS</div>
                <div class="devis-numero">{{ $devis->numero }}</div>
                <div class="devis-meta">
                    <p>Date: {{ \Carbon\Carbon::parse($devis->date_devis ?? $devis->created_at)->format('d/m/Y') }}</p>
                    <p>Validité: {{ $devis->validite_jours }} jours</p>
                </div>
            </div>
        </div>
        
        <!-- Client -->
        <div class="section">
            <div class="section-title">Client</div>
            <div class="client-box">
                <div class="client-name">{{ $devis->client_societe ?? $devis->client_nom . ' ' . $devis->client_prenom }}</div>
                @if($devis->client_adresse)
                <div class="client-info">{{ $devis->client_adresse }}</div>
                <div class="client-info">{{ $devis->client_cp }} {{ $devis->client_ville }}</div>
                @endif
                @if($devis->client_siret)
                <div class="client-info">SIRET: {{ $devis->client_siret }}</div>
                @endif
            </div>
        </div>
        
        <!-- Objet -->
        <div class="section">
            <div class="section-title">Objet</div>
            <div class="objet">{{ $devis->objet }}</div>
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
            $modeTva = $devis->mode_tva ?? 'non_assujetti';
            $tauxTva = $devis->taux_tva ?? 20;
            $montantHt = $devis->montant_ht ?? 0;
            $montantTva = $devis->montant_tva ?? 0;
            $montantTotal = $devis->montant_total ?? $montantHt;
        @endphp
        
        <div class="totaux">
            @if($modeTva === 'non_assujetti')
                <div class="totaux-row final">
                    Total HT: {{ number_format($montantHt, 2, ',', ' ') }} €
                </div>
                <div class="mention-tva">
                    TVA non applicable, art. 293 B du CGI
                </div>
            @elseif($modeTva === 'ht')
                <div class="totaux-row">
                    Total HT: {{ number_format($montantHt, 2, ',', ' ') }} €
                </div>
                <div class="totaux-row">
                    TVA ({{ number_format($tauxTva, 1) }}%): {{ number_format($montantTva, 2, ',', ' ') }} €
                </div>
                <div class="totaux-row final">
                    Total TTC: {{ number_format($montantTotal, 2, ',', ' ') }} €
                </div>
            @elseif($modeTva === 'ttc')
                <div class="totaux-row">
                    Total TTC: {{ number_format($montantTotal, 2, ',', ' ') }} €
                </div>
                <div class="totaux-row">
                    dont TVA ({{ number_format($tauxTva, 1) }}%): {{ number_format($montantTva, 2, ',', ' ') }} €
                </div>
            @endif
        </div>
        
        <!-- Notes -->
        @if($devis->notes)
        <div class="section" style="margin-top: 30px;">
            <div class="section-title">Notes & Conditions</div>
            <div class="notes">{{ $devis->notes }}</div>
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
