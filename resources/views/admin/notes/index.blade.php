@extends('admin.layout')

@section('title', 'Notes Collaboratives')
@section('header', 'Notes Collaboratives')
@section('subheader', 'Créez et partagez des notes en temps réel')

@section('content')
<div class="space-y-6">
    <!-- Actions -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">Mes Notes</h2>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                {{ $notes->total() }} note{{ $notes->total() > 1 ? 's' : '' }}
            </p>
        </div>
        <a 
            href="{{ route('admin.notes.show', 'new') }}"
            class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm font-medium transition"
        >
            + Nouvelle Note
        </a>
    </div>

    <!-- Liste des notes -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($notes as $note)
            <a 
                href="{{ route('admin.notes.show', $note) }}"
                class="bg-white dark:bg-slate-800 rounded-lg p-6 shadow-sm border border-slate-200 dark:border-slate-700 hover:shadow-md transition"
            >
                <div class="flex items-start justify-between mb-3">
                    <h3 class="font-semibold text-slate-900 dark:text-white text-lg">{{ $note->titre }}</h3>
                    <span class="text-xs text-slate-500 dark:text-slate-400">
                        {{ $note->updated_at->diffForHumans() }}
                    </span>
                </div>
                
                @if($note->contenu_markdown)
                    <p class="text-sm text-slate-600 dark:text-slate-400 line-clamp-3 mb-4">
                        {{ Str::limit(strip_tags($note->contenu_markdown), 150) }}
                    </p>
                @else
                    <p class="text-sm text-slate-400 dark:text-slate-500 italic mb-4">Note vide</p>
                @endif

                <div class="flex items-center justify-between pt-4 border-t border-slate-200 dark:border-slate-700">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center text-xs font-medium text-green-700 dark:text-green-400">
                            {{ substr($note->creator->name, 0, 1) }}
                        </div>
                        <span class="text-xs text-slate-600 dark:text-slate-400">{{ $note->creator->name }}</span>
                    </div>
                    
                    @if(isset($note->activeCollaborators) && $note->activeCollaborators->count() > 0)
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-slate-500 dark:text-slate-400">En ligne:</span>
                            <div class="flex -space-x-2">
                                @foreach($note->activeCollaborators->take(3) as $activeUser)
                                    @php
                                        $firstName = explode(' ', $activeUser->name)[0] ?? $activeUser->name;
                                        $initial = strtoupper(substr($firstName, 0, 1));
                                        $avatarColor = '#' . substr(md5($activeUser->id), 0, 6);
                                    @endphp
                                    <div 
                                        class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-medium border-2 border-white dark:border-slate-800"
                                        style="background-color: {{ $avatarColor }}20; color: {{ $avatarColor }};"
                                        title="{{ $activeUser->name }}"
                                    >
                                        {{ $initial }}
                                    </div>
                                @endforeach
                                @if($note->activeCollaborators->count() > 3)
                                    <div class="w-6 h-6 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 flex items-center justify-center text-xs font-medium border-2 border-white dark:border-slate-800">
                                        +{{ $note->activeCollaborators->count() - 3 }}
                                    </div>
                                @endif
                            </div>
                            <span class="text-xs text-green-600 dark:text-green-400 font-medium">
                                {{ $note->activeCollaborators->count() }}
                            </span>
                        </div>
                    @elseif($note->collaborators->count() > 1)
                        <span class="text-xs text-slate-500 dark:text-slate-400">
                            {{ $note->collaborators->count() }} collaborateurs
                        </span>
                    @endif
                </div>
            </a>
        @empty
            <div class="col-span-full text-center py-12">
                <svg class="w-16 h-16 mx-auto text-slate-400 dark:text-slate-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p class="text-slate-600 dark:text-slate-400 mb-4">Aucune note pour le moment</p>
                <a 
                    href="{{ route('admin.notes.show', 'new') }}"
                    class="inline-block px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm font-medium transition"
                >
                    Créer votre première note
                </a>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($notes->hasPages())
        <div class="mt-6">
            {{ $notes->links() }}
        </div>
    @endif
</div>
@endsection
