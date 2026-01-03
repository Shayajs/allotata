<div class="space-y-4 lg:space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <h3 class="text-lg lg:text-xl font-bold text-slate-900 dark:text-white">Historique des réservations</h3>
        <span class="inline-flex px-3 py-1 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 rounded-full text-[10px] font-bold w-fit">{{ $user->reservations->count() }} total</span>
    </div>

    @if($user->reservations->count() > 0)
        <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-100 dark:border-slate-700 shadow-sm overflow-x-auto">
            <table class="w-full text-left min-w-[500px]">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-700">
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Entreprise</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Date</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right">Montant</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
                    @foreach($user->reservations->sortByDesc('date_reservation')->take(10) as $reservation)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/20 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-900 dark:text-white text-sm truncate max-w-[150px]">{{ $reservation->entreprise->nom }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-xs text-slate-600 dark:text-slate-400 font-medium">{{ $reservation->date_reservation->format('d/m/Y') }}</div>
                                <div class="text-[10px] text-slate-400">{{ $reservation->date_reservation->format('H:i') }}</div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="font-bold text-slate-900 dark:text-white text-sm">{{ number_format($reservation->prix, 2, ',', ' ') }} €</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.reservations.show', $reservation) }}" class="text-[10px] font-bold text-green-600 hover:text-green-700 uppercase tracking-wider">Détails</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if($user->reservations->count() > 10)
                <div class="p-4 text-center border-t border-slate-50 dark:border-slate-700 bg-slate-50/30">
                    <p class="text-[10px] text-slate-400 italic font-medium">Affichage des 10 dernières sur {{ $user->reservations->count() }}</p>
                </div>
            @endif
        </div>
    @else
        <div class="bg-slate-50 dark:bg-slate-900/50 rounded-3xl p-12 text-center border-2 border-dashed border-slate-200 dark:border-slate-700">
            <span class="text-4xl mb-4 block">📅</span>
            <p class="text-slate-500 font-medium">Aucune réservation.</p>
        </div>
    @endif
</div>
