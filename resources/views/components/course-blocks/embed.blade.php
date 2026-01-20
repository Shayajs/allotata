@props(['block', 'lesson', 'editMode' => false])

@php
    $content = $block['content'] ?? [];
    $settings = $block['settings'] ?? [];
    
    $url = $content['url'] ?? '';
    $title = $content['title'] ?? '';
    $type = $content['type'] ?? 'pdf'; // pdf, document, etc.
@endphp

<section class="py-8 md:py-12 px-4">
    <div class="max-w-5xl mx-auto">
        @if($title)
            <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-4"
                @if($editMode) data-editable="title" @endif>
                {{ $title }}
            </h3>
        @endif
        
        @if($url)
            @if($type === 'pdf')
                <div class="rounded-xl overflow-hidden shadow-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900">
                    <iframe 
                        src="{{ $url }}"
                        class="w-full"
                        style="height: 600px; border: none;"
                        loading="lazy"
                    ></iframe>
                </div>
            @else
                <div class="text-center p-8 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
                    <a 
                        href="{{ $url }}" 
                        target="_blank"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                        Ouvrir le document
                    </a>
                </div>
            @endif
        @else
            <div class="rounded-xl bg-slate-100 dark:bg-slate-800 border-2 border-dashed border-slate-300 dark:border-slate-600 flex items-center justify-center" style="height: 300px;">
                <div class="text-center p-8">
                    <svg class="w-16 h-16 mx-auto mb-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p class="text-slate-500 dark:text-slate-400">Ajoutez une URL de document</p>
                </div>
            </div>
        @endif
    </div>
</section>
