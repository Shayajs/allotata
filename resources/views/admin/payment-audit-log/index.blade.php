@extends('admin.layout')

@section('title', 'Journal d\'audit paiements')
@section('header', 'Journal d\'audit paiements')
@section('subheader', 'Traçabilité verbose (charge, carte, 3DS) — protection légale')

@section('content')
<div class="mb-6 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl">
    <p class="text-sm text-amber-800 dark:text-amber-300">
        <strong>Audit complet</strong> : chaque action (setup intent, enregistrement carte, charge, 3DS, confirm) est loguée avec IP, user-agent, IDs Stripe et message. Permet de détecter bugs et de se prémunir légalement.
    </p>
</div>

<form method="GET" action="{{ route('admin.payment-audit-log.index') }}" class="mb-6 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
    <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Membre</label>
            <select name="user_id" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                <option value="">Tous</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->email }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Action</label>
            <select name="action" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                <option value="">Toutes</option>
                @foreach($actions as $a)
                    <option value="{{ $a }}" {{ request('action') === $a ? 'selected' : '' }}>{{ $a }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Payment Intent</label>
            <input type="text" name="stripe_payment_intent_id" value="{{ request('stripe_payment_intent_id') }}" placeholder="pi_..." class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Du</label>
            <input type="date" name="from" value="{{ request('from') }}" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Au</label>
            <input type="date" name="to" value="{{ request('to') }}" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition text-sm">Filtrer</button>
        </div>
    </div>
</form>

<div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-700">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase">Action</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase">Membre</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase">Montant</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase">Statut</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase">Message</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase">IP / Request ID</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                @forelse($logs as $log)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="px-2 py-0.5 text-xs font-medium rounded
                                @if(str_ends_with($log->action, '_ok')) bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400
                                @elseif(str_ends_with($log->action, '_fail')) bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400
                                @else bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300
                                @endif">{{ $log->action }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if($log->user)
                                <a href="{{ route('admin.users.show', $log->user) }}" class="text-green-600 dark:text-green-400 hover:underline">{{ $log->user->name }}</a>
                            @else
                                <span class="text-slate-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                            @if($log->amount !== null)
                                {{ number_format($log->amount, 2, ',', ' ') }} {{ $log->currency ?? '€' }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">{{ $log->status ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400 max-w-xs truncate" title="{{ $log->message }}">{{ Str::limit($log->message, 50) }}</td>
                        <td class="px-4 py-3 text-xs text-slate-500 dark:text-slate-400">
                            <div>{{ $log->ip_address ?? '-' }}</div>
                            @if($log->request_id)
                                <div class="font-mono text-[10px] truncate max-w-[120px]" title="{{ $log->request_id }}">{{ Str::limit($log->request_id, 12) }}</div>
                            @endif
                        </td>
                    </tr>
                    @if($log->context && count($log->context) > 0)
                    <tr class="bg-slate-50/50 dark:bg-slate-900/30">
                        <td colspan="7" class="px-4 py-2 text-xs">
                            <details class="cursor-pointer">
                                <summary class="text-slate-500 dark:text-slate-400">Context (JSON)</summary>
                                <pre class="mt-1 p-2 bg-slate-100 dark:bg-slate-800 rounded overflow-x-auto text-[11px]">{{ json_encode($log->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </details>
                        </td>
                    </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">Aucun log pour ces filtres.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
        <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-700">{{ $logs->links() }}</div>
    @endif
</div>
@endsection
