@props([
    'name' => 'file',
    'accept' => 'image/*',
    'required' => false,
    'id' => null,
    'currentImage' => null,
    'maxSize' => '5 Mo',
    'label' => null,
])

@php
    $inputId = $id ?? 'file-upload-' . $name . '-' . uniqid();
@endphp

<div class="file-upload-zone" id="zone-{{ $inputId }}">
    @if($label)
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ $label }}</label>
    @endif

    <!-- Zone de drag-and-drop -->
    <div 
        class="dropzone relative border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-xl p-6 text-center cursor-pointer transition-all hover:border-green-400 dark:hover:border-green-500 hover:bg-green-50/50 dark:hover:bg-green-900/10"
        id="dropzone-{{ $inputId }}"
        onclick="document.getElementById('{{ $inputId }}').click()"
    >
        <!-- Icône et texte par défaut -->
        <div class="upload-placeholder" id="placeholder-{{ $inputId }}">
            <div class="mx-auto w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-3">
                <svg class="w-6 h-6 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400">
                <span class="font-semibold text-green-600 dark:text-green-400">Cliquez pour choisir</span> ou glissez un fichier ici
            </p>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                {{ strtoupper(str_replace(['image/', ',', '.'], ['', ', ', ''], $accept)) }} — Max {{ $maxSize }}
            </p>
        </div>

        <!-- Preview (cachée par défaut) -->
        <div class="upload-preview hidden" id="preview-{{ $inputId }}">
            <div class="flex items-center gap-4">
                <img id="preview-img-{{ $inputId }}" src="" alt="Aperçu" class="w-16 h-16 rounded-lg object-cover border border-slate-200 dark:border-slate-700">
                <div class="flex-1 text-left min-w-0">
                    <p class="text-sm font-medium text-slate-900 dark:text-white truncate" id="preview-name-{{ $inputId }}"></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400" id="preview-size-{{ $inputId }}"></p>
                </div>
                <button 
                    type="button" 
                    onclick="event.stopPropagation(); clearFileUpload('{{ $inputId }}')"
                    class="flex-shrink-0 p-2 text-red-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition"
                    title="Supprimer"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Input file caché -->
        <input 
            type="file" 
            id="{{ $inputId }}" 
            name="{{ $name }}" 
            accept="{{ $accept }}"
            {{ $required ? 'required' : '' }}
            class="hidden"
            onchange="handleFileUpload('{{ $inputId }}', this)"
        >
    </div>

    @if($currentImage)
        <div class="mt-2 flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
            <img src="{{ $currentImage }}" alt="Image actuelle" class="w-8 h-8 rounded object-cover border border-slate-200 dark:border-slate-700">
            <span>Image actuelle</span>
        </div>
    @endif
</div>

<script>
    // On n'enregistre les fonctions qu'une seule fois
    if (typeof window._fileUploadInitialized === 'undefined') {
        window._fileUploadInitialized = true;

        window.handleFileUpload = function(inputId, input) {
            const file = input.files[0];
            if (!file) return;

            const placeholder = document.getElementById('placeholder-' + inputId);
            const preview = document.getElementById('preview-' + inputId);
            const previewImg = document.getElementById('preview-img-' + inputId);
            const previewName = document.getElementById('preview-name-' + inputId);
            const previewSize = document.getElementById('preview-size-' + inputId);
            const dropzone = document.getElementById('dropzone-' + inputId);

            // Afficher la preview
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                };
                reader.readAsDataURL(file);
                previewImg.classList.remove('hidden');
            } else {
                previewImg.classList.add('hidden');
            }

            previewName.textContent = file.name;
            previewSize.textContent = formatFileSize(file.size);

            placeholder.classList.add('hidden');
            preview.classList.remove('hidden');
            dropzone.classList.add('border-green-400', 'dark:border-green-500', 'bg-green-50/30', 'dark:bg-green-900/10');
            dropzone.classList.remove('border-dashed');
        };

        window.clearFileUpload = function(inputId) {
            const input = document.getElementById(inputId);
            const placeholder = document.getElementById('placeholder-' + inputId);
            const preview = document.getElementById('preview-' + inputId);
            const dropzone = document.getElementById('dropzone-' + inputId);

            input.value = '';
            placeholder.classList.remove('hidden');
            preview.classList.add('hidden');
            dropzone.classList.remove('border-green-400', 'dark:border-green-500', 'bg-green-50/30', 'dark:bg-green-900/10');
            dropzone.classList.add('border-dashed');
        };

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 octets';
            const k = 1024;
            const sizes = ['octets', 'Ko', 'Mo', 'Go'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        }

        // Drag-and-drop global
        document.addEventListener('dragover', function(e) { e.preventDefault(); });
        document.addEventListener('drop', function(e) { e.preventDefault(); });

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.dropzone').forEach(function(zone) {
                zone.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    zone.classList.add('border-green-500', 'bg-green-50', 'dark:bg-green-900/20');
                });
                zone.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    zone.classList.remove('border-green-500', 'bg-green-50', 'dark:bg-green-900/20');
                });
                zone.addEventListener('drop', function(e) {
                    e.preventDefault();
                    zone.classList.remove('border-green-500', 'bg-green-50', 'dark:bg-green-900/20');
                    const input = zone.querySelector('input[type="file"]');
                    if (input && e.dataTransfer.files.length > 0) {
                        input.files = e.dataTransfer.files;
                        input.dispatchEvent(new Event('change'));
                    }
                });
            });
        });
    }
</script>
