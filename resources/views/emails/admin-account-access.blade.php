@extends('emails.layout')

@section('content')
    <h1 style="color: #dc2626; margin-bottom: 20px;">Accès administrateur à votre compte</h1>

    <p>Bonjour {{ $user->name }},</p>

    <p>Un administrateur Allo Tata a accédé à votre compte en <strong>mode édition</strong> le {{ now()->format('d/m/Y à H:i') }}.</p>

    <p>En mode édition, l'administrateur peut effectuer des actions au nom de votre compte (modifications, réservations, paramètres, etc.).</p>

    <p>Vous pouvez consulter le détail des accès et actions réalisées dans l'onglet <strong>Sécurité</strong> de votre tableau de bord.</p>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ route('security.index') }}" class="button">Voir l'activité de sécurité</a>
    </div>

    <p style="color: #dc2626; font-weight: bold;">Si vous n'attendiez pas cette intervention ou souhaitez contester une action, contactez notre support via un ticket.</p>

    <div style="text-align: center; margin: 20px 0;">
        <a href="{{ route('tickets.create') }}" class="button" style="background-color: #64748b;">Contacter le support</a>
    </div>

    <p>Bonne journée,<br>L'équipe Allo Tata</p>
@endsection
