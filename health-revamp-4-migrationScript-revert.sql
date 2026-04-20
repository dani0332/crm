-- =============================================================================
-- Health revamp migration — REVERT
-- =============================================================================
-- Prerequisites (same MySQL session / connection throughout):
--   • health-revamp-migrationScript-backup.sql was run BEFORE migration
--   • health-revamp-migrationScript.sql completed (including post-migration
--     capture of health_revamp_new_customer_member_ids)
--   Temp tables are invisible to other connections; do not disconnect before revert.
--
-- Order:
--   1) Delete customer_members rows inserted during migration
--   2) Restore customer_members from backup
--   3) Restore health_quote_request from backup
--   4) Restore personal_quotes from backup
--
-- Review row counts after each step. Run inside a transaction if your engine
-- and locks allow: START TRANSACTION; ... COMMIT; or ROLLBACK;
-- =============================================================================

SET NAMES utf8mb4;

-- -----------------------------------------------------------------------------
-- 1) Remove rows that did not exist before migration (INSERT blocks)
-- -----------------------------------------------------------------------------
DELETE cm
FROM customer_members cm
INNER JOIN health_revamp_new_customer_member_ids n ON n.id = cm.id
WHERE cm.quote_type = 'App\\Models\\HealthQuote';

-- -----------------------------------------------------------------------------
-- 2) Restore customer_members columns
-- -----------------------------------------------------------------------------
UPDATE customer_members cm
INNER JOIN health_revamp_bak_customer_members b ON b.id = cm.id
SET
    cm.is_principal = b.is_principal,
    cm.is_policy_holder = b.is_policy_holder,
    cm.gender = b.gender,
    cm.marital_status_id = b.marital_status_id,
    cm.relation_code = b.relation_code,
    cm.salary_band_id = b.salary_band_id,
    cm.visa_category_id = b.visa_category_id,
    cm.member_category_id = b.member_category_id;

-- -----------------------------------------------------------------------------
-- 3) Restore health_quote_request columns
-- -----------------------------------------------------------------------------
UPDATE health_quote_request hqr
INNER JOIN health_revamp_bak_health_quote_request b ON b.id = hqr.id
SET
    hqr.cover_for_id = b.cover_for_id,
    hqr.insure_code = b.insure_code,
    hqr.policy_holder_code = b.policy_holder_code,
    hqr.gender = b.gender,
    hqr.marital_status_id = b.marital_status_id,
    hqr.policy_holder_category_code = b.policy_holder_category_code,
    hqr.salary_band_id = b.salary_band_id,
    hqr.visa_category_id = b.visa_category_id,
    hqr.member_category_id = b.member_category_id;

-- -----------------------------------------------------------------------------
-- 4) Restore personal_quotes.gender
-- -----------------------------------------------------------------------------
UPDATE personal_quotes pq
INNER JOIN health_revamp_bak_personal_quotes b ON b.id = pq.id
SET
    pq.gender = b.gender;

-- -----------------------------------------------------------------------------
-- Optional: drop temp tables after successful verification (same session)
-- -----------------------------------------------------------------------------
-- DROP TEMPORARY TABLE IF EXISTS health_revamp_new_customer_member_ids;
-- DROP TEMPORARY TABLE IF EXISTS health_revamp_bak_personal_quotes;
-- DROP TEMPORARY TABLE IF EXISTS health_revamp_bak_health_quote_request;
-- DROP TEMPORARY TABLE IF EXISTS health_revamp_bak_customer_members;
-- DROP TEMPORARY TABLE IF EXISTS health_revamp_bak_cm_ids_before;
-- DROP TABLE IF EXISTS health_revamp_bak_entity_health_leads;

-- End revert
