@extends('layouts.user')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Modifier le template : {{ $template->name }}</h1>
        <p class="text-slate-600 dark:text-slate-400 mt-2">{{ $template->description }}</p>
    </div>

    <form action="{{ route('admin.email-templates.update', $template) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Nom</label>
                <input type="text" name="name" id="name" value="{{ old('name', $template->name) }}" required
                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-slate-700 dark:text-white">
            </div>

            <div class="mb-4">
                <label for="subject" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Sujet</label>
                <input type="text" name="subject" id="subject" value="{{ old('subject', $template->subject) }}" required
                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-slate-700 dark:text-white"
                    placeholder="Ex: Bonjour {nom_client}, votre réservation est confirmée">
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Utilisez {nom_client}, {nom_entreprise}, etc. pour les variables dynamiques</p>
            </div>

            <div class="mb-4">
                <label for="body" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Corps du message (HTML)</label>
                <textarea name="body" id="body" rows="15" required
                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-slate-700 dark:text-white font-mono text-sm">{{ old('body', $template->body) }}</textarea>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Variables disponibles : 
                    @if($template->variables)
                        {{ implode(', ', array_map(fn($v) => '{' . $v . '}', $template->variables)) }}
                    @else
                        Aucune variable définie
                    @endif
                </p>
            </div>

            <div class="mb-4">
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $template->is_active) ? 'checked' : '' }}
                        class="rounded border-slate-300 text-green-600 focus:ring-green-500">
                    <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">Template actif</span>
                </label>
            </div>

            <div class="mb-4">
                <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Description</label>
                <textarea name="description" id="description" rows="2"
                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-slate-700 dark:text-white">{{ old('description', $template->description) }}</textarea>
            </div>
        </div>

        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('admin.email-templates.index') }}" class="px-4 py-2 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition">
                Annuler
            </a>
            <button type="submit" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
                Enregistrer
            </button>
        </div>
    </form>
</div>
@endsection
