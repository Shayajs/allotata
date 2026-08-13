@extends('admin.layout')

@section('title', $user->name . ' - Administration')
@section('header', 'Fiche Utilisateur')
@section('subheader', 'Consultation et modification des informations de ' . $user->name)

@section('content')
<div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
    <div class="flex items-center gap-4 lg:gap-6">
        <x-avatar :user="$user" class="w-16 h-16 md:w-24 md:h-24" />
        <div>
            <div class="flex flex-wrap items-center gap-2 lg:gap-3 mb-1">
                <h1 class="text-2xl lg:text-4xl font-extrabold text-slate-900 dark:text-white truncate">{{ $user->name }}</h1>
                @if($user->is_admin)
                    <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-lg text-[10px] font-bold uppercase tracking-widest border border-red-200">Admin</span>
                @endif
            </div>
            <p class="text-sm lg:text-base text-slate-500 font-medium flex items-center gap-2">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                <span class="truncate">{{ $user->email }}</span>
            </p>
        </div>
    </div>
    <a href="{{ route('admin.users.index') }}" class="inline-flex items-center justify-center px-4 md:px-6 py-2 md:py-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-bold rounded-xl md:rounded-2xl hover:bg-slate-50 transition-all shadow-sm text-sm">
        ← <span class="ml-2">Retour</span> <span class="hidden md:inline ml-1">à la liste</span>
    </a>
</div>

<div class="bg-white dark:bg-slate-800 rounded-[32px] shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
    <!-- Tab Navigation -->
    <div class="border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/20">
        <nav class="flex overflow-x-auto scrollbar-hide px-6" aria-label="Tabs">
            @php
                $tabs = [
                    ['id' => 'details', 'label' => 'Informations', 'icon' => '<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>'],
                    ['id' => 'enterprises', 'label' => 'Entreprises', 'icon' => '<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>', 'count' => $user->entreprises->count()],
                    ['id' => 'reservations', 'label' => 'Réservations', 'icon' => '<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>', 'count' => $user->reservations->count()],
                    ['id' => 'subscription', 'label' => 'Abonnement', 'icon' => '<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>'],
                    ['id' => 'roles', 'label' => 'Accès & Rôles', 'icon' => '<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>'],
                    ['id' => 'security', 'label' => 'Sécurité', 'icon' => '<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>'],
                ];
            @endphp
            @foreach($tabs as $tab)
                <button 
                    onclick="showUserTab('{{ $tab['id'] }}')"
                    data-user-tab="{{ $tab['id'] }}"
                    class="user-tab-btn flex items-center gap-2 px-8 py-6 text-sm font-bold whitespace-nowrap border-b-2 transition-all {{ $loop->first ? 'border-green-500 text-green-600' : 'border-transparent text-slate-500 hover:text-slate-700' }}"
                >
                    {!! $tab['icon'] !!}
                    {{ $tab['label'] }}
                    @if(isset($tab['count']))
                        <span class="ml-1 px-1.5 py-0.5 bg-slate-100 dark:bg-slate-700 text-[10px] rounded-md font-extrabold">{{ $tab['count'] }}</span>
                    @endif
                </button>
            @endforeach
        </nav>
    </div>

    <!-- Tab Contents -->
    <div class="p-8 md:p-12">
        <div id="user-tab-details" class="user-tab-content">
            @include('admin.users.partials._details')
        </div>
        
        <div id="user-tab-enterprises" class="user-tab-content hidden">
            @include('admin.users.partials._enterprises')
        </div>
        
        <div id="user-tab-reservations" class="user-tab-content hidden">
            @include('admin.users.partials._reservations')
        </div>
        
        <div id="user-tab-subscription" class="user-tab-content hidden">
            @include('admin.users.partials._subscription')
        </div>
        
        <div id="user-tab-roles" class="user-tab-content hidden">
            @include('admin.users.partials._roles')
        </div>
        
        <div id="user-tab-security" class="user-tab-content hidden">
            @include('admin.users.partials._security')
        </div>
    </div>
</div>

<script>
    function showUserTab(tabId) {
        const run = () => {
            document.querySelectorAll('.user-tab-content').forEach(content => {
                content.classList.add('hidden');
            });

            document.querySelectorAll('.user-tab-btn').forEach(btn => {
                btn.classList.remove('border-green-500', 'text-green-600');
                btn.classList.add('border-transparent', 'text-slate-500');
            });

            document.getElementById('user-tab-' + tabId)?.classList.remove('hidden');

            const activeBtn = document.querySelector(`[data-user-tab="${tabId}"]`);
            if (activeBtn) {
                activeBtn.classList.remove('border-transparent', 'text-slate-500');
                activeBtn.classList.add('border-green-500', 'text-green-600');
            }

            const url = new URL(window.location);
            url.searchParams.set('tab', tabId);
            window.history.replaceState({}, '', url);
        };
        if (window.adminKeepScroll) window.adminKeepScroll(run);
        else run();
    }

    // Handle initial tab from URL
    window.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const initialTab = urlParams.get('tab');
        if (initialTab && document.getElementById('user-tab-' + initialTab)) {
            showUserTab(initialTab);
        }
    });
</script>

<style>
    .scrollbar-hide::-webkit-scrollbar {
        display: none;
    }
    .scrollbar-hide {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>
@endsection

