@extends('admin.layout')

@section('title', 'Logs SMS')
@section('header', 'Logs SMS')
@section('subheader', 'Consultez l\'historique des SMS envoyés et testez l\'envoi')

@section('content')
<!-- Configuration SMS -->
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Configuration SMS</h2>
            <p class="text-sm text-slate-600 dark:text-slate-400">
                Mode actuel : 
                <span class="font-medium {{ $currentMode === 'twilio' ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400' }}">
                    {{ $currentMode === 'twilio' ? 'Production (Twilio)' : 'Test (Log)' }}
                </span>
            </p>
            @if($currentMode === 'log')
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Les SMS sont écrits dans laravel.log, aucun SMS réel n'est envoyé.</p>
            @else
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Les SMS sont envoyés via Twilio. Assurez-vous que les credentials sont configurés.</p>
            @endif
        </div>
        <div class="flex items-center gap-4">
            <form method="POST" action="{{ route('admin.sms-logs.mode.update') }}" id="smsModeForm" class="flex items-center gap-3">
                @csrf
                <input type="hidden" name="mode" id="smsModeInput" value="{{ $currentMode }}">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input 
                        type="checkbox" 
                        class="sr-only peer" 
                        {{ $currentMode === 'twilio' ? 'checked' : '' }}
                        onchange="document.getElementById('smsModeInput').value = this.checked ? 'twilio' : 'log'; document.getElementById('smsModeForm').submit();"
                    >
                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-green-600"></div>
                    <span class="ml-3 text-sm font-medium text-slate-700 dark:text-slate-300">
                        {{ $currentMode === 'twilio' ? 'Production' : 'Test' }}
                    </span>
                </label>
            </form>
            <button onclick="document.getElementById('testSmsModal').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                Envoyer un SMS de test
            </button>
        </div>
    </div>
</div>

    <!-- Statistiques -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="text-sm text-slate-600 dark:text-slate-400">Total</div>
            <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ $stats['total'] }}</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="text-sm text-slate-600 dark:text-slate-400">Envoyés</div>
            <div class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ $stats['envoyes'] }}</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="text-sm text-slate-600 dark:text-slate-400">Échecs</div>
            <div class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">{{ $stats['echecs'] }}</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="text-sm text-slate-600 dark:text-slate-400">En attente</div>
            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">{{ $stats['en_attente'] }}</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="text-sm text-slate-600 dark:text-slate-400">Aujourd'hui</div>
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $stats['aujourd_hui'] }}</div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 mb-6">
        <form method="GET" action="{{ route('admin.sms-logs.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Statut</label>
                <select name="statut" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
                    <option value="">Tous</option>
                    <option value="envoye" {{ request('statut') == 'envoye' ? 'selected' : '' }}>Envoyé</option>
                    <option value="echec" {{ request('statut') == 'echec' ? 'selected' : '' }}>Échec</option>
                    <option value="en_attente" {{ request('statut') == 'en_attente' ? 'selected' : '' }}>En attente</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Provider</label>
                <select name="provider" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
                    <option value="">Tous</option>
                    <option value="twilio" {{ request('provider') == 'twilio' ? 'selected' : '' }}>Twilio</option>
                    <option value="log" {{ request('provider') == 'log' ? 'selected' : '' }}>Log</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Destinataire</label>
                <input type="text" name="destinataire" value="{{ request('destinataire') }}" placeholder="Numéro..." class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
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
                <button type="submit" class="ui-btn-simple bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">Filtrer</button>
                <a href="{{ route('admin.sms-logs.index') }}" class="bg-slate-300 hover:bg-slate-400 dark:bg-slate-600 dark:hover:bg-slate-500 text-slate-700 dark:text-slate-200 px-4 py-2 rounded-lg font-medium">Réinitialiser</a>
            </div>
        </form>
    </div>

    <!-- Tableau des logs -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Destinataire</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Message</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Statut</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Provider</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Informations</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse($logs as $log)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 dark:text-white">
                                {{ $log->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 dark:text-white">
                                {{ $log->destinataire }}
                                @if($log->user)
                                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ $log->user->name }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400 max-w-md">
                                <div class="truncate" title="{{ $log->message }}">{{ Str::limit($log->message, 80) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($log->statut === 'envoye')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Envoyé</span>
                                @elseif($log->statut === 'echec')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Échec</span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">En attente</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">
                                <span class="px-2 py-1 text-xs font-mono rounded bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                                    {{ $log->provider }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">
                                @if($log->reservation)
                                    <div class="text-xs">
                                        <a href="{{ route('reservations.show', [$log->reservation->entreprise->slug, $log->reservation->id]) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400">
                                            Réservation #{{ $log->reservation->id }}
                                        </a>
                                    </div>
                                @endif
                                @if($log->provider_message_id)
                                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                        ID: {{ Str::limit($log->provider_message_id, 20) }}
                                    </div>
                                @endif
                                @if($log->error_message)
                                    <div class="text-xs text-red-600 dark:text-red-400 mt-1" title="{{ $log->error_message }}">
                                        <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                        </svg>
                                        {{ Str::limit($log->error_message, 40) }}
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-sm text-slate-500 dark:text-slate-400">
                                Aucun log SMS trouvé.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        @if($logs->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Test SMS -->
<div id="testSmsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-md w-full mx-4 p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">Envoyer un SMS de test</h2>
            <button onclick="document.getElementById('testSmsModal').classList.add('hidden')" class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form method="POST" action="{{ route('admin.sms-logs.test') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Numéro de téléphone</label>
                <input type="text" name="telephone" value="+33612345678" required class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white" placeholder="+33612345678">
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Format international requis (ex: +33612345678)</p>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Message (optionnel)</label>
                <textarea name="message" rows="3" class="ui-textarea w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white" placeholder="Message par défaut si vide"></textarea>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="ui-btn-simple flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    Envoyer
                </button>
                <button type="button" onclick="document.getElementById('testSmsModal').classList.add('hidden')" class="bg-slate-300 hover:bg-slate-400 dark:bg-slate-600 dark:hover:bg-slate-500 text-slate-700 dark:text-slate-200 px-4 py-2 rounded-lg font-medium">
                    Annuler
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
