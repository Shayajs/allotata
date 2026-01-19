@extends('emails.layout')

@section('content')
    <h1 style="color: #22c55e; margin-bottom: 20px;">Bienvenue sur Allo Tata, {{ $user->name }} !</h1>
    
    <p>Nous sommes ravis de vous accueillir sur <strong>Allo Tata</strong>, la plateforme tout-en-un pour gérer votre activité professionnelle.</p>
    
    <p>Vous pouvez maintenant :</p>
    <ul>
        <li>Créer votre première entreprise</li>
        <li>Gérer votre agenda et vos réservations</li>
        <li>Suivre vos finances</li>
        <li>Communiquer avec vos clients</li>
    </ul>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ route('dashboard') }}" class="button">Accéder à mon dashboard</a>
    </div>
    
    <p>Si vous avez des questions, n'hésitez pas à consulter notre <a href="{{ route('legal.cgu') }}" style="color: #22c55e;">FAQ</a> ou à nous contacter.</p>
    
    <p>Bonne journée,<br>L'équipe Allo Tata</p>
@endsection
