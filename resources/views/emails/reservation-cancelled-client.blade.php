@extends('emails.layout')

@section('content')
    <h1 style="color: #ef4444; margin-bottom: 24px; font-size: 26px;">
        ❌ Réservation annulée
    </h1>
    
    <p style="font-size: 16px; line-height: 1.7; color: #4b5563;">
        Bonjour <strong>{{ $reservation->user ? $reservation->user->name : ($reservation->nom_client ?? 'Client') }}</strong>,
    </p>
    
    <p style="font-size: 16px; line-height: 1.7; color: #4b5563;">
        @if($cancelledBy ?? false)
            Votre réservation a été annulée par <strong>{{ $reservation->entreprise->nom }}</strong>.
        @else
            Votre réservation chez <strong>{{ $reservation->entreprise->nom }}</strong> a été annulée.
        @endif
    </p>
    
    <div class="error-box">
        <h3 style="margin-top: 0; color: #991b1b;">📋 Réservation annulée</h3>
        
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 6px 0; color: #991b1b; font-size: 14px;">Service</td>
                <td style="padding: 6px 0; color: #7f1d1d; font-weight: 600; text-align: right; font-size: 14px;">{{ $reservation->type_service }}</td>
            </tr>
            <tr>
                <td style="padding: 6px 0; color: #991b1b; font-size: 14px;">Date prévue</td>
                <td style="padding: 6px 0; color: #7f1d1d; font-weight: 600; text-align: right; font-size: 14px; text-decoration: line-through;">{{ $reservation->date_reservation->format('d/m/Y à H:i') }}</td>
            </tr>
            <tr>
                <td style="padding: 6px 0; color: #991b1b; font-size: 14px;">Prix</td>
                <td style="padding: 6px 0; color: #7f1d1d; font-weight: 600; text-align: right; font-size: 14px;">{{ number_format($reservation->prix, 2, ',', ' ') }} €</td>
            </tr>
        </table>
    </div>
    
    @if(isset($cancellationReason) && $cancellationReason)
        <div class="info-box">
            <h3 style="margin-top: 0; color: #166534;">📝 Motif de l'annulation</h3>
            <p style="margin-bottom: 0; color: #166534;">{{ $cancellationReason }}</p>
        </div>
    @endif
    
    @if(isset($refundInfo) && $refundInfo)
        <div class="warning-box">
            <h3 style="margin-top: 0; color: #92400e;">💰 Informations de remboursement</h3>
            <p style="margin-bottom: 0; color: #92400e;">{{ $refundInfo }}</p>
        </div>
    @endif
    
    <p style="font-size: 16px; line-height: 1.7; color: #4b5563; margin-top: 24px;">
        Nous sommes désolés pour ce désagrément. N'hésitez pas à prendre un nouveau rendez-vous si vous le souhaitez.
    </p>
    
    <div class="button-container">
        <a href="{{ route('public.entreprise', $reservation->entreprise->slug ?? $reservation->entreprise->id) }}" class="button">
            Prendre un nouveau rendez-vous
        </a>
    </div>
    
    <div class="signature">
        <p style="margin-bottom: 4px;">À bientôt,</p>
        <p class="team-name" style="color: #22c55e; font-weight: 600; margin: 0;">L'équipe Allo Tata</p>
    </div>
@endsection
