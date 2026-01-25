### 🚀 Démarrage et Environnement

Ces commandes gèrent l'état de tes containers (PHP, MySQL, Redis, etc.).

```bash
# Lancer les services (en arrière-plan)
./vendor/bin/sail up -d

# Arrêter les services
./vendor/bin/sail stop

# Voir l'état des containers
./vendor/bin/sail ps

# Entrer dans le terminal du container (Shell)
./vendor/bin/sail shell

./vendor/bin/sail npm run dev

stripe listen --forward-to localhost/stripe/webhook

```

---

### 🛠️ Génération de fichiers (Artisan)

C'est le cœur de ton travail pour créer la structure de ton site de réservation.

```bash
# Créer un Contrôleur simple
./vendor/bin/sail artisan make:controller PublicController

# Créer un Contrôleur avec les 7 méthodes CRUD (pour le manager)
./vendor/bin/sail artisan make:controller Manager/ReservationController --resource

# Créer un Modèle (représentation de la table BDD)
./vendor/bin/sail artisan make:model Entreprise

# Créer un Modèle + une Migration en une seule commande (Gain de temps !)
./vendor/bin/sail artisan make:model Reservation -m

```

---

### 🗄️ Base de données (Migrations)

Pour synchroniser ton code PHP avec les tables MySQL.

```bash
# Appliquer les nouvelles migrations (créer les tables)
./vendor/bin/sail artisan migrate

# Annuler la dernière migration (oups, erreur de colonne !)
./vendor/bin/sail artisan migrate:rollback

# Tout supprimer et tout recommencer (Attention : vide la BDD)
./vendor/bin/sail artisan migrate:fresh

# Voir le statut des migrations
./vendor/bin/sail artisan migrate:status

```

---

### 🛡️ Débogage et Sécurité

Utile pour ton profil cyber et pour comprendre pourquoi une route ne répond pas.

```bash
# Lister toutes les routes enregistrées (URL -> Controller)
./vendor/bin/sail artisan route:list

# Vider le cache des routes (si une nouvelle route n'est pas détectée)
./vendor/bin/sail artisan route:clear

# Vider le cache de la configuration
./vendor/bin/sail artisan config:clear

```

---

### 💡 Astuce de pro (Alias)

Comme tu es sur Ubuntu, taper `./vendor/bin/sail` à chaque fois va vite te fatiguer. Tu peux créer un alias pour taper juste `sail` :

1. Tape `nano ~/.bashrc`
2. Ajoute cette ligne à la fin : `alias sail="./vendor/bin/sail"`
3. Sauvegarde (Ctrl+O, Entrée, Ctrl+X) et tape `source ~/.bashrc`

Désormais, tu pourras faire simplement : **`sail artisan migrate`**.