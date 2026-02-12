<div class="border-l-2 border-slate-200 dark:border-slate-700 pl-4 {{ $level > 0 ? 'ml-4' : '' }}">
    <div class="flex items-start gap-3 mb-2">
        <div class="flex-1">
            <div class="flex items-center gap-2 mb-1">
                <span class="font-semibold text-slate-900 dark:text-white">{{ $comment->user->name }}</span>
                <span class="text-xs text-slate-500 dark:text-slate-400">{{ $comment->created_at->diffForHumans() }}</span>
            </div>
            <p class="text-slate-700 dark:text-slate-300">{{ $comment->contenu }}</p>
        </div>
    </div>
    
    @if($comment->replies->count() > 0)
        <div class="mt-2 space-y-2">
            @foreach($comment->replies as $reply)
                @include('forum.comment', ['comment' => $reply, 'level' => $level + 1])
            @endforeach
        </div>
    @endif
</div>
