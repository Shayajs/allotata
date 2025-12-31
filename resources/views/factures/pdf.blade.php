<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture {{ $facture->numero_facture }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
        }
        .logo-section {
            flex: 1;
        }
        .logo {
            max-width: 150px;
            max-height: 80px;
            margin-bottom: 10px;
        }
        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 5px;
        }
        .company-details {
            font-size: 10px;
            color: #6b7280;
            line-height: 1.5;
        }
        .invoice-info {
            text-align: right;
        }
        .invoice-title {
            font-size: 32px;
            font-weight: bold;
            color: #059669;
            margin-bottom: 10px;
        }
        .invoice-number {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 5px;
        }
        .invoice-date {
            font-size: 12px;
            color: #374151;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }
        .two-columns {
            display: flex;
            justify-content: space-between;
            gap: 40px;
        }
        .column {
            flex: 1;
        }
        .client-info, .company-info {
            background: #f9fafb;
            padding: 15px;
            border-radius: 4px;
        }
        .info-line {
            margin-bottom: 5px;
            font-size: 11px;
        }
        .info-label {
            font-weight: bold;
            color: #374151;
        }
        .info-value {
            color: #6b7280;
        }
        .reservation-details {
            background: #f0fdf4;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 30px;
            border-left: 4px solid #10b981;
        }
        .reservation-title {
            font-weight: bold;
            color: #059669;
            margin-bottom: 10px;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        thead {
            background: #f3f4f6;
        }
        th {
            padding: 12px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
            color: #374151;
            text-transform: uppercase;
            border-bottom: 2px solid #e5e7eb;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 11px;
        }
        .text-right {
            text-align: right;
        }
        .text-left {
            text-align: left;
        }
        .totals {
            margin-left: auto;
            width: 250px;
        }
        .total-line {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 11px;
        }
        .total-label {
            color: #6b7280;
        }
        .total-value {
            font-weight: bold;
            color: #374151;
        }
        .total-final {
            border-top: 2px solid #e5e7eb;
            padding-top: 10px;
            margin-top: 10px;
        }
        .total-final .total-label,
        .total-final .total-value {
            font-size: 16px;
            color: #059669;
            font-weight: bold;
        }
        .notes {
            background: #f9fafb;
            padding: 15px;
            border-radius: 4px;
            margin-top: 30px;
            font-size: 10px;
            color: #6b7280;
        }
        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            margin-top: 20px;
        }
        .status-emise {
            background: #dbeafe;
            color: #1e40af;
        }
        .status-payee {
            background: #d1fae5;
            color: #065f46;
        }
        .status-annulee {
            background: #fee2e2;
            color: #991b1b;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 9px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- En-tête -->
        <div class="header">
            <div class="logo-section">
                @if($facture->entreprise->logo)
                    <img src="{{ public_path('storage/' . $facture->entreprise->logo) }}" alt="Logo {{ $facture->entreprise->nom }}" class="logo">
                @endif
                <div class="company-name">{{ $facture->entreprise->nom }}</div>
                <div class="company-details">
                    {{ $facture->entreprise->type_activite }}<br>
                    @if($facture->entreprise->siren)
                        SIREN : {{ $facture->entreprise->siren }}<br>
                    @endif
                    {{ $facture->entreprise->email }}<br>
                    @if($facture->entreprise->telephone)
                        {{ $facture->entreprise->telephone }}<br>
                    @endif
                    @if($facture->entreprise->ville)
                        {{ $facture->entreprise->ville }}
                    @endif
                </div>
            </div>
            <div class="invoice-info">
                <div class="invoice-title">FACTURE</div>
                <div class="invoice-number">N° {{ $facture->numero_facture }}</div>
                <div class="invoice-date">
                    Date d'émission : {{ $facture->date_facture->format('d/m/Y') }}<br>
                    @if($facture->date_echeance)
                        Échéance : {{ $facture->date_echeance->format('d/m/Y') }}
                    @endif
                </div>
            </div>
        </div>

        <!-- Informations client et entreprise -->
        <div class="two-columns section">
            <div class="column">
                <div class="section-title">Facturé à</div>
                <div class="client-info">
                    <div class="info-line">
                        <span class="info-label">{{ $facture->user->name }}</span>
                    </div>
                    <div class="info-line">
                        <span class="info-value">{{ $facture->user->email }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Détails de la réservation (facture simple) -->
        @if($facture->reservation && !$facture->estGroupee())
            <div class="reservation-details">
                <div class="reservation-title">Détails de la réservation</div>
                <div class="info-line">
                    <span class="info-label">Service :</span>
                    <span class="info-value">{{ $facture->reservation->typeService ? $facture->reservation->typeService->nom : ($facture->reservation->type_service ?? 'Service') }}</span>
                </div>
                <div class="info-line">
                    <span class="info-label">Date :</span>
                    <span class="info-value">{{ $facture->reservation->date_reservation->format('d/m/Y à H:i') }}</span>
                </div>
                @if($facture->reservation->lieu)
                    <div class="info-line">
                        <span class="info-label">Lieu :</span>
                        <span class="info-value">{{ $facture->reservation->lieu }}</span>
                    </div>
                @endif
            </div>
        @endif

        <!-- Tableau des lignes -->
        <table>
            <thead>
                <tr>
                    <th class="text-left">Description</th>
                    <th class="text-left">Date</th>
                    <th class="text-right">Montant HT</th>
                    <th class="text-right">TVA</th>
                    <th class="text-right">Montant TTC</th>
                </tr>
            </thead>
            <tbody>
                @if($facture->estGroupee())
                    @foreach($facture->reservations as $reservation)
                        <tr>
                            <td>
                                {{ $reservation->typeService ? $reservation->typeService->nom : ($reservation->type_service ?? 'Service') }}
                                @if($reservation->duree_minutes)
                                    ({{ $reservation->duree_minutes }} min)
                                @endif
                            </td>
                            <td>{{ $reservation->date_reservation->format('d/m/Y H:i') }}</td>
                            <td class="text-right">{{ number_format($reservation->prix, 2, ',', ' ') }} €</td>
                            <td class="text-right">
                                @if($facture->taux_tva > 0)
                                    {{ $facture->taux_tva }}%<br>
                                    <small>({{ number_format($reservation->prix * ($facture->taux_tva / 100), 2, ',', ' ') }} €)</small>
                                @else
                                    Exonéré
                                @endif
                            </td>
                            <td class="text-right">{{ number_format($reservation->prix * (1 + $facture->taux_tva / 100), 2, ',', ' ') }} €</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td>
                            {{ $facture->reservation->typeService ? $facture->reservation->typeService->nom : ($facture->reservation->type_service ?? 'Service') }}
                            @if($facture->reservation && $facture->reservation->duree_minutes)
                                ({{ $facture->reservation->duree_minutes }} min)
                            @endif
                        </td>
                        <td>
                            @if($facture->reservation)
                                {{ $facture->reservation->date_reservation->format('d/m/Y H:i') }}
                            @endif
                        </td>
                        <td class="text-right">{{ number_format($facture->montant_ht, 2, ',', ' ') }} €</td>
                        <td class="text-right">
                            @if($facture->taux_tva > 0)
                                {{ $facture->taux_tva }}%<br>
                                <small>({{ number_format($facture->montant_tva, 2, ',', ' ') }} €)</small>
                            @else
                                Exonéré
                            @endif
                        </td>
                        <td class="text-right">{{ number_format($facture->montant_ttc, 2, ',', ' ') }} €</td>
                    </tr>
                @endif
            </tbody>
        </table>

        <!-- Totaux -->
        <div class="totals">
            <div class="total-line">
                <span class="total-label">Total HT</span>
                <span class="total-value">{{ number_format($facture->montant_ht, 2, ',', ' ') }} €</span>
            </div>
            @if($facture->taux_tva > 0)
                <div class="total-line">
                    <span class="total-label">TVA ({{ $facture->taux_tva }}%)</span>
                    <span class="total-value">{{ number_format($facture->montant_tva, 2, ',', ' ') }} €</span>
                </div>
            @endif
            <div class="total-line total-final">
                <span class="total-label">Total TTC</span>
                <span class="total-value">{{ number_format($facture->montant_ttc, 2, ',', ' ') }} €</span>
            </div>
        </div>

        <!-- Notes -->
        @if($facture->notes)
            <div class="notes">
                <strong>Notes :</strong><br>
                {{ $facture->notes }}
            </div>
        @endif

        <!-- Statut -->
        <div>
            <span class="status status-{{ $facture->statut }}">
                @if($facture->statut === 'payee')
                    Payée
                @elseif($facture->statut === 'annulee')
                    Annulée
                @elseif($facture->statut === 'brouillon')
                    Brouillon
                @else
                    Émise
                @endif
            </span>
        </div>

        <!-- Pied de page -->
        <div class="footer">
            <p>Facture générée le {{ now()->format('d/m/Y à H:i') }} via Allo Tata</p>
            <p>Merci de votre confiance !</p>
        </div>
    </div>
</body>
</html>

