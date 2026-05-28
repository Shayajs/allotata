@extends('admin.layout')

@section('title', 'Gestion du Forum')
@section('header', 'Gestion du Forum')
@section('subheader', 'Gérez les catégories, posts et nouveautés du forum')

@section('content')
<div class="space-y-8">
    @if(session('success'))
        <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
            <p class="text-green-800 dark:text-green-400">{{ session('success') }}</p>
        </div>
    @endif

    <!-- Statistiques -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ $stats['total_categories'] }}</div>
            <div class="text-sm text-slate-600 dark:text-slate-400">Catégories</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ $stats['total_posts'] }}</div>
            <div class="text-sm text-slate-600 dark:text-slate-400">Total Posts</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['posts_admin'] }}</div>
            <div class="text-sm text-slate-600 dark:text-slate-400">Nouveautés</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $stats['posts_public'] }}</div>
            <div class="text-sm text-slate-600 dark:text-slate-400">Posts Publics</div>
        </div>
    </div>

    <!-- Catégories -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">Catégories</h2>
            <a href="{{ route('admin.forum.category.create') }}" class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition">
                + Nouvelle catégorie
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-700">
                        <th class="text-left py-3 px-4 text-sm font-semibold text-slate-700 dark:text-slate-300">Nom</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-slate-700 dark:text-slate-300">Type</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-slate-700 dark:text-slate-300">Posts</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-slate-700 dark:text-slate-300">Ordre</th>
                        <th class="text-right py-3 px-4 text-sm font-semibold text-slate-700 dark:text-slate-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @foreach($categories as $category)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <td class="py-3 px-4">
                                <div class="font-semibold text-slate-900 dark:text-white">{{ $category->nom }}</div>
                                @if($category->description)
                                    <div class="text-sm text-slate-500 dark:text-slate-400">{{ Str::limit($category->description, 50) }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                @if($category->admin_only)
                                    <span class="px-2 py-1 text-xs bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 rounded">Admin</span>
                                @else
                                    <span class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded">Public</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-slate-600 dark:text-slate-400">{{ $category->posts_count }}</td>
                            <td class="py-3 px-4 text-slate-600 dark:text-slate-400">{{ $category->ordre }}</td>
                            <td class="py-3 px-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.forum.category.edit', $category) }}" class="px-3 py-1 text-sm bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 rounded hover:bg-blue-200 dark:hover:bg-blue-900/50">
                                        Modifier
                                    </a>
                                    <form method="POST" action="{{ route('admin.forum.category.destroy', $category) }}" onsubmit="return confirm('Êtes-vous sûr ?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ui-btn-simple px-3 py-1 text-sm bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 rounded hover:bg-red-200 dark:hover:bg-red-900/50">
                                            Supprimer
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Posts récents -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-6">Posts récents</h2>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-700">
                        <th class="text-left py-3 px-4 text-sm font-semibold text-slate-700 dark:text-slate-300">Titre</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-slate-700 dark:text-slate-300">Auteur</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-slate-700 dark:text-slate-300">Catégorie</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-slate-700 dark:text-slate-300">Commentaires</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-slate-700 dark:text-slate-300">Date</th>
                        <th class="text-right py-3 px-4 text-sm font-semibold text-slate-700 dark:text-slate-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @foreach($posts as $post)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2">
                                    @if($post->est_epingle)
                                        <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z"></path>
                                        </svg>
                                    @endif
                                    <a href="{{ route('forum.post.show', $post) }}" class="font-semibold text-slate-900 dark:text-white hover:text-green-600 dark:hover:text-green-400">
                                        {{ Str::limit($post->titre, 50) }}
                                    </a>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-slate-600 dark:text-slate-400">{{ $post->user->name }}</td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 text-xs bg-slate-100 dark:bg-slate-700 rounded">{{ $post->category->nom }}</span>
                            </td>
                            <td class="py-3 px-4 text-slate-600 dark:text-slate-400">{{ $post->comments_count }}</td>
                            <td class="py-3 px-4 text-slate-600 dark:text-slate-400">{{ $post->created_at->diffForHumans() }}</td>
                            <td class="py-3 px-4">
                                <div class="flex items-center justify-end gap-2">
                                    <form method="POST" action="{{ route('admin.forum.post.toggle-pin', $post) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="ui-btn-simple px-3 py-1 text-sm bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 rounded hover:bg-yellow-200 dark:hover:bg-yellow-900/50">
                                            {{ $post->est_epingle ? 'Désépingler' : 'Épingler' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.forum.post.destroy', $post) }}" onsubmit="return confirm('Êtes-vous sûr ?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ui-btn-simple px-3 py-1 text-sm bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 rounded hover:bg-red-200 dark:hover:bg-red-900/50">
                                            Supprimer
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $posts->links() }}
        </div>
    </div>
</div>
@endsection
