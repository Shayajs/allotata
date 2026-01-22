@extends('brightshell.layout')

@section('title', 'Exports')

@section('content')
<div class="grid grid-2">
    <div class="card">
        <div class="flex items-center gap-4 mb-4">
            <div style="width: 48px; height: 48px; background: rgba(91, 188, 228, 0.15); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <svg width="24" height="24" fill="none" stroke="#5bbce4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div>
                <h4 class="font-bold" style="margin: 0;">Export Clients</h4>
                <p class="text-muted text-sm" style="margin: 0;">Liste complète de vos clients</p>
            </div>
        </div>
        <a href="{{ route('brightshell.exports.download', 'clients') }}" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Télécharger CSV
        </a>
    </div>
    
    <div class="card">
        <div class="flex items-center gap-4 mb-4">
            <div style="width: 48px; height: 48px; background: rgba(245, 158, 11, 0.15); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <svg width="24" height="24" fill="none" stroke="#f59e0b" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div>
                <h4 class="font-bold" style="margin: 0;">Export Factures</h4>
                <p class="text-muted text-sm" style="margin: 0;">Toutes vos factures</p>
            </div>
        </div>
        <a href="{{ route('brightshell.exports.download', 'factures') }}" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Télécharger CSV
        </a>
    </div>
    
    <div class="card">
        <div class="flex items-center gap-4 mb-4">
            <div style="width: 48px; height: 48px; background: rgba(16, 185, 129, 0.15); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <svg width="24" height="24" fill="none" stroke="#10b981" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <h4 class="font-bold" style="margin: 0;">Export Recettes {{ date('Y') }}</h4>
                <p class="text-muted text-sm" style="margin: 0;">Livre des recettes (URSSAF)</p>
            </div>
        </div>
        <a href="{{ route('brightshell.exports.download', 'recettes') }}" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Télécharger CSV
        </a>
    </div>
    
    <div class="card">
        <div class="flex items-center gap-4 mb-4">
            <div style="width: 48px; height: 48px; background: rgba(239, 68, 68, 0.15); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <svg width="24" height="24" fill="none" stroke="#ef4444" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div>
                <h4 class="font-bold" style="margin: 0;">Export Achats {{ date('Y') }}</h4>
                <p class="text-muted text-sm" style="margin: 0;">Registre des achats</p>
            </div>
        </div>
        <a href="{{ route('brightshell.exports.download', 'achats') }}" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Télécharger CSV
        </a>
    </div>
</div>

<div class="card mt-4">
    <h3 class="card-title mb-4">Informations sur les exports</h3>
    <div class="grid grid-2">
        <div>
            <p class="text-muted text-sm mb-2">Format</p>
            <p class="font-bold">CSV (UTF-8 avec BOM)</p>
            <p class="text-muted text-xs">Compatible Excel, Google Sheets, LibreOffice</p>
        </div>
        <div>
            <p class="text-muted text-sm mb-2">Utilisation</p>
            <p class="font-bold">Déclarations URSSAF, Impôts</p>
            <p class="text-muted text-xs">Livre des recettes obligatoire en micro-entreprise</p>
        </div>
    </div>
</div>
@endsection
