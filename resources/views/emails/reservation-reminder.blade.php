@extends('emails.layout')

@section('content')
    <h1 style="color: #f59e0b; margin-bottom: 20px;">Rappel de rendez-vous</h1>
    
    <p>Bonjour {{ $reservation->user ? $reservation->user->name : ($reservation->nom_client ?? 'Client') }},</p>
    
    <p>Ceci est un rappel : vous avez un rendez-vous prévu <strong>dans {{ $hoursBefore }} heures</strong>.</p>
    
    <div class="warning-box">
        <h3 style="margin-top: 0;">Votre rendez-vous :</h3>
        <p><strong>Entreprise :</strong> {{ $reservation->entreprise->nom }}</p>
        <p><strong>Service :</strong> {{ $reservation->type_service }}</p>
        <p><strong>Date et heure :</strong> {{ $reservation->date_reservation->format('d/m/Y à H:i') }}</p>
        <p><strong>Durée :</strong> {{ $reservation->duree_minutes }} minutes</p>
        @if($reservation->lieu)
            <p><strong>Lieu :</strong> {{ $reservation->lieu }}</p>
        @endif
        @if($reservation->membre)
            <p><strong>Avec :</strong> {{ $reservation->membre->user->name ?? 'Membre assigné' }}</p>
        @endif
    </div>
    
    <p><strong>Contact :</strong> {{ $reservation->entreprise->telephone ?? $reservation->entreprise->email }}</p>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ route('public.reservation.show', $reservation->hash ?? $reservation->id) }}" class="button">Voir ma réservation</a>
    </div>
    
    <p>À bientôt !<br>L'équipe Allo Tata</p>
@endsection
