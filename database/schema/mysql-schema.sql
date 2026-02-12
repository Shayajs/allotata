/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` bigint unsigned DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model_id` bigint unsigned DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `changes` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_logs_admin_id_foreign` (`admin_id`),
  KEY `activity_logs_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `activity_logs_action_index` (`action`),
  KEY `activity_logs_created_at_index` (`created_at`),
  CONSTRAINT `activity_logs_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `announcements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('info','warning','success','danger') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `cible` enum('tous','clients','gerants','admins') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tous',
  `date_debut` datetime DEFAULT NULL,
  `date_fin` datetime DEFAULT NULL,
  `est_actif` tinyint(1) NOT NULL DEFAULT '1',
  `afficher_banniere` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `announcements_created_by_foreign` (`created_by`),
  KEY `announcements_est_actif_index` (`est_actif`),
  KEY `announcements_date_debut_date_fin_index` (`date_debut`,`date_fin`),
  CONSTRAINT `announcements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `avis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `avis` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `entreprise_id` bigint unsigned NOT NULL,
  `reservation_id` bigint unsigned DEFAULT NULL,
  `note` int NOT NULL COMMENT 'Note de 1 à 5',
  `commentaire` text COLLATE utf8mb4_unicode_ci,
  `est_approuve` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `avis_user_id_entreprise_id_unique` (`user_id`,`entreprise_id`),
  KEY `avis_reservation_id_foreign` (`reservation_id`),
  KEY `avis_entreprise_id_index` (`entreprise_id`),
  KEY `avis_note_index` (`note`),
  CONSTRAINT `avis_entreprise_id_foreign` FOREIGN KEY (`entreprise_id`) REFERENCES `entreprises` (`id`) ON DELETE CASCADE,
  CONSTRAINT `avis_reservation_id_foreign` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `avis_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contacts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sujet` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `est_lu` tinyint(1) NOT NULL DEFAULT '0',
  `lu_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contacts_user_id_foreign` (`user_id`),
  CONSTRAINT `contacts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `conversations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `entreprise_id` bigint unsigned NOT NULL,
  `reservation_id` bigint unsigned DEFAULT NULL,
  `produit_id` bigint unsigned DEFAULT NULL,
  `type_service_id` bigint unsigned DEFAULT NULL,
  `dernier_message_at` timestamp NULL DEFAULT NULL,
  `est_archivee` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conversations_entreprise_id_foreign` (`entreprise_id`),
  KEY `conversations_dernier_message_at_index` (`dernier_message_at`),
  KEY `conversations_reservation_id_index` (`reservation_id`),
  KEY `conversations_produit_id_index` (`produit_id`),
  KEY `conversations_type_service_id_index` (`type_service_id`),
  KEY `conversations_user_entreprise_index` (`user_id`,`entreprise_id`),
  CONSTRAINT `conversations_entreprise_id_foreign` FOREIGN KEY (`entreprise_id`) REFERENCES `entreprises` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conversations_produit_id_foreign` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE SET NULL,
  CONSTRAINT `conversations_reservation_id_foreign` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `conversations_type_service_id_foreign` FOREIGN KEY (`type_service_id`) REFERENCES `types_services` (`id`) ON DELETE SET NULL,
  CONSTRAINT `conversations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `custom_prices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_prices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `entreprise_id` bigint unsigned DEFAULT NULL,
  `subscription_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stripe_price_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'eur',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `custom_prices_stripe_price_id_unique` (`stripe_price_id`),
  KEY `custom_prices_created_by_foreign` (`created_by`),
  KEY `custom_prices_user_id_subscription_type_is_active_index` (`user_id`,`subscription_type`,`is_active`),
  KEY `custom_prices_entreprise_id_subscription_type_is_active_index` (`entreprise_id`,`subscription_type`,`is_active`),
  KEY `custom_prices_subscription_type_index` (`subscription_type`),
  CONSTRAINT `custom_prices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `custom_prices_entreprise_id_foreign` FOREIGN KEY (`entreprise_id`) REFERENCES `entreprises` (`id`) ON DELETE CASCADE,
  CONSTRAINT `custom_prices_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `entreprise_finances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `entreprise_finances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `entreprise_id` bigint unsigned NOT NULL,
  `type` enum('income','expense') COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_record` date NOT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entreprise_finances_entreprise_id_foreign` (`entreprise_id`),
  CONSTRAINT `entreprise_finances_entreprise_id_foreign` FOREIGN KEY (`entreprise_id`) REFERENCES `entreprises` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `entreprise_invitations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `entreprise_invitations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `entreprise_id` bigint unsigned NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'membre',
  `statut` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_attente_compte',
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `invite_par_user_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `accepte_at` timestamp NULL DEFAULT NULL,
  `refuse_at` timestamp NULL DEFAULT NULL,
  `expire_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `entreprise_invitations_token_unique` (`token`),
  KEY `entreprise_invitations_invite_par_user_id_foreign` (`invite_par_user_id`),
  KEY `entreprise_invitations_user_id_foreign` (`user_id`),
  KEY `entreprise_invitations_entreprise_id_email_index` (`entreprise_id`,`email`),
  KEY `entreprise_invitations_email_statut_index` (`email`,`statut`),
  KEY `entreprise_invitations_email_index` (`email`),
  CONSTRAINT `entreprise_invitations_entreprise_id_foreign` FOREIGN KEY (`entreprise_id`) REFERENCES `entreprises` (`id`) ON DELETE CASCADE,
  CONSTRAINT `entreprise_invitations_invite_par_user_id_foreign` FOREIGN KEY (`invite_par_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `entreprise_invitations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `entreprise_membres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `entreprise_membres` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `entreprise_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `invitation_id` bigint unsigned DEFAULT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'administrateur',
  `est_actif` tinyint(1) NOT NULL DEFAULT '1',
  `invite_at` timestamp NULL DEFAULT NULL,
  `accepte_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `entreprise_membres_entreprise_id_user_id_unique` (`entreprise_id`,`user_id`),
  KEY `entreprise_membres_user_id_foreign` (`user_id`),
  KEY `entreprise_membres_entreprise_id_est_actif_index` (`entreprise_id`,`est_actif`),
  KEY `entreprise_membres_invitation_id_foreign` (`invitation_id`),
  CONSTRAINT `entreprise_membres_entreprise_id_foreign` FOREIGN KEY (`entreprise_id`) REFERENCES `entreprises` (`id`) ON DELETE CASCADE,
  CONSTRAINT `entreprise_membres_invitation_id_foreign` FOREIGN KEY (`invitation_id`) REFERENCES `entreprise_invitations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `entreprise_membres_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `entreprise_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `entreprise_subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `entreprise_id` bigint unsigned NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stripe_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stripe_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stripe_price` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `est_manuel` tinyint(1) NOT NULL DEFAULT '0',
  `actif_jusqu` date DEFAULT NULL,
  `notes_manuel` text COLLATE utf8mb4_unicode_ci,
  `type_renouvellement` enum('mensuel','annuel') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jour_renouvellement` int DEFAULT NULL,
  `date_debut` date DEFAULT NULL,
  `montant` decimal(10,2) DEFAULT NULL,
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `entreprise_subscriptions_stripe_id_unique` (`stripe_id`),
  KEY `entreprise_subscriptions_entreprise_id_type_stripe_status_index` (`entreprise_id`,`type`,`stripe_status`),
  CONSTRAINT `entreprise_subscriptions_entreprise_id_foreign` FOREIGN KEY (`entreprise_id`) REFERENCES `entreprises` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `entreprises`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `entreprises` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `afficher_nom_gerant` tinyint(1) NOT NULL DEFAULT '1',
  `prix_negociables` tinyint(1) NOT NULL DEFAULT '0',
  `rdv_uniquement_messagerie` tinyint(1) NOT NULL DEFAULT '0',
  `abonnement_manuel` tinyint(1) NOT NULL DEFAULT '0',
  `abonnement_manuel_actif_jusqu` date DEFAULT NULL,
  `abonnement_manuel_notes` text COLLATE utf8mb4_unicode_ci,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug_web` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contenu_site_web` json DEFAULT NULL,
  `phrase_accroche` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type_activite` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `siren` varchar(9) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `siren_verifie` tinyint(1) NOT NULL DEFAULT '0',
  `siren_valide` tinyint(1) DEFAULT NULL,
  `siren_refus_raison` text COLLATE utf8mb4_unicode_ci,
  `raison_refus_globale` text COLLATE utf8mb4_unicode_ci,
  `status_juridique` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_cours',
  `est_verifiee` tinyint(1) NOT NULL DEFAULT '0',
  `nom_valide` tinyint(1) DEFAULT NULL,
  `nom_refus_raison` text COLLATE utf8mb4_unicode_ci,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `mots_cles` text COLLATE utf8mb4_unicode_ci,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_fond` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ville` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse_rue` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code_postal` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `afficher_adresse_complete` tinyint(1) NOT NULL DEFAULT '0',
  `rayon_deplacement` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `fiscal_situation_familiale` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'celibataire',
  `fiscal_nombre_enfants` int DEFAULT '0',
  `fiscal_enfants_garde_alternee` int DEFAULT '0',
  `fiscal_parent_isole` tinyint(1) DEFAULT '0',
  `fiscal_prelevement_liberatoire` tinyint(1) DEFAULT '0',
  `fiscal_revenu_fiscal_reference` decimal(12,2) DEFAULT NULL,
  `fiscal_revenus_autres_foyer` decimal(12,2) DEFAULT '0.00',
  `fiscal_invalidite_contribuable` tinyint(1) DEFAULT '0',
  `fiscal_invalidite_conjoint` tinyint(1) DEFAULT '0',
  `fiscal_ancien_combattant` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `entreprises_slug_unique` (`slug`),
  UNIQUE KEY `entreprises_slug_web_unique` (`slug_web`),
  KEY `entreprises_user_id_foreign` (`user_id`),
  KEY `entreprises_latitude_longitude_index` (`latitude`,`longitude`),
  KEY `entreprises_code_postal_index` (`code_postal`),
  CONSTRAINT `entreprises_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `error_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `error_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `level` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `context` text COLLATE utf8mb4_unicode_ci,
  `file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `line` int DEFAULT NULL,
  `trace` text COLLATE utf8mb4_unicode_ci,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `est_vue` tinyint(1) NOT NULL DEFAULT '0',
  `vu_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `error_logs_user_id_foreign` (`user_id`),
  KEY `error_logs_est_vue_index` (`est_vue`),
  KEY `error_logs_created_at_index` (`created_at`),
  KEY `error_logs_level_index` (`level`),
  CONSTRAINT `error_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `essais_gratuits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `essais_gratuits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `essayable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `essayable_id` bigint unsigned NOT NULL,
  `type_abonnement` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_debut` datetime NOT NULL,
  `date_fin` datetime NOT NULL,
  `duree_jours` int NOT NULL DEFAULT '7',
  `statut` enum('actif','expire','converti','annule','revoque') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'actif',
  `date_conversion` datetime DEFAULT NULL,
  `date_annulation` datetime DEFAULT NULL,
  `raison_annulation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notification_rappel_envoye_le` datetime DEFAULT NULL,
  `notification_expiration_envoye_le` datetime DEFAULT NULL,
  `notification_relance_envoye_le` datetime DEFAULT NULL,
  `nb_connexions` int NOT NULL DEFAULT '0',
  `derniere_connexion` datetime DEFAULT NULL,
  `nb_actions` int NOT NULL DEFAULT '0',
  `metriques` json DEFAULT NULL,
  `source` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code_promo_utilise` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parrain_id` bigint unsigned DEFAULT NULL,
  `utm_source` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `utm_medium` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `utm_campaign` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_activation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `accorde_par_admin_id` bigint unsigned DEFAULT NULL,
  `notes_admin` text COLLATE utf8mb4_unicode_ci,
  `valeur_essai` decimal(8,2) DEFAULT NULL,
  `abonnement_converti_id` bigint unsigned DEFAULT NULL,
  `abonnement_converti_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note_satisfaction` tinyint DEFAULT NULL,
  `feedback` text COLLATE utf8mb4_unicode_ci,
  `raison_non_conversion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `essais_gratuits_essayable_type_essayable_id_index` (`essayable_type`,`essayable_id`),
  KEY `essais_gratuits_parrain_id_foreign` (`parrain_id`),
  KEY `essais_gratuits_accorde_par_admin_id_foreign` (`accorde_par_admin_id`),
  KEY `essais_essayable_type_idx` (`essayable_type`,`essayable_id`,`type_abonnement`),
  KEY `essais_statut_date_fin_idx` (`statut`,`date_fin`),
  KEY `essais_gratuits_source_index` (`source`),
  KEY `essais_gratuits_date_debut_index` (`date_debut`),
  CONSTRAINT `essais_gratuits_accorde_par_admin_id_foreign` FOREIGN KEY (`accorde_par_admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `essais_gratuits_parrain_id_foreign` FOREIGN KEY (`parrain_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `facture_reservation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `facture_reservation` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `facture_id` bigint unsigned NOT NULL,
  `reservation_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `facture_reservation_facture_id_reservation_id_unique` (`facture_id`,`reservation_id`),
  KEY `facture_reservation_reservation_id_foreign` (`reservation_id`),
  CONSTRAINT `facture_reservation_facture_id_foreign` FOREIGN KEY (`facture_id`) REFERENCES `factures` (`id`) ON DELETE CASCADE,
  CONSTRAINT `facture_reservation_reservation_id_foreign` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `factures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `factures` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reservation_id` bigint unsigned DEFAULT NULL,
  `entreprise_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `entreprise_subscription_id` bigint unsigned DEFAULT NULL,
  `type_facture` enum('reservation','abonnement_manuel') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'reservation',
  `numero_facture` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_facture` date NOT NULL,
  `date_echeance` date DEFAULT NULL,
  `montant_ht` decimal(10,2) NOT NULL,
  `taux_tva` decimal(5,2) NOT NULL DEFAULT '0.00',
  `montant_tva` decimal(10,2) NOT NULL DEFAULT '0.00',
  `montant_ttc` decimal(10,2) NOT NULL,
  `statut` enum('brouillon','emise','payee','annulee') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'emise',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `factures_numero_facture_unique` (`numero_facture`),
  KEY `factures_reservation_id_foreign` (`reservation_id`),
  KEY `factures_user_id_foreign` (`user_id`),
  KEY `factures_numero_facture_index` (`numero_facture`),
  KEY `factures_date_facture_index` (`date_facture`),
  KEY `factures_entreprise_subscription_id_foreign` (`entreprise_subscription_id`),
  KEY `factures_entreprise_id_foreign` (`entreprise_id`),
  CONSTRAINT `factures_entreprise_id_foreign` FOREIGN KEY (`entreprise_id`) REFERENCES `entreprises` (`id`) ON DELETE CASCADE,
  CONSTRAINT `factures_entreprise_subscription_id_foreign` FOREIGN KEY (`entreprise_subscription_id`) REFERENCES `entreprise_subscriptions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `factures_reservation_id_foreign` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `factures_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `faqs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `faqs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `question` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reponse` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `categorie` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ordre` int NOT NULL DEFAULT '0',
  `est_actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `faqs_categorie_index` (`categorie`),
  KEY `faqs_ordre_index` (`ordre`),
  KEY `faqs_est_actif_index` (`est_actif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `horaires_ouverture`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `horaires_ouverture` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `entreprise_id` bigint unsigned NOT NULL,
  `jour_semaine` int NOT NULL COMMENT '0=dimanche, 1=lundi, ..., 6=samedi',
  `heure_ouverture` time DEFAULT NULL,
  `heure_fermeture` time DEFAULT NULL,
  `est_exceptionnel` tinyint(1) NOT NULL DEFAULT '0',
  `date_exception` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `horaires_ouverture_entreprise_id_jour_semaine_index` (`entreprise_id`,`jour_semaine`),
  CONSTRAINT `horaires_ouverture_entreprise_id_foreign` FOREIGN KEY (`entreprise_id`) REFERENCES `entreprises` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `membre_disponibilites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `membre_disponibilites` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `membre_id` bigint unsigned NOT NULL,
  `jour_semaine` int NOT NULL COMMENT '0=Dimanche, 1=Lundi, ..., 6=Samedi',
  `heure_debut` time DEFAULT NULL,
  `heure_fin` time DEFAULT NULL,
  `est_exceptionnel` tinyint(1) NOT NULL DEFAULT '0',
  `date_exception` date DEFAULT NULL,
  `est_disponible` tinyint(1) NOT NULL DEFAULT '1',
  `raison_indisponibilite` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `memb_disp_jour_idx` (`membre_id`,`jour_semaine`),
  KEY `memb_disp_exc_date_idx` (`membre_id`,`est_exceptionnel`,`date_exception`),
  CONSTRAINT `membre_disponibilites_membre_id_foreign` FOREIGN KEY (`membre_id`) REFERENCES `entreprise_membres` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `membre_indisponibilites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `membre_indisponibilites` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `membre_id` bigint unsigned NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date DEFAULT NULL,
  `heure_debut` time DEFAULT NULL,
  `heure_fin` time DEFAULT NULL,
  `raison` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `membre_indisponibilites_membre_id_date_debut_date_fin_index` (`membre_id`,`date_debut`,`date_fin`),
  CONSTRAINT `membre_indisponibilites_membre_id_foreign` FOREIGN KEY (`membre_id`) REFERENCES `entreprise_membres` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `membre_statistiques`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `membre_statistiques` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `membre_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `nombre_reservations` int NOT NULL DEFAULT '0',
  `revenu_total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `duree_totale_minutes` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `membre_statistiques_membre_id_date_unique` (`membre_id`,`date`),
  KEY `membre_statistiques_membre_id_date_index` (`membre_id`,`date`),
  CONSTRAINT `membre_statistiques_membre_id_foreign` FOREIGN KEY (`membre_id`) REFERENCES `entreprise_membres` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `contenu` text COLLATE utf8mb4_unicode_ci,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `est_lu` tinyint(1) NOT NULL DEFAULT '0',
  `type_message` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'texte',
  `proposition_rdv_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `messages_user_id_foreign` (`user_id`),
  KEY `messages_conversation_id_index` (`conversation_id`),
  KEY `messages_created_at_index` (`created_at`),
  KEY `messages_proposition_rdv_id_foreign` (`proposition_rdv_id`),
  CONSTRAINT `messages_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_proposition_rdv_id_foreign` FOREIGN KEY (`proposition_rdv_id`) REFERENCES `proposition_rendez_vouses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `messages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `lien` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `est_lue` tinyint(1) NOT NULL DEFAULT '0',
  `lue_at` timestamp NULL DEFAULT NULL,
  `donnees` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_user_id_est_lue_index` (`user_id`,`est_lue`),
  KEY `notifications_created_at_index` (`created_at`),
  CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `produit_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `produit_images` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `produit_id` bigint unsigned NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `est_couverture` tinyint(1) NOT NULL DEFAULT '0',
  `ordre` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `produit_images_produit_id_index` (`produit_id`),
  KEY `produit_images_est_couverture_index` (`est_couverture`),
  CONSTRAINT `produit_images_produit_id_foreign` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `produits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `produits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `entreprise_id` bigint unsigned NOT NULL,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `prix` decimal(10,2) NOT NULL,
  `est_actif` tinyint(1) NOT NULL DEFAULT '1',
  `gestion_stock` enum('disponible_immediatement','en_attente_commandes') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'disponible_immediatement',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `produits_entreprise_id_index` (`entreprise_id`),
  KEY `produits_est_actif_index` (`est_actif`),
  CONSTRAINT `produits_entreprise_id_foreign` FOREIGN KEY (`entreprise_id`) REFERENCES `entreprises` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `promo_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promo_codes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('pourcentage','montant_fixe') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pourcentage',
  `valeur` decimal(10,2) NOT NULL,
  `usages_max` int DEFAULT NULL,
  `usages_actuels` int NOT NULL DEFAULT '0',
  `duree_mois` int DEFAULT NULL,
  `date_debut` datetime DEFAULT NULL,
  `date_fin` datetime DEFAULT NULL,
  `est_actif` tinyint(1) NOT NULL DEFAULT '1',
  `premier_abonnement_uniquement` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `promo_codes_code_unique` (`code`),
  KEY `promo_codes_created_by_foreign` (`created_by`),
  KEY `promo_codes_code_index` (`code`),
  KEY `promo_codes_est_actif_index` (`est_actif`),
  CONSTRAINT `promo_codes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `promotions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promotions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `produit_id` bigint unsigned NOT NULL,
  `prix_promotion` decimal(10,2) NOT NULL,
  `date_debut` datetime NOT NULL,
  `date_fin` datetime NOT NULL,
  `est_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `promotions_produit_id_index` (`produit_id`),
  KEY `promotions_est_active_index` (`est_active`),
  KEY `promotions_date_debut_date_fin_index` (`date_debut`,`date_fin`),
  CONSTRAINT `promotions_produit_id_foreign` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `proposition_rendez_vouses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proposition_rendez_vouses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint unsigned NOT NULL,
  `message_id` bigint unsigned DEFAULT NULL,
  `auteur_user_id` bigint unsigned NOT NULL,
  `auteur_type` enum('client','gerant') COLLATE utf8mb4_unicode_ci NOT NULL,
  `entreprise_id` bigint unsigned NOT NULL,
  `type_service_id` bigint unsigned DEFAULT NULL,
  `date_rdv` date NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  `duree_minutes` int NOT NULL DEFAULT '30',
  `prix_propose` decimal(10,2) NOT NULL,
  `prix_final` decimal(10,2) DEFAULT NULL,
  `statut` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'proposee',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `lieu` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reservation_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `proposition_rendez_vouses_message_id_foreign` (`message_id`),
  KEY `proposition_rendez_vouses_user_id_foreign` (`auteur_user_id`),
  KEY `proposition_rendez_vouses_entreprise_id_foreign` (`entreprise_id`),
  KEY `proposition_rendez_vouses_reservation_id_foreign` (`reservation_id`),
  KEY `proposition_rendez_vouses_conversation_id_statut_index` (`conversation_id`,`statut`),
  KEY `proposition_rendez_vouses_date_rdv_index` (`date_rdv`),
  KEY `proposition_rendez_vouses_type_service_id_foreign` (`type_service_id`),
  KEY `prop_conv_auteur_statut_idx` (`conversation_id`,`auteur_type`,`statut`),
  CONSTRAINT `proposition_rendez_vouses_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `proposition_rendez_vouses_entreprise_id_foreign` FOREIGN KEY (`entreprise_id`) REFERENCES `entreprises` (`id`) ON DELETE CASCADE,
  CONSTRAINT `proposition_rendez_vouses_message_id_foreign` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `proposition_rendez_vouses_reservation_id_foreign` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `proposition_rendez_vouses_type_service_id_foreign` FOREIGN KEY (`type_service_id`) REFERENCES `types_services` (`id`) ON DELETE SET NULL,
  CONSTRAINT `proposition_rendez_vouses_user_id_foreign` FOREIGN KEY (`auteur_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `realisation_photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `realisation_photos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `entreprise_id` bigint unsigned NOT NULL,
  `avis_id` bigint unsigned DEFAULT NULL,
  `photo_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titre` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `ordre` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `realisation_photos_entreprise_id_foreign` (`entreprise_id`),
  KEY `realisation_photos_avis_id_index` (`avis_id`),
  CONSTRAINT `realisation_photos_avis_id_foreign` FOREIGN KEY (`avis_id`) REFERENCES `avis` (`id`) ON DELETE CASCADE,
  CONSTRAINT `realisation_photos_entreprise_id_foreign` FOREIGN KEY (`entreprise_id`) REFERENCES `entreprises` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reservations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reservations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `nom_client` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_client` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone_client_non_inscrit` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `creee_manuellement` tinyint(1) NOT NULL DEFAULT '0',
  `entreprise_id` bigint unsigned NOT NULL,
  `membre_id` bigint unsigned DEFAULT NULL,
  `date_reservation` datetime NOT NULL,
  `lieu` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone_client` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone_cache` tinyint(1) NOT NULL DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `prix` decimal(10,2) NOT NULL,
  `est_paye` tinyint(1) NOT NULL DEFAULT '0',
  `date_paiement` timestamp NULL DEFAULT NULL,
  `statut` enum('en_attente','confirmee','annulee','terminee') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_attente',
  `type_service` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type_service_id` bigint unsigned DEFAULT NULL,
  `duree_minutes` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reservations_entreprise_id_foreign` (`entreprise_id`),
  KEY `reservations_type_service_id_foreign` (`type_service_id`),
  KEY `reservations_membre_id_index` (`membre_id`),
  KEY `reservations_user_id_foreign` (`user_id`),
  CONSTRAINT `reservations_entreprise_id_foreign` FOREIGN KEY (`entreprise_id`) REFERENCES `entreprises` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reservations_membre_id_foreign` FOREIGN KEY (`membre_id`) REFERENCES `entreprise_membres` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reservations_type_service_id_foreign` FOREIGN KEY (`type_service_id`) REFERENCES `types_services` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reservations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `service_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_images` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type_service_id` bigint unsigned NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `est_couverture` tinyint(1) NOT NULL DEFAULT '0',
  `ordre` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `service_images_type_service_id_index` (`type_service_id`),
  KEY `service_images_est_couverture_index` (`est_couverture`),
  CONSTRAINT `service_images_type_service_id_foreign` FOREIGN KEY (`type_service_id`) REFERENCES `types_services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string',
  `group` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`),
  KEY `settings_group_index` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `site_web_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `site_web_versions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `entreprise_id` bigint unsigned NOT NULL,
  `contenu` json NOT NULL COMMENT 'Snapshot du contenu du site web',
  `version_number` int NOT NULL DEFAULT '1',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Description optionnelle de la version',
  `is_auto_save` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'True si sauvegarde automatique, false si manuelle',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `site_web_versions_entreprise_id_version_number_index` (`entreprise_id`,`version_number`),
  CONSTRAINT `site_web_versions_entreprise_id_foreign` FOREIGN KEY (`entreprise_id`) REFERENCES `entreprises` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stocks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `produit_id` bigint unsigned NOT NULL,
  `quantite_disponible` int NOT NULL DEFAULT '0',
  `quantite_minimum` int NOT NULL DEFAULT '0',
  `alerte_stock` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stocks_produit_id_unique` (`produit_id`),
  CONSTRAINT `stocks_produit_id_foreign` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stripe_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stripe_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `stripe_customer_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stripe_payment_intent_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stripe_charge_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stripe_invoice_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stripe_subscription_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stripe_checkout_session_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stripe_event_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'eur',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `raw_data` json DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `processed` tinyint(1) NOT NULL DEFAULT '0',
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stripe_transactions_stripe_event_id_unique` (`stripe_event_id`),
  KEY `stripe_transactions_user_id_event_type_index` (`user_id`,`event_type`),
  KEY `stripe_transactions_stripe_customer_id_created_at_index` (`stripe_customer_id`,`created_at`),
  KEY `stripe_transactions_processed_index` (`processed`),
  KEY `stripe_transactions_stripe_customer_id_index` (`stripe_customer_id`),
  KEY `stripe_transactions_stripe_payment_intent_id_index` (`stripe_payment_intent_id`),
  KEY `stripe_transactions_stripe_charge_id_index` (`stripe_charge_id`),
  KEY `stripe_transactions_stripe_invoice_id_index` (`stripe_invoice_id`),
  KEY `stripe_transactions_stripe_subscription_id_index` (`stripe_subscription_id`),
  KEY `stripe_transactions_stripe_checkout_session_id_index` (`stripe_checkout_session_id`),
  CONSTRAINT `stripe_transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subscription_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscription_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `subscription_id` bigint unsigned NOT NULL,
  `stripe_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stripe_product` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stripe_price` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meter_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `meter_event_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscription_items_stripe_id_unique` (`stripe_id`),
  KEY `subscription_items_subscription_id_stripe_price_index` (`subscription_id`,`stripe_price`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stripe_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stripe_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stripe_price` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscriptions_stripe_id_unique` (`stripe_id`),
  KEY `subscriptions_user_id_stripe_status_index` (`user_id`,`stripe_status`),
  CONSTRAINT `subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ticket_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `est_interne` tinyint(1) NOT NULL DEFAULT '0',
  `est_lu` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_messages_ticket_id_index` (`ticket_id`),
  KEY `ticket_messages_user_id_index` (`user_id`),
  CONSTRAINT `ticket_messages_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_messages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tickets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `numero_ticket` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sujet` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `statut` enum('ouvert','en_cours','resolu','ferme') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ouvert',
  `priorite` enum('basse','normale','haute','urgente') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normale',
  `categorie` enum('technique','facturation','compte','autre') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'autre',
  `user_id` bigint unsigned NOT NULL,
  `assigne_a` bigint unsigned DEFAULT NULL,
  `resolu_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tickets_numero_ticket_unique` (`numero_ticket`),
  KEY `tickets_statut_index` (`statut`),
  KEY `tickets_user_id_index` (`user_id`),
  KEY `tickets_assigne_a_index` (`assigne_a`),
  CONSTRAINT `tickets_assigne_a_foreign` FOREIGN KEY (`assigne_a`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `types_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `types_services` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `entreprise_id` bigint unsigned NOT NULL,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `duree_minutes` int NOT NULL,
  `prix` decimal(10,2) NOT NULL,
  `est_actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `types_services_entreprise_id_index` (`entreprise_id`),
  CONSTRAINT `types_services_entreprise_id_foreign` FOREIGN KEY (`entreprise_id`) REFERENCES `entreprises` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `date_naissance` date DEFAULT NULL,
  `adresse` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ville` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code_postal` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo_profil` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `est_client` tinyint(1) NOT NULL DEFAULT '1',
  `est_gerant` tinyint(1) NOT NULL DEFAULT '0',
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `notifications_erreurs_actives` tinyint(1) NOT NULL DEFAULT '0',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `stripe_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pm_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pm_last_four` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `abonnement_manuel` tinyint(1) NOT NULL DEFAULT '0',
  `abonnement_manuel_actif_jusqu` date DEFAULT NULL,
  `abonnement_manuel_notes` text COLLATE utf8mb4_unicode_ci,
  `abonnement_manuel_type_renouvellement` enum('mensuel','annuel') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `abonnement_manuel_jour_renouvellement` int DEFAULT NULL,
  `abonnement_manuel_date_debut` date DEFAULT NULL,
  `abonnement_manuel_montant` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_stripe_id_index` (`stripe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2025_12_30_223712_create_entreprises_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2025_12_30_230702_add_roles_to_users_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2025_12_30_230907_add_user_id_to_entreprises_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2025_12_30_231602_create_reservations_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2025_12_30_232453_add_is_admin_to_users_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2025_12_30_232954_add_mots_cles_and_logo_to_entreprises_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2025_12_30_233443_create_horaires_ouverture_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2025_12_30_233445_create_types_services_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2025_12_30_233448_add_telephone_fields_to_reservations_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2025_12_30_234812_add_siren_verifie_to_entreprises_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2025_12_30_234812_create_factures_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2025_12_30_235508_create_avis_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2025_12_31_001238_create_conversations_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2025_12_31_001241_create_messages_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2025_12_31_001243_add_photo_profil_to_users_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2025_12_31_001246_add_afficher_nom_to_entreprises_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2025_12_31_002630_add_subscription_fields_to_users_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2025_12_31_002633_add_subscription_manual_to_entreprises_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2025_12_31_004117_add_verification_details_to_entreprises_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2025_12_31_004736_add_media_to_entreprises_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2025_12_31_004738_create_realisation_photos_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2025_12_31_010145_create_notifications_table',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2025_12_31_011218_move_subscription_to_users_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2025_12_31_013037_add_rdv_settings_to_entreprises_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2025_12_31_013051_create_proposition_rendez_vouses_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2025_12_31_013546_add_proposition_rdv_to_messages_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2025_12_31_013617_add_duree_to_proposition_rendez_vouses_table',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2025_12_31_015510_create_subscriptions_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2025_12_31_024417_add_abonnement_manuel_columns_to_users_table',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2025_12_31_024641_create_facture_reservation_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2025_12_31_024641_make_reservation_id_nullable_in_factures_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2025_12_31_024714_create_customer_columns',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2025_12_31_024715_create_subscriptions_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2025_12_31_024716_create_subscription_items_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2025_12_31_024717_add_meter_id_to_subscription_items_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2025_12_31_024718_add_meter_event_name_to_subscription_items_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2025_12_31_145943_add_notifications_erreurs_actives_to_users_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2025_12_31_145943_create_error_logs_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2025_12_31_171321_create_service_images_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2025_12_31_175722_create_tickets_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2025_12_31_175723_create_contacts_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2025_12_31_175723_create_faqs_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2025_12_31_173239_add_avis_id_to_realisation_photos_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2025_12_31_175724_create_ticket_messages_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2025_12_31_190000_create_activity_logs_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2025_12_31_190001_create_settings_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2025_12_31_190002_create_announcements_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2025_12_31_190003_create_promo_codes_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2026_01_01_122312_add_site_web_fields_to_entreprises_table',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2026_01_01_122312_create_entreprise_membres_table',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2026_01_01_122312_create_entreprise_subscriptions_table',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2026_01_01_131420_create_site_web_versions_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2026_01_01_133524_add_membre_id_to_reservations_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2026_01_01_133533_create_membre_disponibilites_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2026_01_01_133533_create_membre_indisponibilites_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2026_01_01_133533_create_membre_statistiques_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2026_01_01_142300_create_entreprise_invitations_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2026_01_01_142310_modify_entreprise_membres_for_invitations',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2026_01_01_193544_remove_unique_constraint_from_email_in_entreprises_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2026_01_01_200438_add_reservation_id_to_conversations_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2026_01_01_201703_add_personal_info_to_users_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2026_01_01_203521_add_type_service_id_to_proposition_rendez_vouses_table',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2026_01_01_210102_add_auteur_destinataire_to_proposition_rendez_vouses_table',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2026_01_02_174124_create_stripe_transactions_table',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2026_01_02_175447_add_type_column_to_subscriptions_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2026_01_02_183132_create_custom_prices_table',38);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2026_01_02_185523_add_renewal_fields_to_manual_subscriptions',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2026_01_02_195417_add_deleted_at_to_entreprises_table',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2026_01_02_214045_add_geolocation_to_entreprises_table',41);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2026_01_03_153847_create_essais_gratuits_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2026_01_04_135637_create_entreprise_finances_table',43);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2026_01_04_154346_add_fiscal_settings_to_entreprises_table',44);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2026_01_06_210447_create_produits_table',45);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2026_01_06_210448_create_stocks_table',45);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2026_01_06_210449_create_promotions_table',45);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2026_01_06_210450_create_produit_images_table',45);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2026_01_06_212907_add_contexte_to_conversations_table',46);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2026_01_06_213814_modify_reservations_for_manual_creation',47);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2026_01_06_213920_remove_unique_constraint_from_conversations_table',47);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2026_01_06_214000_force_remove_unique_constraint_from_conversations',48);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2026_01_06_215815_force_remove_unique_constraint_from_conversations',48);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2026_01_06_215956_finally_remove_unique_constraint_conversations',49);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2026_01_06_220000_finally_remove_unique_constraint_conversations',49);
