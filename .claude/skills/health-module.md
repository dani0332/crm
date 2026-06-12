---
name: health-module
description: Health webform revamp — migration service, seeder patterns, revival/renewal integration, and related enums/models introduced in the 0-base, 2-seeder_updates, and 3-revival-renewal branches.
---

# Health Module — Revamp Reference

This skill covers the health webform revamp delivered across three merged PRs:

- **#11273** — `feat/health-webform-revamp/0-base`: core migration engine, enums, models, Vue form revamp, tests
- **#11975** — `feat/health-webform-revamp/2-seeder_updates`: `SeedsIfMissing` trait refactor + seeder cleanup
- **#12013** — `feat/health-webform-revamp/3-revival-renewal`: revival/renewal integration with the mutator

---

## External Service Boundaries

Understanding which system owns each operation is critical before modifying anything in the health flow:

| Operation                       | Owner                                  | Notes                                                                                                                                                                                                                                                                         |
| ------------------------------- | -------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Lead create / update            | **CAPI** (`/api/v1-save-health-quote`) | All lead creation and field updates go through CAPI. Do not write directly to `health_quotes` for these operations.                                                                                                                                                           |
| Member create / update / delete | **KEN**                                | Member operations (including make-principal, make-policyholder) are managed by KEN, not direct DB writes.                                                                                                                                                                     |
| Renewal quote creation          | **Local** (`RenewalsUploadService`)    | Quote row is created locally via `$quoteObject->create($quoteData)`, then the revamp migration is dispatched immediately after on line 1018–1020.                                                                                                                             |
| Revival lead creation           | **CAPI** (`/api/v1-save-health-quote`) | `HealthRevivalLeadsCreationJob` builds a `$dataArr` payload with member details and calls `Capi::request('/api/v1-save-health-quote', 'post', $dataArr)`. The revamp mutator is used to derive field values for the payload — it does **not** mutate the revival lead itself. |

**Renewal flow detail** (`app/Services/RenewalsUploadService.php:1016-1020`):

```php
$quote = $quoteObject->create($quoteData);   // quote created locally

if ($quoteType->id == QuoteTypeId::Health) {
    app(HealthQuoteRevampMigrationService::class)->dispatchForRenewalLead($quote->id);
}
```

The migration fires synchronously right after the quote row is inserted. `dispatchForRenewalLead()` sets `cover_for_id = FAMILY` on the quote, fires the migration event (which runs the mutator), then refreshes and sets `insure_code` and `policy_holder_code`.

**Revival flow detail** (`app/Jobs/Revival/HealthRevivalLeadsCreationJob.php`):

- Checks `isMigrated()` on the parent lead to decide which path to use for deriving values
- When **migrated**: reads `cover_for_id`, `marital_status_id`, `insure_code`, `policy_holder_code`, `gender`, `salary_band_id`, `member_category_id`, `visa_category_id` directly from the lead
- When **not migrated**: calls mutator helpers (`getCoverForId`, `getMartialStatusId`, `getPolicyHolderCategoryCode`, `getGender`, `getSalaryBandId`, `getVisaCategoryId`, `getMemberCategoryId`) to derive values
- Submits the full `$dataArr` (including `memberDetails`) to CAPI — the new lead is created there
- Does **not** call `applyAll()` — member creation happens via CAPI, not the mutator transaction

---

## Architecture Overview

```
HealthQuoteMigration (Event)
    └── RunHealthQuoteRevampMigration (Listener — synchronous, NOT queued)
            └── HealthQuoteRevampMigrationService::migrateLead()
                    ├── HealthQuoteRevampMigrationStateLogger  — before/after diff logging
                    ├── HealthQuoteRevampMigrationMutator::applyAll()  — all mutation steps
                    │       └── HealthQuoteRevampMigrationQueries  — reusable DB queries
                    └── HealthQuoteRevampMigrationContext  — pure helpers (age, dob formatting)
```

**Key constraint**: The listener `RunHealthQuoteRevampMigration` is **intentionally synchronous** (not queued) for immediate user feedback. Do not convert it to a queued listener.

---

## Event Dispatch Entry Points

`HealthQuoteRevampMigrationService` has four named dispatch methods — use the correct one per context:

