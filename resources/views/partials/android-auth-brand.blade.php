<div class="android-auth-brand" aria-hidden="false">
    @php
        $logoUrl = \App\Helpers\SiteHelper::getLogo('pwa')
            ?: \App\Helpers\SiteHelper::getAllotataLogoUrl()
            ?: '/icons/icon-192x192.png';
        $siteName = \App\Helpers\SiteHelper::getSiteName();
    @endphp
    <img src="{{ $logoUrl }}" alt="" class="w-20 h-20 rounded-2xl shadow-xl object-cover bg-slate-800 mb-4">
    <p class="text-2xl font-bold bg-gradient-to-r from-green-400 to-orange-400 bg-clip-text text-transparent">{{ $siteName }}</p>
    <p class="mt-2 text-sm text-slate-400">L’app de votre activité</p>
</div>
