@auth
    @if(auth()->user()->is_admin)
        <div class="fixed bottom-36 sm:bottom-6 right-4 sm:right-6 z-40">
            <a 
                href="{{ route('admin.courses.index') }}"
                class="flex items-center gap-2 px-3 py-2.5 sm:px-4 sm:py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-full sm:rounded-lg shadow-lg hover:shadow-xl transition-all transform hover:scale-105"
                title="Accéder au mode édition"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                <span class="hidden sm:inline">Mode édition</span>
            </a>
        </div>
    @endif
@endauth
