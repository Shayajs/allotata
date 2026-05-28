@extends('admin.layout')

@section('title', 'Modifier la catégorie')
@section('header', 'Modifier la catégorie')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <form method="POST" action="{{ route('admin.forum.category.update', $category) }}">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Nom *
                </label>
                <input 
                    type="text" 
                    name="nom" 
                    value="{{ old('nom', $category->nom) }}"
                    required
                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                >
                @error('nom')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Description
                </label>
                <textarea 
                    name="description" 
                    rows="3"
                    class="ui-textarea w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                >{{ old('description', $category->description) }}</textarea>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Ordre
                </label>
                <input 
                    type="number" 
                    name="ordre" 
                    value="{{ old('ordre', $category->ordre) }}"
                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                >
            </div>

            <div class="mb-4">
                <label class="flex items-center gap-2">
                    <input 
                        type="checkbox" 
                        name="admin_only" 
                        value="1"
                        {{ old('admin_only', $category->admin_only) ? 'checked' : '' }}
                        class="w-4 h-4 text-green-600 border-slate-300 rounded focus:ring-green-500"
                    >
                    <span class="text-sm text-slate-700 dark:text-slate-300">Réservé aux administrateurs (Nouveautés)</span>
                </label>
            </div>

            <div class="flex gap-4">
                <button type="submit" class="ui-btn-simple px-6 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition">
                    Enregistrer
                </button>
                <a href="{{ route('admin.forum.index') }}" class="px-6 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-semibold rounded-lg transition">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
