@extends('brightshell.layout')

@section('title', 'Mailing')

@section('actions')
<a href="{{ route('brightshell.mailing.compose') }}" class="btn btn-primary">
    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
    Nouveau mail
</a>
@endsection

@section('content')
<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Configuration</h3>
    </div>
    <div class="grid grid-2">
        <div>
            <p class="text-muted text-sm">Email</p>
            <p class="font-bold">{{ config('mail.mailers.brightshell.username', 'lucas.espinar@brightshell.fr') }}</p>
        </div>
        <div>
            <p class="text-muted text-sm">SMTP / IMAP</p>
            <p class="font-bold">{{ config('mail.mailers.brightshell.host', 'mail.brightshell.fr') }} — Envoi + Réception</p>
        </div>
    </div>
</div>

{{-- Reçus (INBOX) --}}
<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Reçus (boîte de réception)</h3>
    </div>
    @if(!($imapConfigured ?? false))
    <p class="text-muted text-center" style="padding: 2rem;">
        Configurez <code>BRIGHTSHELL_MAIL_PASSWORD</code> (et optionnellement <code>BRIGHTSHELL_MAIL_HOST</code>, <code>BRIGHTSHELL_MAIL_IMAP_PORT</code>) dans le .env pour afficher les mails reçus.
    </p>
    @elseif(isset($received) && $received->isNotEmpty())
    <div class="table-container" style="border: none; border-radius: 0;">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>De</th>
                    <th>Sujet</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($received as $mail)
                <tr>
                    <td class="text-muted">{{ $mail->date->format('d/m/Y H:i') }}</td>
                    <td>{{ Str::limit($mail->from, 40) }}</td>
                    <td>{{ Str::limit($mail->subject, 50) }}</td>
                    <td>
                        <a href="{{ route('brightshell.mailing.received.show', $mail->id) }}" class="btn btn-sm btn-secondary">Voir</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <p class="text-muted text-center" style="padding: 2rem;">Aucun mail reçu.</p>
    @endif
</div>

{{-- Envoyés --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Envoyés</h3>
    </div>
    @if(isset($sent) && count($sent) > 0)
    <div class="table-container" style="border: none; border-radius: 0;">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Destinataire</th>
                    <th>Sujet</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sent as $mail)
                <tr>
                    <td class="text-muted">{{ \Carbon\Carbon::parse($mail->created_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ $mail->to }}</td>
                    <td>{{ Str::limit($mail->subject, 50) }}</td>
                    <td>
                        @if($mail->status === 'sent')
                        <span class="badge badge-success">Envoyé</span>
                        @else
                        <span class="badge badge-danger">Échec</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <p class="text-muted text-center" style="padding: 2rem;">Aucun email envoyé.</p>
    @endif
</div>
@endsection
