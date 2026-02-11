@extends('emails.layout')

@section('content')
    <h1 style="color: #f59e0b; margin-bottom: 24px; font-size: 26px;">
        ⏰ Rappel de rendez-vous
    </h1>
    
    <p style="font-size: 16px; line-height: 1.7; color: #4b5563;">
        Bonjour <strong>{{ $reservation->user ? $reservation->user->name : ($reservation->nom_client ?? 'Client') }}</strong>,
    </p>
    
    <p style="font-size: 16px; line-height: 1.7; color: #4b5563;">
        Ceci est un rappel : vous avez un rendez-vous prévu 
        <strong style="color: #f59e0b;">{{ $reservation->date_reservation->diffForHumans() }}</strong>.
    </p>
    
    <div class="warning-box">
        <h3 style="margin-top: 0; color: #92400e;">📅 Votre rendez-vous</h3>
        
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 6px 0; color: #92400e; font-size: 14px;">Entreprise</td>
                <td style="padding: 6px 0; color: #78350f; font-weight: 600; text-align: right; font-size: 14px;">{{ $reservation->entreprise->nom }}</td>
            </tr>
            <tr>
                <td style="padding: 6px 0; color: #92400e; font-size: 14px;">Service</td>
                <td style="padding: 6px 0; color: #78350f; font-weight: 600; text-align: right; font-size: 14px;">{{ $reservation->type_service }}</td>
            </tr>
            <tr>
                <td style="padding: 6px 0; color: #92400e; font-size: 14px;">Date et heure</td>
                <td style="padding: 6px 0; color: #78350f; font-weight: 700; text-align: right; font-size: 14px;">{{ $reservation->date_reservation->format('d/m/Y à H:i') }}</td>
            </tr>
            <tr>
                <td style="padding: 6px 0; color: #92400e; font-size: 14px;">Durée</td>
                <td style="padding: 6px 0; color: #78350f; font-weight: 600; text-align: right; font-size: 14px;">{{ $reservation->duree_minutes }} minutes</td>
            </tr>
            @if($reservation->lieu)
            <tr>
                <td style="padding: 6px 0; color: #92400e; font-size: 14px;">Lieu</td>
                <td style="padding: 6px 0; color: #78350f; font-weight: 600; text-align: right; font-size: 14px;">{{ $reservation->lieu }}</td>
            </tr>
            @endif
            @if($reservation->membre)
            <tr>
                <td style="padding: 6px 0; color: #92400e; font-size: 14px;">Avec</td>
                <td style="padding: 6px 0; color: #78350f; font-weight: 600; text-align: right; font-size: 14px;">{{ $reservation->membre->user->name ?? 'Membre assigné' }}</td>
            </tr>
            @endif
        </table>
    </div>
    
    @if($reservation->entreprise->telephone || $reservation->entreprise->email)
        <div class="info-box">
            <h3 style="margin-top: 0; color: #166534;">📞 Contact</h3>
            @if($reservation->entreprise->telephone)
                <p style="margin-bottom: 4px; color: #166534;">Téléphone : <strong>{{ $reservation->entreprise->telephone }}</strong></p>
            @endif
            @if($reservation->entreprise->email)
                <p style="margin-bottom: 0; color: #166534;">Email : <strong>{{ $reservation->entreprise->email }}</strong></p>
            @endif
        </div>
    @endif
    
    <div class="button-container">
        <a href="{{ route('public.reservation.show', $reservation->hash ?? $reservation->id) }}" class="button button-secondary" style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);">
            Voir ma réservation
        </a>
    </div>
    
    <div class="signature">
        <p style="margin-bottom: 4px;">À bientôt !</p>
        <p class="team-name" style="color: #22c55e; font-weight: 600; margin: 0;">L'équipe Allo Tata</p>
    </div>
@endsection
