-- =============================================================================
-- Run health-revamp-2-migrationScript-backup.sql FIRST, then this file, same DB
-- session. Requires table health_revamp_bak_entity_health_leads from that backup.
-- Revert in same session if needed.
--
-- All changes apply only when health_quote_request.is_quote_locked = 0 (unlocked),
-- quote_status_id is not in (70, 71), and the quote id is NOT listed in
-- health_revamp_bak_entity_health_leads (Entity insured leads — excluded from migration).
-- =============================================================================

# cases
#  all data updates will be done for customer type individual only
#  fill missing is_principal for customer type individual -- no principal needed for entity --- done
#  investigate records with same first_name and last_name --- done
#    - check records which are marked as principal
#    - check records which are not marked as principal
#  for customers with same firstname and lastname as health quote mark it as is_policy_holder -> 1 -- for customer type individual -- done
#    - if there is one members with same name that will be policyholder
#    - if there are more than one members with same name but later one marked as principal then that will be policyholder
#    - if there is no marching record then insert a new record with is_policy_holder -> 1 and is_insured -> 0, is_principal -> 0 -- for customer type individual
#    - if there is no customer member record at all then insert a new record with is_policy_holder -> 1 and is_insured -> 1, is_principal -> 1 -- for customer type individual
#  for health quote entity, insert a new record with is_policy_holder -> 1 and is_insured -> 0 with customer type entity -- no need to insert new record for entity
#  update customer members based on provided datampping, taking in account for principal, policy holder, insured and customer type
#  update health quote coumns, sync princiapl member fields

#--------  fill missing is_principal for customer type individual -- no principal needed for entity
# update is_principal for leads without any principal
WITH unlocked_health_quotes AS (
    SELECT id FROM health_quote_request
    WHERE is_quote_locked = 0 AND quote_status_id NOT IN (70, 71)
      AND id NOT IN (SELECT id FROM health_revamp_bak_entity_health_leads)
),
no_principal_quotes AS (
    SELECT cm.quote_id
    FROM customer_members cm
    INNER JOIN unlocked_health_quotes u ON u.id = cm.quote_id
    WHERE cm.quote_type = 'App\\Models\\HealthQuote'
      AND cm.customer_type = 'Individual'
      AND cm.is_third_party_payer = 0
      AND cm.deleted_at is null
    GROUP BY cm.quote_id
    HAVING SUM(cm.is_principal) = 0
),
name_matched AS (
    -- Pick the FIRST (lowest id) name-matched non-payer per quote
    SELECT MIN(cm.id) AS id, cm.quote_id
    FROM customer_members cm
    INNER JOIN no_principal_quotes npq ON npq.quote_id = cm.quote_id
    INNER JOIN health_quote_request hqr ON hqr.id = cm.quote_id
    WHERE cm.quote_type = 'App\\Models\\HealthQuote'
      AND cm.customer_type = 'Individual'
      AND cm.is_third_party_payer = 0
      AND cm.deleted_at is null
      AND hqr.is_quote_locked = 0 AND hqr.quote_status_id NOT IN (70, 71)
      AND hqr.id NOT IN (SELECT id FROM health_revamp_bak_entity_health_leads)
      AND cm.first_name = hqr.first_name
      AND cm.last_name = hqr.last_name
    GROUP BY cm.quote_id
),
first_non_payer AS (
    -- Pick the FIRST (lowest id) non-payer per quote as fallback
    SELECT MIN(cm.id) AS id, cm.quote_id
    FROM customer_members cm
    INNER JOIN no_principal_quotes npq ON npq.quote_id = cm.quote_id
    WHERE cm.quote_type = 'App\\Models\\HealthQuote'
      AND cm.customer_type = 'Individual'
      AND cm.is_third_party_payer = 0
      AND cm.deleted_at is null
    GROUP BY cm.quote_id
),
to_update AS (
    -- Name match wins; fallback to first non-payer only when no name match exists for that quote
    SELECT id, quote_id FROM name_matched

    UNION

    SELECT fnp.id, fnp.quote_id
    FROM first_non_payer fnp
    WHERE fnp.quote_id NOT IN (SELECT quote_id FROM name_matched)
)
UPDATE customer_members
SET is_principal = 1
WHERE id IN (SELECT id FROM to_update);


