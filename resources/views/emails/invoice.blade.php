@extends('emails.layout')

@section('content')
    <h1 style="color: #22c55e; margin-bottom: 20px;">Facture #{{ $facture->numero }}</h1>
    
    @if($isForClient)
        <p>Bonjour,</p>
        <p>Vous trouverez ci-joint votre facture <strong>#{{ $facture->numero }}</strong> de <strong>{{ $facture->entreprise->nom }}</strong>.</p>
    @else
        <p>Bonjour {{ $facture->entreprise->user->name }},</p>
        <p>Une nouvelle facture a été générée pour <strong>{{ $facture->entreprise->nom }}</strong>.</p>
    @endif
    
    <div class="info-box">
        <h3 style="margin-top: 0;">Détails de la facture :</h3>
        <p><strong>Numéro :</strong> {{ $facture->numero }}</p>
        <p><strong>Date :</strong> {{ $facture->date_facture->format('d/m/Y') }}</p>
        <p><strong>Montant TTC :</strong> {{ number_format($facture->montant_ttc, 2, ',', ' ') }} €</p>
        @if($facture->reservations->count() > 0)
            <p><strong>Réservations :</strong> {{ $facture->reservations->count() }}</p>
        @endif
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ route('factures.download', $facture->id) }}" class="button">Télécharger la facture (PDF)</a>
    </div>
    
    <p>Cordialement,<br>L'équipe Allo Tata</p>
@endsection
