@extends('brightshell.layout')

@section('title', $mail ? $mail->subject : 'Message reçu')

@section('actions')
<a href="{{ route('brightshell.mailing') }}" class="btn btn-secondary">← Retour</a>
@endsection

@section('content')
@if(!$mail)
<div class="card">
    <p class="text-muted text-center" style="padding: 2rem;">Message introuvable. Vérifiez la connexion IMAP (BRIGHTSHELL_MAIL_PASSWORD).</p>
    <div class="text-center">
        <a href="{{ route('brightshell.mailing') }}" class="btn btn-primary">Retour au mailing</a>
    </div>
</div>
@else
<div class="card">
    <div class="card-header" style="flex-direction: column; align-items: flex-start; gap: 0.5rem;">
        <h3 class="card-title">{{ $mail->subject }}</h3>
        <p class="text-muted text-sm" style="margin: 0;"><strong>De :</strong> {{ $mail->from }}</p>
        <p class="text-muted text-sm" style="margin: 0;"><strong>Date :</strong> {{ $mail->date->format('d/m/Y H:i') }}</p>
    </div>
    <div style="padding: 1.5rem; border-top: 1px solid var(--bs-border);">
        @if(!empty($mail->body_html))
        <div style="font-size: 0.9rem; line-height: 1.6;">{!! $mail->body_html !!}</div>
        @elseif(!empty($mail->body_text))
        <pre style="white-space: pre-wrap; font-family: inherit; font-size: 0.9rem; margin: 0; color: var(--bs-text-muted);">{{ $mail->body_text }}</pre>
        @else
        <p class="text-muted">Aucun contenu.</p>
        @endif
    </div>
</div>
@endif
@endsection
