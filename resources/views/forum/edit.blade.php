@extends('layouts.user')

@section('title', 'Modifier le post')

@section('content')
<div class="max-w-7xl 2xl:max-w-[1600px] mx-auto">
    <div class="mb-6">
        <a href="{{ route('forum.post.show', $post) }}" class="text-green-600 dark:text-green-400 hover:underline flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Retour au post
        </a>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Modifier le post</h1>

        <form method="POST" action="{{ route('forum.post.update', $post) }}">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Titre *
                </label>
                <input 
                    type="text" 
                    name="titre" 
                    value="{{ old('titre', $post->titre) }}"
                    required
                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                >
                @error('titre')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Contenu *
                </label>
                <textarea 
                    name="contenu" 
                    rows="10"
                    required
                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                >{{ old('contenu', $post->contenu) }}</textarea>
                @error('contenu')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-4">
                <button type="submit" class="px-6 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition">
                    Enregistrer
                </button>
                <a href="{{ route('forum.post.show', $post) }}" class="px-6 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-semibold rounded-lg transition">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