#  for customers with same firstname and lastname as health quote mark it as is_policy_holder -> 1 -- for customer type individual
#    - if there is one members with same name that will be policyholder
#    - if there are more than one members with same name but later one marked as principal then that will be policyholder
#    - if there is no marching record then insert a new record with is_policy_holder -> 1 and is_insured -> 0, is_principal -> 0 -- for customer type individual
#    - if there is no customer member record at all then insert a new record with is_policy_holder -> 1 and is_insured -> 1, is_principal -> 1 -- for customer type individual

-- ============================================================
-- STEP 1: Update is_policy_holder for quotes WITH a name match
--         Only members aged >= 18 (by dob) may be policy holder.
-- ============================================================
WITH unlocked_health_quotes AS (
    SELECT id FROM health_quote_request
    WHERE is_quote_locked = 0 AND quote_status_id NOT IN (70, 71)
      AND id NOT IN (SELECT id FROM health_revamp_bak_entity_health_leads)
),
no_policy_holder_quotes AS (
    SELECT cm.quote_id
    FROM customer_members cm
    INNER JOIN unlocked_health_quotes u ON u.id = cm.quote_id
    WHERE cm.quote_type = 'App\\Models\\HealthQuote'
      AND cm.customer_type = 'Individual'
      AND cm.is_third_party_payer = 0
    GROUP BY cm.quote_id
    HAVING SUM(cm.is_policy_holder) = 0
),
name_matched AS (
    SELECT cm.id, cm.quote_id, cm.is_principal,
           COUNT(*) OVER (PARTITION BY cm.quote_id) AS match_count
    FROM customer_members cm
    INNER JOIN no_policy_holder_quotes npq ON npq.quote_id = cm.quote_id
    INNER JOIN health_quote_request hqr ON hqr.id = cm.quote_id
    WHERE cm.quote_type = 'App\\Models\\HealthQuote'
      AND cm.customer_type = 'Individual'
      AND cm.is_third_party_payer = 0
      AND cm.deleted_at is null
      AND hqr.is_quote_locked = 0 AND hqr.quote_status_id NOT IN (70, 71)
      AND hqr.id NOT IN (SELECT id FROM health_revamp_bak_entity_health_leads)
      AND cm.first_name = hqr.first_name
      AND cm.last_name = hqr.last_name
      AND cm.dob IS NOT NULL
      AND TIMESTAMPDIFF(YEAR, cm.dob, CURDATE()) >= 18
),
ranked AS (
    -- Rank within each quote: prefer is_principal=1, then lowest id
    SELECT id, quote_id,
           ROW_NUMBER() OVER (
               PARTITION BY quote_id
               ORDER BY is_principal DESC, id ASC
           ) AS rn
    FROM name_matched
),
to_update AS (
    SELECT id FROM ranked WHERE rn = 1
)
UPDATE customer_members
SET is_policy_holder = 1
WHERE id IN (SELECT id FROM to_update);

-- ============================================================
-- STEP 2: Insert new record for quotes with NO name match
--         Skip insert if an 18+ name-matched policy holder already exists
--         (see NOT EXISTS: cm.dob age check on that row).
-- ============================================================
INSERT INTO customer_members (
    quote_type, quote_id, customer_entity_id, code, customer_type,
    first_name, last_name, salary_band_id,
    is_pec_marked, visa_category_id, dob,
    nationality_id, is_insured, is_policy_holder, is_principal,
    created_at, updated_at
)
SELECT
    'App\\Models\\HealthQuote',
    hqr.id,
    hqr.customer_id,
    CONCAT(
        'IND-',
        hqr.customer_id,
        '-',
        1 + COALESCE(ind_cnt.individual_entity_count, 0)
    ),
    'Individual',
    hqr.first_name,
    hqr.last_name,
    hqr.salary_band_id,
    0,
    hqr.visa_category_id,
    DATE(hqr.dob),
    hqr.nationality_id,
    0,
    1,
    0,
    NOW(),
    NOW()
