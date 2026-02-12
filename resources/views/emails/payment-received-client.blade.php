@extends('emails.layout')

@section('content')
    <h1 style="color: #22c55e; margin-bottom: 24px; font-size: 26px;">
        💳 Paiement reçu
    </h1>
    
    <p style="font-size: 16px; line-height: 1.7; color: #4b5563;">
        Bonjour <strong>{{ $clientName ?? 'Client' }}</strong>,
    </p>
    
    <p style="font-size: 16px; line-height: 1.7; color: #4b5563;">
        Nous vous confirmons la réception de votre paiement. Merci pour votre confiance !
    </p>
    
    <div class="info-box">
        <h3 style="margin-top: 0; color: #166534;">✅ Paiement confirmé</h3>
        <p style="margin-bottom: 0; color: #166534; font-size: 24px; font-weight: 700;">
            {{ number_format($amount ?? 0, 2, ',', ' ') }} €
        </p>
    </div>
    
    <div class="details-card" style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; margin: 24px 0;">
        <h3 style="color: #111827; margin-top: 0; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #e5e7eb; font-size: 16px;">
            📋 Détails du paiement
        </h3>
        
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Montant</td>
                <td style="padding: 8px 0; color: #22c55e; font-weight: 700; text-align: right; font-size: 16px;">{{ number_format($amount ?? 0, 2, ',', ' ') }} €</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Date du paiement</td>
                <td style="padding: 8px 0; color: #111827; font-weight: 600; text-align: right; font-size: 14px;">{{ isset($paymentDate) ? $paymentDate->format('d/m/Y à H:i') : now()->format('d/m/Y à H:i') }}</td>
            </tr>
            @if(isset($service))
            <tr>
                <td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Service</td>
                <td style="padding: 8px 0; color: #111827; font-weight: 600; text-align: right; font-size: 14px;">{{ $service }}</td>
            </tr>
            @endif
            @if(isset($entreprise))
            <tr>
                <td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Entreprise</td>
                <td style="padding: 8px 0; color: #111827; font-weight: 600; text-align: right; font-size: 14px;">{{ $entreprise }}</td>
            </tr>
            @endif
            @if(isset($reference))
            <tr>
                <td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Référence</td>
                <td style="padding: 8px 0; color: #6b7280; font-family: monospace; text-align: right; font-size: 12px;">{{ $reference }}</td>
            </tr>
            @endif
        </table>
    </div>
    
    @if(isset($reservation))
        <div class="button-container">
            <a href="{{ route('public.reservation.show', $reservation->hash ?? $reservation->id) }}" class="button">
                Voir ma réservation
            </a>
        </div>
    @endif
    
    <div class="divider"></div>
    
    <p style="color: #6b7280; font-size: 13px;">
        Un reçu de paiement est disponible dans votre espace client. 
        Si vous avez des questions concernant ce paiement, n'hésitez pas à 
        <a href="{{ url('/contact') }}" style="color: #22c55e;">nous contacter</a>.
    </p>
    
    <div class="signature">
        <p style="margin-bottom: 4px;">Merci pour votre confiance !</p>
        <p class="team-name" style="color: #22c55e; font-weight: 600; margin: 0;">L'équipe Allo Tata</p>
    </div>
@endsection
