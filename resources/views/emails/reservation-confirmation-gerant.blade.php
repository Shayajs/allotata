@extends('emails.layout')

@section('content')
    <h1 style="color: #f97316; margin-bottom: 20px;">Nouvelle réservation</h1>
    
    <p>Bonjour,</p>
    
    <p>Une nouvelle réservation a été créée pour <strong>{{ $reservation->entreprise->nom }}</strong>.</p>
    
    <div class="info-box">
        <h3 style="margin-top: 0;">Détails de la réservation :</h3>
        <p><strong>Client :</strong> {{ $reservation->user ? $reservation->user->name : ($reservation->nom_client ?? 'Client non inscrit') }}</p>
        <p><strong>Service :</strong> {{ $reservation->type_service }}</p>
        <p><strong>Date et heure :</strong> {{ $reservation->date_reservation->format('d/m/Y à H:i') }}</p>
        @if($reservation->typeService && $reservation->typeService->estDateButoire())
            <p><strong>Délai :</strong> {{ $reservation->typeService->duree_formatee }}</p>
        @else
            <p><strong>Durée :</strong> {{ $reservation->duree_minutes }} minutes</p>
        @endif
        <p><strong>Prix :</strong> {{ number_format($reservation->prix, 2, ',', ' ') }} €</p>
        <p><strong>Téléphone :</strong> {{ $reservation->telephone_client }}</p>
        @if($reservation->lieu)
            <p><strong>Lieu :</strong> {{ $reservation->lieu }}</p>
        @endif
    </div>
    
    @if($reservation->notes)
        <p><strong>Notes du client :</strong> {{ $reservation->notes }}</p>
    @endif
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ route('reservations.show', [$reservation->entreprise->slug, $reservation->id]) }}" class="button">Voir la réservation</a>
    </div>
    
    <p>Cordialement,<br>L'équipe Allo Tata</p>
@endsection
