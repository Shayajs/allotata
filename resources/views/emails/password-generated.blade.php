@extends('emails.layout')

@section('content')
    <h1 style="color: #22c55e; margin-bottom: 20px;">Nouveau mot de passe généré</h1>
    
    <p>Bonjour {{ $user->name }},</p>
    
    <p>Un nouveau mot de passe a été généré pour votre compte <strong>Allo Tata</strong> par un administrateur.</p>
    
    <div style="background-color: #f3f4f6; border: 2px solid #22c55e; border-radius: 8px; padding: 20px; margin: 30px 0; text-align: center;">
        <p style="margin: 0; font-size: 18px; font-weight: bold; color: #1f2937; font-family: monospace; letter-spacing: 2px;">
            {{ $password }}
        </p>
    </div>
    
    <p style="color: #dc2626; font-weight: bold;">⚠️ Important :</p>
    <ul>
        <li>Ce mot de passe est temporaire et doit être changé lors de votre prochaine connexion</li>
        <li>Ne partagez jamais votre mot de passe avec qui que ce soit</li>
        <li>Si vous n'avez pas demandé ce changement, contactez immédiatement le support</li>
    </ul>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ route('login') }}" class="button">Se connecter</a>
    </div>
    
    <p>Si vous avez des questions ou des préoccupations, n'hésitez pas à nous contacter.</p>
    
    <p>Bonne journée,<br>L'équipe Allo Tata</p>
@endsection
