<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport financier - {{ $entreprise->nom }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #22c55e;
            padding-bottom: 20px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin: 30px 0;
        }
        .stat-box {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
        }
        .stat-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #22c55e;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #22c55e;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Rapport financier</h1>
        <h2>{{ $entreprise->nom }}</h2>
        <p>Période : {{ \Carbon\Carbon::parse($stats['date_debut'])->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($stats['date_fin'])->format('d/m/Y') }}</p>
        <p>Généré le : {{ now()->format('d/m/Y à H:i') }}</p>
    </div>

    <div class="stats">
        <div class="stat-box">
            <div class="stat-label">Total réservations</div>
            <div class="stat-value">{{ $stats['total_reservations'] }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Réservations confirmées</div>
            <div class="stat-value">{{ $stats['reservations_confirmees'] }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Revenu total</div>
            <div class="stat-value">{{ number_format($stats['revenu_total'], 2, ',', ' ') }} €</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Revenu encaissé</div>
            <div class="stat-value">{{ number_format($stats['revenu_paye'], 2, ',', ' ') }} €</div>
        </div>
    </div>

    <h3>Détail des réservations</h3>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Client</th>
                <th>Service</th>
                <th>Prix</th>
                <th>Statut</th>
                <th>Payé</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reservations as $reservation)
                <tr>
                    <td>{{ $reservation->date_reservation ? $reservation->date_reservation->format('d/m/Y H:i') : '' }}</td>
                    <td>{{ $reservation->user ? $reservation->user->name : ($reservation->nom_client ?? 'N/A') }}</td>
                    <td>{{ $reservation->type_service ?? 'N/A' }}</td>
                    <td>{{ number_format($reservation->prix, 2, ',', ' ') }} €</td>
                    <td>{{ ucfirst($reservation->statut) }}</td>
                    <td>{{ $reservation->est_paye ? 'Oui' : 'Non' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Rapport généré par Allo Tata</p>
    </div>
</body>
</html>
