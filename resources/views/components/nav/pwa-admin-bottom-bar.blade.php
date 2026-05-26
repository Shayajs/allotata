{{-- Bottom Tab Bar Admin – Visible uniquement en PWA mobile via CSS --}}
@php
    $currentRoute = request()->route()?->getName() ?? '';

    $bottomItems = [
        [
            'label' => 'Dashboard',
            'short' => 'Admin',
            'icon'  => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
            'route' => 'admin.index',
            'match' => 'admin.index',
        ],
        [
            'label' => 'Utilisateurs',
            'short' => 'Users',
            'icon'  => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
            'route' => 'admin.users.index',
            'match' => 'admin.users.*',
        ],
        [
            'label' => 'Entreprises',
            'short' => 'Entrep.',
            'icon'  => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
            'route' => 'admin.entreprises.index',
            'match' => 'admin.entreprises.*',
        ],
        [
            'label' => 'Tickets',
            'short' => 'Tickets',
            'icon'  => 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z',
            'route' => 'admin.tickets.index',
            'match' => 'admin.tickets.*',
            'badge_query' => true,
        ],
    ];

    $moreGroups = [
        'Gestion' => [
            ['label' => 'Kanban',             'icon' => 'M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2', 'route' => 'admin.kanban.index', 'match' => 'admin.kanban.*'],
            ['label' => 'Notes',              'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'route' => 'admin.notes.index', 'match' => 'admin.notes.*'],
            ['label' => 'Réservations',       'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'route' => 'admin.reservations.index', 'match' => 'admin.reservations.*'],
        ],
        'Communication' => [
            ['label' => 'Contacts',           'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 'route' => 'admin.contacts.index', 'match' => 'admin.contacts.*'],
            ['label' => 'Messagerie interne', 'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z', 'route' => 'admin.messagerie-interne.index', 'match' => 'admin.messagerie-interne.*'],
            ['label' => 'Forum',              'icon' => 'M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z', 'route' => 'admin.forum.index', 'match' => 'admin.forum.*'],
            ['label' => 'Emails & Templates', 'icon' => 'M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z', 'route' => 'admin.email-templates.index', 'match' => 'admin.email-templates.*'],
            ['label' => 'Notifications Push', 'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9', 'route' => 'admin.push-notifications.index', 'match' => 'admin.push-notifications.*'],
        ],
        'Contenu' => [
            ['label' => 'FAQs',               'icon' => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'route' => 'admin.faqs.index', 'match' => 'admin.faqs.*'],
            ['label' => 'Annonces',           'icon' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z', 'route' => 'admin.announcements.index', 'match' => 'admin.announcements.*'],
            ['label' => 'Cours',              'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', 'route' => 'admin.courses.index', 'match' => 'admin.courses.*'],
            ['label' => 'Médiathèque',        'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z', 'route' => 'admin.media.index', 'match' => 'admin.media.*'],
        ],
        'Finances' => [
            ['label' => 'Finances Entreprises','icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'route' => 'admin.finances.index', 'match' => 'admin.finances.*'],
            ['label' => 'Factures',           'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'route' => 'admin.factures.index', 'match' => 'admin.factures.*'],
            ['label' => 'Statistiques',       'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'route' => 'admin.statistiques.index', 'match' => 'admin.statistiques.*'],
        ],
        'Abonnements' => [
            ['label' => 'Abonnements',        'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', 'route' => 'admin.subscriptions.index', 'match' => 'admin.subscriptions.*'],
            ['label' => 'Paiements',          'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', 'route' => 'admin.echeances.index', 'match' => 'admin.echeances.*'],
            ['label' => 'Essais gratuits',    'icon' => 'M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7', 'route' => 'admin.essais-gratuits.index', 'match' => 'admin.essais-gratuits.*'],
            ['label' => 'Codes promo',        'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z', 'route' => 'admin.promo-codes.index', 'match' => 'admin.promo-codes.*'],
            ['label' => 'Tarifs',             'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', 'route' => 'admin.stripe-prices.index', 'match' => 'admin.stripe-prices.*'],
        ],
        'Système' => [
            ['label' => 'Erreurs',            'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z', 'route' => 'admin.errors.index', 'match' => 'admin.errors.*'],
            ['label' => 'Logs d\'activité',   'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01', 'route' => 'admin.activity-logs.index', 'match' => 'admin.activity-logs.*'],
            ['label' => 'Logs Emails',        'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 'route' => 'admin.email-logs.index', 'match' => 'admin.email-logs.*'],
            ['label' => 'Logs SMS',           'icon' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z', 'route' => 'admin.sms-logs.index', 'match' => 'admin.sms-logs.*'],
            ['label' => 'Tâches CRON',        'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'route' => 'admin.scheduled-tasks.index', 'match' => 'admin.scheduled-tasks.*'],
            ['label' => 'Exports',            'icon' => 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4', 'route' => 'admin.exports.index', 'match' => 'admin.exports.*'],
            ['label' => 'RGPD',               'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'route' => 'admin.gdpr.index', 'match' => 'admin.gdpr.*'],
            ['label' => 'Paramètres',         'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z', 'route' => 'admin.settings.index', 'match' => 'admin.settings.*', 'icon_extra' => 'M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
            ['label' => 'Base de données',    'icon' => 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4', 'route' => 'admin.database.index', 'match' => 'admin.database.*'],
        ],
        'Outils' => [
            ['label' => 'Documentation Dev',  'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', 'route' => 'dev.index', 'match' => 'dev.*'],
            ['label' => 'BrightShell ERP',    'icon' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z', 'route' => 'brightshell.index', 'match' => 'brightshell.*', 'special_color' => '#4a6fa5'],
        ],
    ];

    $ticketsOuverts = \App\Models\Ticket::where('statut', 'ouvert')->count();
@endphp

<div class="pwa-bottom-bar fixed bottom-0 left-0 right-0 z-50 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700 shadow-[0_-2px_10px_rgba(0,0,0,0.08)]" style="padding-bottom: env(safe-area-inset-bottom, 0px);">
    <nav class="flex items-stretch justify-around h-16 w-full px-2" aria-label="Navigation admin">
        @foreach($bottomItems as $bItem)
            @php
                $isActive = request()->routeIs($bItem['match']);
            @endphp
            <a
                href="{{ route($bItem['route']) }}"
                class="pwa-tab-btn flex flex-col items-center justify-center flex-1 gap-0.5 relative transition-colors {{ $isActive ? 'text-green-600 dark:text-green-400' : 'text-slate-400 dark:text-slate-500' }}"
            >
                @if($isActive)
                    <span class="pwa-active-indicator absolute top-0 left-1/2 -translate-x-1/2 w-8 h-0.5 bg-green-500 rounded-full"></span>
                @endif
                <span class="relative">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ $isActive ? '2.5' : '1.5' }}"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $bItem['icon'] }}"></path></svg>
                    @if(($bItem['badge_query'] ?? false) && $ticketsOuverts > 0)
                        <span class="absolute -top-1 -right-2 min-w-[16px] h-4 px-1 text-[10px] font-bold bg-red-500 text-white rounded-full flex items-center justify-center">{{ $ticketsOuverts }}</span>
                    @endif
                </span>
                <span class="text-[10px] font-medium leading-tight text-center">{{ $bItem['short'] }}</span>
            </a>
        @endforeach

        {{-- Bouton "Plus" --}}
        <button
            onclick="document.getElementById('pwa-admin-more-sheet').classList.remove('translate-y-full'); document.getElementById('pwa-admin-more-overlay').classList.remove('hidden');"
            class="pwa-tab-btn flex flex-col items-center justify-center flex-1 gap-0.5 text-slate-400 dark:text-slate-500"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"></path></svg>
            <span class="text-[10px] font-medium leading-tight text-center">Plus</span>
        </button>
    </nav>
</div>

{{-- Sheet "Plus" (slide-up drawer) --}}
<div id="pwa-admin-more-overlay" class="pwa-more-overlay fixed inset-0 z-[60] bg-black/40 hidden transition-opacity" onclick="closePwaAdminMoreSheet()"></div>
<div id="pwa-admin-more-sheet" class="pwa-more-sheet fixed bottom-0 left-0 right-0 z-[70] bg-white dark:bg-slate-900 rounded-t-2xl shadow-2xl transform translate-y-full transition-transform duration-300 ease-out max-h-[80vh] overflow-y-auto" style="padding-bottom: env(safe-area-inset-bottom, 0px);">
    {{-- Handle --}}
    <div class="flex justify-center pt-3 pb-2 sticky top-0 bg-white dark:bg-slate-900 z-10">
        <div class="w-10 h-1 bg-slate-300 dark:bg-slate-600 rounded-full"></div>
    </div>

    <div class="px-2 pb-6">
        {{-- Lien retour site --}}
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 mb-2 rounded-xl text-blue-700 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            <span class="text-sm font-medium">Mon compte</span>
            <svg class="w-4 h-4 ml-auto text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
        </a>

        @foreach($moreGroups as $groupLabel => $groupItems)
            <div class="px-4 pt-3 pb-1.5">
                <h4 class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">{{ $groupLabel }}</h4>
            </div>
            <div class="space-y-0.5">
                @foreach($groupItems as $mItem)
                    @php
                        $mIsActive = request()->routeIs($mItem['match']);
                        $specialColor = $mItem['special_color'] ?? null;
                    @endphp
                    <a
                        href="{{ route($mItem['route']) }}"
                        class="flex items-center gap-3 px-4 py-3 rounded-xl transition-colors {{ $mIsActive ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}"
                        @if($specialColor) style="color: {{ $specialColor }};" @endif
                        onclick="closePwaAdminMoreSheet()"
                    >
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $mItem['icon'] }}"></path>
                            @if(isset($mItem['icon_extra']))
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $mItem['icon_extra'] }}"></path>
                            @endif
                        </svg>
                        <span class="text-sm font-medium">{{ $mItem['label'] }}</span>
                    </a>
                @endforeach
            </div>
        @endforeach

        {{-- Déconnexion --}}
        <div class="border-t border-slate-200 dark:border-slate-700 mt-3 pt-3">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    <span class="text-sm font-medium">Déconnexion</span>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
if (typeof closePwaAdminMoreSheet === 'undefined') {
    window.closePwaAdminMoreSheet = function() {
        const sheet = document.getElementById('pwa-admin-more-sheet');
        const overlay = document.getElementById('pwa-admin-more-overlay');
        if (sheet) sheet.classList.add('translate-y-full');
        if (overlay) overlay.classList.add('hidden');
    }
}
</script>
