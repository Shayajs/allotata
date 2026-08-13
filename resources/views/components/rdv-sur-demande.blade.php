@props(['entreprise'])

@php
    $message = trim((string) ($entreprise->rdv_sur_demande_message ?? ''));
    $messagerieUrl = auth()->check()
        ? route('messagerie.show', $entreprise->slug)
        : route('login', ['return' => url()->current()]);
@endphp

<div {{ $attributes->class('bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden') }}>
    <div class="bg-gradient-to-r from-green-600 to-emerald-500 px-6 py-5">
        <p class="text-sm font-semibold uppercase tracking-wide text-white/80">Prise de rendez-vous</p>
        <h2 class="text-2xl font-bold text-white mt-1">Sur rendez-vous uniquement</h2>
    </div>

    <div class="p-6 sm:p-8 space-y-6">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-slate-700 dark:text-slate-300 leading-relaxed">
                    {{ $entreprise->nom }} n’affiche pas de créneaux en ligne. Les rendez-vous se prennent <strong class="text-slate-900 dark:text-white">sur demande</strong> : contactez l’entreprise pour convenir d’une date et d’un horaire.
                </p>
            </div>
        </div>

        @if($message !== '')
            <div class="rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700/40 px-4 py-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-1">Message de l’entreprise</p>
                <p class="text-slate-800 dark:text-slate-200 whitespace-pre-line">{{ $message }}</p>
            </div>
        @endif

        <div class="flex flex-col sm:flex-row gap-3">
            <a href="{{ $messagerieUrl }}" class="inline-flex items-center justify-center gap-2 px-5 py-3 bg-gradient-to-r from-green-600 to-emerald-500 hover:from-green-700 hover:to-emerald-600 text-white font-semibold rounded-xl transition shadow-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
                Demander un rendez-vous
            </a>
            @if($entreprise->telephone)
                <a href="tel:{{ $entreprise->telephone }}" class="inline-flex items-center justify-center gap-2 px-5 py-3 border-2 border-slate-300 dark:border-slate-600 text-slate-800 dark:text-slate-200 font-semibold rounded-xl hover:border-green-500 dark:hover:border-green-500 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                    </svg>
                    Appeler
                </a>
            @endif
            @if($entreprise->email)
                <a href="mailto:{{ $entreprise->email }}" class="inline-flex items-center justify-center gap-2 px-5 py-3 border-2 border-slate-300 dark:border-slate-600 text-slate-800 dark:text-slate-200 font-semibold rounded-xl hover:border-green-500 dark:hover:border-green-500 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    E-mail
                </a>
            @endif
        </div>
    </div>
</div>
