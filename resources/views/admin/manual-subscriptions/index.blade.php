@extends('admin.layout')

@section('title', 'Paiements manuels')
@section('header', 'Paiements manuels à vérifier')
@section('subheader', 'Le membre reste actif tant que le paiement n\'est pas explicitement marqué non réalisé.')

@section('content')
<div class="space-y-4">
    @if(session('success'))
        <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-green-800 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-red-800 dark:text-red-300">
            {{ session('error') }}
        </div>
    @endif

    @forelse($periodes as $periode)
        @php
            $user = $periode->user;
            $days = $subscriptions->daysLate($periode);
            $history = ($events[$user->id] ?? collect())->take(3);
        @endphp
        <article class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 shadow-sm">
            <div class="flex flex-col lg:flex-row lg:items-start gap-5">
                <div class="flex items-start gap-4 flex-1 min-w-0">
                    <x-avatar :user="$user" size="lg" />
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ $user->full_name }}</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ $user->email }}</p>
                        <p class="text-sm text-slate-700 dark:text-slate-300 mt-2">
                            Abonnement manuel depuis le {{ $user->abonnement_manuel_date_debut?->format('d/m/Y') ?? '—' }}
                        </p>
                        <dl class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1 text-sm">
                            <div><dt class="inline text-slate-500">Paiement</dt> <dd class="inline font-semibold text-slate-900 dark:text-white">{{ ucfirst($periode->libelleMois()) }}</dd></div>
                            <div><dt class="inline text-slate-500">Échéance</dt> <dd class="inline font-semibold text-slate-900 dark:text-white">{{ $periode->echeance_at->format('d/m/Y') }}</dd></div>
                            <div><dt class="inline text-slate-500">Retard</dt> <dd class="inline font-semibold text-slate-900 dark:text-white">{{ $days }} jour{{ $days > 1 ? 's' : '' }}</dd></div>
                            <div><dt class="inline text-slate-500">État</dt> <dd class="inline font-semibold text-slate-900 dark:text-white">{{ $periode->libelleStatut() }}</dd></div>
                            <div><dt class="inline text-slate-500">Paiement déclaré</dt> <dd class="inline font-semibold text-slate-900 dark:text-white">{{ $periode->statut === \App\Models\AbonnementManuelPeriode::MEMBER_DECLARED ? 'oui' : 'non' }}</dd></div>
                        </dl>
                        @if($history->isNotEmpty())
                            <ul class="mt-3 text-xs text-slate-500 dark:text-slate-400 space-y-1">
                                @foreach($history as $event)
                                    <li>{{ $event->created_at->format('d/m/Y H:i') }} — {{ $event->message }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row lg:flex-col gap-2 shrink-0">
                    <form method="POST" action="{{ route('admin.manual-subscriptions.confirm', $periode) }}">
                        @csrf
                        <button type="submit" class="ui-btn-simple w-full px-4 py-2.5 rounded-lg bg-green-600 hover:bg-green-700 text-white text-sm font-semibold">
                            Paiement réalisé
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.manual-subscriptions.refuse', $periode) }}" onsubmit="return confirm('Marquer ce paiement comme non réalisé ? L\'abonnement manuel sera annulé et les avantages retirés.');">
                        @csrf
                        <button type="submit" class="ui-btn-simple w-full px-4 py-2.5 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 text-sm font-semibold hover:bg-red-100">
                            Paiement non réalisé
                        </button>
                    </form>
                </div>
            </div>
        </article>
    @empty
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-10 text-center text-slate-500 dark:text-slate-400">
            Aucun paiement manuel à vérifier.
        </div>
    @endforelse
</div>
@endsection
