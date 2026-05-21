# Non-Motor CQF Renewal Pipeline — Workflow

## Overview

The Non-Motor CQF (Create Quote Flow) renewal pipeline automatically creates renewal leads in IMCRM for non-motor LOBs by sourcing data from previously booked policies (PersonalQuote records).

**Supported LOBs:** Home, Pet, Bike, Cycle, Yacht, Business (covers Group Medical + Corpline)

---

## Pipeline Flow

```
Artisan Command
    ProcessNonMotorCQFRenewalLeads
            │
            ▼
    ProcessNonMotorCQFOrchestratorJob
    ─────────────────────────────────
    Dispatches one ProcessNonMotorCQFLOBJob
    per supported LOB
            │
            ├──▶ Home
            ├──▶ Pet
            ├──▶ Bike
            ├──▶ Cycle
            ├──▶ Yacht
            └──▶ Business (Group Medical + Corpline)
                        │
                        ▼
            ProcessNonMotorCQFLOBJob  (per LOB)
            ──────────────────────────────────────
            Queries eligible PersonalQuotes:
              - policy_expiry_date within target window
              - no existing renewal lead
            Special case (Bike):
              - also queries CarQuote where vehicle_type_id = Bike
            Dispatches one ProcessNonMotorCQFQuoteJob per quote
                        │
                        ▼
            ProcessNonMotorCQFQuoteJob  (per quote)
            ────────────────────────────────────────
            Retries on failure. Runs through pipeline:

              Step 1 → LOBValidationPipe
                         Confirms the LOB is supported in the registry

              Step 2 → DuplicateCheckPipe
                         Guards against creating duplicate renewal leads

              Step 3 → InslyCheckPipe
                         Validates Insly integration requirements

              Step 4 → ForeignKeyValidationPipe
                         Validates FK constraints before storage

              Step 5 → StoragePipe
                         Calls the LOB-specific storage service
                        │
                        ▼
            BaseCQFQuoteStorageService::storeRenewalQuote()
            ────────────────────────────────────────────────
            1. generateUUID()           via CAPI
            2. mapRenewalQuote()        builds PersonalQuote data array
                                        (BaseCQFQuoteMappingService)
            3. DB::transaction:
                 a. PersonalQuote::create($quoteData)
                 b. quoteDetail()->create([])
                 c. copyLobQuoteDetail()
                      copies LOB-specific table row from old quote
                      (homeQuote → HomeQuote, petQuote → PetQuote, etc.)
                      then aligns renewal fields via
                      alignCopiedLobRowWithRenewalPersonalQuote()
                 d. saveEmbeddedTransaction()
                 e. collectEmbeddedProductCodes()   [Bike only — RDX]
                        │
                        ▼
            FinalizeNonMotorCQFLOBJob
            ──────────────────────────
            Marks batch as complete.
            Sends failure email for any failed quotes.
```

---

## Field Mapping

### Source of Fields

| Source | Description |
|---|---|
| Policy booked lead | Previous `PersonalQuote` and its LOB detail table |
| Upload & update sheet | `RenewalsUploadLeads` record tied to the batch |
| Automated | Computed by the pipeline (e.g. renewal batch ID from expiry date) |
| Hardcoded | Fixed value set by the pipeline (e.g. `source = RENEWAL_UPLOAD`, `aml_status = AML_PENDING`) |

---

### Base Fields — All LOBs (PersonalQuote → PersonalQuote)

Handled by `BaseCQFQuoteMappingService::buildBaseQuoteDataFromPersonalQuote()`

#### Customer Profile

| IMCRM Field | Source | Required |
|---|---|---|
| First name | `personal_quotes.first_name` | Yes |
| Last name | `personal_quotes.last_name` | Yes |
| Insured first name | `personal_quotes.insured_id` → `insured.first_name` | Yes |
| Insured last name | `personal_quotes.insured_id` → `insured.last_name` | Yes |
| Mobile number | `personal_quotes.mobile_no` | Yes |
| Email | `personal_quotes.email` | Yes |
| Nationality | `personal_quotes.nationality_id` | Yes |
| Date of birth | `personal_quotes.dob` | Yes |
| Gender | `personal_quotes.gender` | No |

