-- =============================================================================
-- Health revamp migration — BACKUP (run immediately before migration script)
-- =============================================================================
-- Creates regular backup tables (health_revamp_bak_*). Drop them before re-run
-- or use a fresh database session. Migration/revert scripts must use the same
-- connection if they reference these tables.
--
-- Execution order (same connection):
--   1) This file
--   2) health-revamp-migrationScript.sql (includes post-migration ID capture)
--   3) health-revamp-migrationScript-revert.sql (only if rollback needed)
-- =============================================================================

SET NAMES utf8mb4;

-- -----------------------------------------------------------------------------
-- Snapshot: all health quote requests (leads) with Entity insured (any lock/status)
--   id = health_quote_request.id (one row per quote; DISTINCT if multiple insured)
-- -----------------------------------------------------------------------------
CREATE TABLE health_revamp_bak_entity_health_leads (
    id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    backed_up_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO health_revamp_bak_entity_health_leads (id, backed_up_at)
SELECT DISTINCT hqr.id, CURRENT_TIMESTAMP
FROM health_quote_request hqr
INNER JOIN customer_insured ci
    ON ci.quote_request_id = hqr.id
    AND ci.quote_type_id = 3
    AND ci.customer_id = hqr.customer_id
    AND ci.is_active = 1
INNER JOIN insured i ON i.id = ci.insured_id
WHERE i.customer_type = 'Entity';

-- -----------------------------------------------------------------------------
-- Snapshot: all customer_members IDs before migration (detects INSERTs later)
-- -----------------------------------------------------------------------------
CREATE TABLE health_revamp_bak_cm_ids_before (
    id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    quote_id BIGINT UNSIGNED NULL
);

INSERT INTO health_revamp_bak_cm_ids_before (id, quote_id)
SELECT cm.id, cm.quote_id
FROM customer_members cm
INNER JOIN health_quote_request hqr ON hqr.id = cm.quote_id
WHERE cm.quote_type = 'App\\Models\\HealthQuote'
  AND hqr.is_quote_locked = 0
  AND hqr.quote_status_id NOT IN (70, 71)
  AND hqr.id NOT IN (SELECT id FROM health_revamp_bak_entity_health_leads);

-- -----------------------------------------------------------------------------
-- customer_members: columns updated by migration
--   is_principal, is_policy_holder, gender, marital_status_id,
--   relation_code, salary_band_id, visa_category_id, member_category_id
-- -----------------------------------------------------------------------------
CREATE TABLE health_revamp_bak_customer_members (
    id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    quote_id BIGINT UNSIGNED NULL,
    is_principal TINYINT(1) NULL,
    is_policy_holder TINYINT(1) NULL,
    gender VARCHAR(255) NULL,
    marital_status_id BIGINT UNSIGNED NULL,
    relation_code VARCHAR(255) NULL,
    salary_band_id BIGINT UNSIGNED NULL,
    visa_category_id BIGINT UNSIGNED NULL,
    member_category_id BIGINT UNSIGNED NULL,
    backed_up_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO health_revamp_bak_customer_members (
    id,
    quote_id,
    is_principal,
    is_policy_holder,
    gender,
    marital_status_id,
    relation_code,
    salary_band_id,
    visa_category_id,
    member_category_id,
    backed_up_at
)
SELECT
    cm.id,
    cm.quote_id,
    cm.is_principal,
    cm.is_policy_holder,
    cm.gender,
    cm.marital_status_id,
    cm.relation_code,
    cm.salary_band_id,
    cm.visa_category_id,
    cm.member_category_id,
    CURRENT_TIMESTAMP
FROM customer_members cm
INNER JOIN health_quote_request hqr ON hqr.id = cm.quote_id
WHERE cm.quote_type = 'App\\Models\\HealthQuote'
  AND hqr.is_quote_locked = 0
  AND hqr.quote_status_id NOT IN (70, 71)
  AND hqr.id NOT IN (SELECT id FROM health_revamp_bak_entity_health_leads)
  AND cm.is_third_party_payer = 0
  AND cm.deleted_at IS NULL;

-- -----------------------------------------------------------------------------
-- health_quote_request: snapshot of columns the migration may change
--   (unlocked quotes only: is_quote_locked = 0, quote_status_id not in 70/71)
-- -----------------------------------------------------------------------------
CREATE TABLE health_revamp_bak_health_quote_request (
    id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    code VARCHAR(30) NULL,
    cover_for_id BIGINT UNSIGNED NULL,
    insure_code VARCHAR(255) NULL,
    policy_holder_code VARCHAR(255) NULL,
    gender VARCHAR(255) NULL,
    marital_status_id BIGINT UNSIGNED NULL,
    policy_holder_category_code VARCHAR(255) NULL,
    salary_band_id BIGINT UNSIGNED NULL,
    visa_category_id BIGINT UNSIGNED NULL,
    member_category_id BIGINT UNSIGNED NULL,
    backed_up_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO health_revamp_bak_health_quote_request (
    id,
    code,
    cover_for_id,
    insure_code,
    policy_holder_code,
    gender,
    marital_status_id,
    policy_holder_category_code,
    salary_band_id,
    visa_category_id,
    member_category_id,
    backed_up_at
)
SELECT
    hqr.id,
    hqr.code,
    hqr.cover_for_id,
    hqr.insure_code,
    hqr.policy_holder_code,
    hqr.gender,
    hqr.marital_status_id,
    hqr.policy_holder_category_code,
    hqr.salary_band_id,
    hqr.visa_category_id,
    hqr.member_category_id,
    CURRENT_TIMESTAMP
FROM health_quote_request hqr
WHERE hqr.is_quote_locked = 0
  AND hqr.quote_status_id NOT IN (70, 71)
  AND hqr.id NOT IN (SELECT id FROM health_revamp_bak_entity_health_leads);

-- -----------------------------------------------------------------------------
-- personal_quotes: gender only (quote_type_id = 3, unlocked, quote_status_id not 70/71)
-- -----------------------------------------------------------------------------
CREATE TABLE health_revamp_bak_personal_quotes (
    id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    code VARCHAR(30) NULL,
    gender VARCHAR(255) NULL,
    backed_up_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO health_revamp_bak_personal_quotes (
    id,
    code,
    gender,
    backed_up_at
)
SELECT
    pq.id,
    pq.code,
    pq.gender,
    CURRENT_TIMESTAMP
FROM personal_quotes pq
WHERE pq.quote_type_id = 3
  AND pq.is_quote_locked = 0
  AND pq.quote_status_id NOT IN (70, 71)
  AND pq.quote_id NOT IN (SELECT id FROM health_revamp_bak_entity_health_leads)
  AND pq.gender IN ('M', 'Male', 'F', 'FS', 'Female', 'FM');

-- End backup
