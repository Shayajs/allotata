@extends('admin.layout')

@php
    use Illuminate\Support\Str;
@endphp

@section('title', 'Logs Emails')
@section('header', '📧 Logs Emails')
@section('subheader', 'Consultez l\'historique des emails envoyés et vérifiez manuellement les emails')

@section('content')
    <!-- Statistiques -->
    <div class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-6">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="text-sm text-slate-600 dark:text-slate-400">Total</div>
            <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ $stats['total'] }}</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="text-sm text-slate-600 dark:text-slate-400">Envoyés</div>
            <div class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ $stats['sent'] }}</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="text-sm text-slate-600 dark:text-slate-400">Échecs</div>
            <div class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">{{ $stats['failed'] }}</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="text-sm text-slate-600 dark:text-slate-400">En attente</div>
            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">{{ $stats['pending'] }}</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="text-sm text-slate-600 dark:text-slate-400">Vérification</div>
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $stats['verification'] }}</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="text-sm text-slate-600 dark:text-slate-400">Réinitialisation</div>
            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1">{{ $stats['password_reset'] }}</div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 mb-6">
        <form method="GET" action="{{ route('admin.email-logs.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Statut</label>
                <select name="status" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
                    <option value="">Tous</option>
                    <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Envoyé</option>
                    <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Échec</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>En attente</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Type</label>
                <select name="type" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
                    <option value="">Tous</option>
                    <option value="verification" {{ request('type') == 'verification' ? 'selected' : '' }}>Vérification</option>
                    <option value="password_reset" {{ request('type') == 'password_reset' ? 'selected' : '' }}>Réinitialisation</option>
                    <option value="welcome" {{ request('type') == 'welcome' ? 'selected' : '' }}>Bienvenue</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Email destinataire</label>
                <input type="text" name="recipient_email" value="{{ request('recipient_email') }}" placeholder="email@example.com" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Date début</label>
                <input type="date" name="date_debut" value="{{ request('date_debut') }}" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Date fin</label>
                <input type="date" name="date_fin" value="{{ request('date_fin') }}" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
            </div>
            <div class="md:col-span-5 flex gap-2">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium">
                    🔍 Filtrer
                </button>
                <a href="{{ route('admin.email-logs.index') }}" class="bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 px-4 py-2 rounded-lg font-medium">
                    🗑️ Réinitialiser
                </a>
            </div>
        </form>
    </div>

    <!-- Liste des logs -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Destinataire</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Sujet</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Statut</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse($logs as $log)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 dark:text-white">
                                {{ $log->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-slate-900 dark:text-white">{{ $log->recipient_email }}</div>
                                @if($log->user)
                                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ $log->user->name }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-slate-900 dark:text-white">{{ Str::limit($log->subject, 50) }}</div>
                                @if($log->content_preview)
                                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ Str::limit($log->content_preview, 100) }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    @if($log->type === 'verification') bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400
                                    @elseif($log->type === 'password_reset') bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400
                                    @elseif($log->type === 'welcome') bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400
                                    @else bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-400
                                    @endif">
                                    {{ ucfirst(str_replace('_', ' ', $log->type ?? 'Autre')) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($log->status === 'sent')
                                    <span class="px-2 py-1 text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full">✓ Envoyé</span>
                                @elseif($log->status === 'failed')
                                    <span class="px-2 py-1 text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full">✗ Échec</span>
                                @else
                                    <span class="px-2 py-1 text-xs font-medium bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded-full">⏳ En attente</span>
                                @endif
                                @if($log->sent_at)
                                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ $log->sent_at->format('H:i') }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($log->user && !$log->user->hasVerifiedEmail() && $log->type === 'verification')
                                    <button 
                                        onclick="document.getElementById('verifyModal-{{ $log->id }}').classList.remove('hidden')"
                                        class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium"
                                        title="Vérifier manuellement cet email"
                                    >
                                        ✓ Vérifier
                                    </button>
                                    
                                    <!-- Modal de vérification -->
                                    <div id="verifyModal-{{ $log->id }}" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                                        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-md w-full p-6">
                                            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Vérifier manuellement l'email</h3>
                                            <form action="{{ route('admin.email-logs.verify-user', $log->user->id) }}" method="POST">
                                                @csrf
                                                <div class="mb-4">
                                                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-2">
                                                        Voulez-vous marquer l'email de <strong>{{ $log->user->email }}</strong> comme vérifié ?
                                                    </p>
                                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                        Raison (optionnel)
                                                    </label>
                                                    <textarea name="reason" rows="3" placeholder="Ex: Vérifié par téléphone, client confirmé..." class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white"></textarea>
                                                </div>
                                                <div class="flex gap-3">
                                                    <button type="button" onclick="document.getElementById('verifyModal-{{ $log->id }}').classList.add('hidden')" class="flex-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 px-4 py-2 rounded-lg font-medium">
                                                        Annuler
                                                    </button>
                                                    <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium">
                                                        Vérifier
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endif
                                @if($log->error_message)
                                    <button onclick="alert('{{ addslashes($log->error_message) }}')" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" title="Voir l'erreur">
                                        ⚠️
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-slate-500 dark:text-slate-400">
                                Aucun log email trouvé.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700">
            {{ $logs->links() }}
        </div>
    </div>
@endsection
