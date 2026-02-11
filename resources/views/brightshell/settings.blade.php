@extends('brightshell.layout')

@section('title', 'Paramètres')

@section('content')
<div class="grid grid-2">
    <!-- Logo -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Logo BrightShell</h3>
        </div>
        <div style="text-align: center; padding: 2rem 0;">
            @if($logo)
                <img src="{{ $logo }}" alt="Logo BrightShell" style="max-width: 150px; max-height: 100px; object-fit: contain; margin-bottom: 1rem;">
            @else
                <div style="width: 120px; height: 80px; margin: 0 auto 1rem; background: var(--bs-bg-hover); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--bs-text-muted);"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
            @endif
        </div>
        <form action="{{ route('brightshell.settings.logo') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label class="form-label">Importer un nouveau logo</label>
                <input type="file" name="logo" class="form-input" accept="image/png,image/jpeg,image/svg+xml,image/webp" required>
            </div>
            <button type="submit" class="btn btn-primary w-full">Mettre à jour le logo</button>
        </form>
    </div>

    <!-- Signature -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Signature Numérique</h3>
        </div>
        <div style="text-align: center; padding: 2rem 0;">
            @if($signature)
                <img src="{{ $signature }}" alt="Signature" style="max-width: 200px; max-height: 100px; object-fit: contain; filter: grayscale(1); margin-bottom: 1rem;">
            @else
                <div style="width: 200px; height: 80px; margin: 0 auto 1rem; border: 1px dashed var(--bs-border); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <span class="text-muted text-xs">Aucune signature</span>
                </div>
            @endif
        </div>
        <form action="{{ route('brightshell.settings.signature') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label class="form-label">Importer votre signature (PNG fond transparent conseillé)</label>
                <input type="file" name="signature" class="form-input" accept="image/png,image/jpeg,image/svg+xml,image/webp" required>
            </div>
            <button type="submit" class="btn btn-primary w-full">Mettre à jour la signature</button>
        </form>
    </div>
    
    <!-- Favicon -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Favicon</h3>
        </div>
        <div style="text-align: center; padding: 1.5rem 0;">
            @if($favicon)
                <img src="{{ $favicon }}" alt="Favicon" style="width: 32px; height: 32px; object-fit: contain; margin-bottom: 1rem; padding: 4px; background: white; border-radius: 4px;">
            @else
                <div style="width: 32px; height: 32px; margin: 0 auto 1rem; border: 1px dashed var(--bs-border); border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                    <span class="text-xs" style="font-size: 8px;">32x32</span>
                </div>
            @endif
        </div>
        <form action="{{ route('brightshell.settings.favicon') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group" style="margin-top: 1rem;">
                <label class="form-label">Importer votre favicon (PNG/ICO)</label>
                <input type="file" name="favicon" class="form-input" accept="image/png,image/x-icon" required>
            </div>
            <button type="submit" class="btn btn-primary w-full">Mettre à jour le favicon</button>
        </form>
    </div>

    <!-- Instructions -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Aide & Identité</h3>
        </div>
        <div class="grid grid-2" style="gap: 1rem;">
            <div>
                <p class="text-muted text-sm">Raison sociale</p>
                <p class="font-bold">{{ $entreprise['forme_juridique'] }} {{ $entreprise['nom'] }}</p>
            </div>
            <div>
                <p class="text-muted text-sm">Responsable</p>
                <p class="font-bold">{{ $entreprise['responsable'] }}</p>
            </div>
            <div>
                <p class="text-muted text-sm">SIRET</p>
                <p class="font-bold">{{ $entreprise['siret'] }}</p>
            </div>
            <div>
                <p class="text-muted text-sm">Email</p>
                <p class="font-bold">{{ $entreprise['email'] }}</p>
            </div>
        </div>
        <p class="text-muted text-xs mt-4">
            Pour modifier ces informations, éditez <code>BrightShellController.php</code>.
        </p>
    </div>
</div>

<!-- Mentions légales factures -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">Mentions légales sur les factures</h3>
    </div>
    <div class="grid grid-2">
        <div>
            <p class="text-muted text-sm mb-2">TVA</p>
            <p class="font-bold">TVA non applicable, art. 293 B du CGI</p>
            <p class="text-muted text-xs">Micro-entreprise en franchise de base de TVA</p>
        </div>
        <div>
            <p class="text-muted text-sm mb-2">Seuils {{ date('Y') }}</p>
            <p class="font-bold">Franchise TVA : 36 800 €</p>
            <p class="font-bold">Plafond Micro : 77 700 €</p>
        </div>
    </div>
</div>

<!-- Config Mail -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">Configuration Mailing</h3>
    </div>
    <div class="grid grid-3">
        <div>
            <p class="text-muted text-sm">Serveur SMTP</p>
            <p class="font-bold">mail.brightshell.fr</p>
        </div>
        <div>
            <p class="text-muted text-sm">Port</p>
            <p class="font-bold">465 (SSL)</p>
        </div>
        <div>
            <p class="text-muted text-sm">Email</p>
            <p class="font-bold">lucas.espinar@brightshell.fr</p>
        </div>
    </div>
    <p class="text-muted text-xs mt-4">
        Configuration dans <code>.env</code> : BRIGHTSHELL_MAIL_HOST, BRIGHTSHELL_MAIL_PORT, etc.
    </p>
</div>

<!-- Chemin logo -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">Emplacement du logo</h3>
    </div>
    <p class="text-muted">Le logo est stocké dans <code>public/media/brightshell/logo.png</code></p>
