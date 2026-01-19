@extends('emails.layout')

@section('content')
    <h1 style="color: #ef4444; margin-bottom: 20px;">Réservation annulée</h1>
    
    <p>Bonjour {{ $reservation->user ? $reservation->user->name : ($reservation->nom_client ?? 'Client') }},</p>
    
    @if($cancelledBy === 'client')
        <p>Nous vous confirmons l'annulation de votre réservation du <strong>{{ $reservation->date_reservation->format('d/m/Y à H:i') }}</strong>.</p>
    @else
        <p>Votre réservation du <strong>{{ $reservation->date_reservation->format('d/m/Y à H:i') }}</strong> a été annulée par <strong>{{ $reservation->entreprise->nom }}</strong>.</p>
    @endif
    
    <div class="info-box">
        <h3 style="margin-top: 0;">Détails de la réservation annulée :</h3>
        <p><strong>Service :</strong> {{ $reservation->type_service }}</p>
        <p><strong>Date et heure :</strong> {{ $reservation->date_reservation->format('d/m/Y à H:i') }}</p>
        <p><strong>Prix :</strong> {{ number_format($reservation->prix, 2, ',', ' ') }} €</p>
    </div>
    
    @if(!$reservation->est_paye)
        <p>Si un paiement avait été effectué, il sera remboursé selon nos conditions générales.</p>
    @endif
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ route('public.entreprise', $reservation->entreprise->slug) }}" class="button">Prendre un nouveau rendez-vous</a>
    </div>
    
    <p>À bientôt !<br>L'équipe Allo Tata</p>
@endsection
