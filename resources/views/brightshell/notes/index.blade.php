@extends('brightshell.layout')

@section('title', 'Notes')

@section('actions')
<a href="{{ route('brightshell.notes.create') }}" class="btn btn-primary">
    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
    Nouvelle note
</a>
@endsection

@section('content')
@if(count($notes) > 0)
<div class="grid grid-3">
    @foreach($notes as $note)
    @php
        $colors = [
            'default' => ['bg' => 'var(--bs-bg-card)', 'border' => 'var(--bs-border)'],
            'jaune' => ['bg' => 'rgba(251, 191, 36, 0.15)', 'border' => 'rgba(251, 191, 36, 0.4)'],
            'vert' => ['bg' => 'rgba(34, 197, 94, 0.15)', 'border' => 'rgba(34, 197, 94, 0.4)'],
            'bleu' => ['bg' => 'rgba(59, 130, 246, 0.15)', 'border' => 'rgba(59, 130, 246, 0.4)'],
            'rose' => ['bg' => 'rgba(236, 72, 153, 0.15)', 'border' => 'rgba(236, 72, 153, 0.4)'],
            'violet' => ['bg' => 'rgba(139, 92, 246, 0.15)', 'border' => 'rgba(139, 92, 246, 0.4)'],
        ];
        $color = $colors[$note->couleur] ?? $colors['default'];
    @endphp
    <a href="{{ route('brightshell.notes.show', $note->id) }}" class="card" style="text-decoration: none; color: inherit; background: {{ $color['bg'] }}; border-color: {{ $color['border'] }};">
        <h4 style="font-weight: 600; margin: 0 0 0.5rem;">{{ $note->titre }}</h4>
        <p class="text-muted text-sm" style="margin: 0;">{{ Str::limit($note->contenu, 150) }}</p>
        <p class="text-muted text-xs" style="margin-top: 1rem;">{{ \Carbon\Carbon::parse($note->updated_at)->diffForHumans() }}</p>
    </a>
    @endforeach
</div>
@else
<div class="empty-state">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
    <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">Aucune note</h3>
    <p style="margin-bottom: 1.5rem;">Créez votre première note.</p>
    <a href="{{ route('brightshell.notes.create') }}" class="btn btn-primary">Créer une note</a>
</div>
@endif
@endsection
