@auth
    @php
        $manualAttention = app(\App\Services\ManualSubscriptionService::class)->attention(auth()->user());
    @endphp
    @if($manualAttention)
        @php
            $level = $manualAttention['level'];
            $echeanceLabel = $manualAttention['echeance']->locale('fr')->translatedFormat('j F Y');
            $styles = [
                'yellow' => 'bg-amber-50 dark:bg-amber-950/40 border-amber-300 dark:border-amber-700 text-amber-950 dark:text-amber-100',
                'red' => 'bg-red-50 dark:bg-red-950/40 border-red-300 dark:border-red-700 text-red-950 dark:text-red-100',
                'cancelled' => 'bg-red-600 text-white border-red-700',
                'declared' => 'bg-sky-50 dark:bg-sky-950/40 border-sky-300 dark:border-sky-700 text-sky-950 dark:text-sky-100',
            ];
        @endphp
        <div class="border-b {{ $styles[$level] ?? $styles['yellow'] }}">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex flex-col sm:flex-row sm:items-center gap-3">
                <div class="flex-1 text-sm leading-relaxed">
                    @if($level === 'cancelled')
                        <p class="font-semibold">Abonnement manuel annulé</p>
                        <p>Le paiement de l'échéance du {{ $echeanceLabel }} n'a pas été confirmé. Les avantages associés sont suspendus. Votre compte et votre historique sont conservés.</p>
                    @elseif($level === 'red')
                        <p class="font-semibold">Paiement manuel à signaler</p>
                        <p>Votre échéance du {{ $echeanceLabel }} n'a pas encore été confirmée{{ $manualAttention['days'] > 0 ? ' ('.$manualAttention['days'].' jours)' : '' }}. Votre accès reste actif. Si vous avez déjà effectué votre paiement, signalez-le ou contactez l'administration.</p>
                    @elseif($level === 'declared')
                        <p class="font-semibold">Paiement signalé</p>
                        <p>Votre déclaration pour l'échéance du {{ $echeanceLabel }} est en cours de vérification. Votre accès reste actif.</p>
                    @else
                        <p class="font-semibold">Votre paiement mensuel manuel doit être vérifié.</p>
                        <p>L'échéance du {{ $echeanceLabel }} attend une vérification. Votre accès reste actif. Si vous avez effectué votre paiement, vous pouvez le signaler à l'administration.</p>
                    @endif
                </div>
                @if($level !== 'declared')
                    <form method="POST" action="{{ route('subscription.manual.declare') }}" class="shrink-0">
                        @csrf
                        <button type="submit" class="ui-btn-simple px-4 py-2 rounded-lg text-sm font-semibold {{ $level === 'cancelled' ? 'bg-white text-red-700 hover:bg-red-50' : 'bg-slate-900 text-white hover:bg-slate-800' }}">
                            J'ai effectué le paiement manuel
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @endif
@endauth
