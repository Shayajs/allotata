@extends('brightshell.layout')

@section('title', 'Installer l\'application')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold text-white">Installer l'application</h2>
    </div>

    <!-- PWA / App Native -->
    <div style="background: linear-gradient(135deg, #0f1420 0%, #1a2236 100%); border-radius: 16px; padding: 2rem; position: relative; overflow: hidden; margin-bottom: 2rem; border: 1px solid rgba(91, 188, 228, 0.15);">
        <div style="position: absolute; top: 0; right: 0; margin-top: -4rem; margin-right: -4rem; width: 16rem; height: 16rem; background: #5bbce4; border-radius: 9999px; opacity: 0.1; filter: blur(40px);"></div>
        
        <div style="position: relative; z-index: 10; display: flex; flex-direction: column; gap: 2rem;">
            <div class="flex flex-col md:flex-row items-center gap-8">
                <div style="background: rgba(255, 255, 255, 0.05); padding: 1rem; border-radius: 16px; backdrop-filter: blur(4px); border: 1px solid rgba(255, 255, 255, 0.1);">
                    <img src="{{ asset('media/brightshell/favicon.png') }}" alt="App Icon" style="width: 96px; height: 96px; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5);">
                </div>
                
                <div style="flex: 1; text-align: center; md:text-align: left;">
                    <div style="display: flex; align-items: center; justify-content: center; md:justify-content: flex-start; gap: 0.75rem; margin-bottom: 0.5rem;">
                        <h3 style="font-size: 1.5rem; font-weight: 700; color: white;">App BrightShell</h3>
                        <span class="badge badge-info">OFFICIELLE</span>
                    </div>
                    <p class="text-muted mb-4" style="max-width: 600px; margin-bottom: 1.5rem;">
                        Installez l'application officielle BrightShell ERP sur votre appareil pour un accès rapide à votre gestion d'entreprise, factures et clients.
                    </p>
                    
                    <button 
                        id="pwa-install-btn-brightshell"
                        onclick="window.installPwa()" 
                        class="btn btn-primary"
                        style="padding: 0.75rem 1.5rem; font-size: 1rem;"
                    >
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span id="pwa-install-text-brightshell">Installer maintenant</span>
                    </button>
                    
                    <script>
                        document.addEventListener('DOMContentLoaded', () => {
                            const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
                            if (isStandalone) {
                                const btn = document.getElementById('pwa-install-btn-brightshell');
                                const text = document.getElementById('pwa-install-text-brightshell');
                                
                                if (btn && text) {
                                    text.textContent = "Déjà installé";
                                    btn.style.background = '#334155';
                                    btn.style.color = '#94a3b8';
                                    btn.style.cursor = 'default';
                                    btn.onclick = null;
                                }
                            }
                        });
                    </script>
                    
                    <p class="text-muted text-xs mt-4">
                        Compatible iOS & Android • Windows, Mac & Linux
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-4">
        <!-- Windows -->
        <div class="stat-card" style="opacity: 0.6; filter: grayscale(1);">
            <div style="position: absolute; inset: 0; background: rgba(15, 20, 32, 0.7); display: flex; align-items: center; justify-content: center; z-index: 10;">
                <span class="badge badge-warning">BIENTÔT</span>
            </div>
            <div class="flex flex-col items-center text-center">
                <div style="width: 48px; height: 48px; background: rgba(59, 130, 246, 0.15); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem; color: #3b82f6;">
                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M0 3.449L9.75 2.1v9.451H0m10.949-9.602L24 0v11.4h-13.051M0 12.6h9.75v9.451L0 20.699M10.949 12.6H24V24l-12.9-1.801"/></svg>
                </div>
                <h3 class="font-bold text-white mb-1">Windows</h3>
                <p class="text-xs text-muted">Native .exe</p>
            </div>
        </div>

        <!-- Mac -->
        <div class="stat-card" style="opacity: 0.6; filter: grayscale(1);">
            <div style="position: absolute; inset: 0; background: rgba(15, 20, 32, 0.7); display: flex; align-items: center; justify-content: center; z-index: 10;">
                <span class="badge badge-warning">BIENTÔT</span>
            </div>
            <div class="flex flex-col items-center text-center">
                <div style="width: 48px; height: 48px; background: rgba(255, 255, 255, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem; color: #fff;">
                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.79-1.31.02-2.3-1.23-3.17-2.43-1.75-2.5-2.98-7.09-.04-9.64 1.46-1.26 2.54-1.71 3.48-1.71.97 0 1.88.66 2.48.66.59 0 1.57-.82 2.76-.7 2.19.16 3.07 1.09 3.07 1.09s-1.72 1.1-1.68 3.55c.03 2.55 2.22 3.44 2.22 3.44s-.65 1.77-1.55 3.07zM15.5 5.5c.84-1.18 1.4-2.85 1.23-4.5-1.5 0-2.88.94-3.53 2.05-.72 1.21-1.23 2.91 1 2.98.05-.01.07-.02.04-.02h1.26"/></svg>
                </div>
                <h3 class="font-bold text-white mb-1">macOS</h3>
                <p class="text-xs text-muted">Native .dmg</p>
            </div>
        </div>

        <!-- Android -->
        <div class="stat-card" style="opacity: 0.6; filter: grayscale(1);">
             <div style="position: absolute; inset: 0; background: rgba(15, 20, 32, 0.7); display: flex; align-items: center; justify-content: center; z-index: 10;">
                <span class="badge badge-warning">BIENTÔT</span>
            </div>
            <div class="flex flex-col items-center text-center">
                <div style="width: 48px; height: 48px; background: rgba(34, 197, 94, 0.15); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem; color: #22c55e;">
                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M17.523 15.3414c-.5511 0-.9993-.4486-.9993-.9997s.4482-.9993.9993-.9993c.5511 0 .9993.4482.9993.9993s-.4482.9997-.9993.9997m-11.046 0c-.5511 0-.9993-.4486-.9993-.9997s.4482-.9993.9993-.9993c.5511 0 .9993.4482.9993.9993s-.4482.9997-.9993.9997m11.4045-6.02l1.9973-3.459-1.0493-.6094-1.9213 3.3283c-1.5428-.6803-3.2386-1.0594-5.0069-1.0594-1.7683 0-3.4641.3791-5.0076 1.0594l-1.922-3.3283-1.0486.6094 1.998 3.459c-2.4497 1.3286-4.0954 3.7504-4.4173 6.5546h20.8016c-.3211-2.8042-1.9676-5.226-4.4239-6.5546"/></svg>
                </div>
                <h3 class="font-bold text-white mb-1">Android</h3>
                <p class="text-xs text-muted">Native .apk</p>
            </div>
        </div>

        <!-- Linux -->
        <div class="stat-card" style="opacity: 0.6; filter: grayscale(1);">
             <div style="position: absolute; inset: 0; background: rgba(15, 20, 32, 0.7); display: flex; align-items: center; justify-content: center; z-index: 10;">
                <span class="badge badge-warning">BIENTÔT</span>
            </div>
            <div class="flex flex-col items-center text-center">
                <div style="width: 48px; height: 48px; background: rgba(249, 115, 22, 0.15); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem; color: #f97316;">
                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M12.003 0c-1.93 0-3.193.955-3.193 2.924 0 2.21 2.38 3.748 2.38 6.516 0 1.258-2.673 2.186-2.673 4.887 0 2.822 3.19 2.506 3.013 4.293-.178 1.787-2.607 1.49-2.329 4.36l.169 1.02h5.27l.169-1.02c.277-2.87-2.152-2.573-2.33-4.36-.176-1.787 3.014-1.47 3.014-4.293 0-2.7-2.672-3.63-2.672-4.887 0-2.768 2.38-4.306 2.38-6.516C15.196.955 13.934 0 12.003 0z"/></svg>
                </div>
                <h3 class="font-bold text-white mb-1">Linux</h3>
                <p class="text-xs text-muted">Paquet .deb / .snap</p>
            </div>
        </div>
    </div>
</div>
@endsection
