@extends('emails.layout')

@section('content')
    <h1 style="color: #22c55e; margin-bottom: 24px; font-size: 26px;">
        💬 Nouveau message
    </h1>
    
    <p style="font-size: 16px; line-height: 1.7; color: #4b5563;">
        Bonjour <strong>{{ $recipientName ?? 'Client' }}</strong>,
    </p>
    
    <p style="font-size: 16px; line-height: 1.7; color: #4b5563;">
        Vous avez reçu un nouveau message de <strong style="color: #22c55e;">{{ $senderName ?? 'Un utilisateur' }}</strong>
        @if(isset($entreprise))
            concernant <strong>{{ $entreprise->nom }}</strong>
        @endif.
    </p>
    
    <div class="details-card" style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; margin: 24px 0;">
        <h3 style="color: #111827; margin-top: 0; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #e5e7eb; font-size: 16px;">
            📩 Message reçu
        </h3>
        <div style="background: #ffffff; border-radius: 8px; padding: 16px; border-left: 4px solid #22c55e;">
            <p style="margin: 0; color: #374151; font-size: 15px; line-height: 1.7; white-space: pre-wrap;">{{ $messageContent ?? 'Contenu du message' }}</p>
        </div>
        @if(isset($sentAt))
            <p style="margin-top: 12px; margin-bottom: 0; color: #9ca3af; font-size: 12px;">
                Envoyé le {{ $sentAt->format('d/m/Y à H:i') }}
            </p>
        @endif
    </div>
    
    <div class="button-container">
        <a href="{{ route('messagerie.index') }}" class="button">
            Répondre au message
        </a>
    </div>
    
    <div class="divider"></div>
    
    <p style="color: #9ca3af; font-size: 13px; text-align: center;">
        Vous recevez cet email car vous avez activé les notifications par email.
        <br>Vous pouvez modifier vos préférences dans votre <a href="{{ route('dashboard') }}" style="color: #22c55e;">espace client</a>.
    </p>
    
    <div class="signature">
        <p style="margin-bottom: 4px;">Cordialement,</p>
        <p class="team-name" style="color: #22c55e; font-weight: 600; margin: 0;">L'équipe Allo Tata</p>
    </div>
@endsection
