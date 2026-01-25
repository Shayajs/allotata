@extends('emails.layout')

@section('content')
    <h1 style="color: #f59e0b; margin-bottom: 24px; font-size: 26px;">
        🔐 Authentification requise pour votre paiement
    </h1>
    
    <p style="font-size: 16px; line-height: 1.7; color: #4b5563;">
        Bonjour <strong>{{ $nom_client ?? 'Client' }}</strong>,
    </p>
    
    <p style="font-size: 16px; line-height: 1.7; color: #4b5563;">
        Votre banque demande une confirmation supplémentaire pour finaliser le paiement de votre échéance.
    </p>
    
    <div class="info-box" style="background-color: #fef3c7; border: 2px solid #f59e0b; border-radius: 12px; padding: 24px; margin: 24px 0;">
        <h3 style="margin-top: 0; color: #92400e; font-size: 18px;">⚠️ Action requise</h3>
        <p style="margin-bottom: 12px; color: #78350f; font-size: 16px;">
            <strong>Montant :</strong> {{ $montant ?? '0,00 €' }}
        </p>
        <p style="margin-bottom: 0; color: #78350f; font-size: 14px;">
            {{ $libelle_echeance ?? 'Échéance' }} – {{ $periode ?? '' }}
        </p>
    </div>
    
    <div class="details-card" style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; margin: 24px 0;">
        <h3 style="color: #111827; margin-top: 0; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #e5e7eb; font-size: 16px;">
            📋 Que faire maintenant ?
        </h3>
        
        <p style="font-size: 15px; line-height: 1.7; color: #4b5563; margin-bottom: 16px;">
            Cliquez sur le bouton ci-dessous pour finaliser votre paiement. Vous serez redirigé vers une page sécurisée où votre banque vous demandera de confirmer le paiement (authentification 3D Secure).
        </p>
        
        <div style="text-align: center; margin: 32px 0;">
            <a href="{{ $url_authenticate ?? '#' }}" 
               style="display: inline-block; background-color: #22c55e; color: #ffffff; text-decoration: none; padding: 16px 32px; border-radius: 8px; font-weight: 600; font-size: 16px; transition: background-color 0.2s;">
                🔐 Finaliser mon paiement
            </a>
        </div>
        
        <p style="font-size: 13px; line-height: 1.6; color: #6b7280; margin-top: 24px; margin-bottom: 0; padding-top: 16px; border-top: 1px solid #e5e7eb;">
            <strong>Note :</strong> Cette authentification est demandée par votre banque pour sécuriser votre paiement. Le processus prend généralement moins d'une minute.
        </p>
    </div>
    
    <p style="font-size: 14px; line-height: 1.7; color: #6b7280; margin-top: 24px;">
        Si vous avez des questions ou rencontrez un problème, n'hésitez pas à nous contacter.
    </p>
    
    <p style="font-size: 14px; line-height: 1.7; color: #6b7280; margin-top: 16px;">
        Vous pouvez également accéder à votre <a href="{{ $url_checkout ?? route('checkout.index') }}" style="color: #22c55e; text-decoration: underline;">espace paiement</a> pour gérer vos échéances.
    </p>
@endsection
