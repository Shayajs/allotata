@extends('emails.layout')

@section('content')
    <h1 style="color: #22c55e; margin-bottom: 24px; font-size: 26px;">
        📄 Votre facture est disponible
    </h1>
    
    <p style="font-size: 16px; line-height: 1.7; color: #4b5563;">
        Bonjour <strong>{{ $clientName ?? 'Client' }}</strong>,
    </p>
    
    <p style="font-size: 16px; line-height: 1.7; color: #4b5563;">
        Veuillez trouver ci-joint votre facture 
        @if(isset($invoiceNumber))
            <strong>n°{{ $invoiceNumber }}</strong>
        @endif
        @if(isset($entreprise))
            de <strong>{{ $entreprise }}</strong>
        @endif.
    </p>
    
    <div class="details-card" style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; margin: 24px 0;">
        <h3 style="color: #111827; margin-top: 0; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #e5e7eb; font-size: 16px;">
            📋 Récapitulatif de la facture
        </h3>
        
        <table style="width: 100%; border-collapse: collapse;">
            @if(isset($invoiceNumber))
            <tr>
                <td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Numéro de facture</td>
                <td style="padding: 8px 0; color: #111827; font-weight: 600; text-align: right; font-size: 14px;">{{ $invoiceNumber }}</td>
            </tr>
            @endif
            @if(isset($invoiceDate))
            <tr>
                <td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Date d'émission</td>
                <td style="padding: 8px 0; color: #111827; font-weight: 600; text-align: right; font-size: 14px;">{{ $invoiceDate }}</td>
            </tr>
            @endif
            @if(isset($dueDate))
            <tr>
                <td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Date d'échéance</td>
                <td style="padding: 8px 0; color: #f59e0b; font-weight: 600; text-align: right; font-size: 14px;">{{ $dueDate }}</td>
            </tr>
            @endif
            <tr style="border-top: 2px solid #e5e7eb;">
                <td style="padding: 12px 0; color: #111827; font-size: 16px; font-weight: 600;">Montant total</td>
                <td style="padding: 12px 0; color: #22c55e; font-weight: 700; text-align: right; font-size: 20px;">{{ number_format($amount ?? 0, 2, ',', ' ') }} €</td>
            </tr>
        </table>
    </div>
    
    @if(isset($items) && count($items) > 0)
        <div class="info-box">
            <h3 style="margin-top: 0; color: #166534;">📦 Détail des prestations</h3>
            <table style="width: 100%; border-collapse: collapse;">
                @foreach($items as $item)
                <tr>
                    <td style="padding: 6px 0; color: #166534; font-size: 14px;">{{ $item['description'] }}</td>
                    <td style="padding: 6px 0; color: #166534; font-weight: 600; text-align: right; font-size: 14px;">{{ number_format($item['amount'], 2, ',', ' ') }} €</td>
                </tr>
                @endforeach
            </table>
        </div>
    @endif
    
    <div class="button-container">
        @if(isset($invoiceUrl))
            <a href="{{ $invoiceUrl }}" class="button">
                Télécharger la facture
            </a>
        @else
            <a href="{{ route('dashboard') }}" class="button">
                Voir mes factures
            </a>
        @endif
    </div>
    
    <div class="divider"></div>
    
    <p style="color: #6b7280; font-size: 13px;">
        Cette facture est également disponible dans votre espace client. 
        Pour toute question, n'hésitez pas à <a href="{{ url('/contact') }}" style="color: #22c55e;">nous contacter</a>.
    </p>
    
    <div class="signature">
        <p style="margin-bottom: 4px;">Cordialement,</p>
        <p class="team-name" style="color: #22c55e; font-weight: 600; margin: 0;">L'équipe Allo Tata</p>
    </div>
@endsection
