# Guide d'intégration de la Médiathèque

## Utilisation de la modale de sélection de fichiers

La médiathèque fournit une modale réutilisable qui peut être intégrée dans n'importe quel éditeur ou formulaire du site.

### Exemple 1 : Intégration dans un éditeur de texte

```html
<!-- Bouton pour ouvrir la sélection de fichiers -->
<button type="button" onclick="openMediaSelector(handleFileSelect)">
    Insérer une image
</button>

<script>
function handleFileSelect(file, url) {
    // file : objet avec les informations du fichier (id, name, type, etc.)
    // url : URL complète du fichier (ex: /media/media/images/photo.jpg)
    
    // Exemple : insérer dans un éditeur TinyMCE
    if (window.tinymce) {
        const editor = tinymce.get('content');
        if (editor) {
            if (file.type === 'image') {
                editor.insertContent(`<img src="${url}" alt="${file.name}" />`);
            } else {
                editor.insertContent(`<a href="${url}">${file.name}</a>`);
            }
        }
    }
    
    // Exemple : insérer dans un textarea simple
    const textarea = document.getElementById('description');
    if (textarea) {
        textarea.value += `\n${url}\n`;
    }
    
    console.log('Fichier sélectionné:', file);
    console.log('URL:', url);
}
</script>
```

### Exemple 2 : Intégration dans un champ input

```html
<!-- Champ avec bouton pour sélectionner un fichier -->
<div class="flex gap-2">
    <input type="text" id="image-url" readonly class="flex-1" placeholder="Aucun fichier sélectionné">
    <button type="button" onclick="openMediaSelector(selectImageUrl)">
        Parcourir
    </button>
</div>

<script>
function selectImageUrl(file, url) {
    document.getElementById('image-url').value = url;
    
    // Optionnel : afficher un aperçu
    const preview = document.getElementById('image-preview');
    if (preview && file.type === 'image') {
        preview.innerHTML = `<img src="${url}" alt="${file.name}" class="max-w-xs">`;
    }
}
</script>
```

### Exemple 3 : Intégration dans un éditeur de cours (Blade)

```blade
<!-- Dans resources/views/admin/courses/lesson-edit.blade.php -->

<button type="button" 
        onclick="openMediaSelector(insertIntoEditor)" 
        class="px-4 py-2 bg-green-600 text-white rounded-lg">
    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
    </svg>
    Insérer un média
</button>

<script>
function insertIntoEditor(file, url) {
    // Insérer dans l'éditeur de blocs de cours
    const editor = document.getElementById('content-editor');
    if (editor && window.courseEditor) {
        if (file.type === 'image') {
            window.courseEditor.insertBlock({
                type: 'image',
                data: {
                    src: url,
                    alt: file.name || file.alt_text || '',
                }
            });
        } else if (file.type === 'video') {
            window.courseEditor.insertBlock({
                type: 'video',
                data: {
                    src: url,
                }
            });
        } else {
            // Document ou autre
            window.courseEditor.insertBlock({
                type: 'text',
                data: {
                    content: `<a href="${url}" target="_blank">${file.name}</a>`,
                }
            });
        }
    }
}
</script>
```

### Exemple 4 : Filtrage par type de fichier

```javascript
// Ouvrir la modale en ne montrant que les images
openMediaSelector(function(file, url) {
    console.log('Image sélectionnée:', url);
}, ['image']); // Types acceptés : 'image', 'video', 'audio', 'pdf', 'text', 'document'

// Ouvrir la modale en ne montrant que les vidéos
openMediaSelector(function(file, url) {
    console.log('Vidéo sélectionnée:', url);
}, ['video']);
```

## Fonctionnalités disponibles

### Navigation
- **Arborescence de dossiers** : Naviguez dans une structure de dossiers comme sur Windows
- **Breadcrumb** : Fil d'Ariane pour savoir où vous êtes
- **Recherche** : Recherche en temps réel dans les noms et descriptions de fichiers

### Opérations sur les fichiers
- **Upload** : Glisser-déposer ou sélection multiple
- **Renommer** : Clic droit ou menu d'actions → Renommer
- **Déplacer** : Déplacer un fichier dans un autre dossier
- **Supprimer** : Suppression avec confirmation
- **Prévisualisation** : Aperçu des images et vidéos directement dans la grille

### Types de fichiers supportés
- **Images** : JPG, PNG, GIF, WebP, SVG
- **Vidéos** : MP4, WebM, OGG, MOV, AVI
- **Audio** : MP3, WAV, OGG
- **Documents** : PDF
- **Textes** : TXT, MD, HTML, CSS, JS

## Structure de l'objet `file`

Lors de la sélection d'un fichier, vous recevez un objet avec les propriétés suivantes :

```javascript
{
    id: 1,
    name: "mon-image.jpg",
    original_name: "image_originale.jpg",
    path: "media/images/2024/mon-image_1234567890.jpg",
    folder_path: "images/2024",
    type: "image", // image, video, audio, pdf, text, document
    mime_type: "image/jpeg",
    size: 1048576, // en octets
    width: 1920, // pour les images/vidéos
    height: 1080, // pour les images/vidéos
    description: "Description optionnelle",
    alt_text: "Texte alternatif",
    uploaded_by: 1,
    created_at: "2024-01-20T10:00:00.000000Z",
    updated_at: "2024-01-20T10:00:00.000000Z",
    uploader: {
        id: 1,
        name: "Admin",
        email: "admin@example.com"
    }
}
```

## Migration

Pour activer le système, exécutez la migration :

```bash
php artisan migrate
```

Cela créera la table `media_files` pour stocker les métadonnées des fichiers.

## Stockage

Les fichiers sont stockés dans `storage/app/public/media/` et accessibles via la route `/media/{path}`.

Le contrôleur `StorageController` a été mis à jour pour autoriser l'accès au dossier `media`.

## API disponible

Toutes les routes sont préfixées par `/admin/api/media/` :

- `GET /admin/api/media/list` - Liste des fichiers (avec pagination, filtres, recherche)
- `POST /admin/api/media/upload` - Upload d'un fichier
- `GET /admin/api/media/{id}` - Détails d'un fichier
- `PUT /admin/api/media/{id}/rename` - Renommer un fichier
- `PUT /admin/api/media/{id}/move` - Déplacer un fichier
- `DELETE /admin/api/media/{id}` - Supprimer un fichier

Toutes ces routes nécessitent l'authentification et le rôle admin.
