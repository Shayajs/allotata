@props(['user', 'fallback' => 'N/A'])

@if($user)
    {{-- Version courte pour mobile (P. Nom) --}}
    <span class="md:hidden">{{ $user->short_name }}</span>
    {{-- Version complète pour desktop (Prénom Nom) --}}
    <span class="hidden md:inline">{{ $user->full_name }}</span>
@else
    {{ $fallback }}
@endif