FROM health_quote_request hqr
LEFT JOIN (
    SELECT customer_entity_id, COUNT(*) AS individual_entity_count
    FROM customer_members
    WHERE customer_type = 'Individual'
      AND deleted_at IS NULL
    GROUP BY customer_entity_id
) ind_cnt ON ind_cnt.customer_entity_id = hqr.customer_id
WHERE EXISTS (
    SELECT 1
    FROM customer_members
    WHERE quote_type = 'App\\Models\\HealthQuote'
      AND quote_id = hqr.id
      AND customer_type = 'Individual'
      AND is_third_party_payer = 0
      AND deleted_at is null
)
AND hqr.id IN (
    SELECT quote_id
    FROM customer_members
    WHERE quote_type = 'App\\Models\\HealthQuote'
      AND customer_type = 'Individual'
      AND is_third_party_payer = 0
      AND deleted_at is null
    GROUP BY quote_id
    HAVING SUM(is_policy_holder) = 0
)
AND NOT EXISTS (
    SELECT 1
    FROM customer_members cm
    WHERE cm.quote_type = 'App\\Models\\HealthQuote'
      AND cm.quote_id = hqr.id
      AND cm.customer_type = 'Individual'
      AND cm.is_policy_holder = 1
      AND cm.first_name = hqr.first_name
      AND cm.last_name = hqr.last_name
      AND cm.is_third_party_payer = 0
      AND cm.deleted_at IS NULL
      AND cm.dob IS NOT NULL
      AND TIMESTAMPDIFF(YEAR, cm.dob, CURDATE()) >= 18
)
AND hqr.is_quote_locked = 0 AND hqr.quote_status_id NOT IN (70, 71)
AND hqr.id NOT IN (SELECT id FROM health_revamp_bak_entity_health_leads)
AND hqr.customer_id IS NOT NULL;

-- ============================================================
-- step 3: No customer_members AND no insured linked
--         → Insert from health_quote_request directly
-- ============================================================
INSERT INTO customer_members (
    quote_type, quote_id, customer_entity_id, code, customer_type,
    first_name, last_name, salary_band_id,
    is_pec_marked, visa_category_id, dob,
    nationality_id, is_insured, is_policy_holder, is_principal,
    created_at, updated_at
)
SELECT
    'App\\Models\\HealthQuote',
    hqr.id,
    hqr.customer_id,
    CONCAT(
        'IND-',
        hqr.customer_id,
        '-',
        1 + COALESCE(ind_cnt.cnt, 0)
    ),
    'Individual',
    hqr.first_name,
    hqr.last_name,
    hqr.salary_band_id,
    0,
    hqr.visa_category_id,
    DATE(hqr.dob),
    hqr.nationality_id,
    1,
    1,
    1,
    NOW(),
    NOW()
FROM health_quote_request hqr
LEFT JOIN (
    SELECT customer_entity_id, COUNT(*) AS cnt
    FROM customer_members
    WHERE customer_type = 'Individual'
      AND deleted_at IS NULL
    GROUP BY customer_entity_id
) ind_cnt ON ind_cnt.customer_entity_id = hqr.customer_id
WHERE NOT EXISTS (
    SELECT 1
    FROM customer_members cm
    WHERE cm.quote_id = hqr.id
      AND cm.quote_type = 'App\\Models\\HealthQuote'
      AND cm.is_third_party_payer = 0
      AND cm.deleted_at is null
)
AND NOT EXISTS (
    SELECT 1
    FROM customer_insured ci
    WHERE ci.quote_request_id = hqr.id AND ci.quote_type_id = 3
      AND ci.customer_id = hqr.customer_id
      AND ci.is_active = 1
)
AND hqr.is_quote_locked = 0 AND hqr.quote_status_id NOT IN (70, 71)
AND hqr.id NOT IN (SELECT id FROM health_revamp_bak_entity_health_leads)
AND hqr.customer_id is not null;


