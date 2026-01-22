@extends('brightshell.layout')

@section('title', 'Composer un email')

@section('content')
<div class="card" style="max-width: 800px;">
    <form action="{{ route('brightshell.mailing.send') }}" method="POST">
        @csrf
        
        <div class="form-group">
            <label class="form-label">Destinataire *</label>
            <div class="flex gap-2">
                <input type="email" name="to" id="email-to" class="form-input" value="{{ old('to') }}" required placeholder="email@exemple.com">
                @if(count($clients) > 0)
                <select id="client-select" class="form-input" style="width: auto;" onchange="document.getElementById('email-to').value = this.value">
                    <option value="">Choisir un client</option>
                    @foreach($clients as $client)
                    <option value="{{ $client->email }}">{{ $client->nom }} - {{ $client->email }}</option>
                    @endforeach
                </select>
                @endif
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Sujet *</label>
            <input type="text" name="subject" class="form-input" value="{{ old('subject') }}" required placeholder="Objet de l'email">
        </div>
        
        <div class="form-group">
            <label class="form-label">Message *</label>
            <textarea name="body" class="form-textarea" style="min-height: 250px;" required placeholder="Votre message...">{{ old('body') }}</textarea>
        </div>
        
        <div style="background: var(--bs-bg-dark); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
            <p class="text-muted text-sm">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: inline; vertical-align: middle; margin-right: 0.5rem;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                L'email sera envoyé depuis <strong>lucas.espinar@brightshell.fr</strong> via le serveur SMTP BrightShell.
            </p>
        </div>
        
        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                Envoyer
            </button>
            <a href="{{ route('brightshell.mailing') }}" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>
@endsection