| Method                                                                               | When to call                                                                                                                          |
| ------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------- |
| `dispatchForLockedLead(int $healthQuoteId, mixed $leadStatus)`                       | CRUD controller status-change flow; only fires when quote is locked                                                                   |
| `dispatchForNonEntityLead(int $healthQuoteId, mixed $quoteStatusId, bool $isEntity)` | AMLService after insured/customer data updates; skips entity leads                                                                    |
| `dispatchForNewChildLead(?int $healthQuoteId)`                                       | SendUpdateLogController after CIR child lead creation                                                                                 |
| `dispatchForRenewalLead(?int $healthQuoteId)`                                        | Renewals flow — sets `cover_for_id = FAMILY` before dispatching, then refreshes and sets `insure_code` and `policy_holder_code` after |

All four call `HealthQuoteMigration::dispatch($healthQuoteId)` which resolves through the synchronous listener.

---

## Migration Guard: `isMigrated()`

```php
// HealthQuoteRevampMigrationService
public function isMigrated(HealthQuote $healthQuote): bool
{
    return in_array($healthQuote->cover_for_id, [
        HealthCoverForEnum::INDIVIDUAL_AND_FAMILIES->value,
        HealthCoverForEnum::DOMESTIC_HELPER->value,
    ]);
}
```

`migrateLead()` skips entity leads and already-migrated leads. Do not add extra guard conditions without checking this.

---

## Mutator (`HealthQuoteRevampMigrationMutator`)

All steps run inside a **single DB transaction** in `applyAll()`. Members are loaded once before the transaction and kept in-memory — steps never re-query the database.

### Step Execution Order (must stay in this order)

**Pre-steps (insert/promote members):**

1. `insertDomesticWorkerInsuredMember` — adds domestic worker insured member if missing
2. `fillMissingPrincipals` — promotes a member to principal (prefers name-match, falls back to first by id)
3. `updatePolicyHoldersFromNameMatch` — promotes adult name-matched member to policy holder
4. `insertIndividualPolicyHolderWhenNoNameMatch` — inserts a policy holder when no name match found
5. `insertMembersWhenNoneAndNoActiveInsured` — inserts members when none exist and no active insured
6. `insertMembersWhenNoneAndInsuredIndividual` — inserts members when none exist and insured is individual

**Field-update steps:** 7. `applyCoverForIdUpdates` 8. `applyInsureAndPolicyHolderCodes` 9. `applyMaritalStatusUpdates` 10. `normalizeGenderValues` 11. `applyPolicyHolderCategoryCode` 12. `applyHealthQuoteSalaryBandAndVisaFromMemberCategory` 13. `applyMemberRelationSalaryAndVisa` 14. `applyHealthQuoteMemberCategoryRemap` 15. `applyCustomerMemberCategoryRemap`

### Visa/Salary Threshold (IMPORTANT — intentional, not a bug)

The mutator uses `18 * 12` months (216 months = 18 years) and `12` months as age thresholds for visa category and salary band decisions. These values are correct domain rules — do not flag them as magic numbers or bugs.

### `getCoverForId` — null fallback (IMPORTANT)

`getCoverForId()` initializes `$coverForId = $hqr->cover_for_id` (no int cast). When `cover_for_id` is `null` and the lead is not a domestic worker, it falls back to `HealthCoverForEnum::INDIVIDUAL_AND_FAMILIES->value` via the null-coalescing return:

```php
return $coverForId ?? HealthCoverForEnum::INDIVIDUAL_AND_FAMILIES->value;
```

**Do not revert to `(int) $hqr->cover_for_id`** as the initializer — casting null to int produces `0`, which violates the `health_cover_for` FK constraint (`SQLSTATE[23000] 1452`).

---

## Key Enums (all in `app/Enums/`)

| Enum                           | Purpose                                                              |
| ------------------------------ | -------------------------------------------------------------------- |
| `HealthCoverForEnum`           | `FAMILY`, `INDIVIDUAL`, `INDIVIDUAL_AND_FAMILIES`, `DOMESTIC_HELPER` |
| `HealthInsureEnum`             | `MYSELF`, `MYSELF_AND_MY_FAMILY_MEMBERS`, etc.                       |
| `HealthPolicyHolderEnum`       | `ME`, `MY_SPOUSE`, etc.                                              |
| `MemberCategoryEnum`           | Member category codes                                                |
| `PolicyHolderCategoryCodeEnum` | Policy holder category codes                                         |
| `RelationCodeEnum`             | 94-value relation code mapping                                       |
| `SalaryBandEnum`               | Salary band codes                                                    |
| `VisaCategoryEnum`             | Visa category codes                                                  |
| `MaritalStatusIdEnum`          | Marital status IDs                                                   |

---