#### Last Year's Policy Details

| IMCRM Field | Source | Required |
|---|---|---|
| Renewal batch number | Auto-computed from `policy_expiry_date` (ISO week/year) | Yes |
| Previous policy number | `personal_quotes.policy_number` | Yes |
| Previous policy expiry date | `personal_quotes.policy_expiry_date` | Yes |
| Previous policy premium | `personal_quotes.price_with_vat` | No |
| Previous policy start date | `personal_quotes.policy_start_date` | Yes |
| Previous advisor | `personal_quotes.advisor_id` | No |

#### System / Lead Fields

| Field | Value |
|---|---|
| `source` | `RENEWAL_UPLOAD` (hardcoded) |
| `quote_status_id` | `NewLead` (hardcoded) |
| `aml_status` | `AML_PENDING` (hardcoded) — set on the copied **LOB row** via `alignCopiedLobRowWithRenewalPersonalQuote`; not explicitly set on `PersonalQuote` (relies on DB default) |
| `advisor_id` | `null` (assigned post-creation) |
| `transaction_type_id` | Lookup: `TRANSACTION_TYPES / EXT_CUSTOMER_RENEWAL` |
| `renewal_import_code` | From `RenewalsUploadLeads` |

---

### LOB-Specific Fields

LOB detail rows (e.g. `home_quote_request`, `pet_quote_request`) are copied wholesale from the old quote via `copyLobQuoteDetail()`. Stale policy/plan fields are nulled out by `alignCopiedLobRowWithRenewalPersonalQuote()`.

#### Home

| IMCRM Section | Fields Copied From |
|---|---|
| Home details | `company_name`, `company_address` from `personal_quotes` |
| Quote details | `ownership_status`, `type_of_property`, `type_of_owner_occupancy`, `has_contents`, `contents_aed`, `has_building`, `building_aed`, `has_personal_belongings`, `personal_belongings_aed` — all from `home_quote_request` |
| Quote details | `claim_history_id` — `null` (sourced from upload & update sheet post-creation) |
| Quote details | `currently_insured_with_id` — from `personal_quotes` |
| Customer profile | `address`, `floor_villa_apartment_number`, `villa_building_name`, `street_name` — from `home_quote_request` |
| Customer profile | `location_area` — `null` (sourced from upload & update sheet post-creation) |
| Lead detail | `advisor_id` — from upload & update file |

#### Pet

| IMCRM Section | Fields Copied From |
|---|---|
| Quote details | `type_of_pet` (`pet_type_id`), `breed_of_pet1`, `is_neutered`, `is_microchipped`, `microchip_no`, `is_mixed_breed`, `has_injury`, `gender` — from `pet_quote_request` |
| Quote details | `age_of_pet` (`pet_age_id`) — from `pet_quote_request` **+ 1 year** |
| Quote details | `accommodation_type` (`ilivein_accommodation_type_id`), `possession_type` (`iam_possesion_type_id`) — from `pet_quote_request` |

#### Bike

| IMCRM Section | Fields Copied From |
|---|---|
| Quote details | `make_id`, `model_id`, `year_of_manufacture`, `bike_value`, `seat_capacity`, `chassis_number`, `emirate_of_registration_id`, `insurance_type_id` — from `bike_quote_request` |
| Quote details | `claim_history_id` — `null` (sourced from upload & update sheet post-creation) |
| Quote details | `has_ncd_supporting_documents` — `null` (sourced from upload & update sheet post-creation) |
| Customer profile | `uae_license_held_for_id` — from `bike_quote_request` **+ 1 year** |
| Customer profile | `back_home_license_held_for_id` — from `bike_quote_request` **+ 1 year** |
| Special | If source is `CarQuote` (vehicle_type_id = Bike): columns remapped (`car_make_id` → `make_id`, `car_model_id` → `model_id`, `car_value` → `bike_value`, `car_type_insurance_id` → `insurance_type_id`) |
| Embedded product | RDX code collected if previously selected + payment captured |

#### Cycle

| IMCRM Section | Fields Copied From |
|---|---|
| Quote details | `cycle_make`, `cycle_model`, `year_of_manufacture_id`, `purchased_of_value`, `accessories`, `has_accident`, `has_good_condition` — from `cycle_quote_request` |

