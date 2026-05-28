@extends('admin.layout')

@section('title', 'Annonces')
@section('header', 'Annonces')
@section('subheader', 'Gérez les annonces affichées aux utilisateurs')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Annonces</h1>
    <a href="{{ route('admin.announcements.create') }}" class="inline-flex items-center justify-center px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
        ➕ Nouvelle annonce
    </a>
</div>

<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
    <div class="overflow-x-auto table-responsive-to-cards">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Statut</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Titre</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Cible</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Période</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                @forelse($announcements as $announcement)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 {{ !$announcement->est_actif ? 'opacity-50' : '' }}">
                        <td class="px-6 py-4 whitespace-nowrap" data-label="Statut">
                            @if($announcement->est_actif)
                                <span class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded">Active</span>
                            @else
                                <span class="px-2 py-1 text-xs bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 rounded">Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-4" data-label="Titre">
                            <div class="text-sm font-medium text-slate-900 dark:text-white">{{ Str::limit($announcement->titre, 40) }}</div>
                            <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ Str::limit($announcement->message, 60) }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap" data-label="Type">
                            <span class="px-2 py-1 text-xs rounded bg-{{ $announcement->type_color }}-100 dark:bg-{{ $announcement->type_color }}-900/30 text-{{ $announcement->type_color }}-800 dark:text-{{ $announcement->type_color }}-400">
                                {{ $announcement->type_icon }} {{ ucfirst($announcement->type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400" data-label="Cible">
                            {{ ucfirst($announcement->cible) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400" data-label="Période">
                            @if($announcement->date_debut || $announcement->date_fin)
                                {{ $announcement->date_debut?->format('d/m/Y') ?? '...' }} - {{ $announcement->date_fin?->format('d/m/Y') ?? '...' }}
                            @else
                                Permanent
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium" data-label="Actions">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.announcements.edit', $announcement) }}" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">
                                    Modifier
                                </a>
                                <form method="POST" action="{{ route('admin.announcements.destroy', $announcement) }}" class="inline" onsubmit="return confirm('Êtes-vous sûr ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ui-btn-simple text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                        Supprimer
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-slate-500 dark:text-slate-400">
                            Aucune annonce. <a href="{{ route('admin.announcements.create') }}" class="text-green-600 hover:underline">Créer la première</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700">
        {{ $announcements->links() }}
    </div>
</div>
@endsection
