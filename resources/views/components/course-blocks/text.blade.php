@props(['block', 'lesson', 'editMode' => false])

@php
    $content = $block['content'] ?? [];
    $settings = $block['settings'] ?? [];
    
    $alignClass = match($settings['alignment'] ?? 'left') {
        'left' => 'text-left',
        'center' => 'text-center',
        'right' => 'text-right',
        default => 'text-left'
    };
    
    $maxWidthClass = match($settings['maxWidth'] ?? 'prose') {
        'narrow' => 'max-w-xl',
        'prose' => 'max-w-4xl',
        'wide' => 'max-w-5xl',
        'full' => 'max-w-none',
        default => 'max-w-4xl'
    };
    
    $html = $content['html'] ?? '<p>Votre texte ici...</p>';
@endphp

<section class="py-6 md:py-10 px-4">
    <div class="{{ $maxWidthClass }} mx-auto {{ $alignClass }}">
        <div class="course-text-content prose prose-lg dark:prose-invert max-w-none
                    prose-headings:font-bold prose-headings:tracking-tight
                    prose-h2:text-2xl prose-h2:mt-10 prose-h2:mb-4 prose-h2:text-slate-900 dark:prose-h2:text-white
                    prose-h3:text-xl prose-h3:mt-8 prose-h3:mb-3
                    prose-p:text-slate-700 dark:prose-p:text-slate-300 prose-p:leading-relaxed prose-p:text-base prose-p:mb-4
                    prose-strong:text-slate-900 dark:prose-strong:text-white prose-strong:font-semibold
                    prose-a:text-green-600 dark:prose-a:text-green-400 prose-a:font-medium prose-a:underline prose-a:decoration-green-300 dark:prose-a:decoration-green-700 prose-a:underline-offset-2 hover:prose-a:decoration-green-500
                    prose-ul:my-4 prose-ol:my-4
                    prose-li:text-slate-700 dark:prose-li:text-slate-300 prose-li:leading-relaxed
                    prose-blockquote:border-l-green-500 prose-blockquote:bg-green-50/50 dark:prose-blockquote:bg-green-900/10 prose-blockquote:rounded-r-lg prose-blockquote:py-1 prose-blockquote:px-4 prose-blockquote:not-italic prose-blockquote:text-slate-700 dark:prose-blockquote:text-slate-300
                    prose-code:text-green-700 dark:prose-code:text-green-400 prose-code:bg-slate-100 dark:prose-code:bg-slate-800 prose-code:px-1.5 prose-code:py-0.5 prose-code:rounded-md prose-code:text-sm prose-code:font-medium prose-code:before:content-none prose-code:after:content-none
                    prose-img:rounded-xl prose-img:shadow-md
                    prose-hr:border-slate-200 dark:prose-hr:border-slate-700"
             @if($editMode) data-editable="html" @endif>
            {!! $html !!}
        </div>
    </div>
</section>

@once
@push('styles')
<style>
    /* Listes à puces personnalisées Allotata */
    .course-text-content ul {
        list-style: none;
        padding-left: 0;
    }
    .course-text-content ul > li {
        position: relative;
        padding-left: 1.75rem;
    }
    .course-text-content ul > li::before {
        content: '';
        position: absolute;
        left: 0.25rem;
        top: 0.65em;
        width: 0.45rem;
        height: 0.45rem;
        border-radius: 9999px;
        background-color: #22c55e;
    }
    .course-text-content ol {
        counter-reset: allotata-counter;
        list-style: none;
        padding-left: 0;
    }
    .course-text-content ol > li {
        counter-increment: allotata-counter;
        position: relative;
        padding-left: 2.25rem;
    }
    .course-text-content ol > li::before {
        content: counter(allotata-counter);
        position: absolute;
        left: 0;
        top: 0.15em;
        width: 1.5rem;
        height: 1.5rem;
        border-radius: 9999px;
        background-color: #22c55e;
        color: white;
        font-size: 0.75rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>
@endpush
@endonce
