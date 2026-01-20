@props(['block', 'lesson', 'editMode' => false])

@php
    $content = $block['content'] ?? [];
    $settings = $block['settings'] ?? [];
    
    $items = $content['items'] ?? [];
    $title = $content['title'] ?? '';
@endphp

<section class="py-8 md:py-12 px-4">
    <div class="max-w-4xl mx-auto">
        @if($title)
            <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-6"
                @if($editMode) data-editable="title" @endif>
                {{ $title }}
            </h3>
        @endif
        
        @if(count($items) > 0)
            <div class="space-y-3">
                @foreach($items as $index => $item)
                    <label class="flex items-start gap-3 p-4 bg-slate-50 dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 transition cursor-pointer">
                        <input 
                            type="checkbox" 
                            class="mt-1 w-5 h-5 text-green-600 rounded border-slate-300 dark:border-slate-600 focus:ring-green-500"
                            disabled
                        >
                        <span class="flex-1 text-slate-700 dark:text-slate-300"
                              @if($editMode) data-editable="item-{{ $index }}" @endif>
                            {{ $item['text'] ?? 'Item de liste' }}
                        </span>
                    </label>
                @endforeach
            </div>
        @else
            <div class="text-center py-8 text-slate-500 dark:text-slate-400">
                <p>Aucun item dans la checklist</p>
            </div>
        @endif
    </div>
</section>
