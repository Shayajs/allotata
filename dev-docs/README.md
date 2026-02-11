# Documentation développeur (/dev)

Chaque sous-dossier est une **section** affichée sur la page `/dev`.

## section.json

Dans chaque section, un fichier `section.json` définit :

- **title** : intitulé de la section
- **emoji** : emoji affiché à côté du titre
- **color** : couleur hex (ex. `#10b981`) pour la section
- **description** : texte court sur la page d’accueil `/dev`
- **admin_only** : liste de noms de fichiers (ex. `EMERGENCY_RECOVERY.md`) ou de **regex** (ex. `/^diagnostic.*/`) réservés aux administrateurs. Les autres utilisateurs ne voient pas ces fichiers.

Seuls les `.md` à la racine du dossier de section sont listés.

## Exemple

```json
{
    "title": "Stripe & Paiements",
    "emoji": "💳",
    "color": "#635bff",
    "description": "Configuration Stripe, webhooks, paiements.",
    "admin_only": ["SECRET.md", "/^internal-.*/"]
}
```
