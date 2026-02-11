-- Migration pour ajouter la colonne notifications_erreurs_actives à la table users
ALTER TABLE `users` ADD COLUMN `notifications_erreurs_actives` BOOLEAN DEFAULT FALSE AFTER `is_admin`;

-- Migration pour créer la table error_logs
CREATE TABLE IF NOT EXISTS `error_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `level` VARCHAR(255) NOT NULL,
  `message` VARCHAR(255) NOT NULL,
  `context` TEXT NULL,
  `file` VARCHAR(255) NULL,
  `line` INT NULL,
  `trace` TEXT NULL,
  `url` VARCHAR(255) NULL,
  `method` VARCHAR(255) NULL,
  `ip` VARCHAR(255) NULL,
  `user_agent` VARCHAR(255) NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `est_vue` BOOLEAN DEFAULT FALSE,
  `vu_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_level` (`level`),
  INDEX `idx_est_vue` (`est_vue`),
  INDEX `idx_created_at` (`created_at`),
  CONSTRAINT `error_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