-- ============================================================
-- step 4: No customer_members BUT has insured with customer_type = 'Individual'
--         → Insert from insured data
-- ============================================================
INSERT INTO customer_members (
    quote_type, quote_id, customer_entity_id, code, customer_type,
    first_name, last_name, salary_band_id,
    is_pec_marked, visa_category_id, dob,
    nationality_id, is_insured, is_policy_holder, is_principal,
    created_at, updated_at
)
SELECT
    'App\\Models\\HealthQuote',
    hqr.id,
    hqr.customer_id,
    CONCAT(
        'IND-',
        hqr.customer_id,
        '-',
        1 + COALESCE(ind_cnt.cnt, 0)
    ),
    'Individual',
    hqr.first_name,
    hqr.last_name,
    hqr.salary_band_id,
    0,
    hqr.visa_category_id,
    DATE(hqr.dob),
    hqr.nationality_id,
    1,
    1,
    1,
    NOW(),
    NOW()
FROM health_quote_request hqr
INNER JOIN customer_insured ci
    ON ci.quote_request_id = hqr.id
    AND ci.quote_type_id = 3
    AND ci.customer_id = hqr.customer_id
    AND ci.is_active = 1
INNER JOIN insured i ON i.id = ci.insured_id
LEFT JOIN (
    SELECT customer_entity_id, COUNT(*) AS cnt
    FROM customer_members
    WHERE customer_type = 'Individual'
      AND deleted_at IS NULL
    GROUP BY customer_entity_id
) ind_cnt ON ind_cnt.customer_entity_id = hqr.customer_id
WHERE i.customer_type = 'Individual'
  AND NOT EXISTS (
      SELECT 1
      FROM customer_members cm
      WHERE cm.quote_id = hqr.id
        AND cm.quote_type = 'App\\Models\\HealthQuote'
        AND cm.is_third_party_payer = 0
        AND cm.deleted_at is null
  )
AND hqr.is_quote_locked = 0 AND hqr.quote_status_id NOT IN (70, 71)
AND hqr.id NOT IN (SELECT id FROM health_revamp_bak_entity_health_leads)
AND hqr.customer_id is not null;


  #### update cover for id
update health_quote_request set cover_for_id=4 where cover_for_id in (1, 2) and is_quote_locked = 0 and quote_status_id not in (70, 71) and id not in (select id from health_revamp_bak_entity_health_leads);
update health_quote_request set cover_for_id=5 where member_category_id in (12) and is_quote_locked = 0 and quote_status_id not in (70, 71) and id not in (select id from health_revamp_bak_entity_health_leads);

#### update insure_code and policy_holder_code
WITH active_insured_types AS (
    SELECT DISTINCT hqr.id AS quote_id, i.customer_type
    FROM health_quote_request hqr
    INNER JOIN customer_insured ci
        ON ci.quote_request_id = hqr.id
        AND ci.quote_type_id = 3
        AND ci.customer_id = hqr.customer_id
        AND ci.is_active = 1
    INNER JOIN insured i ON i.id = ci.insured_id
    WHERE hqr.is_quote_locked = 0 AND hqr.quote_status_id NOT IN (70, 71)
      AND hqr.id NOT IN (SELECT id FROM health_revamp_bak_entity_health_leads)
),
quotes_without_active_insured AS (
    SELECT hqr.id AS quote_id
    FROM health_quote_request hqr
    WHERE hqr.is_quote_locked = 0 AND hqr.quote_status_id NOT IN (70, 71)
      AND hqr.id NOT IN (SELECT id FROM health_revamp_bak_entity_health_leads)
      AND NOT EXISTS (
        SELECT 1
        FROM active_insured_types ait
        WHERE ait.quote_id = hqr.id
    )
),
allowed_customer_types AS (
    SELECT ait.quote_id, ait.customer_type
    FROM active_insured_types ait

    UNION ALL

    SELECT qws.quote_id, 'Individual' AS customer_type
    FROM quotes_without_active_insured qws
),
member_agg AS (
    SELECT
        cm.quote_id,
        MAX(CASE WHEN cm.is_policy_holder = 1 THEN cm.is_insured ELSE 0 END) AS ph_is_insured,
        SUM(CASE WHEN cm.is_insured = 1 THEN 1 ELSE 0 END) AS insured_cnt
    FROM customer_members cm
    INNER JOIN allowed_customer_types act
        ON act.quote_id = cm.quote_id
        AND act.customer_type = cm.customer_type
        AND cm.is_third_party_payer = 0
    WHERE cm.quote_type = 'App\\Models\\HealthQuote'
      AND cm.deleted_at IS NULL
    GROUP BY cm.quote_id
)
UPDATE health_quote_request hqr
INNER JOIN member_agg agg ON agg.quote_id = hqr.id
SET
    hqr.insure_code = CASE
        WHEN agg.insured_cnt > 1 THEN 'MYSELF_AND_MY_FAMILY_MEMBERS'
        ELSE 'ONLY_MYSELF'
    END,
    hqr.policy_holder_code = CASE
        WHEN agg.ph_is_insured = 0 THEN 'OTHER_ADULT_FAMILY_MEMBER'
        ELSE 'ME'
    END
