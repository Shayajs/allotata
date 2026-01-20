@props(['block', 'lesson', 'editMode' => false])

@php
    $content = $block['content'] ?? [];
    $settings = $block['settings'] ?? [];
    
    $code = $content['code'] ?? '';
    $language = $content['language'] ?? 'plaintext';
    $showLineNumbers = $settings['showLineNumbers'] ?? true;
@endphp

<section class="py-8 md:py-12 px-4">
    <div class="max-w-5xl mx-auto">
        <div class="bg-slate-900 dark:bg-slate-950 rounded-xl overflow-hidden shadow-xl border border-slate-800 dark:border-slate-700">
            @if($language !== 'plaintext')
                <div class="bg-slate-800 dark:bg-slate-900 px-4 py-2 border-b border-slate-700 dark:border-slate-800 flex items-center justify-between">
                    <span class="text-xs font-mono text-slate-400 uppercase">{{ $language }}</span>
                    <button 
                        onclick="copyCodeToClipboard(this)" 
                        class="text-xs text-slate-400 hover:text-white transition flex items-center gap-1"
                        title="Copier le code"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                        Copier
                    </button>
                </div>
            @endif
            <pre class="p-4 md:p-6 overflow-x-auto @if($showLineNumbers) line-numbers @endif"><code 
                class="language-{{ $language }} text-sm text-slate-100"
                @if($editMode) data-editable="code" @endif
            >{{ htmlspecialchars($code) }}</code></pre>
        </div>
    </div>
</section>

<script>
function copyCodeToClipboard(button) {
    const code = button.closest('section').querySelector('code').textContent;
    navigator.clipboard.writeText(code).then(() => {
        const originalText = button.innerHTML;
        button.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Copié';
        setTimeout(() => {
            button.innerHTML = originalText;
        }, 2000);
    });
}
</script>