</div>

<!-- Couleurs PDF -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">Couleurs des Devis & Factures PDF</h3>
    </div>
    <form action="{{ route('brightshell.settings.pdf-colors') }}" method="POST">
        @csrf
        <div class="grid grid-3" style="gap: 1rem; margin-bottom: 1.5rem;">
            <div class="form-group">
                <label class="form-label">Couleur principale</label>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <input type="color" name="pdf_color_primary" value="{{ $couleurs['primary'] ?? '#5bbce4' }}" 
                           style="width: 50px; height: 38px; border: 1px solid var(--bs-border); border-radius: 6px; cursor: pointer;">
                    <input type="text" class="form-input" value="{{ $couleurs['primary'] ?? '#5bbce4' }}" readonly style="flex: 1;">
                </div>
                <p class="text-muted text-xs mt-1">Titres, totaux, accents</p>
            </div>
            <div class="form-group">
                <label class="form-label">Couleur secondaire</label>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <input type="color" name="pdf_color_secondary" value="{{ $couleurs['secondary'] ?? '#0a0e1a' }}" 
                           style="width: 50px; height: 38px; border: 1px solid var(--bs-border); border-radius: 6px; cursor: pointer;">
                    <input type="text" class="form-input" value="{{ $couleurs['secondary'] ?? '#0a0e1a' }}" readonly style="flex: 1;">
                </div>
                <p class="text-muted text-xs mt-1">En-têtes de tableau</p>
            </div>
            <div class="form-group">
                <label class="form-label">Couleur du texte</label>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <input type="color" name="pdf_color_text" value="{{ $couleurs['text'] ?? '#1a1a1a' }}" 
                           style="width: 50px; height: 38px; border: 1px solid var(--bs-border); border-radius: 6px; cursor: pointer;">
                    <input type="text" class="form-input" value="{{ $couleurs['text'] ?? '#1a1a1a' }}" readonly style="flex: 1;">
                </div>
                <p class="text-muted text-xs mt-1">Texte principal</p>
            </div>
            <div class="form-group">
                <label class="form-label">Couleur atténuée</label>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <input type="color" name="pdf_color_muted" value="{{ $couleurs['muted'] ?? '#6b7280' }}" 
                           style="width: 50px; height: 38px; border: 1px solid var(--bs-border); border-radius: 6px; cursor: pointer;">
                    <input type="text" class="form-input" value="{{ $couleurs['muted'] ?? '#6b7280' }}" readonly style="flex: 1;">
                </div>
                <p class="text-muted text-xs mt-1">Labels, méta</p>
            </div>
            <div class="form-group">
                <label class="form-label">Fond des encadrés</label>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <input type="color" name="pdf_color_background" value="{{ $couleurs['background'] ?? '#f9fafb' }}" 
                           style="width: 50px; height: 38px; border: 1px solid var(--bs-border); border-radius: 6px; cursor: pointer;">
                    <input type="text" class="form-input" value="{{ $couleurs['background'] ?? '#f9fafb' }}" readonly style="flex: 1;">
                </div>
                <p class="text-muted text-xs mt-1">Blocs client, notes</p>
            </div>
            <div class="form-group">
                <label class="form-label">Bordures</label>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <input type="color" name="pdf_color_border" value="{{ $couleurs['border'] ?? '#e5e7eb' }}" 
                           style="width: 50px; height: 38px; border: 1px solid var(--bs-border); border-radius: 6px; cursor: pointer;">
                    <input type="text" class="form-input" value="{{ $couleurs['border'] ?? '#e5e7eb' }}" readonly style="flex: 1;">
                </div>
                <p class="text-muted text-xs mt-1">Lignes, séparateurs</p>
            </div>
            <div class="form-group">
                <label class="form-label">Couleur succès</label>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <input type="color" name="pdf_color_success" value="{{ $couleurs['success'] ?? '#10b981' }}" 
                           style="width: 50px; height: 38px; border: 1px solid var(--bs-border); border-radius: 6px; cursor: pointer;">
                    <input type="text" class="form-input" value="{{ $couleurs['success'] ?? '#10b981' }}" readonly style="flex: 1;">
                </div>
                <p class="text-muted text-xs mt-1">Badge "Payée"</p>
            </div>
        </div>
        
        <div class="flex gap-2" style="align-items: center;">
            <button type="submit" class="btn btn-primary">Enregistrer les couleurs</button>
            <button type="button" class="btn btn-secondary" onclick="resetColors()">Réinitialiser par défaut</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
// Synchroniser les champs couleur avec le texte
document.querySelectorAll('input[type="color"]').forEach(input => {
    const textInput = input.parentElement.querySelector('input[type="text"]');
    input.addEventListener('input', function() {
        textInput.value = this.value;
    });
});

function resetColors() {
    const defaults = {
        'pdf_color_primary': '#5bbce4',
        'pdf_color_secondary': '#0a0e1a',
        'pdf_color_text': '#1a1a1a',
        'pdf_color_muted': '#6b7280',
        'pdf_color_background': '#f9fafb',
        'pdf_color_border': '#e5e7eb',
        'pdf_color_success': '#10b981',
    };
    
    for (const [name, value] of Object.entries(defaults)) {
        const colorInput = document.querySelector(`input[name="${name}"]`);
        const textInput = colorInput.parentElement.querySelector('input[type="text"]');
        colorInput.value = value;
        textInput.value = value;
    }
}
</script>
@endpush
@endsection
