{{-- Lignes de détail d'une échéance (réutilisé dans chaque section du checkout) --}}
@if(!empty($calc['lignes']))
    <dl class="space-y-1.5 text-sm mb-3">
        @foreach($calc['lignes'] as $ligne)
            <div class="flex justify-between gap-4">
                <dt class="text-slate-600 dark:text-slate-400">{{ $ligne['label'] }}</dt>
                <dd class="font-medium text-slate-900 dark:text-white tabular-nums">{{ number_format($ligne['montant'], 2, ',', ' ') }} &euro;</dd>
            </div>
        @endforeach
    </dl>
@endif
@if(($calc['reduction_promo'] ?? 0) > 0)
    <p class="text-sm text-green-600 dark:text-green-400 font-medium">&minus; {{ number_format($calc['reduction_promo'], 2, ',', ' ') }} &euro; (code promo)</p>
@endif
@if(($e->reduction_manuel ?? 0) > 0)
    <p class="text-sm text-amber-600 dark:text-amber-400 font-medium">&minus; {{ number_format($e->reduction_manuel, 2, ',', ' ') }} &euro; (r&eacute;duction)</p>
@endif
