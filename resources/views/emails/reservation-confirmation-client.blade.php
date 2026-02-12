@extends('emails.layout')

@section('content')
    <h1 style="color: #22c55e; margin-bottom: 24px; font-size: 26px;">
        Réservation confirmée ! ✅
    </h1>
    
    <p style="font-size: 16px; line-height: 1.7; color: #4b5563;">
        Bonjour <strong>{{ $reservation->user ? $reservation->user->name : ($reservation->nom_client ?? 'Client') }}</strong>,
    </p>
    
    <p style="font-size: 16px; line-height: 1.7; color: #4b5563;">
        Votre réservation a été <strong style="color: #22c55e;">confirmée</strong> par 
        <strong>{{ $reservation->entreprise->nom }}</strong>.
    </p>
    
    <div class="details-card" style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; margin: 24px 0;">
        <h3 style="color: #111827; margin-top: 0; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #e5e7eb; font-size: 16px;">
            📋 Détails de votre réservation
        </h3>
        
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Service</td>
                <td style="padding: 8px 0; color: #111827; font-weight: 600; text-align: right; font-size: 14px;">{{ $reservation->type_service }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Date et heure</td>
                <td style="padding: 8px 0; color: #111827; font-weight: 600; text-align: right; font-size: 14px;">{{ $reservation->date_reservation->format('d/m/Y à H:i') }}</td>
            </tr>
            @if($reservation->typeService && $reservation->typeService->estDateButoire())
            <tr>
                <td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Délai</td>
                <td style="padding: 8px 0; color: #111827; font-weight: 600; text-align: right; font-size: 14px;">{{ $reservation->typeService->duree_formatee }}</td>
            </tr>
            @else
            <tr>
                <td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Durée</td>
                <td style="padding: 8px 0; color: #111827; font-weight: 600; text-align: right; font-size: 14px;">{{ $reservation->duree_minutes }} minutes</td>
            </tr>
            @endif
            <tr>
                <td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Prix</td>
                <td style="padding: 8px 0; color: #22c55e; font-weight: 700; text-align: right; font-size: 16px;">{{ number_format($reservation->prix, 2, ',', ' ') }} €</td>
            </tr>
            @if($reservation->lieu)
            <tr>
                <td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Lieu</td>
                <td style="padding: 8px 0; color: #111827; font-weight: 600; text-align: right; font-size: 14px;">{{ $reservation->lieu }}</td>
            </tr>
            @endif
            @if($reservation->membre)
            <tr>
                <td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Avec</td>
                <td style="padding: 8px 0; color: #111827; font-weight: 600; text-align: right; font-size: 14px;">{{ $reservation->membre->user->name ?? 'Membre assigné' }}</td>
            </tr>
            @endif
        </table>
    </div>
    
    @if($reservation->notes)
        <div class="info-box">
            <h3 style="margin-top: 0; color: #166534;">📝 Notes</h3>
            <p style="margin-bottom: 0; color: #166534;">{{ $reservation->notes }}</p>
        </div>
    @endif
    
    <div class="button-container">
        <a href="{{ route('public.reservation.show', $reservation->hash ?? $reservation->id) }}" class="button">
            Voir ma réservation
        </a>
    </div>
    
    <div class="signature">
        <p style="margin-bottom: 4px;">À bientôt !</p>
        <p class="team-name" style="color: #22c55e; font-weight: 600; margin: 0;">L'équipe Allo Tata</p>
    </div>
@endsection
