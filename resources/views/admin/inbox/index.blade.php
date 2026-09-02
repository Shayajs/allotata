@extends('admin.layout')

@section('title', 'Notifications')
@section('header', 'Notifications')
@section('subheader', 'Créations, modifications d\'entreprises et alertes plateforme')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <p class="text-slate-600 dark:text-slate-400">
        @if($nombreNonLues > 0)
            {{ $nombreNonLues }} non lue{{ $nombreNonLues > 1 ? 's' : '' }}
        @else
            Aucune notification non lue
        @endif
    </p>
    @if($nombreNonLues > 0)
        <form action="{{ route('admin.inbox.marquer-toutes-lues') }}" method="POST">
            @csrf
            <button type="submit" class="px-4 py-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 rounded-lg text-sm font-medium">
                Tout marquer comme lu
            </button>
        </form>
    @endif
</div>

@if($modifications->isNotEmpty())
    <section class="mb-8">
        <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Modifications en attente</h2>
        <div class="space-y-3">
            @foreach($modifications as $modification)
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-orange-200 dark:border-orange-800 p-4 sm:p-5">
                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-slate-900 dark:text-white">
                                {{ $modification->entreprise?->nom ?? 'Entreprise' }}
                            </p>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                {{ $modification->user?->name }} · {{ $modification->created_at->diffForHumans() }}
                            </p>
                            <p class="text-sm text-slate-600 dark:text-slate-300 mt-1">
                                La fiche publique actuelle reste en ligne jusqu'à confirmation.
                            </p>
                        </div>
                        <a href="{{ route('admin.entreprises.show', $modification->entreprise) }}" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg">
                            Examiner
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif

<div class="mb-6">
    <form method="GET" action="{{ route('admin.inbox.index') }}" class="flex gap-3">
        <select name="statut" class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
            <option value="">Toutes</option>
            <option value="non_lue" {{ request('statut') === 'non_lue' ? 'selected' : '' }}>Non lues</option>
            <option value="lue" {{ request('statut') === 'lue' ? 'selected' : '' }}>Lues</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-slate-900 dark:bg-slate-600 text-white rounded-lg text-sm font-medium">Filtrer</button>
    </form>
</div>

@if($notifications->count() > 0)
    <div class="space-y-3">
        @foreach($notifications as $notification)
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 sm:p-5 {{ ! $notification->est_lue ? 'ring-2 ring-green-500/20' : '' }}">
                <div class="flex flex-col sm:flex-row sm:items-start gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-slate-900 dark:text-white">{{ $notification->titre }}</p>
                        <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">{{ $notification->message }}</p>
                        <p class="text-xs text-slate-400 mt-2">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        @if(! $notification->est_lue)
                            <form action="{{ route('admin.inbox.marquer-lue', $notification->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 text-xs font-medium text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-600 rounded-lg">Lu</button>
                            </form>
                        @endif
                        <a href="{{ route('admin.inbox.show', $notification->id) }}" class="px-3 py-1.5 text-xs font-semibold bg-green-600 hover:bg-green-700 text-white rounded-lg">
                            Ouvrir
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="mt-6">{{ $notifications->links() }}</div>
@else
    <p class="text-slate-500 dark:text-slate-400">Aucune notification.</p>
@endif
@endsection
