<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservation - {{ $entreprise->nom }} - Allo Tata</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-script')
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">
    <!-- Navigation -->
    <nav class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="{{ route('home') }}" class="text-xl sm:text-2xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                    Allo Tata
                </a>
                <div class="flex items-center gap-4">
                    <a href="{{ route('public.entreprise', $entreprise->slug) }}" class="px-3 sm:px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                        ← Retour
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-green-800 dark:text-green-300 font-medium">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
                @foreach($errors->all() as $error)
                    <p class="text-red-800 dark:text-red-300">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <!-- En-tête de la réservation -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 p-6 sm:p-8 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white mb-3">
                        Réservation #{{ $reservation->id }}
                    </h1>
                    <span class="inline-block px-3 py-1 text-sm font-medium rounded-full
                        @if($reservation->statut === 'confirmee') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                        @elseif($reservation->statut === 'annulee') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400
                        @elseif($reservation->statut === 'terminee') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400
                        @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400
                        @endif">
                        @if($reservation->statut === 'confirmee') ✓ Confirmée
                        @elseif($reservation->statut === 'annulee') ✗ Annulée
                        @elseif($reservation->statut === 'terminee') ✓ Terminée
                        @else ⏳ En attente
                        @endif
                    </span>
                </div>
                @if($peutAnnuler)
                    <form action="{{ route('public.reservation.annuler', $reservation->hash ?? $reservation->id) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ?');">
                        @csrf
                        <button type="submit" class="w-full sm:w-auto px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-xl transition-all shadow-lg hover:shadow-xl">
                            Annuler la réservation
                        </button>
                    </form>
                @endif
            </div>

            <!-- Informations de la réservation -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 uppercase mb-3">Qui a réservé</h3>
                    <div class="flex items-center gap-3">
                        @if($reservation->user)
                            <x-avatar :user="$reservation->user" size="lg" />
                        @else
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-green-500 to-orange-500 flex items-center justify-center text-white font-semibold text-lg">
                                {{ strtoupper(substr($reservation->nom_client ?? 'N', 0, 1)) }}
                            </div>
                        @endif
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="font-semibold text-lg text-slate-900 dark:text-white">
                                    @if($reservation->user)
                                        <x-user-name :user="$reservation->user" />
                                    @else
                                        {{ $reservation->nom_client ?? 'N/A' }}
                                    @endif
                                </p>
                                @if($reservation->estPourClienteNonInscrite())
                                    <span class="px-2 py-1 text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 rounded-full">Non inscrit</span>
                                @endif
                                @if($reservation->creee_manuellement)
                                    <span class="px-2 py-1 text-xs bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-400 rounded-full">Manuelle</span>
                                @endif
                            </div>
                            <p class="text-sm text-slate-600 dark:text-slate-400">{{ $reservation->user ? $reservation->user->email : ($reservation->email_client ?? 'N/A') }}</p>
                            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                                📞 {{ $reservation->telephone_client ?? $reservation->telephone_client_non_inscrit ?? 'N/A' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 uppercase mb-3">Détails du service</h3>
                    <p class="text-lg font-semibold text-slate-900 dark:text-white mb-1">{{ $reservation->type_service ?? 'Service' }}</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Durée : {{ $reservation->duree_minutes }} minutes</p>
                    <p class="text-xl font-bold text-green-600 dark:text-green-400 mt-2">{{ number_format($reservation->prix, 2, ',', ' ') }} €</p>
                    @if($reservation->est_paye)
                        <span class="inline-block mt-2 px-3 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-full">
                            ✓ Payé le {{ $reservation->date_paiement->format('d/m/Y') }}
                        </span>
                    @else
                        <span class="inline-block mt-2 px-3 py-1 text-xs bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 rounded-full">
                            ⏳ Non payé
                        </span>
                    @endif
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 uppercase mb-3">Date et heure</h3>
                    <p class="text-lg font-semibold text-slate-900 dark:text-white">{{ $reservation->date_reservation->format('l d F Y') }}</p>
                    <p class="text-xl font-bold text-green-600 dark:text-green-400 mt-1">{{ $reservation->date_reservation->format('H:i') }}</p>
                </div>

                @if($reservation->lieu)
                <div>
                    <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 uppercase mb-3">Lieu</h3>
                    <p class="text-slate-900 dark:text-white">📍 {{ $reservation->lieu }}</p>
                </div>
                @endif

                @if($reservation->membre && $reservation->membre->user)
                <div>
                    <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 uppercase mb-3">Personne assignée</h3>
                    <div class="flex items-center gap-3">
                        <x-avatar :user="$reservation->membre->user" size="md" />
                        <p class="text-slate-900 dark:text-white font-medium">
                            @if($reservation->membre && $reservation->membre->user)
                                <x-user-name :user="$reservation->membre->user" />
                            @else
                                Membre assigné
                            @endif
                        </p>
                    </div>
                </div>
                @endif
            </div>

            @if($reservation->notes)
                <div class="p-4 bg-slate-50 dark:bg-slate-700/50 rounded-xl mb-6">
                    <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 uppercase mb-2">Notes</h3>
                    <p class="text-slate-900 dark:text-white whitespace-pre-line">{{ $reservation->notes }}</p>
                </div>
            @endif
        </div>

        <!-- Informations de l'entreprise -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 p-6 sm:p-8 mb-6">
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-green-500 to-orange-500 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </span>
                Informations de l'entreprise
            </h2>

            <div class="flex flex-col sm:flex-row items-start gap-6 mb-6">
                @if($entreprise->logo)
                    <img src="{{ asset('media/' . $entreprise->logo) }}" alt="{{ $entreprise->nom }}" class="w-24 h-24 rounded-xl object-cover border-2 border-slate-200 dark:border-slate-700 shadow-md flex-shrink-0">
                @else
                    <div class="w-24 h-24 rounded-xl bg-gradient-to-br from-green-500 to-orange-500 flex items-center justify-center text-white text-3xl font-bold flex-shrink-0">
                        {{ strtoupper(substr($entreprise->nom, 0, 1)) }}
                    </div>
                @endif
                <div class="flex-1 min-w-0">
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">{{ $entreprise->nom }}</h3>
                    <p class="text-slate-600 dark:text-slate-400 mb-4">{{ $entreprise->type_activite }} @if($entreprise->ville) • {{ $entreprise->ville }} @endif</p>
                    <a href="{{ route('public.entreprise', $entreprise->slug) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-500 hover:from-green-700 hover:to-emerald-600 text-white font-semibold rounded-xl transition-all shadow-lg hover:shadow-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                        Voir la page de l'entreprise
                    </a>
                </div>
            </div>

            @if($entreprise->description)
                <div class="mb-6">
                    <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 uppercase mb-2">Description</h4>
                    <p class="text-slate-900 dark:text-white whitespace-pre-line">{{ $entreprise->description }}</p>
                </div>
            @endif

            @if($entreprise->adresse || $entreprise->code_postal || $entreprise->ville)
                <div class="mb-6">
                    <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 uppercase mb-2">Adresse</h4>
                    <p class="text-slate-900 dark:text-white">
                        @if($entreprise->adresse){{ $entreprise->adresse }}<br>@endif
                        @if($entreprise->code_postal || $entreprise->ville)
                            {{ $entreprise->code_postal }} {{ $entreprise->ville }}
                        @endif
                    </p>
                </div>
            @endif

            @if($entreprise->telephone)
                <div class="mb-6">
                    <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 uppercase mb-2">Téléphone</h4>
                    <a href="tel:{{ $entreprise->telephone }}" class="text-slate-900 dark:text-white hover:text-green-600 dark:hover:text-green-400 transition">
                        📞 {{ $entreprise->telephone }}
                    </a>
                </div>
            @endif

            @if($entreprise->email)
                <div class="mb-6">
                    <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 uppercase mb-2">Email</h4>
                    <a href="mailto:{{ $entreprise->email }}" class="text-slate-900 dark:text-white hover:text-green-600 dark:hover:text-green-400 transition">
                        ✉️ {{ $entreprise->email }}
                    </a>
                </div>
            @endif

            @if($entreprise->site_web)
                <div class="mb-6">
                    <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 uppercase mb-2">Site web</h4>
                    <a href="{{ $entreprise->site_web }}" target="_blank" rel="noopener noreferrer" class="text-green-600 dark:text-green-400 hover:underline">
                        🌐 {{ $entreprise->site_web }}
                    </a>
                </div>
            @endif

            @if($horaires->count() > 0)
                <div>
                    <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 uppercase mb-3">Horaires d'ouverture</h4>
                    <div class="space-y-2">
                        @php
                            $jours = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
                            $horairesReguliers = $horaires->where('est_exceptionnel', false)->groupBy('jour_semaine');
                        @endphp
                        @for($i = 0; $i < 7; $i++)
                            @php
                                $horairesJour = $horairesReguliers->get($i, collect())->sortBy('ordre_plage');
                            @endphp
                            <div class="flex items-center justify-between py-2 border-b border-slate-200 dark:border-slate-700 last:border-0">
                                <span class="font-medium text-slate-900 dark:text-white">{{ $jours[$i] }}</span>
                                <span class="text-slate-600 dark:text-slate-400">
                                    @if($horairesJour->isEmpty())
                                        Fermé
                                    @else
                                        @foreach($horairesJour as $horaire)
                                            {{ \Carbon\Carbon::parse($horaire->heure_ouverture)->format('H:i') }} - 
                                            {{ \Carbon\Carbon::parse($horaire->heure_fermeture)->format('H:i') }}@if(!$loop->last), @endif
                                        @endforeach
                                    @endif
                                </span>
                            </div>
                        @endfor
                    </div>
                </div>
            @endif
        </div>
    </div>

    @include('partials.footer')
</body>
</html>