{{-- Onglet Notifications --}}
<div>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Notifications</h2>
        <a href="{{ route('notifications.index') }}" class="px-4 py-2 text-sm font-medium bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white rounded-lg transition">
            Voir tout →
        </a>
    </div>

    @php
        $userNotifications = $user->notifications()->latest()->take(15)->get();
    @endphp

    @if($userNotifications->count() > 0)
        <div class="space-y-2">
            @foreach($userNotifications as $notification)
                <div class="p-4 border border-slate-200 dark:border-slate-700 rounded-lg {{ $notification->read_at ? '' : 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800' }}">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            @if(!$notification->read_at)
                                <span class="w-3 h-3 bg-blue-500 rounded-full block"></span>
                            @else
                                <span class="w-3 h-3 bg-slate-300 dark:bg-slate-600 rounded-full block"></span>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between mb-1">
                                <p class="font-medium text-slate-900 dark:text-white text-sm">
                                    {{ $notification->data['title'] ?? 'Notification' }}
                                </p>
                                <span class="text-xs text-slate-500 dark:text-slate-400 flex-shrink-0 ml-2">
                                    {{ $notification->created_at->diffForHumans() }}
                                </span>
                            </div>
                            <p class="text-sm text-slate-600 dark:text-slate-400">
                                {{ $notification->data['message'] ?? $notification->data['body'] ?? '' }}
                            </p>
                            @if(isset($notification->data['action_url']))
                                <a href="{{ $notification->data['action_url'] }}" class="mt-2 inline-block text-xs text-green-600 dark:text-green-400 hover:underline">
                                    {{ $notification->data['action_text'] ?? 'Voir détails' }} →
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <div class="mt-6 text-center">
            <a href="{{ route('notifications.index') }}" class="text-sm text-green-600 dark:text-green-400 hover:underline">
                Voir toutes les notifications →
            </a>
        </div>
    @else
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-slate-900 dark:text-white">Aucune notification</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Vous êtes à jour ! Aucune notification pour le moment.
            </p>
        </div>
    @endif
</div>