WHERE hqr.is_quote_locked = 0 AND hqr.quote_status_id NOT IN (70, 71)
  AND hqr.id NOT IN (SELECT id FROM health_revamp_bak_entity_health_leads);


#### update maritial status
UPDATE health_quote_request hqr
SET hqr.marital_status_id = CASE
    WHEN hqr.marital_status_id IS NULL
         AND hqr.gender IN ('M', 'F', 'FS', 'Female', 'Male') THEN 1
    WHEN hqr.marital_status_id IS NULL
         AND hqr.gender IN ('FM') THEN 2
    WHEN hqr.marital_status_id IS NOT NULL
         AND hqr.marital_status_id = 5 THEN 1
    ELSE hqr.marital_status_id
END
WHERE hqr.is_quote_locked = 0 AND hqr.quote_status_id NOT IN (70, 71)
  AND hqr.id NOT IN (SELECT id FROM health_revamp_bak_entity_health_leads);

UPDATE customer_members cm
INNER JOIN health_quote_request hqr
    ON hqr.id = cm.quote_id
    AND cm.quote_type = 'App\\Models\\HealthQuote'
SET cm.marital_status_id = CASE
    WHEN cm.is_principal = 1 THEN hqr.marital_status_id
    WHEN cm.is_principal = 0
         AND cm.gender IN ('M', 'F', 'FS', 'Female', 'Male') THEN 1
    WHEN cm.is_principal = 0
         AND cm.gender IN ('FM') THEN 2
    ELSE cm.marital_status_id
END
WHERE cm.customer_type='Individual' and cm.is_third_party_payer=0 and cm.deleted_at is null and hqr.is_quote_locked = 0 and hqr.quote_status_id NOT IN (70, 71)
  AND hqr.id NOT IN (SELECT id FROM health_revamp_bak_entity_health_leads);


#### update gender
update health_quote_request set gender='M' where gender in ('M', 'Male') and is_quote_locked=0 and quote_status_id not in (70, 71) and id not in (select id from health_revamp_bak_entity_health_leads);
update health_quote_request set gender='F' where gender in ('F', 'FS', 'Female', 'FM') and is_quote_locked=0 and quote_status_id not in (70, 71) and id not in (select id from health_revamp_bak_entity_health_leads);

update personal_quotes pq
set pq.gender='M' where pq.gender in ('M', 'Male') and pq.quote_type_id=3 and pq.is_quote_locked=0 and pq.quote_status_id not in (70, 71) and pq.quote_id not in (select id from health_revamp_bak_entity_health_leads);
update personal_quotes pq
set pq.gender='F' where pq.gender in ('F', 'FS', 'Female', 'FM') and pq.quote_type_id=3 and pq.is_quote_locked=0 and pq.quote_status_id not in (70, 71) and pq.quote_id not in (select id from health_revamp_bak_entity_health_leads);

