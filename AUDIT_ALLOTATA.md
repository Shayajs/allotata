# AUDIT COMPLET — ALLOTATA
## De simple site web à entité indétrônable

> Document rédigé le 11/02/2026 — Audit à froid (sans exécution Docker)
> Projet : Laravel 12 | Tailwind CSS v4 | Stripe | Reverb/Pusher | SQLite/MySQL

---

## TABLE DES MATIÈRES

1. [FAILLES CRITIQUES DE SÉCURITÉ](#1-failles-critiques-de-sécurité)
2. [ARCHITECTURE & CODE SMELLS](#2-architecture--code-smells)
3. [BASE DE DONNÉES & MODÈLES](#3-base-de-données--modèles)
4. [SYSTÈME DE PAIEMENT (STRIPE)](#4-système-de-paiement-stripe)
5. [AUTHENTIFICATION & SÉCURITÉ UTILISATEUR](#5-authentification--sécurité-utilisateur)
6. [FRONTEND / UX / UI](#6-frontend--ux--ui)
7. [EMAILS & NOTIFICATIONS](#7-emails--notifications)
8. [PWA & PERFORMANCE](#8-pwa--performance)
9. [API & INTÉGRATIONS TIERCES](#9-api--intégrations-tierces)
10. [TESTS & QUALITÉ](#10-tests--qualité)
11. [DEVOPS & DÉPLOIEMENT](#11-devops--déploiement)
12. [BRIGHTSHELL (MODULE ERP)](#12-brightshell-module-erp)
13. [PISTES D'AMÉLIORATION BUSINESS](#13-pistes-damélioration-business)
14. [QUESTIONS POUR LE FONDATEUR](#14-questions-pour-le-fondateur)

---

## 1. FAILLES CRITIQUES DE SÉCURITÉ

### 🔴 CRITIQUE — TempAdminController SANS PROTECTION

**Fichier** : `routes/web.php` (lignes ~243-249)

Les routes `/temp-admin/*` n'ont **aucun middleware**. N'importe qui peut :
- Créer un compte admin (`/temp-admin/create-admin`)
- Promouvoir n'importe quel utilisateur en admin (`/temp-admin/promote/{user}`)
- Se connecter en tant que n'importe quel utilisateur (`/temp-admin/login-as/{user}`)

**Impact** : Compromission totale du système.
**Action** : Supprimer immédiatement ou ajouter le middleware `['auth', 'admin']`.

---

### 🔴 CRITIQUE — Routes de debug en production

| Route | Problème |
|-------|----------|
| `/test-storage` | Expose les chemins du serveur (storage_path, base_path) |
| `/test-image` | Sert des fichiers directement sans contrôle |
| `/diagnostic-auth` | Expose les détails d'authentification utilisateur |
| `/debug-cannelle` | Aucune authentification, email hardcodé |
| `/test-email` | Permet d'envoyer des emails (vérification admin insuffisante) |
| `/run-error-notifications-migration` | Route de migration DB accessible |

**Action** : Tout supprimer ou conditionner à `APP_ENV=local`.

---

### 🔴 CRITIQUE — `is_admin` dans le $fillable du User

Le champ `is_admin` est dans la liste `$fillable` du modèle `User`. Un attaquant pourrait potentiellement s'auto-promouvoir admin via mass assignment si un contrôleur utilise `$user->update($request->all())`.

Même risque pour : `google2fa_secret`, `stripe_id`.

**Action** : Retirer `is_admin`, `google2fa_secret`, `stripe_id` du `$fillable` et les manipuler explicitement.

---

### 🟠 HAUTE — Session lifetime de 10 ans

La configuration de session semble avoir `SESSION_LIFETIME=5256000` (10 ans). Un token volé reste valide quasi indéfiniment.

**Action** : Réduire à 2-4 semaines max avec "Remember Me" optionnel.

---

### 🟠 HAUTE — Emergency Recovery prédictible

Le hash de la route d'urgence est `md5(APP_KEY + 'emergency-recovery-allotata')`. Si l'APP_KEY est compromise, la route d'urgence l'est aussi. Le token de fallback est `'change-me-in-production-' + random`.

**Action** : Vérifier que `EMERGENCY_RECOVERY_TOKEN` est défini en production avec un token fort. Ajouter un IP whitelist.

---

### 🟡 MOYENNE — Pas de Content Security Policy (CSP)

Aucun header CSP détecté. Le site est vulnérable aux attaques XSS via injection de scripts tiers.

**Action** : Ajouter des headers CSP via middleware.

---

### 🟡 MOYENNE — XSS potentiel dans le frontend

- `welcome.blade.php` : utilisation de `innerHTML` avec des données utilisateur
- Templates Blade : certains usages de `{!! !!}` (raw output) sans sanitisation
- `@json()` sans flags de protection XSS (`JSON_HEX_TAG`, etc.)
- `checkout.js` : concaténation HTML avec données utilisateur (ligne ~199)

**Action** : Audit systématique de tous les `{!! !!}`, remplacement de `innerHTML` par `textContent`, ajout des flags JSON.

---

### 🟡 MOYENNE — Exemption CSRF large

```php
$middleware->validateCsrfTokens(except: ['stripe/*', 'broadcasting/auth']);
```

L'exemption `stripe/*` est standard mais couvre potentiellement plus que le webhook.

**Action** : Restreindre à `stripe/webhook` uniquement.

---

## 2. ARCHITECTURE & CODE SMELLS

### Contrôleurs trop volumineux

| Contrôleur | Problème estimé |
|------------|----------------|
| `AdminController` | ~2000+ lignes — à découper |
| `CheckoutController` | ~1500+ lignes — logique paiement complexe |
| `ReservationController` | ~700+ lignes |
| `StripeWebhookController` | ~690 lignes |

**Action** : Extraire en services dédiés. Un contrôleur = orchestration, pas logique métier.

---

### Routes API mélangées dans web.php

Il n'y a pas de fichier `routes/api.php`. Toutes les routes API (`/api/address/*`, `/api/tracking/*`, `/api/search/*`) sont dans `web.php`.

**Impact** : Pas de middleware API, pas de rate limiting, pas de versioning.
**Action** : Créer `routes/api.php` avec middleware `api`, throttle, et préfixe `/api/v1/`.

---

### Mélange français/anglais dans le code

Les noms de modèles, méthodes et variables alternent entre français et anglais :
- `Entreprise`, `Echeance`, `Facture` (FR) vs `User`, `Feedback`, `Stock` (EN)
- `peutEtreGereePar()` vs `isAccessibleBy()`
- `est_paye`, `est_lu` vs `is_admin`, `is_locked`

**Impact** : Confusion pour de futurs développeurs.
**Recommandation** : Choisir une convention et s'y tenir (le français pour le domaine métier est cohérent avec la cible FR).

---

### Pas de DTOs / Value Objects

Les données circulent sous forme de tableaux associatifs. Aucun DTO ou Value Object pour structurer les données complexes (paiements, réservations, etc.).

**Action** : Introduire des DTOs pour les flux critiques (checkout, réservation, facturation).

---

### Authorisation incohérente

- Certains contrôleurs vérifient `peutEtreGereePar()`
- D'autres vérifient `is_admin` directement
- Certains utilisent des policies Laravel, d'autres non
- Pas de Gate/Policy systématique

**Action** : Unifier via les Policies Laravel + middleware d'autorisation.

---

## 3. BASE DE DONNÉES & MODÈLES

### 91 modèles identifiés

Le projet contient environ 91 modèles Eloquent. C'est un projet mature et ambitieux.

### Index manquants (performance)

| Table | Colonnes à indexer | Raison |
|-------|-------------------|--------|
| `reservations` | `statut`, `date_reservation`, `est_paye`, `membre_id` | Requêtes fréquentes |
| `reservations` | `(entreprise_id, date_reservation)` composé | Requêtes calendrier |
| `factures` | `statut`, `type_facture`, `(entreprise_id, date_facture)` | Rapports |
| `entreprises` | `est_verifiee`, `type_activite`, `ville`, `code_postal` | Recherche |
| `entreprises` | `(latitude, longitude)` | Recherche géo |
| `users` | `est_client`, `est_gerant`, `is_admin`, `statut_compte` | Filtrage rôles |
| `messages` | `est_lu`, `(conversation_id, created_at)` | Messages non lus |
| `notifications` | `est_lue`, `(user_id, est_lue, created_at)` | Notifications non lues |
| `echeances` | `statut`, `subscription_type`, `(user_id, statut)` | Paiements |

**Impact** : Lenteurs significatives à mesure que la base grossit.

---

### Soft Deletes manquants

Modèles qui devraient avoir le soft delete mais ne l'ont pas :

| Modèle | Raison |
|--------|--------|
| `Reservation` | Historique client, obligations légales |
| `Facture` | Obligation légale de conservation |
| `Message` / `Conversation` | Historique communication |
| `TypeService` / `Produit` | Services désactivés mais pas supprimés |
| `EntrepriseMembre` | Historique des équipes |
| `CustomPrice` | Piste d'audit |
| `Echeance` | Historique comptable |
| `EntrepriseSubscription` | Historique abonnements |

---

### Relations manquantes ou incohérentes

1. **Service vs TypeService** : Le modèle `Service` existe mais semble inutilisé, `TypeService` est utilisé partout → confusion potentielle
2. **User → StripeTransaction** : Relation manquante côté User
3. **Avis / ServiceAvis / ProduitAvis** : 3 modèles séparés au lieu d'une relation polymorphique
4. **RealisationPhoto** : 3 clés étrangères (`avis_id`, `service_avis_id`, `produit_avis_id`) au lieu d'un polymorphe `photoable_type/id`
5. **Conversation** : peut lier `reservation_id`, `produit_id`, ou `type_service_id` → devrait être polymorphique

---

### Risques N+1

Les accesseurs suivants déclenchent des requêtes à chaque appel sans eager loading :

- `User::getNombreNotificationsNonLuesAttribute()` → `count()`
- `Entreprise::getNoteMoyenneAttribute()` → `avg()`
- `Entreprise::getNombreAvisAttribute()` → `count()`
- `Produit::getNoteMoyenneAttribute()` → `avg()`
- `TypeService::getNoteMoyenneAttribute()` → `avg()`
- `Entreprise::getCompletionStatus()` → multiples queries
- `Conversation::messagesNonLus()` → `count()`

**Action** : Utiliser `withCount()`, `withAvg()`, et eager loading systématique.

---

### Mass Assignment — Champs sensibles exposés

| Modèle | Champ dangereux dans $fillable |
|--------|-------------------------------|
| `User` | `is_admin`, `google2fa_secret`, `stripe_id` |
| `Entreprise` | `est_verifiee` |
| `AccountLockout` | `is_locked`, `locked_until`, `failed_attempts` |

---

## 4. SYSTÈME DE PAIEMENT (STRIPE)

### Points forts
- Laravel Cashier v16.1 intégré
- Protection contre les race conditions (`lockForUpdate()`)
- Validation de montant (tolérance 0.01€)
- Audit log des paiements (`PaymentAuditLog`)
- Idempotence des opérations
- Réconciliation quotidienne CRON pour les webhooks manqués
- Support du grandfathering (anciens prix)

### Points faibles

| Problème | Impact |
|----------|--------|
| `StripeWebhookController` = 690 lignes | Difficile à maintenir, à tester |
| Multiples chemins de vérification paiement | Complexité accrue, bugs possibles |
| Pas de rate limiting sur les appels Stripe API | Risque de ban Stripe |
| Pas de circuit breaker pour les appels externes | Cascade de failures possible |
| Logique métier dans le contrôleur webhook | Devrait être dans des services/handlers dédiés |

### Flux de paiement actuel
1. Checkout Session (legacy) OU PaymentIntent (moderne)
2. Webhook : `checkout.session.completed`, `payment_intent.succeeded`
3. Vérification directe sur la page de succès
4. Réconciliation CRON quotidienne

**Recommandation** : Extraire chaque handler de webhook dans une classe dédiée (pattern Strategy/Handler).

---

## 5. AUTHENTIFICATION & SÉCURITÉ UTILISATEUR

### Points forts
- Verrouillage après 5 tentatives (15 min)
- Détection d'anciens mots de passe
- Logging de sécurité
- Tracking des appareils de confiance
- 2FA : Google Authenticator (TOTP) + Email/SMS
- Vérification IP pour 2FA conditionnel

### Points faibles

| Problème | Recommandation |
|----------|---------------|
| Un seul guard (`web`) | Ajouter un guard `api` pour les endpoints API |
| Un seul provider (`users`) | Suffisant pour l'instant |
| Password reset custom + natif Laravel coexistent | Unifier |
| Pas de OAuth/Social Login | Ajouter Google/Apple pour réduire la friction |
| Pas de passwordless login (magic link) | Tendance forte, à considérer |

---

## 6. FRONTEND / UX / UI

### Stack
- Blade + Tailwind CSS v4 + Vanilla JS (pas de framework JS)
- Vite 7.0.7 avec multi-entrypoints
- Font : Instrument Sans (Bunny Fonts)
- Thème sombre supporté (cookie)

### Forces
- Design gradient vert→orange cohérent
- 54 composants Blade réutilisables
- Support mobile avec safe-area-insets
- Touch targets >= 44x44px

### Problèmes UX/UI identifiés

| Problème | Détail |
|----------|--------|
| **Scripts inline massifs** | `welcome.blade.php` : 200+ lignes de JS inline, `agenda.blade.php` : 600+ lignes |
| **Pas de code splitting** | Tout le JS dans quelques gros bundles |
| **Duplication de code** | Formulaire réservation dupliqué mobile/desktop dans `agenda.blade.php` |
| **Inconsistance des styles de boutons** | Mix gradient / solid sans règle claire |
| **Inconsistance des formulaires** | Styles différents selon les vues |
| **Navigation mobile/desktop** | Patterns différents (burger vs sidebar) |
| **Pas de lazy loading images** | Toutes les images chargées immédiatement |
| **Pas de skeleton loaders** | Pas d'états de chargement consistants |
| **Pas de validation client cohérente** | Mélange HTML5 natif + custom JS + rien |
| **Modales inconsistantes** | Certaines full-screen mobile, d'autres non |
| **Tableaux pas responsive partout** | Certains ont `.mobile-card-view`, d'autres non |

### Accessibilité (A11Y)

| Problème | Impact |
|----------|--------|
| ARIA labels manquants sur éléments interactifs | Screen readers |
| Focus management inconsistant dans les modales | Navigation clavier |
| Contraste couleurs sur fond gradient | WCAG AA potentiellement échoué |
| Attributs `alt` manquants sur certaines images | Screen readers |
| Labels `for` manquants sur certains inputs | Association label/input |
| Dropdowns custom sans support clavier (arrow keys) | Navigation clavier |
| Pas de skip links | Navigation clavier |

---

### Brightshell (module ERP) — CSS séparé

Le module Brightshell utilise 716 lignes de CSS custom avec variables CSS au lieu de Tailwind. C'est un monde parallèle stylistiquement.

**Recommandation** : Migrer vers Tailwind pour la cohérence ou assumer le CSS séparé mais documenter la convention.

---

## 7. EMAILS & NOTIFICATIONS

### Infrastructure
- 10 classes Mailable (welcome, confirmation, facture, rappel, etc.)
- Template system avec remplacement de variables
- Logging de tous les emails envoyés (`EmailLogger`)
- Rapports hebdo/mensuels (`EmailReportService`)
- Intégration IMAP pour emails entrants (BrightShell)

### Problèmes

| Problème | Impact |
|----------|--------|
| Pas de retry pour les emails échoués | Emails perdus silencieusement |
| Pas de queue dédiée pour les emails | Emails bloqués si la queue est saturée |
| Gestion d'erreur limitée dans `EmailHelper` | Retourne `bool` sans logger les failures systématiquement |
| Pas de preview email dans l'admin | Difficile de debugger les templates |
| IMAP sans retry sur échec connexion | Emails entrants manqués |

---

## 8. PWA & PERFORMANCE

### État actuel
- `manifest.json` présent avec config de base
- Service Worker (`sw.js`) avec stratégie Network First
- Icônes : 192x192, 512x512, 1024x1024
- Bannière d'installation PWA

### Problèmes

| Problème | Impact |
|----------|--------|
| **Conflit SW** : `app.js` désenregistre TOUS les SW, mais le dashboard en enregistre un | Le SW est perpétuellement détruit puis recréé |
| Pas de page de fallback offline | L'utilisateur voit une erreur brute |
| Cache minimaliste (root, manifest, icônes seulement) | Quasi aucun bénéfice offline |
| Pas de background sync | Réservations perdues si offline |
| Pas de push notifications | Opportunité manquée de réengagement |
| Fichier `workbox-004510d2.js` présent mais non référencé | Dead code |
| Pas de `font-display: swap` | Flash de texte invisible (FOIT) |

---

## 9. API & INTÉGRATIONS TIERCES

### APIs publiques sans protection

| Route | Protection |
|-------|-----------|
| `/api/address/*` | Aucune auth, aucun rate limit |
| `/api/tracking/visite/*` | Aucune auth, aucun rate limit |
| `/api/search/autocomplete` | Aucune auth, aucun rate limit |

**Risque** : Abus, scraping, DDoS sur ces endpoints.
**Action** : Ajouter un throttle middleware (`throttle:60,1` minimum).

### Intégrations tierces

| Service | Usage | État |
|---------|-------|------|
| Stripe | Paiements, abonnements | ✅ Intégré, à refactorer |
| Reverb | WebSockets | ⚠️ Configuré, usage limité |
| Pusher | Broadcasting | ⚠️ Configuré, doublon avec Reverb ? |
| IMAP | Emails entrants BrightShell | ✅ Fonctionnel |
| API Adresse (gouv.fr) | Autocomplétion adresse | ✅ Fonctionnel |

**Clarification** : Reverb ET Pusher sont configurés mais la situation est un bazar :
- `BROADCAST_CONNECTION=null` → Le système de broadcasting Laravel est **désactivé**
- `bootstrap.js` → Pas de Laravel Echo importé (juste Axios)
- `admin-notes.js` → Connexion **directe à Pusher** (sans Echo, sans broadcasting Laravel)
- `config/reverb.php` → Configuré mais **jamais utilisé**

**Résultat** : Seul Pusher est utilisé, et uniquement pour les notes collaboratives, en contournant complètement l'architecture Laravel. Reverb est du dead code.

**Recommandation** : Choisir Pusher OU Reverb, puis utiliser Laravel Echo pour tout unifier.

---

## 10. TESTS & QUALITÉ

### État actuel : quasi inexistant

- 6 fichiers de tests seulement dans `tests/`
- Pas de tests pour les flux critiques (paiement, réservation, auth)
- Pas de tests pour les webhooks Stripe
- `UserFactory` est le seul factory disponible
- Seeders limités (3 seeders)

**Impact** : Chaque modification est un risque de régression.

### Recommandations prioritaires de tests

1. Tests du flux de paiement complet (checkout → webhook → facture)
2. Tests d'authentification (login, 2FA, lockout, password reset)
3. Tests de réservation (création, annulation, modification)
4. Tests des webhooks Stripe (tous les événements gérés)
5. Tests des commandes CRON (échéances, réconciliation)
6. Tests des permissions (admin, gérant, client, visiteur)

---

## 11. DEVOPS & DÉPLOIEMENT

### Infrastructure détectée
- Docker (actuellement éteint)
- Nginx configuré
- Supervisor pour les workers
- Script de déploiement (`deploy/production/deploy.sh`)
- Scripts de backup BD

### Commandes CRON configurées

| Commande | Fréquence | Rôle |
|----------|-----------|------|
| `essais:check-expiration` | Quotidien 09:00 | Vérification essais gratuits |
| `subscriptions:check-echeances` | Quotidien 06:00 | Création factures mensuelles |
| `subscriptions:reconcile-echeances` | Quotidien 06:30 | Réconciliation paiements Stripe |

### Manques

| Élément | Recommandation |
|---------|---------------|
| Pas de CI/CD visible | GitHub Actions ou GitLab CI |
| Pas de monitoring applicatif | Laravel Telescope, Sentry, ou Bugsnag |
| Pas d'alerting pour les CRON échoués | Healthchecks.io ou alerting Slack |
| Pas de Laravel Horizon | Pour monitorer les queues |
| Pas de tests automatisés en CI | Régressions non détectées |

---

## 12. BRIGHTSHELL (MODULE ERP)

### Fonctionnalités détectées
- Agenda professionnel
- Gestion clients / fournisseurs
- Facturation / devis
- Comptabilité
- Gestion de stock
- Mailing (IMAP intégré)
- Notes / Projets / Tâches (Kanban)
- Documents / Exports
- Ressources / Statistiques
- Mentions légales

### Observations
- Module très complet mais CSS séparé (716 lignes de custom CSS)
- Semble être un ERP complet intégré dans l'app de prise de RDV
- Risque de scope creep : l'ERP et la prise de RDV sont deux produits différents
- Complexité accrue pour la maintenance

---

## 13. PISTES D'AMÉLIORATION BUSINESS

### Pour devenir indétrônable

| Axe | Idée | Priorité |
|-----|------|----------|
| **Onboarding** | Wizard pas-à-pas pour les nouveaux gérants (configurer agenda, services, horaires) | 🔴 Haute |
| **Marketplace** | Page de recherche/découverte d'entreprises par localisation, catégorie, note | 🔴 Haute |
| **Réservation sans compte** | Permettre la réservation en tant qu'invité (email uniquement) | 🔴 Haute |
| **Rappels automatiques** | SMS/Email/Push 24h et 1h avant le RDV (automatisé) | 🔴 Haute |
| **Widget embeddable** | Bouton/widget de réservation à intégrer sur le site du client | 🔴 Haute |
| **Calendrier sync** | Synchronisation bidirectionnelle Google Calendar / iCal | 🟠 Haute |
| **Multi-langues** | i18n complet (FR actuellement dominant, but locale = EN par défaut ?) | 🟠 Haute |
| **Mobile App** | Application mobile native ou Capacitor/Ionic wrapping la PWA | 🟠 Haute |
| **Analytics avancés** | Dashboard stats pour les gérants (taux de remplissage, CA, tendances) | 🟡 Moyenne |
| **Système de fidélité** | Déjà amorcé (LoyaltyProgram) — compléter et rendre visible | 🟡 Moyenne |
| **Paiement en ligne** | Paiement à la réservation (acompte ou totalité) pour les clients finaux | 🟡 Moyenne |
| **File d'attente** | Système de liste d'attente quand un créneau est complet | 🟡 Moyenne |
| **Réservation récurrente** | RDV hebdomadaire/mensuel automatique | 🟡 Moyenne |
| **API publique** | API REST documentée pour intégrations tierces | 🟡 Moyenne |
| **Webhooks sortants** | Notifier les systèmes tiers quand un RDV est créé/modifié | 🟡 Moyenne |
| **Import/Export** | Import CSV de clients, export de données | 🟡 Moyenne |
| **Multi-établissements** | Un gérant gère plusieurs lieux | 🟡 Moyenne |
| **Système d'avis vérifié** | Seuls les clients ayant réservé peuvent noter | ✅ Semble implémenté |
| **Forum & communauté** | ✅ Déjà implémenté |
| **Système de tickets support** | ✅ Déjà implémenté |

---

## 14. QUESTIONS POUR LE FONDATEUR

### Stratégie & Vision

1. **Cible principale** : Tu vises quel type de professionnels ? (Coiffeurs, médecins, coachs, artisans, tous ?) Ça impacte les fonctionnalités prioritaires.

2. **Modèle économique** : 14€/mois pour le premium, 2€/mois site web, 20€/mois multi-personnes — est-ce le pricing final ? As-tu testé d'autres tarifications ?

3. **Localisation** : Le `.env.example` a `APP_LOCALE=en` et `APP_FAKER_LOCALE=en_US`, mais le site est en français. Est-ce que tu vises à terme l'international ou c'est 100% FR ?

4. **Marketplace ou SaaS pur** : Est-ce qu'Allotata est un outil SaaS (chaque pro a son espace) OU une marketplace (les clients finaux trouvent des pros) ? Les deux impliquent des stratégies différentes.

5. **BrightShell** : C'est quoi exactement la relation entre Allotata et BrightShell ? C'est un produit séparé intégré ? Un module premium ? Le même produit avec deux marques ?

### Technique

6. **Base de données** : SQLite en dev — et en production ? MySQL ? PostgreSQL ? La réponse impacte les recommandations d'indexation.

7. **Reverb vs Pusher** : Les deux sont configurés. Lequel est utilisé en production ? L'autre peut être retiré ?

8. **Trafic actuel** : Combien d'utilisateurs actifs ? Combien de réservations/jour ? Ça aide à prioriser les optimisations de perf.

9. **Docker** : Tu mentionnes que Docker est éteint pour des raisons de perf. En production, c'est Docker aussi ? Serveur nu ?

10. **CI/CD** : Y a-t-il un pipeline de déploiement automatisé ? Comment tu déploies actuellement ?

### Fonctionnel

11. **Réservation sans compte** : Est-ce possible aujourd'hui ? J'ai vu que `reservation.user_id` semble nullable dans le modèle mais constrained dans la migration — c'est quoi l'état réel ?

12. **Paiement client final** : Est-ce que les clients finaux (ceux qui réservent) paient en ligne actuellement, ou le paiement Stripe est uniquement pour les abonnements des gérants ?

13. **SMS** : Tu utilises quel provider SMS ? J'ai vu des classes de notification SMS mais pas de configuration de provider.

14. **Rappels de RDV** : La commande `SendReservationReminders` existe — elle tourne en production ? À quelle fréquence ?

15. **Emails** : Quel provider email en production ? (Mailgun, SES, Postmark ?) Le `.env.example` montre `MAIL_MAILER=log`.

16. **Essais gratuits** : Comment fonctionne le système d'essai gratuit ? Durée ? Quelles limitations pendant l'essai ?

17. **Custom Prices** : Quel est le cas d'usage des prix custom ? C'est pour des réductions négociées ? Du parrainage ?

### Sécurité

18. **Le TempAdminController** : Est-ce que c'est un vestige de développement ou c'est volontaire ? C'est la faille la plus critique du projet.

19. **Les routes de debug** : Sont-elles accessibles en production actuellement ?

20. **Backups** : Les scripts de backup fonctionnent ? Quelle fréquence ? Où sont stockés les backups ?

### UX & Clients finaux

21. **Parcours client** : Un client qui veut réserver, il fait quoi exactement ? Il arrive sur la page publique de l'entreprise, il choisit un service, un créneau, et ensuite ? Il doit créer un compte ?

22. **Multi-prestataires** : Un client qui réserve chez plusieurs entreprises différentes — est-ce qu'il a un dashboard unifié de ses RDV ?

23. **Annulation** : Quelle est la politique d'annulation ? Le client peut annuler librement ? Y a-t-il un délai minimum ?

24. **Modification de RDV** : Le client peut modifier un RDV existant (changer d'horaire, de service) ou il doit annuler + recréer ?

25. **Notifications client** : Quand un gérant confirme/annule/modifie un RDV, le client est notifié comment ? Email seulement ?

26. **Conflits d'horaires** : Que se passe-t-il si deux clients tentent de réserver le même créneau en même temps ? Il y a un mécanisme de lock ?

27. **Fuseaux horaires** : Les horaires sont en quelle timezone ? C'est géré pour des clients/pros dans différentes TZ ?

28. **Jours fériés** : Le système gère automatiquement les jours fériés français, ou le gérant doit les marquer manuellement ?

### Scénarios critiques

29. **Paiement échoué en cours de réservation** : Que voit le client ? Est-il informé clairement ? La réservation est-elle annulée ?

30. **Double réservation** : Si un client réserve deux fois le même créneau (par erreur), c'est détecté ?

31. **Gérant qui supprime son compte** : Que deviennent les réservations futures de ses clients ?

32. **Client qui supprime son compte** : Que deviennent ses réservations futures ? L'historique pour le gérant ?

33. **Entreprise désactivée/bannie** : Que voient les clients qui ont des RDV chez cette entreprise ?

34. **Perte de connexion pendant la réservation** : L'état est sauvegardé ? Le client peut reprendre ?

35. **Plusieurs onglets ouverts** : Un gérant qui gère son agenda dans 2 onglets — les changements sont synchronisés en temps réel ?

36. **Email client erroné** : Si un client donne un mauvais email à la réservation, comment le corriger ?

37. **Membre d'équipe absent** : Comment un gérant signale l'absence d'un membre d'équipe ? Les RDV existants sont-ils automatiquement réassignés ?

38. **Surréservation** : Si le système autorise plus de RDV que le pro peut gérer (bug ou config), quelle est la gestion de crise ?

39. **Migration de données** : Si un pro veut quitter Allotata, peut-il exporter toutes ses données (clients, historique, factures) ?

40. **RGPD** : Y a-t-il un mécanisme de suppression de données personnelles sur demande ? Export des données utilisateur ?

### Ambitions

41. **Concurrence** : Tu te positionnes contre qui ? Calendly, Doctolib, SimplyBook, Treatwell, Planity ? Qu'est-ce qui te différencie ?

42. **Fonctionnalité killer** : Quelle est LA fonctionnalité que tes concurrents n'ont pas et que tu veux offrir ?

43. **Scale** : Tu prévois combien d'entreprises / d'utilisateurs dans 1 an ? 3 ans ?

44. **Monétisation secondaire** : Au-delà de l'abonnement mensuel, as-tu prévu d'autres sources de revenus ? (Commission sur paiements, publicité, services premium additionnels ?)

45. **Partenariats** : Des intégrations prévues avec d'autres outils ? (Comptabilité, CRM, Google My Business, réseaux sociaux ?)

---

## RÉSUMÉ DES PRIORITÉS

### 🔴 Actions immédiates (avant toute mise en production)
1. ~~Supprimer/sécuriser TempAdminController~~ ✅ FAIT — Middleware `NoAdminExists` + routes dangereuses supprimées
2. ~~Supprimer les routes de debug~~ ✅ FAIT — Conditionnées à `APP_ENV=local`
3. ~~Retirer `is_admin` du `$fillable` de User~~ ✅ FAIT — `is_admin`, `google2fa_secret`, `stripe_id` retirés + adaptations
4. Réduire le session lifetime — REPORTÉ (décision du fondateur)
5. Ajouter rate limiting sur les APIs publiques — À FAIRE

### 🟠 Court terme (1-2 semaines)
6. Créer `routes/api.php` et séparer les routes
7. Ajouter les index DB manquants
8. Corriger le conflit Service Worker
9. Ajouter CSP headers
10. Refactorer les gros contrôleurs en services

### 🟡 Moyen terme (1-2 mois)
11. Écrire les tests critiques (paiement, auth, réservation)
12. Mettre en place CI/CD
13. Ajouter monitoring (Sentry/Telescope)
14. Soft deletes sur les modèles critiques
15. Résoudre les N+1 queries

### 🔵 Long terme (3-6 mois)
16. Widget de réservation embeddable
17. Sync Google Calendar
18. App mobile (ou PWA optimisée)
19. API publique documentée
20. Système de push notifications

### ✅ Anti-doublon réservation — FAIT
- `ReservationSlotService` créé avec vérification SQL + `lockForUpdate()` + `DB::transaction()`
- Intégré dans les 3 contrôleurs : `PublicController`, `SiteWebController`, `ReservationController`
- La faille critique de `SiteWebController` (aucune vérification) est corrigée

---

---

## 15. RÉPONSES DU FONDATEUR & DÉCISIONS

### Réponse 1 — TempAdminController
> "C'est un vestige pour créer le premier admin (moi), mais on doit l'enlever ou le mettre en on/off."

**Décision** : Transformer en mode "bootstrap" — actif UNIQUEMENT s'il n'existe aucun admin dans la base. Dès qu'un admin existe, les routes deviennent inaccessibles (404).

**Implémentation prévue** :
- Ajouter un middleware `NoAdminExists` qui vérifie `User::where('is_admin', true)->exists()`
- Si un admin existe → 404
- Si aucun admin → afficher la page de création du premier admin uniquement
- Supprimer les routes `promote`, `demote`, `loginAs` (dangereuses, l'admin panel suffit)

---

### Réponse 2 — Routes de debug
> "En phase de test actuellement."

**Décision** : Conditionner toutes les routes de debug à `APP_ENV=local` pour qu'elles disparaissent automatiquement en production.

**Implémentation prévue** :
```php
if (app()->environment('local')) {
    // Routes de debug ici
}
```

---

### Réponse 3 — BrightShell
> "C'est ma société, l'entreprise qui a les droits sur Allotata."

**Impact** : Le module ERP BrightShell dans l'app est donc un module d'administration interne / back-office pour la gestion de l'entreprise BrightShell elle-même. Ce n'est PAS un module pour les utilisateurs d'Allotata.

**Recommandation** : Clarifier dans le code et la navigation que BrightShell = back-office administrateur de l'entreprise. Potentiellement séparer ce module si l'application grossit (microservice ou sous-domaine admin).

---

### Réponse 4 — Cible
> "Tous les particuliers avec micro-entreprise principalement, mais ça va beaucoup plus loin."

**Impact stratégique** : La cible micro-entrepreneur est excellente car :
- Volume énorme en France (~4M de micro-entreprises)
- Besoin réel de simplification (pas de secrétaire, tout faire seul)
- Prix sensible → le pricing 14€/mois est bien positionné
- Besoin d'un outil tout-en-un (agenda + facturation + site web)

**Recommandations business affinées** :
1. **Onboarding ultra-simplifié** : En 3 clics, le micro-entrepreneur a son agenda en ligne
2. **Templates métier** : Pré-configurations par secteur (coiffeur, coach, thérapeute, artisan...)
3. **Conformité micro-entreprise** : Mentions obligatoires, numérotation factures, plafonds CA
4. **Intégration URSSAF** : Calcul automatique des charges sociales
5. **Site vitrine + SEO** : L'option site web à 2€/mois est un killer feature si bien exécuté

---

### Réponse 5 — Reverb vs Pusher
> "Je sais plus du tout."

**Diagnostic complet** :
- Le broadcasting Laravel est **désactivé** (`BROADCAST_CONNECTION=null`)
- **Pusher** est utilisé directement (sans Laravel Echo) uniquement pour les notes collaboratives (`admin-notes.js`)
- **Reverb** est configuré mais **jamais utilisé** — c'est du dead code
- **Laravel Echo** n'est **pas importé** du tout

**Décision recommandée** :
- **Option A — Pusher (plus simple)** : Garder Pusher, supprimer la config Reverb, intégrer Laravel Echo pour les futurs besoins real-time (notifications en direct, mise à jour agenda, etc.)
- **Option B — Reverb (gratuit, self-hosted)** : Migrer vers Reverb + Echo. Pas de coût mensuel Pusher, mais nécessite un serveur WebSocket à maintenir.

Pour un solo dev : **Option A (Pusher)** est probablement plus raisonnable. Moins de maintenance.

### Réponse 6 — Questions 11, 12, 21, 26, 40

**Q11 — Réservation sans compte** :
> "Non, j'aimerais le rajouter et inciter à se connecter pour garder une trace."

L'infrastructure est déjà prête (`user_id` est nullable, champs `nom_client`/`email_client` existent). Il reste à adapter le `PublicController` pour ne plus bloquer les non-connectés, et ajouter un formulaire guest dans les vues.

**Q12 — Paiement client final** :
> "Uniquement pour les abonnements gérants. Je ne sais pas comment reverser l'argent."

La solution serait Stripe Connect (mode plateforme) : le client paie, Stripe reverse au gérant, Allotata prend une commission. C'est un chantier majeur mais c'est le modèle Doctolib/Planity. À planifier en v2.

**Q21 — Parcours client** :
> "Page d'accueil → choix → envoi sur l'agenda. Ou listing des résa. Ou par message."

Trois parcours confirmés. Le parcours messagerie est le plus risqué (pas de vérification anti-doublon côté conversation).

**Q26 — Anti-doublon** :
> "Aucun mécanisme."

✅ CORRIGÉ — `ReservationSlotService` créé avec `lockForUpdate()` + `DB::transaction()`. Intégré dans les 3 points d'entrée. La faille critique de `SiteWebController` (aucune vérification) est également corrigée.

**Q40 — RGPD** :
> "Pas encore."

À planifier : export des données utilisateur (droit d'accès), suppression sur demande (droit à l'oubli), consentement cookies, politique de confidentialité. Obligation légale pour tout service opérant en UE.

---

## 16. RECOMMANDATIONS AFFINÉES POST-RÉPONSES

### Positionnement vs Concurrence

| Concurrent | Cible | Prix | Différenciateur Allotata |
|-----------|-------|------|--------------------------|
| Calendly | Tous (international) | 8-16$/mois | Allotata = français, micro-entreprises, tout-en-un |
| Doctolib | Médecins | Commission | Allotata = tous les métiers, pas de commission |
| Planity | Coiffeurs/beauté | Commission | Allotata = tous les métiers, flat fee |
| SimplyBook | Tous (international) | 8-50$/mois | Allotata = français natif, BrightShell ERP |

**Angle unique d'Allotata** : Le seul outil de prise de RDV français qui intègre un mini-ERP (factures, devis, comptabilité) adapté aux micro-entreprises. Pas une simple prise de RDV, mais un assistant business complet.

### Top 5 des features pour se démarquer

1. **"Lancé en 3 minutes"** — Onboarding wizard qui crée agenda + page publique + premiers créneaux automatiquement
2. **Widget embed "Réserver"** — Un bout de code JS que le micro-entrepreneur colle sur son site existant ou ses réseaux
3. **Facturation automatique** — Chaque RDV terminé génère automatiquement une facture conforme micro-entreprise
4. **Rappels intelligents** — SMS/Email/Push adaptatifs (le client qui annule souvent reçoit un rappel plus tôt)
5. **Page Google-ready** — La page publique de chaque pro est SEO-optimisée pour apparaître dans Google Maps / recherche locale

---

> **Note** : Ce document sera mis à jour au fur et à mesure de l'audit. Aucune modification de code n'a été effectuée.
