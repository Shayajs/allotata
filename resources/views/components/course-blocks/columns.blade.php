@props(['block', 'lesson', 'editMode' => false])

@php
    $content = $block['content'] ?? [];
    $settings = $block['settings'] ?? [];
    
    $columns = $content['columns'] ?? 2;
    $columnContents = $content['content'] ?? array_fill(0, $columns, ['html' => '<p>Colonne...</p>']);
    $gap = $settings['gap'] ?? 'medium';
    
    $gapClass = match($gap) {
        'small' => 'gap-4',
        'medium' => 'gap-6',
        'large' => 'gap-8',
        default => 'gap-6'
    };
    
    $colClass = match($columns) {
        2 => 'md:grid-cols-2',
        3 => 'md:grid-cols-3',
        4 => 'md:grid-cols-4',
        default => 'md:grid-cols-2'
    };
@endphp

<section class="py-8 md:py-12 px-4">
    <div class="max-w-6xl mx-auto">
        <div class="grid grid-cols-1 {{ $colClass }} {{ $gapClass }}">
            @foreach($columnContents as $index => $column)
                <div class="prose prose-lg dark:prose-invert max-w-none"
                     @if($editMode) data-editable="column-{{ $index }}" @endif>
                    {!! $column['html'] ?? '<p>Contenu de la colonne...</p>' !!}
                </div>
            @endforeach
        </div>
    </div>
</section>
