@extends('emails.layout')

@section('content')
    <h1 style="color: #22c55e; margin-bottom: 20px;">Paiement reçu</h1>
    
    <p>Bonjour {{ $reservation->user ? $reservation->user->name : ($reservation->nom_client ?? 'Client') }},</p>
    
    <p>Nous vous confirmons la réception de votre paiement pour la réservation du <strong>{{ $reservation->date_reservation->format('d/m/Y à H:i') }}</strong>.</p>
    
    <div class="info-box">
        <h3 style="margin-top: 0;">Détails du paiement :</h3>
        <p><strong>Montant :</strong> {{ number_format($reservation->prix, 2, ',', ' ') }} €</p>
        <p><strong>Date de paiement :</strong> {{ $reservation->date_paiement ? $reservation->date_paiement->format('d/m/Y à H:i') : now()->format('d/m/Y à H:i') }}</p>
        <p><strong>Service :</strong> {{ $reservation->type_service }}</p>
        <p><strong>Entreprise :</strong> {{ $reservation->entreprise->nom }}</p>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ route('public.reservation.show', $reservation->id) }}" class="button">Voir ma réservation</a>
    </div>
    
    <p>Merci pour votre confiance !<br>L'équipe Allo Tata</p>
@endsection
