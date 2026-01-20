@props(['block', 'lesson', 'editMode' => false])

@php
    $content = $block['content'] ?? [];
    $settings = $block['settings'] ?? [];
    
    $type = $content['type'] ?? 'info'; // info, warning, tip, danger
    $title = $content['title'] ?? '';
    $html = $content['html'] ?? '<p>Contenu de l\'encadré...</p>';
    
    $typeConfig = [
        'info' => [
            'bg' => 'bg-blue-50 dark:bg-blue-900/20',
            'border' => 'border-blue-200 dark:border-blue-800',
            'icon' => 'ℹ️',
            'titleColor' => 'text-blue-900 dark:text-blue-300',
        ],
        'warning' => [
            'bg' => 'bg-yellow-50 dark:bg-yellow-900/20',
            'border' => 'border-yellow-200 dark:border-yellow-800',
            'icon' => '⚠️',
            'titleColor' => 'text-yellow-900 dark:text-yellow-300',
        ],
        'tip' => [
            'bg' => 'bg-green-50 dark:bg-green-900/20',
            'border' => 'border-green-200 dark:border-green-800',
            'icon' => '💡',
            'titleColor' => 'text-green-900 dark:text-green-300',
        ],
        'danger' => [
            'bg' => 'bg-red-50 dark:bg-red-900/20',
            'border' => 'border-red-200 dark:border-red-800',
            'icon' => '🔴',
            'titleColor' => 'text-red-900 dark:text-red-300',
        ],
    ];
    
    $config = $typeConfig[$type] ?? $typeConfig['info'];
@endphp

<section class="py-6 md:py-8 px-4">
    <div class="max-w-4xl mx-auto">
        <div class="{{ $config['bg'] }} {{ $config['border'] }} border-l-4 rounded-r-lg p-4 md:p-6">
            <div class="flex gap-4">
                <div class="flex-shrink-0 text-2xl">
                    {{ $config['icon'] }}
                </div>
                <div class="flex-1">
                    @if($title)
                        <h4 class="{{ $config['titleColor'] }} font-semibold mb-2"
                            @if($editMode) data-editable="title" @endif>
                            {{ $title }}
                        </h4>
                    @endif
                    <div class="prose prose-sm dark:prose-invert max-w-none text-slate-700 dark:text-slate-300"
                         @if($editMode) data-editable="html" @endif>
                        {!! $html !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
