@props(['block', 'lesson', 'editMode' => false])

@php
    $content = $block['content'] ?? [];
    $settings = $block['settings'] ?? [];
    
    $title = $content['title'] ?? 'Exercice pratique';
    $instructions = $content['instructions'] ?? '';
    $responseArea = $content['responseArea'] ?? false;
    $solution = $content['solution'] ?? '';
    $showSolution = $editMode || ($settings['showSolution'] ?? false); // Admin voit toujours la solution
@endphp

<section class="py-8 md:py-12 px-4">
    <div class="max-w-4xl mx-auto">
        <div class="bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20 rounded-xl border-2 border-orange-200 dark:border-orange-800 p-6 md:p-8">
            <div class="flex items-start gap-4 mb-6">
                <div class="flex-shrink-0 w-12 h-12 rounded-full bg-orange-500 text-white font-bold flex items-center justify-center text-xl">
                    ✍️
                </div>
                <div class="flex-1">
                    <h3 class="text-2xl font-bold text-orange-900 dark:text-orange-300 mb-3"
                        @if($editMode) data-editable="title" @endif>
                        {{ $title }}
                    </h3>
                    @if($instructions)
                        <div class="prose prose-sm dark:prose-invert max-w-none text-orange-800 dark:text-orange-200"
                             @if($editMode) data-editable="instructions" @endif>
                            {!! $instructions !!}
                        </div>
                    @endif
                </div>
            </div>
            
            @if($responseArea)
                <div class="mt-6 p-4 bg-white dark:bg-slate-800 rounded-lg border border-orange-300 dark:border-orange-700">
                    <textarea 
                        rows="5"
                        placeholder="Votre réponse ici..."
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        disabled
                    ></textarea>
                </div>
            @endif
            
            @if($showSolution && $solution)
                <div class="mt-6 p-4 bg-white dark:bg-slate-900 rounded-lg border border-orange-300 dark:border-orange-700">
                    <h4 class="font-semibold text-orange-900 dark:text-orange-300 mb-2 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Solution
                    </h4>
                    <div class="prose prose-sm dark:prose-invert max-w-none text-slate-700 dark:text-slate-300"
                         @if($editMode) data-editable="solution" @endif>
                        {!! $solution !!}
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>
