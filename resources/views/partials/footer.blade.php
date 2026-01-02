<footer class="bg-slate-900 dark:bg-slate-950 text-slate-400 py-12 px-4 sm:px-6 lg:px-8 mt-auto">
    <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
            <div>
                <h3 class="text-2xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent mb-4">
                    Allo Tata
                </h3>
                <p class="text-slate-500">
                    La plateforme de gestion complète pour les micro-entreprises à succès.
                </p>
            </div>
            <div>
                <h4 class="text-white font-semibold mb-4">Navigation</h4>
                <ul class="space-y-2">
                    <li><a href="{{ route('home') }}#fonctionnalites" class="hover:text-green-400 transition">Fonctionnalités</a></li>
                    @auth
                        <li><a href="{{ route('dashboard') }}" class="hover:text-green-400 transition">Dashboard</a></li>
                    @else
                        @if (Route::has('login'))
                            <li><a href="{{ route('login') }}" class="hover:text-green-400 transition">Connexion</a></li>
                        @endif
                        @if (Route::has('signup'))
                            <li><a href="{{ route('signup') }}" class="hover:text-green-400 transition">Inscription</a></li>
                        @endif
                    @endauth
                </ul>
            </div>
            <div>
                <h4 class="text-white font-semibold mb-4">Support & Légal</h4>
                <ul class="space-y-2">
                    <li><a href="{{ route('pages.about') }}" class="hover:text-green-400 transition">À propos</a></li>
                    <li><a href="{{ route('legal.mentions') }}" class="hover:text-green-400 transition">Mentions Légales</a></li>
                    <li><a href="{{ route('legal.confidentialite') }}" class="hover:text-green-400 transition">Politique de Confidentialité</a></li>
                    <li><a href="{{ route('legal.cgu') }}" class="hover:text-green-400 transition">C.G.U.</a></li>
                    <li><a href="{{ route('legal.cgv') }}" class="hover:text-green-400 transition">C.G.V.</a></li>
                    <li><a href="#" class="hover:text-green-400 transition">Documentation</a></li>
                    <li>
                        <button onclick="if(document.getElementById('contact-modal')) document.getElementById('contact-modal').classList.remove('hidden')" class="hover:text-green-400 transition text-left">
                            Contact
                        </button>
                    </li>
                </ul>
            </div>
        </div>
        <div class="border-t border-slate-800 pt-8 text-center text-slate-500">
            <p>&copy; {{ date('Y') }} Allo Tata. Tous droits réservés.</p>
        </div>
    </div>
</footer>

<!-- Modal de contact (si non présent sur la page) -->
@once
    <div id="contact-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" onclick="if(event.target === this) document.getElementById('contact-modal').classList.add('hidden')">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl w-full max-w-md p-6" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white">Nous contacter</h3>
                <button onclick="document.getElementById('contact-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            @if(session('contact_success'))
                <div class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <p class="text-sm text-green-800 dark:text-green-300">{{ session('contact_success') }}</p>
                </div>
            @endif

            <form action="{{ route('contact.store') }}" method="POST" class="space-y-4">
                @csrf
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Nom *
                    </label>
                    <input 
                        type="text" 
                        name="nom" 
                        value="{{ old('nom', auth()->user()->name ?? '') }}"
                        required
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Email *
                    </label>
                    <input 
                        type="email" 
                        name="email" 
                        value="{{ old('email', auth()->user()->email ?? '') }}"
                        required
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Sujet *
                    </label>
                    <input 
                        type="text" 
                        name="sujet" 
                        value="{{ old('sujet') }}"
                        required
                        placeholder="Ex: Question sur les abonnements"
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Message *
                    </label>
                    <textarea 
                        name="message" 
                        rows="5"
                        required
                        placeholder="Décrivez votre demande..."
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >{{ old('message') }}</textarea>
                </div>

                <div class="flex gap-3">
                    <button 
                        type="submit" 
                        class="flex-1 px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition"
                    >
                        Envoyer
                    </button>
                    <button 
                        type="button" 
                        onclick="document.getElementById('contact-modal').classList.add('hidden')"
                        class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                    >
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>
@endonce
