@php
    $apkReady = \App\Http\Controllers\NativeAppDownloadController::apkAvailable();
    $playStoreUrl = config('play.store_url');
@endphp
<div class="android-store-card p-6 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 relative overflow-hidden">
    <div class="flex flex-col items-center text-center">
        <div class="w-12 h-12 mb-4 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center text-green-600 dark:text-green-400">
            <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17.523 15.3414c-.5511 0-.9993-.4486-.9993-.9997s.4482-.9993.9993-.9993c.5511 0 .9993.4482.9993.9993s-.4482.9997-.9993.9997m-11.046 0c-.5511 0-.9993-.4486-.9993-.9997s.4482-.9993.9993-.9993c.5511 0 .9993.4482.9993.9993s-.4482.9997-.9993.9997m11.4045-6.02l1.9973-3.459-1.0493-.6094-1.9213 3.3283c-1.5428-.6803-3.2386-1.0594-5.0069-1.0594-1.7683 0-3.4641.3791-5.0076 1.0594l-1.922-3.3283-1.0486.6094 1.998 3.459c-2.4497 1.3286-4.0954 3.7504-4.4173 6.5546h20.8016c-.3211-2.8042-1.9676-5.226-4.4239-6.5546"/>
            </svg>
        </div>
        <h3 class="font-semibold text-slate-900 dark:text-white mb-1">Android</h3>
        <p class="text-xs text-slate-500 mb-4">Application native Allo Tata</p>
        <div class="flex flex-col gap-2 w-full">
            <a href="{{ $playStoreUrl }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center min-h-[40px] px-3 py-2 text-sm font-semibold rounded-lg bg-green-600 hover:bg-green-700 text-white transition">
                Google Play
            </a>
            @if($apkReady)
                <a href="{{ route('downloads.apk') }}" class="inline-flex items-center justify-center min-h-[40px] px-3 py-2 text-sm font-semibold rounded-lg bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-800 dark:text-slate-100 transition">
                    Télécharger l’APK
                </a>
            @else
                <span class="text-xs text-slate-400">APK de test dès le prochain build release</span>
            @endif
        </div>
    </div>
</div>
