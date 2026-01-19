@extends('emails.layout')

@section('content')
    <h1 style="color: #ef4444; margin-bottom: 20px;">Réservation annulée</h1>
    
    <p>Bonjour,</p>
    
    @if($cancelledBy === 'client')
        <p>La réservation du <strong>{{ $reservation->date_reservation->format('d/m/Y à H:i') }}</strong> a été annulée par le client.</p>
    @else
        <p>Vous avez annulé la réservation du <strong>{{ $reservation->date_reservation->format('d/m/Y à H:i') }}</strong>.</p>
    @endif
    
    <div class="info-box">
        <h3 style="margin-top: 0;">Détails de la réservation annulée :</h3>
        <p><strong>Client :</strong> {{ $reservation->user ? $reservation->user->name : ($reservation->nom_client ?? 'Client non inscrit') }}</p>
        <p><strong>Service :</strong> {{ $reservation->type_service }}</p>
        <p><strong>Date et heure :</strong> {{ $reservation->date_reservation->format('d/m/Y à H:i') }}</p>
        <p><strong>Prix :</strong> {{ number_format($reservation->prix, 2, ',', ' ') }} €</p>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ route('reservations.show', [$reservation->entreprise->slug, $reservation->id]) }}" class="button">Voir la réservation</a>
    </div>
    
    <p>Cordialement,<br>L'équipe Allo Tata</p>
@endsection
