@extends('emails.layout')

@section('content')
    <h1 style="color: #22c55e; margin-bottom: 20px;">Réservation confirmée !</h1>
    
    <p>Bonjour {{ $reservation->user ? $reservation->user->name : ($reservation->nom_client ?? 'Client') }},</p>
    
    <p>Votre réservation a été <strong>confirmée</strong> par <strong>{{ $reservation->entreprise->nom }}</strong>.</p>
    
    <div class="info-box">
        <h3 style="margin-top: 0;">Détails de votre réservation :</h3>
        <p><strong>Service :</strong> {{ $reservation->type_service }}</p>
        <p><strong>Date et heure :</strong> {{ $reservation->date_reservation->format('d/m/Y à H:i') }}</p>
        <p><strong>Durée :</strong> {{ $reservation->duree_minutes }} minutes</p>
        <p><strong>Prix :</strong> {{ number_format($reservation->prix, 2, ',', ' ') }} €</p>
        @if($reservation->lieu)
            <p><strong>Lieu :</strong> {{ $reservation->lieu }}</p>
        @endif
        @if($reservation->membre)
            <p><strong>Avec :</strong> {{ $reservation->membre->user->name ?? 'Membre assigné' }}</p>
        @endif
    </div>
    
    @if($reservation->notes)
        <p><strong>Notes :</strong> {{ $reservation->notes }}</p>
    @endif
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ route('public.reservation.show', $reservation->hash ?? $reservation->id) }}" class="button">Voir ma réservation</a>
    </div>
    
    <p>À bientôt !<br>L'équipe Allo Tata</p>
@endsection