#### Yacht

| IMCRM Section | Fields Copied From |
|---|---|
| Quote details | `boat_details`, `engine_details`, `claim_experience`, `sum_insured`, `use`, `operator_experience` — from `yacht_quote_request` |

#### Business (Group Medical + Corpline)

| IMCRM Section | Fields Copied From |
|---|---|
| Business quote detail | `company_name`, `company_address`, `policy_number`, `price`, `number_of_employees`, `brief_details`, `gender` — from `business_quote` |
| Business quote detail | `business_insurance_type` — from `business_quote` (Group Medical = hardcoded; Corpline = from booked lead) |
| Business quote detail | `advisor_id` — from upload & update file |
| Entity profile | `first_name`, `last_name`, `mobile_no`, `email`, `company_name`, `trade_license_no`, `emirate_of_registration_id`, `company_address`, `industry_type_code`, `entity_type` — from `business_quote` |
| Entity profile | `risk_category` — based on KYC (post-creation) |
| Entity profile | UBO details table — from `business_quote` |

---

## Nulled-Out Fields on New Renewal Quote

The following fields from the old policy are explicitly set to `null` on the copied **LOB row** so the advisor starts fresh:

`insurance_provider_id`, `price_vat_applicable`, `price_vat_not_applicable`, `price_with_vat`, `insurer_quote_number`, `policy_number`, `policy_issuance_date`, `policy_issuance_status_id`, `policy_start_date`, `policy_expiry_date`, `kyc_decision`

`transaction_approved_at` is inherited from the new `PersonalQuote` (where it is not set, so effectively `null`) rather than being explicitly nulled.

---

## Known Gaps

| # | LOB | Gap | Severity |
|---|---|---|---|
| 1 | All | `insured_id` not copied → Insured first/last name missing on new quote | High |
| 2 | All | `gender` not in base mapping | Medium |
| 3 | Home, Business | `company_name` not in base mapping | Medium |
| 4 | Home, Business | `company_address` not in base mapping | Medium |
| 5 | Pet | `pet_age_id` copied as-is — requires +1 year increment | High |
| 6 | Bike | `uae_license_held_for_id` copied as-is — requires +1 year increment | High |
| 7 | Bike | `back_home_license_held_for_id` copied as-is — requires +1 year increment | High |

---

## Class Reference

| Class | Responsibility |
|---|---|
| `App\Console\Commands\ProcessNonMotorCQFRenewalLeads` | Artisan entry point |
| `App\Jobs\CQF\ProcessNonMotorCQFOrchestratorJob` | Dispatches per-LOB jobs |
| `App\Jobs\CQF\ProcessNonMotorCQFLOBJob` | Queries eligible quotes per LOB |
| `App\Jobs\CQF\ProcessNonMotorCQFQuoteJob` | Runs per-quote pipeline |
| `App\Jobs\CQF\FinalizeNonMotorCQFLOBJob` | Finalises batch, sends failure emails |
| `App\Services\CQF\NonMotor\NonMotorCQFRegistry` | Maps `QuoteTypes` → LOB services |
| `App\Services\CQF\NonMotor\NonMotorCQFRenewalExecutionService` | Coordinates pipeline execution |
| `App\Services\CQF\NonMotor\BaseCQFQuoteMappingService` | Base PersonalQuote field mapping |
| `App\Services\CQF\NonMotor\BaseCQFQuoteStorageService` | Base storage transaction |
| `App\Services\CQF\NonMotor\LOBs\Home*` | Home mapping, storage, validation |
| `App\Services\CQF\NonMotor\LOBs\Pet*` | Pet mapping, storage, validation |
| `App\Services\CQF\NonMotor\LOBs\Bike*` | Bike mapping, storage, validation |
| `App\Services\CQF\NonMotor\LOBs\Cycle*` | Cycle mapping, storage, validation |
| `App\Services\CQF\NonMotor\LOBs\Yacht*` | Yacht mapping, storage, validation |
| `App\Services\CQF\NonMotor\LOBs\Business*` | Business mapping, storage, validation |
| `App\Services\CQF\NonMotor\Pipes\*` | Pipeline stage pipes |
