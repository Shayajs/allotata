@php
    $reservation = $reservation ?? null;
    $canModify = ($isGerant || ($entreprise->prix_negociables && !$isGerant));
    $hasActiveProposition = isset($propositionActive) && $propositionActive && in_array($propositionActive->statut, ['proposee', 'negociee']);
@endphp

@if($reservation)
    <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-lg rounded-2xl shadow-xl border border-slate-200/50 dark:border-slate-700/50 p-6 sticky top-6">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white text-xl shadow-md">
                📋
            </div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white">Détails de la réservation</h3>
        </div>

        <!-- Informations de la réservation -->
        <div class="space-y-4 mb-6">
            <div class="p-4 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Service</p>
                <p class="font-bold text-lg text-slate-900 dark:text-white">
                    {{ $reservation->type_service ?? 'Service' }}
                </p>
                @if($reservation->typeService)
                    <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                        {{ $reservation->typeService->description ?? '' }}
                    </p>
                @endif
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="p-4 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Date</p>
                    <p class="font-bold text-slate-900 dark:text-white">
                        {{ $reservation->date_reservation->format('d/m/Y') }}
                    </p>
                </div>
                <div class="p-4 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Heure</p>
                    <p class="font-bold text-slate-900 dark:text-white">
                        {{ $reservation->date_reservation->format('H:i') }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="p-4 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Durée</p>
                    <p class="font-bold text-slate-900 dark:text-white">
                        {{ $reservation->duree_minutes ?? 30 }} min
                    </p>
                </div>
                <div class="p-4 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Prix</p>
                    <p class="font-bold text-green-600 dark:text-green-400">
                        {{ number_format($reservation->prix, 2, ',', ' ') }} €
                    </p>
                </div>
            </div>

            @if($reservation->lieu)
                <div class="p-4 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Lieu</p>
                    <p class="font-semibold text-slate-900 dark:text-white">{{ $reservation->lieu }}</p>
                </div>
            @endif

            @if($reservation->notes)
                <div class="p-4 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Notes</p>
                    <p class="text-sm text-slate-900 dark:text-white">{{ $reservation->notes }}</p>
                </div>
            @endif

            <div class="p-4 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Statut</p>
                <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full
                    @if($reservation->statut === 'confirmee') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                    @elseif($reservation->statut === 'annulee') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400
                    @elseif($reservation->statut === 'terminee') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400
                    @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400
                    @endif">
                    @if($reservation->statut === 'confirmee') Confirmée
                    @elseif($reservation->statut === 'annulee') Annulée
                    @elseif($reservation->statut === 'terminee') Terminée
                    @else En attente
                    @endif
                </span>
            </div>
        </div>

        <!-- Proposition active -->
        @if($propositionActive && in_array($propositionActive->statut, ['proposee', 'negociee']))
            <div class="mb-6 p-4 bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 border-2 border-amber-300 dark:border-amber-700 rounded-xl">
                <h4 class="font-bold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                    <span class="text-xl">📝</span>
                    Proposition en cours
                </h4>
                <div class="space-y-2 text-sm mb-4">
                    <p><strong>Date :</strong> {{ $propositionActive->date_rdv->format('d/m/Y') }}</p>
                    <p><strong>Heure :</strong> {{ \Carbon\Carbon::parse($propositionActive->heure_debut)->format('H:i') }} - {{ \Carbon\Carbon::parse($propositionActive->heure_fin)->format('H:i') }}</p>
                    <p><strong>Durée :</strong> {{ $propositionActive->duree_minutes }} min</p>
                    <p><strong>Prix :</strong> <span class="font-bold text-green-600 dark:text-green-400">{{ number_format($propositionActive->prix_propose, 2, ',', ' ') }} €</span></p>
                    @if($propositionActive->lieu)
                        <p><strong>Lieu :</strong> {{ $propositionActive->lieu }}</p>
                    @endif
                </div>
            </div>
        @endif

        <!-- Actions de modification -->
        @if($reservation->statut === 'en_attente' && $canModify)
            <div class="space-y-3">
                @if($hasActiveProposition)
                    <div class="p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl mb-3">
                        <p class="text-xs text-amber-800 dark:text-amber-300">
                            ⚠️ Une proposition est déjà en cours. Vous pouvez créer une contre-proposition.
                        </p>
                    </div>
                @endif
                <button 
                    onclick="openModifyPropositionModal({{ $reservation->id }}, '{{ $reservation->date_reservation->format('Y-m-d') }}', '{{ $reservation->date_reservation->format('H:i') }}', {{ $reservation->duree_minutes ?? 30 }}, {{ number_format($reservation->prix, 2, '.', '') }}, {!! json_encode($reservation->lieu ?? '') !!}, {!! json_encode($reservation->notes ?? '') !!}, {{ $isGerant ? 'true' : 'false' }}, {{ $reservation->type_service_id ?? 'null' }})"
                    class="w-full px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition flex items-center justify-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    {{ $hasActiveProposition ? 'Créer une contre-proposition' : 'Modifier la proposition' }}
                </button>
            </div>
        @endif
    </div>
@endif
