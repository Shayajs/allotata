<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Message de {{ $contact->nom }} - Admin</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('partials.theme-script')
    </head>
    <body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">
        @include('admin.partials.nav')

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-2">
                        <svg class="w-8 h-8 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        Message de Contact
                    </h1>
                    <p class="text-slate-600 dark:text-slate-400">{{ $contact->sujet }}</p>
                </div>
                <a href="{{ route('admin.contacts.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                    ← Retour à la liste
                </a>
            </div>

            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <p class="text-green-800 dark:text-green-400">{{ session('success') }}</p>
                </div>
            @endif

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                <!-- En-tête -->
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

                <!-- Sujet -->
                <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                    <span class="text-sm text-slate-500 dark:text-slate-400">Sujet</span>
                    <p class="font-semibold text-slate-900 dark:text-white text-lg mt-1">{{ $contact->sujet }}</p>
                </div>

                <!-- Message -->
                <div class="p-6">
                    <span class="text-sm text-slate-500 dark:text-slate-400">Message</span>
                    <div class="mt-2 p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                        <p class="text-slate-700 dark:text-slate-300 whitespace-pre-wrap leading-relaxed">{{ $contact->message }}</p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="p-6 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/50 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <form method="POST" action="{{ route('admin.contacts.toggle-read', $contact) }}">
                            @csrf
                            <button type="submit" class="ui-btn-simple px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-all">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                {{ $contact->est_lu ? 'Marquer comme non lu' : 'Marquer comme lu' }}
                            </button>
                        </form>
                        <a href="mailto:{{ $contact->email }}?subject=Re: {{ $contact->sujet }}" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition-all">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            Répondre par email
                        </a>
                    </div>
                    <form method="POST" action="{{ route('admin.contacts.destroy', $contact) }}" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce message ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="ui-btn-simple px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition-all">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            Supprimer
                        </button>
                    </form>
                </div>

                @if($contact->user)
                    <!-- Info utilisateur inscrit -->
                    <div class="p-6 border-t border-slate-200 dark:border-slate-700">
                        <div class="flex items-center gap-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 flex-shrink-0 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        </div>
    </body>
</html>