update customer_members cm
join health_quote_request hqr on cm.quote_id = hqr.id and cm.quote_type='App\\Models\\HealthQuote'
set cm.gender='M' where cm.gender in ('M', 'Male') and cm.customer_type='Individual' and cm.is_third_party_payer=0 and cm.deleted_at is null and hqr.is_quote_locked=0 and hqr.quote_status_id not in (70, 71) and hqr.id not in (select id from health_revamp_bak_entity_health_leads);
update customer_members cm
join health_quote_request hqr on cm.quote_id = hqr.id and cm.quote_type='App\\Models\\HealthQuote'
set cm.gender='F' where cm.gender in ('F', 'FS', 'Female', 'FM') and cm.customer_type='Individual' and cm.is_third_party_payer=0 and cm.deleted_at is null and hqr.is_quote_locked=0 and hqr.quote_status_id not in (70, 71) and hqr.id not in (select id from health_revamp_bak_entity_health_leads);


#### update policy holder category code
UPDATE health_quote_request hqr
SET hqr.policy_holder_category_code = CASE
    WHEN hqr.nationality_id IN (154, 289, 96, 135, 145, 14) THEN 'GCC_CITIZEN'
    WHEN hqr.nationality_id IN (56, 269) THEN 'UAE_CITIZEN'
    ELSE 'RESIDENT'
END
WHERE hqr.is_quote_locked = 0 AND hqr.quote_status_id NOT IN (70, 71)
  AND hqr.id NOT IN (SELECT id FROM health_revamp_bak_entity_health_leads);

#### update relation code, salary band, visa categories
UPDATE health_quote_request hqr
SET
    hqr.salary_band_id = CASE hqr.member_category_id
        WHEN 12 THEN 1
        WHEN 18 THEN 3
        WHEN 17 THEN 1
        WHEN 11 THEN 4
        WHEN 9 THEN 4
        WHEN 10 THEN 4
        WHEN 16 THEN 5
        WHEN 15 THEN 5
        WHEN 13 THEN 5
        WHEN 14 THEN 5
        ELSE hqr.salary_band_id
    END,
    hqr.visa_category_id = CASE
        WHEN hqr.member_category_id IN (12, 18, 17, 16, 15, 13) THEN 4
        WHEN hqr.member_category_id = 11 THEN 3
        WHEN hqr.member_category_id = 9 THEN 2
        WHEN hqr.member_category_id = 10 THEN 1
        WHEN hqr.member_category_id = 14
             AND hqr.dob IS NOT NULL
             AND TIMESTAMPDIFF(MONTH, hqr.dob, CURDATE()) <= 12 THEN 5
        WHEN hqr.member_category_id = 14 THEN 4
        ELSE hqr.visa_category_id
    END
WHERE hqr.is_quote_locked = 0 AND hqr.quote_status_id NOT IN (70, 71)
  AND hqr.id NOT IN (SELECT id FROM health_revamp_bak_entity_health_leads);

UPDATE customer_members cm
INNER JOIN health_quote_request hqr
    ON hqr.id = cm.quote_id
    AND cm.quote_type = 'App\\Models\\HealthQuote'
SET
    cm.relation_code = CASE
        WHEN cm.is_policy_holder = 1 THEN NULL
        WHEN cm.member_category_id = 12 THEN 'relDomesticWorker'
        WHEN cm.member_category_id IN (18, 17, 11, 9, 10) THEN NULL
        WHEN cm.member_category_id = 16 THEN 'relSiblingOrRelatives'
        WHEN cm.member_category_id = 15 THEN 'relParent'
        WHEN cm.member_category_id = 13 THEN 'relSpouse'
        WHEN cm.member_category_id = 14 THEN 'relChild'
        ELSE cm.relation_code
    END,
    cm.salary_band_id = CASE
        WHEN cm.is_policy_holder = 1 THEN hqr.salary_band_id
        WHEN cm.member_category_id = 12 THEN 1
        WHEN cm.member_category_id = 18 THEN 3
        WHEN cm.member_category_id = 17 THEN 1
        WHEN cm.member_category_id = 11 THEN 4
        WHEN cm.member_category_id = 9 THEN 4
        WHEN cm.member_category_id = 10 THEN 4
        WHEN cm.member_category_id = 16 THEN 5
        WHEN cm.member_category_id = 15 THEN 5
        WHEN cm.member_category_id = 13 THEN 5
        WHEN cm.member_category_id = 14 THEN 5
        ELSE cm.salary_band_id
    END,
    cm.visa_category_id = CASE
        WHEN cm.is_policy_holder = 1 THEN hqr.visa_category_id
        WHEN cm.member_category_id IN (12, 18, 17, 16, 15, 13) THEN 4
        WHEN cm.member_category_id = 11 THEN 3
        WHEN cm.member_category_id = 9 THEN 2
        WHEN cm.member_category_id = 10 THEN 1
        WHEN cm.member_category_id = 14
             AND cm.dob IS NOT NULL
             AND TIMESTAMPDIFF(MONTH, cm.dob, CURDATE()) <= 12 THEN 5
        WHEN cm.member_category_id = 14 THEN 4
        ELSE cm.visa_category_id
    END
