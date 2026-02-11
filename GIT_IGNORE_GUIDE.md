# Guide : Ignorer des fichiers pour les commits et pulls Git

## Situation actuelle

Les fichiers `nginx.conf` et `docker-compose.yaml` sont maintenant dans le `.gitignore` et ne sont **pas** suivis par Git localement.

## Scénarios possibles

### Scénario 1 : Les fichiers ne sont PAS dans le dépôt distant ✅ (Votre cas)

Si les fichiers n'existent pas dans le dépôt distant, le `.gitignore` suffit. Git les ignorera automatiquement lors des :
- ✅ **Commits** : Les fichiers ne seront pas inclus
- ✅ **Pulls** : Les fichiers locaux ne seront pas écrasés

**Aucune action supplémentaire nécessaire !**

### Scénario 2 : Les fichiers SONT dans le dépôt distant ⚠️

Si les fichiers existent déjà dans le dépôt distant, il faut les retirer du suivi Git.

#### Étape 1 : Retirer les fichiers du suivi Git (sans les supprimer localement)

```bash
cd /home/shayajs/www
git rm --cached nginx.conf docker-compose.yaml
```

#### Étape 2 : Commiter cette suppression

```bash
git commit -m "Remove nginx.conf and docker-compose.yaml from git tracking"
git push
```

#### Étape 3 : Protéger les fichiers locaux lors des pulls futurs

Pour éviter que Git ne réapplique les fichiers du dépôt distant lors d'un pull :

```bash
# Option 1 : Marquer comme "assume-unchanged" (recommandé pour fichiers locaux)
git update-index --assume-unchanged nginx.conf docker-compose.yaml

# Option 2 : Marquer comme "skip-worktree" (pour fichiers avec modifications locales)
git update-index --skip-worktree nginx.conf docker-compose.yaml
```

**Différence entre les deux :**
- `--assume-unchanged` : Git assume que le fichier n'a pas changé (plus léger)
- `--skip-worktree` : Git ignore complètement le fichier (plus robuste pour fichiers locaux)

## Commandes utiles

### Vérifier si un fichier est ignoré

```bash
git check-ignore nginx.conf docker-compose.yaml
```

### Vérifier si un fichier est suivi par Git

```bash
git ls-files | grep nginx.conf
git ls-files | grep docker-compose.yaml
```

### Voir les fichiers ignorés

```bash
git status --ignored
```

### Annuler "assume-unchanged" ou "skip-worktree"

Si vous voulez que Git recommence à suivre les fichiers :

```bash
git update-index --no-assume-unchanged nginx.conf docker-compose.yaml
git update-index --no-skip-worktree nginx.conf docker-compose.yaml
```

## Protection supplémentaire : Fichiers locaux lors des pulls

Si vous voulez être **absolument sûr** que vos fichiers locaux ne seront jamais écrasés lors d'un pull, utilisez :

```bash
# Pour nginx.conf
git update-index --skip-worktree nginx.conf

# Pour docker-compose.yaml
git update-index --skip-worktree docker-compose.yaml
```

Cela garantit que même si quelqu'un commit ces fichiers dans le dépôt distant, Git ne les écrasera pas lors d'un pull.

## Vérification finale

Pour vérifier que tout fonctionne :

```bash
# 1. Vérifier que les fichiers sont ignorés
git check-ignore nginx.conf docker-compose.yaml

# 2. Vérifier qu'ils n'apparaissent pas dans git status
git status

# 3. Tester un commit (les fichiers ne doivent pas apparaître)
git add .
git status
```

## Résumé

✅ **Fichiers déjà dans .gitignore** : `nginx.conf` et `docker-compose.yaml`
✅ **Fichiers non suivis localement** : Confirmed
✅ **Protection recommandée** : Utiliser `git update-index --skip-worktree` pour une protection maximale
