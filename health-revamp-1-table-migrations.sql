-- Raw MySQL equivalent of:
--   2026_02_10_122346_add_is_policy_holder_and_is_insured_column_to_customer_members_table.php
--   2026_02_11_135601_add_to_insure_id_placeholder_id_is_quote_revisible_to_health_quote_request_table.php
--
-- Run against the same database as the Laravel app. If a column or FK already exists, skip or adjust
-- the corresponding statement (MySQL 8.0 has no ADD COLUMN IF NOT EXISTS).
-- Requires InnoDB and existing tables: health_cover_for, marital_status, customer_members, health_quote_request.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- 2026_02_10: visa_categories + customer_members
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `visa_categories` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL,
  `text` VARCHAR(50) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NULL DEFAULT NULL,
  `health_cover_for_id` INT NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `visa_categories_health_cover_for_id_foreign`
    FOREIGN KEY (`health_cover_for_id`) REFERENCES `health_cover_for` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `customer_members`
  ADD COLUMN `is_policy_holder` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN `is_insured` TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN `marital_status_id` INT NULL DEFAULT NULL,
  ADD COLUMN `visa_category_id` INT NULL DEFAULT NULL;

ALTER TABLE `customer_members`
  ADD CONSTRAINT `customer_members_marital_status_id_foreign`
    FOREIGN KEY (`marital_status_id`) REFERENCES `marital_status` (`id`),
  ADD CONSTRAINT `customer_members_visa_category_id_foreign`
    FOREIGN KEY (`visa_category_id`) REFERENCES `visa_categories` (`id`);

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------------
-- 2026_02_11: health_quote_request
-- ---------------------------------------------------------------------------

SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `health_quote_request`
  ADD COLUMN `insure_code` VARCHAR(50) NULL DEFAULT NULL,
  ADD COLUMN `policy_holder_code` VARCHAR(50) NULL DEFAULT NULL,
  ADD COLUMN `is_quote_revisable` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN `visa_category_id` INT NULL DEFAULT NULL,
  ADD COLUMN `policy_holder_category_code` VARCHAR(50) NULL DEFAULT NULL;

ALTER TABLE `health_quote_request`
  ADD CONSTRAINT `health_quote_request_visa_category_id_foreign`
    FOREIGN KEY (`visa_category_id`) REFERENCES `visa_categories` (`id`);

SET FOREIGN_KEY_CHECKS = 1;