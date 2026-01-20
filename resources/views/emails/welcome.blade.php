@extends('emails.layout')

@section('content')
    <h1 style="color: #22c55e; margin-bottom: 24px; font-size: 28px;">
        Bienvenue sur Allo Tata, {{ $user->name }} ! 🎉
    </h1>
    
    <p style="font-size: 16px; line-height: 1.7; color: #4b5563;">
        Nous sommes ravis de vous accueillir sur <strong style="color: #111827;">Allo Tata</strong>, 
        la plateforme tout-en-un pour gérer votre activité professionnelle.
    </p>
    
    <div class="info-box">
        <h3 style="margin-top: 0; color: #166534;">Vous pouvez maintenant :</h3>
        <ul style="margin: 0; padding-left: 20px; color: #166534;">
            <li style="margin-bottom: 8px;">✅ Créer votre première entreprise</li>
            <li style="margin-bottom: 8px;">📅 Gérer votre agenda et vos réservations</li>
            <li style="margin-bottom: 8px;">💰 Suivre vos finances et facturation</li>
            <li style="margin-bottom: 8px;">💬 Communiquer avec vos clients</li>
            <li style="margin-bottom: 0;">📊 Analyser vos performances</li>
        </ul>
    </div>
    
    <div class="button-container">
        <a href="{{ route('dashboard') }}" class="button">Accéder à mon dashboard</a>
    </div>
    
    <div class="divider"></div>
    
    <p style="color: #6b7280; font-size: 14px;">
        Si vous avez des questions, n'hésitez pas à consulter notre 
        <a href="{{ route('legal.cgu') }}" style="color: #22c55e;">FAQ</a> 
        ou à <a href="{{ url('/contact') }}" style="color: #22c55e;">nous contacter</a>.
    </p>
    
    <div class="signature">
        <p style="margin-bottom: 4px;">À très bientôt,</p>
        <p class="team-name" style="color: #22c55e; font-weight: 600; margin: 0;">L'équipe Allo Tata</p>
    </div>
@endsection