WHERE hqr.is_quote_locked = 0 AND hqr.quote_status_id NOT IN (70, 71) and cm.customer_type='Individual' and cm.is_third_party_payer=0 and cm.deleted_at is null
  AND hqr.id NOT IN (SELECT id FROM health_revamp_bak_entity_health_leads);
 

#### update member categories
UPDATE health_quote_request hqr
SET hqr.member_category_id = CASE
    WHEN hqr.dob IS NOT NULL
         AND TIMESTAMPDIFF(MONTH, hqr.dob, CURDATE()) <= 12 THEN 24
    WHEN hqr.nationality_id IN (56, 269) THEN 23
    WHEN hqr.nationality_id IN (154, 289, 322, 96, 135, 145, 14) THEN 22
    WHEN hqr.emirate_of_your_visa_id = 2 THEN 20
    WHEN NOT (hqr.emirate_of_your_visa_id = 2) THEN 21
    ELSE hqr.member_category_id
END
WHERE hqr.is_quote_locked = 0 AND hqr.quote_status_id NOT IN (70, 71)
  AND hqr.id NOT IN (SELECT id FROM health_revamp_bak_entity_health_leads);

UPDATE customer_members cm
INNER JOIN health_quote_request hqr
    ON hqr.id = cm.quote_id
    AND cm.quote_type = 'App\\Models\\HealthQuote'
SET cm.member_category_id = CASE
    WHEN cm.is_principal = 1 THEN hqr.member_category_id
    WHEN cm.dob IS NOT NULL
         AND TIMESTAMPDIFF(MONTH, cm.dob, CURDATE()) <= 12 THEN 24
    WHEN cm.nationality_id IN (56, 269) THEN 23
    WHEN cm.nationality_id IN (154, 289, 322, 96, 135, 145, 14) THEN 22
    WHEN cm.emirate_of_your_visa_id <=> 2 THEN 20
    WHEN NOT (cm.emirate_of_your_visa_id <=> 2) THEN 21
    ELSE cm.member_category_id
END
WHERE hqr.is_quote_locked = 0 AND hqr.quote_status_id NOT IN (70, 71) and  cm.customer_type='Individual' and cm.is_third_party_payer=0 and cm.deleted_at is null
  AND hqr.id NOT IN (SELECT id FROM health_revamp_bak_entity_health_leads);

-- =============================================================================
-- POST-MIGRATION: rows inserted into customer_members (for revert DELETE)
-- Requires TEMPORARY TABLE health_revamp_bak_cm_ids_before (same session).
-- =============================================================================
CREATE TABLE health_revamp_new_customer_member_ids (
    id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    quote_id BIGINT UNSIGNED NULL
);

INSERT INTO health_revamp_new_customer_member_ids (id, quote_id)
SELECT cm.id, cm.quote_id
FROM customer_members cm
LEFT JOIN health_revamp_bak_cm_ids_before old ON old.id = cm.id
INNER JOIN health_quote_request hqr ON hqr.id = cm.quote_id
WHERE old.id IS NULL
  AND cm.quote_type = 'App\\Models\\HealthQuote'
  AND hqr.is_quote_locked = 0 AND hqr.quote_status_id NOT IN (70, 71)
  AND hqr.id NOT IN (SELECT id FROM health_revamp_bak_entity_health_leads);