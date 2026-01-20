@props(['block', 'lesson', 'editMode' => false])

@php
    $content = $block['content'] ?? [];
    $settings = $block['settings'] ?? [];
    
    $steps = $content['steps'] ?? [];
    $title = $content['title'] ?? '';
    $layout = $settings['layout'] ?? 'vertical'; // vertical, horizontal
@endphp

<section class="py-8 md:py-12 px-4">
    <div class="max-w-4xl mx-auto">
        @if($title)
            <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-6 text-center"
                @if($editMode) data-editable="title" @endif>
                {{ $title }}
            </h3>
        @endif
        
        @if(count($steps) > 0)
            <div class="space-y-6">
                @foreach($steps as $index => $step)
                    <div class="flex gap-4">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-green-500 text-white font-bold flex items-center justify-center text-lg">
                            {{ $index + 1 }}
                        </div>
                        <div class="flex-1 pt-1">
                            @if(isset($step['title']))
                                <h4 class="font-semibold text-slate-900 dark:text-white mb-2"
                                    @if($editMode) data-editable="step-title-{{ $index }}" @endif>
                                    {{ $step['title'] }}
                                </h4>
                            @endif
                            @if(isset($step['content']))
                                <div class="prose prose-sm dark:prose-invert max-w-none text-slate-700 dark:text-slate-300"
                                     @if($editMode) data-editable="step-content-{{ $index }}" @endif>
                                    {!! $step['content'] !!}
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8 text-slate-500 dark:text-slate-400">
                <p>Aucune étape ajoutée</p>
            </div>
        @endif
    </div>
</section>
