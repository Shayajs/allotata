# courses:manage — Gestionnaire des cours en CLI

Commande Artisan complète pour gérer les cours AlloTata depuis le terminal.  
18 sous-commandes couvrant la consultation, la modification, les backups et la maintenance.

## Installation

La commande est automatiquement enregistrée par Laravel. Aucune configuration requise.

```bash
php artisan courses:manage help
```

---

## Sous-commandes

### Consultation

| Commande | Description |
|----------|-------------|
| `help` | Afficher l'aide complète avec tous les exemples |
| `view` | Voir la structure détaillée (tout, un module, ou une leçon) |
| `tree` | Arbre visuel compact de toute la structure |
| `status` | Tableau de bord rapide (compteurs, alertes) |
| `stats` | Statistiques détaillées + progressions utilisateurs |
| `search` | Rechercher dans les titres, descriptions, questions et blocs |

### Modification

| Commande | Description |
|----------|-------------|
| `modify` | Modifier des modules/leçons/questions existants |
| `delete` | Supprimer des éléments (avec confirmation) |
| `add` | Ajouter des modules/leçons/blocs (remplissage IA) |
| `duplicate` | Dupliquer un module complet ou une leçon |
| `move` | Déplacer une leçon vers un autre module |
| `publish` | Publier ou dépublier des leçons |

### Données JSON

| Commande | Description |
|----------|-------------|
| `json` | Export, import, schéma, validation |
| `cmd` | Commande brute (JSON avec champ `action`) |
| `backup` | Sauvegarder toute la structure en JSON horodaté |
| `restore` | Restaurer depuis un fichier backup |
| `diff` | Comparer l'état actuel avec un fichier |

### Maintenance

| Commande | Description |
|----------|-------------|
| `clean` | Détecter et corriger les problèmes (orphelins, ordres, etc.) |

---

## Options globales

| Option | Description |
|--------|-------------|
| `--id=` | ID de l'élément cible |
| `--module=` | ID du module cible |
| `--type=` | Type : `module`, `lesson`, `question`, `global`, `export`, `schema`, `validate`, `import`, `unpublish` |
| `--file=` | Chemin vers un fichier JSON |
| `--inline=` | JSON en ligne (entre guillemets simples) |
| `--query=` | Terme de recherche |
| `--to=` | Module de destination (pour `move`) |
| `--force` | Pas de confirmation interactive |
| `--drafts` | Filtrer uniquement les brouillons (pour `tree`) |
| `--inactive` | Filtrer uniquement les inactifs (pour `tree`) |
| `--compact` | Affichage compact (pour `tree`) |

---

## Exemples détaillés

### 1. Consulter

```bash
# Voir tout l'arbre
php artisan courses:manage tree

# Voir seulement les brouillons
php artisan courses:manage tree --drafts

# Tableau de bord rapide
php artisan courses:manage status

# Stats détaillées avec progressions
php artisan courses:manage stats

# Détails d'un module
php artisan courses:manage view --id=3 --type=module

# Détails d'une leçon (avec blocs et questions)
php artisan courses:manage view --id=5 --type=lesson

# Détails d'une question quiz
php artisan courses:manage view --id=12 --type=question

# Rechercher un terme
php artisan courses:manage search --query="micro-entreprise"
```

### 2. Modifier

```bash
# Modifier le titre d'un module
php artisan courses:manage modify --id=1 --type=module --inline='{"titre":"Nouveau titre"}'

# Modifier plusieurs éléments en une fois
php artisan courses:manage modify --inline='{"modules":[{"id":1,"titre":"Nouveau"}],"lessons":[{"id":5,"titre":"Modifié"}]}'

# Modifier depuis un fichier JSON
php artisan courses:manage modify --file=modifications.json

# Modifier sans confirmation
php artisan courses:manage modify --id=2 --type=module --inline='{"description":"Nouvelle desc"}' --force
```

### 3. Supprimer

```bash
# Supprimer un module (et ses leçons/questions en cascade)
php artisan courses:manage delete --id=5 --type=module

# Supprimer une leçon
php artisan courses:manage delete --id=12 --type=lesson

# Supprimer une question
php artisan courses:manage delete --id=30 --type=question

# Suppression en masse depuis un fichier
php artisan courses:manage delete --file=delete.json

# Suppression forcée (pas de confirmation)
php artisan courses:manage delete --id=5 --type=lesson --force
```

### 4. Ajouter (Remplissage IA)

```bash
# Ajouter des modules complets (mode global)
php artisan courses:manage add --file=cours.json --type=global

# Ajouter des leçons à un module existant
php artisan courses:manage add --file=lecons.json --module=3

# Ajouter des blocs à une leçon existante
php artisan courses:manage add --file=blocs.json --type=lesson --id=10

# Ajout en ligne
php artisan courses:manage add --inline='{"modules":[{"titre":"Test","lessons":[{"titre":"Leçon 1","type":"course","blocks":[{"type":"text","content":{"html":"<p>Hello</p>"}}]}]}]}'
```

### 5. Dupliquer

```bash
# Dupliquer un module complet (avec toutes ses leçons et questions)
php artisan courses:manage duplicate --id=1 --type=module

# Dupliquer une leçon dans le même module
php artisan courses:manage duplicate --id=5 --type=lesson

# Dupliquer une leçon dans un autre module
php artisan courses:manage duplicate --id=5 --type=lesson --module=3
```

