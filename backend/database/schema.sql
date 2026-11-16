-- ====================================================================
-- Schéma de base de données Immolode (PHP / MySQL)
-- ====================================================================

CREATE DATABASE IF NOT EXISTS `immolode` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `immolode`;

-- 1. Propriétés (Biens immobiliers)
CREATE TABLE IF NOT EXISTS `properties` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `reference` VARCHAR(50) NOT NULL UNIQUE,
    `title` VARCHAR(255) NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `transaction` VARCHAR(50) NOT NULL,
    `price` DECIMAL(15, 2) NOT NULL,
    `price_period` VARCHAR(20) DEFAULT NULL,
    `wilaya` VARCHAR(100) NOT NULL,
    `city` VARCHAR(100) NOT NULL,
    `district` VARCHAR(150) DEFAULT NULL,
    `surface` DECIMAL(10, 2) NOT NULL,
    `land_surface` DECIMAL(10, 2) DEFAULT NULL,
    `bedrooms` INT DEFAULT NULL,
    `bathrooms` INT DEFAULT NULL,
    `floor` INT DEFAULT NULL,
    `floors` INT DEFAULT NULL,
    `year` INT DEFAULT NULL,
    `garage` INT DEFAULT 0,
    `features` TEXT DEFAULT NULL,
    `images` TEXT DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `status` VARCHAR(50) DEFAULT 'Disponible',
    `is_featured` INT DEFAULT 0,
    `is_new` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_prop_transaction` (`transaction`),
    INDEX `idx_prop_type` (`type`),
    INDEX `idx_prop_status` (`status`),
    INDEX `idx_prop_city` (`city`),
    INDEX `idx_prop_price` (`price`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Demandes (Contact & Estimations)
CREATE TABLE IF NOT EXISTS `demandes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `type` VARCHAR(50) NOT NULL,
    `nom` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(50) NOT NULL,
    `subject` VARCHAR(255) DEFAULT NULL,
    `message` TEXT DEFAULT NULL,
    `details` TEXT DEFAULT NULL,
    `status` VARCHAR(50) DEFAULT 'Nouveau',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_demande_type` (`type`),
    INDEX `idx_demande_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Fiches client (Visites, mandats, engagement)
CREATE TABLE IF NOT EXISTS `client_fiches` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `reference` VARCHAR(50) NOT NULL UNIQUE,
    `nom` VARCHAR(100) NOT NULL,
    `prenom` VARCHAR(100) NOT NULL,
    `telephone` VARCHAR(50) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `transaction` VARCHAR(50) NOT NULL,
    `type_bien` VARCHAR(50) NOT NULL,
    `property_id` INT DEFAULT NULL,
    `piece_identite_name` VARCHAR(255) DEFAULT NULL,
    `piece_identite_size` INT DEFAULT NULL,
    `piece_identite_type` VARCHAR(100) DEFAULT NULL,
    `piece_identite_path` TEXT DEFAULT NULL,
    `signature_data` MEDIUMTEXT DEFAULT NULL,
    `engagement_accepte` INT DEFAULT 1,
    `status` VARCHAR(50) DEFAULT 'Nouveau',
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_fiche_ref` (`reference`),
    INDEX `idx_fiche_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Utilisateurs (Administrateurs & Collaborateurs)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nom` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` VARCHAR(50) DEFAULT 'Agent commercial',
    `poste` VARCHAR(100) DEFAULT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `actif` INT DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Paramètres généraux de l'agence
CREATE TABLE IF NOT EXISTS `settings` (
    `setting_key` VARCHAR(100) PRIMARY KEY,
    `setting_value` TEXT DEFAULT NULL,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
