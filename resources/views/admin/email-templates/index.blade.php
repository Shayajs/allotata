@extends('admin.layout')

@section('title', 'Gestion des emails')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white flex items-center gap-3">
                <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                Gestion des emails
            </h1>
            <p class="text-slate-600 dark:text-slate-400 mt-2">Gérez vos templates d'emails et envoyez des emails personnalisés</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.email-templates.compose') }}" 
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-semibold rounded-xl shadow-lg shadow-orange-500/25 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                </svg>
                Composer un email
            </a>
            <a href="{{ route('admin.email-templates.create') }}" 
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-semibold rounded-xl shadow-lg shadow-green-500/25 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nouveau template
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-xl flex items-center gap-3">
            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-green-800 dark:text-green-200">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-xl flex items-center gap-3">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-red-800 dark:text-red-200">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-green-100 dark:bg-green-900/50 rounded-lg">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Templates actifs</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $templates->where('is_active', true)->count() }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-slate-100 dark:bg-slate-700 rounded-lg">
                    <svg class="w-6 h-6 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Total templates</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $templates->count() }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-blue-100 dark:bg-blue-900/50 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Emails envoyés (30j)</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $emailsSent ?? 0 }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-orange-100 dark:bg-orange-900/50 rounded-lg">
                    <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Dernier envoi</p>
                    <p class="text-lg font-semibold text-slate-900 dark:text-white">{{ $lastSent ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Templates Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @foreach($templates as $template)
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-md transition-shadow">
                <div class="p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-gradient-to-br from-green-100 to-green-50 dark:from-green-900/50 dark:to-green-800/30 rounded-lg">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-slate-900 dark:text-white">{{ $template->name }}</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 font-mono">{{ $template->type }}</p>
                            </div>
                        </div>
                        @if($template->is_active)
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400">
                                Actif
                            </span>
                        @else
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-400">
                                Inactif
                            </span>
                        @endif
                    </div>
                    
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-3">
                        <strong>Sujet :</strong> {{ Str::limit($template->subject, 50) }}
                    </p>
                    
                    @if($template->description)
                        <p class="text-sm text-slate-500 dark:text-slate-500 mb-4">{{ Str::limit($template->description, 100) }}</p>
                    @endif
                    
                    @if($template->variables && count($template->variables) > 0)
                        <div class="flex flex-wrap gap-1.5 mb-4">
                            @foreach(array_slice($template->variables, 0, 5) as $var)
                                <span class="px-2 py-0.5 text-xs font-mono bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 rounded">
                                    {{'{'}}{{ $var }}{{'}'}}
                                </span>
                            @endforeach
                            @if(count($template->variables) > 5)
                                <span class="px-2 py-0.5 text-xs bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 rounded">
                                    +{{ count($template->variables) - 5 }}
                                </span>
                            @endif
                        </div>
                    @endif
                    
                    <div class="flex items-center gap-2 pt-4 border-t border-slate-200 dark:border-slate-700">
                        <a href="{{ route('admin.email-templates.edit', $template) }}" 
                           class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Modifier
                        </a>
                        <a href="{{ route('admin.email-templates.preview', $template) }}" 
                           target="_blank"
                           class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium text-green-700 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/30 rounded-lg transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            Prévisualiser
                        </a>
                        <button type="button" 
                                onclick="sendTestEmail({{ $template->id }})"
                                class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium text-orange-700 dark:text-orange-400 hover:bg-orange-50 dark:hover:bg-orange-900/30 rounded-lg transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                            Test
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<!-- Modal Test Email -->
<div id="testEmailModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-md">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Envoyer un email de test</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">L'email sera envoyé avec des données d'exemple</p>
        </div>
        <form id="testEmailForm" method="POST" action="{{ route('admin.email-templates.test', ['emailTemplate' => 0]) }}">
            @csrf
            <div class="p-6 space-y-4">
                <div>
                    <label for="test_email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Adresse email
                    </label>
                    <input type="email" name="email" id="test_email" value="{{ auth()->user()->email }}" required
                           class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-slate-700 dark:text-white">
                </div>
            </div>
            <div class="p-6 pt-0 flex gap-3">
                <button type="button" onclick="closeTestModal()" 
                        class="flex-1 px-4 py-2.5 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition">
                    Annuler
                </button>
                <button type="submit" 
                        class="flex-1 px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
                    Envoyer
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function sendTestEmail(templateId) {
    const modal = document.getElementById('testEmailModal');
    const form = document.getElementById('testEmailForm');
    form.action = form.action.replace(/\/\d+\/test$/, '/' + templateId + '/test');
    modal.classList.remove('hidden');
}

function closeTestModal() {
    document.getElementById('testEmailModal').classList.add('hidden');
}

// Close modal on outside click
document.getElementById('testEmailModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeTestModal();
    }
});
</script>
@endsection
