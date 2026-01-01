@props(['block', 'entreprise', 'editMode' => false])

@php
    $content = $block['content'] ?? [];
    $settings = $block['settings'] ?? [];
    
    $title = $content['title'] ?? 'Questions fréquentes';
    $items = $content['items'] ?? [];
    
    // Exemples par défaut si vide
    if (empty($items)) {
        $items = [
            ['question' => 'Comment prendre rendez-vous ?', 'answer' => 'Vous pouvez prendre rendez-vous directement via notre page de réservation ou nous contacter par téléphone.'],
            ['question' => 'Quels sont vos horaires ?', 'answer' => 'Nous sommes disponibles du lundi au samedi. Consultez notre page de contact pour les horaires détaillés.'],
            ['question' => 'Quels modes de paiement acceptez-vous ?', 'answer' => 'Nous acceptons les paiements en espèces, carte bancaire et virements.'],
        ];
    }
    
    $faqId = 'faq-' . ($block['id'] ?? uniqid());
@endphp

<section class="py-16 md:py-24 px-4">
    <div class="max-w-3xl mx-auto">
        <h2 class="text-3xl md:text-4xl font-bold text-center mb-12"
            style="font-family: var(--site-font-heading); color: var(--site-text);"
            @if($editMode) data-editable="title" @endif>
            {{ $title }}
        </h2>
        
        <div class="space-y-4">
            @foreach($items as $index => $item)
                <div class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden">
                    <button 
                        type="button" 
                        onclick="toggleFaq('{{ $faqId }}-{{ $index }}')"
                        class="w-full flex items-center justify-between p-5 text-left bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 transition"
                    >
                        <span class="font-semibold pr-4" style="color: var(--site-text); font-family: var(--site-font-heading);">
                            {{ $item['question'] }}
                        </span>
                        <svg id="{{ $faqId }}-{{ $index }}-icon" class="w-5 h-5 flex-shrink-0 transform transition-transform" style="color: var(--site-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div id="{{ $faqId }}-{{ $index }}" class="hidden">
                        <div class="p-5 pt-0 bg-white dark:bg-slate-800">
                            <p class="text-slate-600 dark:text-slate-400" style="font-family: var(--site-font-body);">
                                {{ $item['answer'] }}
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<script>
function toggleFaq(id) {
    const content = document.getElementById(id);
    const icon = document.getElementById(id + '-icon');
    
    if (content.classList.contains('hidden')) {
        content.classList.remove('hidden');
        icon.classList.add('rotate-180');
    } else {
        content.classList.add('hidden');
        icon.classList.remove('rotate-180');
    }
}
</script>
