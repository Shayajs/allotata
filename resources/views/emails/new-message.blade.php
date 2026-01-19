@extends('emails.layout')

@section('content')
    <h1 style="color: #22c55e; margin-bottom: 20px;">Nouveau message</h1>
    
    @if($conversation->user_id)
        <p>Bonjour {{ $conversation->user->name }},</p>
        <p>Vous avez reçu un nouveau message de <strong>{{ $conversation->entreprise->nom }}</strong>.</p>
    @else
        <p>Bonjour,</p>
        <p>Vous avez reçu un nouveau message de <strong>{{ $message->user ? $message->user->name : 'Un client' }}</strong>.</p>
    @endif
    
    <div class="info-box">
        <p style="margin: 0;"><strong>Message :</strong></p>
        <p style="margin-top: 10px; white-space: pre-wrap;">{{ $message->contenu }}</p>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        @if($conversation->user_id)
            <a href="{{ route('messagerie.show', $conversation->entreprise->slug) }}" class="button">Répondre</a>
        @else
            <a href="{{ route('messagerie.show-gerant', [$conversation->entreprise->slug, $conversation->id]) }}" class="button">Répondre</a>
        @endif
    </div>
    
    <p>Cordialement,<br>L'équipe Allo Tata</p>
@endsection
