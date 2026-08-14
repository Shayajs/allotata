@extends('emails.layout')

@section('content')
    <h1 style="color: #0f172a; margin-bottom: 20px;">Votre essai gratuit est arrêté</h1>

    <p>Bonjour {{ $user->name }},</p>

    <p>Votre essai gratuit <strong>{{ $typeLabel }}</strong> est terminé. L'accès associé a été retiré.</p>

    <p><strong>Vous ne pouvez plus démarrer un nouvel essai</strong> pour cette option. Pour continuer à l'utiliser, il faut souscrire un abonnement.</p>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $lienAbonnement }}" class="button">S'abonner</a>
    </div>

    <p>Bonne journée,<br>L'équipe Allo Tata</p>
@endsection
