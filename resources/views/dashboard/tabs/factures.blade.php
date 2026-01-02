{{-- Onglet Factures --}}
<div>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Mes Factures</h2>
    </div>

    @php
        $userFactures = \App\Models\Facture::where('user_id', $user->id)
            ->with(['reservation.entreprise'])
            ->orderBy('created_at', 'desc')
            ->get();
    @endphp

    @if($userFactures->count() > 0)
        <div class="space-y-4">
            @foreach($userFactures as $facture)
                <div class="p-5 border border-slate-200 dark:border-slate-700 rounded-lg hover:border-green-500 dark:hover:border-green-500 transition-all">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                                        Facture #{{ $facture->numero ?? $facture->id }}
                                    </h3>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">
                                        {{ $facture->reservation->entreprise->nom ?? 'Entreprise' }}
                                    </p>
                                </div>
                                <span class="px-3 py-1 text-xs font-medium rounded-full
                                    @if($facture->statut === 'payee') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                                    @elseif($facture->statut === 'annulee') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400
                                    @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400
                                    @endif">
                                    @if($facture->statut === 'payee') Payée
                                    @elseif($facture->statut === 'annulee') Annulée
                                    @else En attente
                                    @endif
                                </span>
                            </div>
                            
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mt-4">
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="text-sm text-slate-600 dark:text-slate-400">
                                        {{ $facture->created_at->format('d/m/Y') }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-sm font-semibold text-slate-900 dark:text-white">
                                        {{ number_format($facture->montant_ttc ?? $facture->montant, 2, ',', ' ') }} €
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex gap-2">
                            <a href="{{ route('factures.show', $facture->id) }}" class="px-4 py-2 text-sm font-medium bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white rounded-lg transition">
                                Voir
                            </a>
                            @if($facture->pdf_path)
                                <a href="{{ route('factures.download', $facture->id) }}" class="px-4 py-2 text-sm font-medium bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white rounded-lg transition">
                                    📄 PDF
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-slate-900 dark:text-white">Aucune facture</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Vos factures apparaîtront ici après vos paiements.
            </p>
        </div>
    @endif
</div>
