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
                <span class="text-lg">✉️</span> <span class="truncate">{{ $user->email }}</span>
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
                    ['id' => 'details', 'label' => 'Informations', 'icon' => '👤'],
                    ['id' => 'enterprises', 'label' => 'Entreprises', 'icon' => '🏢', 'count' => $user->entreprises->count()],
                    ['id' => 'reservations', 'label' => 'Réservations', 'icon' => '📅', 'count' => $user->reservations->count()],
                    ['id' => 'subscription', 'label' => 'Abonnement', 'icon' => '💳'],
                    ['id' => 'roles', 'label' => 'Accès & Rôles', 'icon' => '🛡️'],
                ];
            @endphp
            @foreach($tabs as $tab)
                <button 
                    onclick="showUserTab('{{ $tab['id'] }}')"
                    data-user-tab="{{ $tab['id'] }}"
                    class="user-tab-btn flex items-center gap-2 px-8 py-6 text-sm font-bold whitespace-nowrap border-b-2 transition-all {{ $loop->first ? 'border-green-500 text-green-600' : 'border-transparent text-slate-500 hover:text-slate-700' }}"
                >
                    <span class="text-xl">{{ $tab['icon'] }}</span>
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
    </div>
</div>

<script>
    function showUserTab(tabId) {
        // Hide all contents
        document.querySelectorAll('.user-tab-content').forEach(content => {
            content.classList.add('hidden');
        });

        // Reset all buttons
        document.querySelectorAll('.user-tab-btn').forEach(btn => {
            btn.classList.remove('border-green-500', 'text-green-600');
            btn.classList.add('border-transparent', 'text-slate-500');
        });

        // Show selected content
        document.getElementById('user-tab-' + tabId)?.classList.remove('hidden');

        // Activate selected button
        const activeBtn = document.querySelector(`[data-user-tab="${tabId}"]`);
        if (activeBtn) {
            activeBtn.classList.remove('border-transparent', 'text-slate-500');
            activeBtn.classList.add('border-green-500', 'text-green-600');
        }

        // Update URL without reload
        const url = new URL(window.location);
        url.searchParams.set('tab', tabId);
        window.history.replaceState({}, '', url);
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

