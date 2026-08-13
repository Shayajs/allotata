@extends('admin.layout')

@section('title', 'Message de ' . $contact->nom)
@section('header', 'Message de contact')
@section('subheader', $contact->sujet)

@section('content')
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Message de contact</h1>
            <p class="text-slate-600 dark:text-slate-400 mt-1">{{ $contact->sujet }}</p>
        </div>
        <a href="{{ route('admin.contacts.index') }}" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
            ← Retour à la liste
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
            <p class="text-green-800 dark:text-green-400">{{ session('success') }}</p>
        </div>
    @endif

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/50">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <span class="text-sm text-slate-500 dark:text-slate-400">Expéditeur</span>
                    <p class="font-semibold text-slate-900 dark:text-white text-lg">{{ $contact->nom }}</p>
                </div>
                <div>
                    <span class="text-sm text-slate-500 dark:text-slate-400">Email</span>
                    <p class="font-medium text-slate-900 dark:text-white">
                        <a href="mailto:{{ $contact->email }}" class="text-green-600 hover:text-green-700 dark:text-green-400 dark:hover:text-green-300">
                            {{ $contact->email }}
                        </a>
                    </p>
                </div>
                <div>
                    <span class="text-sm text-slate-500 dark:text-slate-400">Date d'envoi</span>
                    <p class="font-medium text-slate-900 dark:text-white">{{ $contact->created_at->format('d/m/Y à H:i') }}</p>
                </div>
                <div>
                    <span class="text-sm text-slate-500 dark:text-slate-400">Statut</span>
                    <p>
                        @if($contact->est_lu)
                            <span class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded">Lu le {{ $contact->lu_at?->format('d/m/Y à H:i') }}</span>
                        @else
                            <span class="px-2 py-1 text-xs bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-400 rounded">Non lu</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <span class="text-sm text-slate-500 dark:text-slate-400">Sujet</span>
            <p class="font-semibold text-slate-900 dark:text-white text-lg mt-1">{{ $contact->sujet }}</p>
        </div>

        <div class="p-6">
            <span class="text-sm text-slate-500 dark:text-slate-400">Message</span>
            <div class="mt-2 p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                <p class="text-slate-700 dark:text-slate-300 whitespace-pre-wrap leading-relaxed">{{ $contact->message }}</p>
            </div>
        </div>

        <div class="p-6 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                <form method="POST" action="{{ route('admin.contacts.toggle-read', $contact) }}">
                    @csrf
                    <button type="submit" class="ui-btn-simple w-full sm:w-auto px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-all">
                        {{ $contact->est_lu ? 'Marquer comme non lu' : 'Marquer comme lu' }}
                    </button>
                </form>
                <a href="mailto:{{ $contact->email }}?subject=Re: {{ $contact->sujet }}" class="inline-flex items-center justify-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition-all">
                    Répondre par email
                </a>
            </div>
            <form method="POST" action="{{ route('admin.contacts.destroy', $contact) }}" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce message ?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="ui-btn-simple w-full sm:w-auto px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition-all">
                    Supprimer
                </button>
            </form>
        </div>

        @if($contact->user)
            <div class="p-6 border-t border-slate-200 dark:border-slate-700">
                <div class="flex items-center gap-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-blue-600 dark:text-blue-400">Cet utilisateur est inscrit sur la plateforme</p>
                        <a href="{{ route('admin.users.show', $contact->user) }}" class="font-medium text-blue-700 dark:text-blue-300 hover:underline">
                            Voir le profil de {{ $contact->user->name }} →
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
