<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ ($doc['type'] ?? 'facture') === 'devis' ? 'Devis' : 'Facture' }} {{ $doc['numero'] ?? '' }}</title>
    @php
        $c = $doc['couleurs'] ?? [
            'primary' => '#059669',
            'secondary' => '#1f2937',
            'text' => '#1a1a1a',
            'muted' => '#6b7280',
            'background' => '#f9fafb',
            'border' => '#e5e7eb',
            'success' => '#10b981',
        ];
        $emetteur = $doc['emetteur'] ?? [];
        $client = $doc['client'] ?? [];
        $totaux = $doc['totaux'] ?? [];
        $paiement = $doc['paiement'] ?? [];
        $mentions = $doc['mentions'] ?? [];
        $estDevis = ($doc['type'] ?? '') === 'devis';
        $estPlateforme = ($doc['emetteur_kind'] ?? '') === 'plateforme';
        $siretAffiche = $emetteur['siret_formate'] ?? ($emetteur['siret'] ?? '');
        $nomEmetteur = $estPlateforme
            ? ($emetteur['marque'] ?? 'Allotata')
            : ($emetteur['nom'] ?? '');
        $sousTitreEmetteur = $estPlateforme
            ? trim(($emetteur['nom'] ?? '').', EI')
            : ($emetteur['forme_juridique'] ?? '');
    @endphp
    <style>
        @page { margin: 1.8cm 1.6cm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            line-height: 1.45;
            color: {{ $c['text'] }};
        }
        table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: top; }
        .logo { max-height: 56px; max-width: 160px; margin-bottom: 8px; }
        .company-name { font-size: 16px; font-weight: bold; color: {{ $c['secondary'] }}; }
        .muted { color: {{ $c['muted'] }}; font-size: 9px; }
        .doc-title { font-size: 26px; font-weight: bold; color: {{ $c['primary'] }}; text-align: right; }
        .doc-numero { font-size: 13px; font-weight: 600; color: {{ $c['secondary'] }}; text-align: right; margin-top: 4px; }
        .meta { text-align: right; color: {{ $c['muted'] }}; margin-top: 10px; font-size: 10px; }
        .stamp {
            display: inline-block;
            margin-top: 8px;
            padding: 4px 10px;
            background: {{ $c['success'] }};
            color: #fff;
            font-weight: bold;
            font-size: 10px;
        }
        .section { margin-top: 16px; }
        .label {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: {{ $c['muted'] }};
            margin-bottom: 4px;
        }
        .box { background: {{ $c['background'] }}; padding: 10px; }
        .name { font-weight: 600; font-size: 12px; color: {{ $c['secondary'] }}; }
        thead th {
            background: {{ $c['secondary'] }};
            color: #fff;
            padding: 7px 8px;
            font-size: 8px;
            text-transform: uppercase;
            text-align: left;
        }
        thead th.right, td.right { text-align: right; }
        tbody td {
            padding: 8px;
            border-bottom: 1px solid {{ $c['border'] }};
            vertical-align: top;
        }
        .details { color: {{ $c['muted'] }}; font-size: 8px; margin-top: 2px; }
        .totaux { width: 280px; margin-left: auto; margin-top: 12px; }
        .totaux td { padding: 4px 0; border: 0; }
        .totaux .final td {
            font-size: 13px;
            font-weight: bold;
            color: {{ $c['primary'] }};
            border-top: 2px solid {{ $c['border'] }};
            padding-top: 8px;
        }
        .mention-tva { font-size: 9px; color: {{ $c['muted'] }}; margin-top: 6px; font-style: italic; }
        .legal {
            margin-top: 18px;
            padding: 10px;
            background: {{ $c['background'] }};
            font-size: 8px;
            color: {{ $c['muted'] }};
        }
        .footer {
            margin-top: 24px;
            padding-top: 8px;
            border-top: 1px solid {{ $c['border'] }};
            text-align: center;
            font-size: 8px;
            color: {{ $c['muted'] }};
        }
        .bandeau {
            margin: 12px 0 0;
            padding: 6px 10px;
            background: {{ $c['primary'] }};
            color: #fff;
            font-size: 10px;
            font-weight: bold;
            text-align: center;
        }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td width="55%">
                @if(!empty($doc['logo_base64']))
                    <img src="{{ $doc['logo_base64'] }}" alt="{{ $estPlateforme ? 'Allotata' : 'Logo' }}" class="logo">
                @endif
                <div class="company-name">{{ $nomEmetteur }}</div>
                <div class="muted">{{ $sousTitreEmetteur }}</div>
                @if($estPlateforme && !empty($emetteur['forme_juridique']))
                    <div class="muted">{{ $emetteur['forme_juridique'] }}</div>
                @endif
                @if(!empty($siretAffiche))
                    <div class="muted">SIRET {{ $siretAffiche }}</div>
                @endif
                @if(!empty($emetteur['tva_intracommunautaire']))
                    <div class="muted">TVA intra. {{ $emetteur['tva_intracommunautaire'] }}</div>
                @endif
                @if(!empty($emetteur['rcs']))
                    <div class="muted">{{ $emetteur['rcs'] }}{{ !empty($emetteur['ape']) ? ' — APE '.$emetteur['ape'] : '' }}</div>
                @endif
                @if(!empty($emetteur['adresse']))
                    <div class="muted">{!! nl2br(e($emetteur['adresse'])) !!}</div>
                @endif
                <div class="muted">{{ $emetteur['email'] ?? '' }}{{ !empty($emetteur['telephone']) ? ' — '.$emetteur['telephone'] : '' }}</div>
            </td>
            <td width="45%">
                <div class="doc-title">{{ $estDevis ? 'DEVIS' : 'FACTURE' }}</div>
                <div class="doc-numero">{{ $doc['numero'] ?? '' }}</div>
                @if($estPlateforme && !empty($doc['bandeau']))
                    <div class="bandeau">{{ $doc['bandeau'] }}</div>
                @endif
                <div class="meta">
                    Date d'émission : {{ $doc['date_emission'] ?? '' }}<br>
                    @if(!empty($doc['date_prestation']))
                        Date de la prestation : {{ $doc['date_prestation'] }}<br>
                    @endif
                    @if(!empty($doc['date_echeance']) && ! $estDevis)
                        Échéance : {{ $doc['date_echeance'] }}<br>
                    @endif
                    @if(!empty($doc['date_validite']) && $estDevis)
                        Validité : {{ $doc['date_validite'] }}<br>
                    @endif
                    @if(!empty($doc['date_proposee']) && $estDevis)
                        Date proposée : {{ $doc['date_proposee'] }}
                    @endif
                </div>
                @if(!empty($paiement['acquittee']))
                    <div class="stamp">ACQUITTÉE{{ !empty($paiement['date_paiement']) ? ' le '.$paiement['date_paiement'] : '' }}</div>
                @endif
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="label">{{ $estDevis ? 'Destinataire' : 'Facturé à' }}</div>
        <div class="box">
            <div class="name">{{ $client['nom'] ?? 'Client' }}</div>
            @if(!empty($client['adresse']))
                <div class="muted">{!! nl2br(e($client['adresse'])) !!}</div>
            @endif
            @if(!empty($client['email']))
                <div class="muted">{{ $client['email'] }}</div>
            @endif
            @if(!empty($client['telephone']))
                <div class="muted">{{ $client['telephone'] }}</div>
            @endif
        </div>
    </div>

    <div class="section">
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Date</th>
                    <th class="right">Qté</th>
                    <th class="right">Prix HT</th>
                    <th class="right">TVA</th>
                    <th class="right">Total TTC</th>
                </tr>
            </thead>
            <tbody>
                @foreach(($doc['lignes'] ?? []) as $ligne)
                    <tr>
                        <td>
                            {{ $ligne['description'] ?? '' }}
                            @if(!empty($ligne['details']))
                                <div class="details">{{ $ligne['details'] }}</div>
                            @endif
                        </td>
                        <td>{{ $ligne['date'] ?? '' }}</td>
                        <td class="right">{{ $ligne['quantite'] ?? 1 }}</td>
                        <td class="right">{{ number_format((float) ($ligne['montant_ht'] ?? 0), 2, ',', ' ') }} €</td>
                        <td class="right">
                            @if(!empty($totaux['assujetti_tva']))
                                {{ number_format((float) ($ligne['taux_tva'] ?? 0), 1, ',', ' ') }} %
                            @else
                                —
                            @endif
                        </td>
                        <td class="right">{{ number_format((float) ($ligne['montant_ttc'] ?? 0), 2, ',', ' ') }} €</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <table class="totaux">
        <tr>
            <td>Total HT</td>
            <td class="right">{{ number_format((float) ($totaux['montant_ht'] ?? 0), 2, ',', ' ') }} €</td>
        </tr>
        @if(!empty($totaux['assujetti_tva']))
            <tr>
                <td>TVA ({{ number_format((float) ($totaux['taux_tva'] ?? 0), 1, ',', ' ') }} %)</td>
                <td class="right">{{ number_format((float) ($totaux['montant_tva'] ?? 0), 2, ',', ' ') }} €</td>
            </tr>
        @endif
        <tr class="final">
            <td>Total TTC</td>
            <td class="right">{{ number_format((float) ($totaux['montant_ttc'] ?? 0), 2, ',', ' ') }} €</td>
        </tr>
    </table>
    @if(!empty($totaux['mention_tva']))
        <div class="mention-tva">{{ $totaux['mention_tva'] }}</div>
    @endif

    @if(!empty($doc['notes']))
        <div class="section">
            <div class="label">Notes</div>
            <div class="muted">{{ $doc['notes'] }}</div>
        </div>
    @endif

    <div class="legal">
        @if($estDevis)
            <p>{{ $mentions['validite'] ?? '' }}</p>
            <p>{{ $mentions['acceptation'] ?? '' }}</p>
            <p>{{ $mentions['escompte'] ?? 'Pas d\'escompte pour paiement anticipé.' }}</p>
        @else
            <p>{{ $mentions['tva'] ?? $totaux['mention_tva'] ?? '' }}</p>
            <p>{{ $mentions['escompte'] ?? 'Pas d\'escompte pour paiement anticipé.' }}</p>
            <p>{{ $mentions['penalites'] ?? '' }}</p>
        @endif
    </div>

    <div class="footer">
        @if($estPlateforme)
            Lucas Espinar, EI — Allotata
            {{ !empty($siretAffiche) ? ' — SIRET '.$siretAffiche : '' }}
        @else
            {{ $emetteur['nom'] ?? '' }}
            {{ !empty($emetteur['forme_juridique']) ? ' — '.$emetteur['forme_juridique'] : '' }}
            {{ !empty($emetteur['siret']) ? ' — SIRET '.$emetteur['siret'] : '' }}
        @endif
    </div>
</body>
</html>
