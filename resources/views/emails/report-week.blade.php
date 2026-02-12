@extends('emails.layout')

@section('content')
    <h1 style="color: #22c55e; margin-bottom: 20px;">Rapport hebdomadaire</h1>
    
    <p>Bonjour {{ $user->name }},</p>
    
    <p>Voici votre rapport hebdomadaire pour <strong>{{ $entreprise->nom }}</strong>.</p>
    
    <div class="info-box">
        <h3 style="margin-top: 0;">Statistiques de la semaine :</h3>
        <p><strong>Réservations totales :</strong> {{ $stats['total_reservations'] ?? 0 }}</p>
        <p><strong>Réservations confirmées :</strong> {{ $stats['reservations_confirmees'] ?? 0 }}</p>
        <p><strong>Réservations en attente :</strong> {{ $stats['reservations_en_attente'] ?? 0 }}</p>
        <p><strong>Revenu total :</strong> {{ number_format($stats['revenu_total'] ?? 0, 2, ',', ' ') }} €</p>
        <p><strong>Revenu encaissé :</strong> {{ number_format($stats['revenu_paye'] ?? 0, 2, ',', ' ') }} €</p>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ route('entreprise.dashboard', $entreprise->slug) }}" class="button">Voir le dashboard</a>
    </div>
    
    <p>Bonne semaine !<br>L'équipe Allo Tata</p>
@endsection