> Le module/leçon dupliqué est créé en état **inactif** et **brouillon** avec le suffixe "(copie)".

### 6. Déplacer

```bash
# Déplacer la leçon 5 vers le module 2
php artisan courses:manage move --id=5 --to=2

# Déplacer sans confirmation
php artisan courses:manage move --id=5 --to=2 --force
```

### 7. Publier / Dépublier

```bash
# Publier une leçon
php artisan courses:manage publish --id=5

# Dépublier une leçon
php artisan courses:manage publish --id=5 --type=unpublish

# Publier toutes les leçons d'un module
php artisan courses:manage publish --module=3

# Dépublier toutes les leçons d'un module
php artisan courses:manage publish --module=3 --type=unpublish
```

### 8. JSON

```bash
# Exporter toute la structure en JSON
php artisan courses:manage json --type=export

# Exporter dans un fichier
php artisan courses:manage json --type=export --file=export.json

# Afficher la documentation des formats JSON
php artisan courses:manage json --type=schema

# Valider un fichier JSON sans l'exécuter
php artisan courses:manage json --type=validate --file=data.json

# Importer depuis un fichier (alias de add)
php artisan courses:manage json --type=import --file=cours.json
```

### 9. Commandes brutes

```bash
# Toggle (activer/désactiver)
php artisan courses:manage cmd --inline='{"action":"toggle","activer_modules":[1,2],"desactiver_lecons":[7]}'

# Réordonner les modules
php artisan courses:manage cmd --inline='{"action":"reorder","modules":[3,1,2,5,4]}'

# Réordonner les leçons dans un module
php artisan courses:manage cmd --inline='{"action":"reorder","lessons":{"1":[5,3,4,1,2]}}'

# Depuis un fichier
php artisan courses:manage cmd --file=commande.json
```

### 10. Backup & Restore

```bash
# Sauvegarder
php artisan courses:manage backup
# → Crée courses_backup_2026-02-13_143025.json

# Sauvegarder dans un fichier spécifique
php artisan courses:manage backup --file=mon_backup.json

# Restaurer (ajoute les données, ne remplace pas)
php artisan courses:manage restore --file=courses_backup_2026-02-13_143025.json
```

### 11. Diff

```bash
# Comparer l'état actuel avec un backup
php artisan courses:manage diff --file=backup_hier.json
```

Affiche les modules ajoutés, supprimés et modifiés depuis le backup.

### 12. Nettoyage

```bash
# Analyser les problèmes
php artisan courses:manage clean

# Corriger automatiquement
php artisan courses:manage clean --force
```

Détecte et corrige :
- Modules sans leçons
- Leçons sans contenu (0 blocs)
- Quiz sans questions
- Questions orphelines (leçon supprimée)
- Leçons orphelines (module supprimé)
- Ordres discontinus (trous dans la séquence)
- Progressions utilisateur orphelines

---

## Formats JSON

Pour voir la documentation complète des formats JSON acceptés :

```bash
php artisan courses:manage json --type=schema
```

### Résumé rapide

**Remplissage (add)** — Créer de nouveaux éléments :
```json
{
  "modules": [{
    "titre": "Mon module",
    "lessons": [{
      "titre": "Ma leçon",
      "type": "course",
      "blocks": [
        { "type": "heading", "content": { "text": "Titre", "level": 1 } },
        { "type": "text", "content": { "html": "<p>Contenu</p>" } }
      ]
    }]
  }]
}
```

**Modification (modify)** — Modifier des éléments existants par ID :
```json
{
  "modules": [{ "id": 1, "titre": "Nouveau titre" }],
  "lessons": [{ "id": 5, "type": "quiz" }]
}
```

**Suppression (delete)** — Tableau d'IDs :
```json
{ "modules": [1, 3], "lessons": [5], "questions": [10, 11] }
```

**Toggle** — Activer/désactiver :
```json
{
  "activer_modules": [1, 2],
  "desactiver_lecons": [7],
  "publier_lecons": [5, 6]
}
```

**Réordonnancement** — Nouvel ordre par position :
```json
{
  "modules": [3, 1, 2],
  "lessons": { "1": [5, 3, 4, 1, 2] }
}
```

---

## Utilisation en production (Docker)

Si l'application tourne dans un container Docker :

```bash
# Depuis le serveur host
docker exec laravel_app sh -c 'cd /var/www/html && php artisan courses:manage tree'

# Via SSH
ssh user@server "docker exec laravel_app sh -c 'cd /var/www/html && php artisan courses:manage status'"
```

---

## Architecture

```
app/
├── Console/Commands/
│   └── CoursesManageCommand.php    ← La commande Artisan (18 sous-commandes)
├── Services/
│   └── CoursesBulkService.php      ← Logique métier partagée (web + CLI)
├── Http/Controllers/Admin/
│   └── CourseController.php        ← Controller web (délègue au service)
└── Models/
    ├── CourseModule.php
    ├── CourseLesson.php
    └── QuizQuestion.php
```

Le `CoursesBulkService` centralise toute la logique métier :
- Validation et exécution des remplissages (fill)
- Validation et exécution des actions bulk (update, delete, toggle, reorder)
- Export complet de la structure
- Documentation des schémas JSON

Le controller web et la commande Artisan utilisent tous deux ce service, garantissant un comportement identique.