## Supporting Services

### `HealthQuoteRevampMigrationContext`

Pure, stateless helpers — no DB access:

- `monthsSinceDob(?string $dob): ?int` — complete months from dob to today; returns `null` when dob is absent
- `dobToDateString(mixed $dob): ?string` — normalises raw dob to `Y-m-d`; returns `null` when absent
- `memberIsAtLeastYearsOld(mixed $dob, int $years = 18): bool` — returns `false` when dob is missing (treats unknown age as ineligible)

### `HealthQuoteRevampMigrationStateLogger`

Logs before/after diffs for audit. Tracked fields on `HealthQuote`: `cover_for_id`, `insure_code`, `policy_holder_code`, `gender`, `marital_status_id`, `policy_holder_category_code`, `salary_band_id`, `visa_category_id`, `member_category_id`. Tracked fields on `CustomerMembers`: `is_principal`, `is_policy_holder`, `is_insured`, `relation_code`, `salary_band_id`, `visa_category_id`, `member_category_id`, etc.

### `LookupService`

Introduced in the base branch. Centralises lookup data access with cache support. Check `app/Services/LookupService.php` before querying lookup tables directly.

---

## Seeder Pattern (`SeedsIfMissing` trait)

Location: `app/Traits/SeedsIfMissing.php`

Use this trait in all health-related seeders. Three methods:

```php
// Bulk upsert — requires unique index on `code` (+ scope columns)
$this->seedUpsertIfMissing(HealthCoverFor::class, $definitions);

// Row-by-row firstOrCreate — for tables without a unique index on `code`
$this->seedFirstOrCreateIfMissing(VisaCategory::class, $definitions);

// Lookup-specific shortcut (wraps seedFirstOrCreateIfMissing with key scope)
$this->seedLookupsIfMissing(LookupsEnum::SALARY_BAND, $definitions);
```

Each `$definitions` array row must include `code`. Scope columns (e.g. `['key' => LookupsEnum::X]`) are merged into every row automatically.

**Affected seeders**: `HealthCoverForSeeder`, `LookupSeeder`, `MemberCategorySeeder`, `SalaryBandSeeder`, `VisaCategorySeeder`.

---

## Revival / Renewal Integration (PR #12013)

### `HealthRevivalLeadsCreationJob`

- Checks `isMigrated()` on the revival lead before applying revamp logic
- When migrated: reads `cover_for_id` and `marital_status_id`, creates member records using `RelationCodeEnum`, then calls `HealthQuoteRevampMigrationMutator::applyAll()` directly
- When not migrated: follows the legacy revival path
- Queue: `'renewals'`, tries: 3, timeout: 300s

### `RenewalsUploadService`

Calls `dispatchForRenewalLead()` after creating a renewal health quote. The renewal dispatch method sets `cover_for_id = FAMILY` before the migration event fires, then refreshes and sets `insure_code` and `policy_holder_code` after.

---

## Vue / Frontend (base branch)

- **`resources/js/inertia/Pages/HealthQuote/Form.vue`** — substantially revised health quote form
- **`resources/js/inertia/Components/HealthMemberDetails.vue`** — new component for member detail management (large component, ~1578 lines at merge)
- **`resources/js/inertia/Composables/useHealthQuoteFlags.js`** — feature flag composable for health quote revamp UI state
- **`resources/js/inertia/Composables/countryCallingCodes.json`** — country calling codes data
- **`resources/js/inertia/Composables/rules.js`** — validation rules

---

## Factories (base branch)

New factories for health revamp testing:

- `CustomerInsuredFactory`, `CustomerMembersFactory`, `HealthQuoteFactory`, `InsuredFactory`, `LookupFactory`, `VisaCategoryFactory`

Always use factories in tests. Check factory states before manually setting model attributes.

---

## Test Coverage

Key test files:

- `tests/Feature/Health/General/HealthRevampServiceTest.php` — integration tests for the migration service
- `tests/Unit/Services/HealthQuoteRevampMigrationServiceTest.php` — unit tests for the service
- `tests/Unit/Models/CustomerMembersRevampTest.php` — model tests
- `tests/Unit/Enums/HealthRevampEnumsTest.php`, `RelationCodeEnumTest.php` — enum tests
- `tests/Unit/Traits/TransformsAuditablesTest.php`, `LookupSeederHealthRevampTest.php`

Run health revamp tests:

```bash
php artisan test --compact --filter=HealthRevamp
php artisan test --compact tests/Feature/Health/
```
