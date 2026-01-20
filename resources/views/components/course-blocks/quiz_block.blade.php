@props(['block', 'lesson', 'editMode' => false])

@php
    $content = $block['content'] ?? [];
    $settings = $block['settings'] ?? [];
    
    $question = $content['question'] ?? '';
    $type = $content['type'] ?? 'multiple_choice';
    $options = $content['options'] ?? [];
    $correctAnswer = $content['correctAnswer'] ?? '';
    $explanation = $content['explanation'] ?? '';
    $showExplanation = $editMode || ($settings['showExplanation'] ?? false);
@endphp

<section class="py-8 md:py-12 px-4">
    <div class="max-w-3xl mx-auto">
        <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-xl border-2 border-purple-200 dark:border-purple-800 p-6 md:p-8">
            <div class="flex items-start gap-4 mb-6">
                <div class="flex-shrink-0 w-12 h-12 rounded-full bg-purple-500 text-white font-bold flex items-center justify-center text-xl">
                    ❓
                </div>
                <div class="flex-1">
                    <h4 class="text-lg font-semibold text-purple-900 dark:text-purple-300 mb-4"
                        @if($editMode) data-editable="question" @endif>
                        {{ $question }}
                    </h4>
                    
                    @if($type === 'multiple_choice' && count($options) > 0)
                        <div class="space-y-2">
                            @foreach($options as $index => $option)
                                <label class="flex items-center gap-3 p-3 bg-white dark:bg-slate-800 rounded-lg border border-purple-200 dark:border-purple-700 hover:bg-purple-50 dark:hover:bg-purple-900/30 cursor-pointer">
                                    <input 
                                        type="radio" 
                                        name="quiz-block-{{ $block['id'] ?? 'default' }}"
                                        value="{{ $option }}"
                                        class="w-4 h-4 text-purple-600 focus:ring-purple-500"
                                        disabled
                                    >
                                    <span class="text-slate-700 dark:text-slate-300"
                                          @if($editMode) data-editable="option-{{ $index }}" @endif>
                                        {{ $option }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @elseif($type === 'true_false')
                        <div class="space-y-2">
                            <label class="flex items-center gap-3 p-3 bg-white dark:bg-slate-800 rounded-lg border border-purple-200 dark:border-purple-700 hover:bg-purple-50 dark:hover:bg-purple-900/30 cursor-pointer">
                                <input type="radio" name="quiz-block-{{ $block['id'] ?? 'default' }}" value="1" class="w-4 h-4 text-purple-600 focus:ring-purple-500" disabled>
                                <span class="text-slate-700 dark:text-slate-300">Vrai</span>
                            </label>
                            <label class="flex items-center gap-3 p-3 bg-white dark:bg-slate-800 rounded-lg border border-purple-200 dark:border-purple-700 hover:bg-purple-50 dark:hover:bg-purple-900/30 cursor-pointer">
                                <input type="radio" name="quiz-block-{{ $block['id'] ?? 'default' }}" value="0" class="w-4 h-4 text-purple-600 focus:ring-purple-500" disabled>
                                <span class="text-slate-700 dark:text-slate-300">Faux</span>
                            </label>
                        </div>
                    @elseif($type === 'text')
                        <textarea 
                            rows="3"
                            placeholder="Votre réponse..."
                            class="w-full px-4 py-2 border border-purple-300 dark:border-purple-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                            disabled
                        ></textarea>
                    @endif
                    
                    @if($showExplanation && $explanation)
                        <div class="mt-6 p-4 bg-white dark:bg-slate-900 rounded-lg border border-purple-300 dark:border-purple-700">
                            <h5 class="font-semibold text-purple-900 dark:text-purple-300 mb-2 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Explication
                            </h5>
                            <div class="prose prose-sm dark:prose-invert max-w-none text-slate-700 dark:text-slate-300"
                                 @if($editMode) data-editable="explanation" @endif>
                                {!! $explanation !!}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
