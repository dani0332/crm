# Plan: Explicit LOB Renewal Mapper Methods

## Context

The current copy approach in each LOB storage service calls `copyableAttributes()` (copies ALL attributes from old record) then `alignCopiedLobRowWithRenewalPersonalQuote()` (nulls out ~25 stale policy/pricing fields and overwrites ~16 renewal-alignment fields). This is fragile: any new column added to a LOB table is silently copied to the renewal, even if it shouldn't be.

The refactor inverts the pattern — each LOB storage service gets a dedicated `mapLobRenewalDetail()` method that builds the record from scratch, explicitly listing every field and its source. Fields omitted from the mapper default to NULL (DB default). No more implicit copy-all-then-null-out.

---

## Files to Modify

1. `app/Services/CQF/NonMotor/BaseCQFQuoteStorageService.php` — add `formatPolicyDate()` helper
2. `app/Services/CQF/NonMotor/LOBs/CycleCQFQuoteStorageService.php`
3. `app/Services/CQF/NonMotor/LOBs/PetCQFQuoteStorageService.php`
4. `app/Services/CQF/NonMotor/LOBs/HomeCQFQuoteStorageService.php`
5. `app/Services/CQF/NonMotor/LOBs/YachtCQFQuoteStorageService.php`
6. `app/Services/CQF/NonMotor/LOBs/BikeCQFQuoteStorageService.php`
7. `app/Services/CQF/NonMotor/LOBs/BusinessCQFQuoteStorageService.php`

### Test files to update

- `tests/Unit/Services/CQF/NonMotor/LOBs/CycleCQFQuoteStorageServiceTest.php`
- `tests/Unit/Services/CQF/NonMotor/LOBs/PetCQFQuoteStorageServiceTest.php`
- `tests/Unit/Services/CQF/NonMotor/LOBs/HomeCQFQuoteStorageServiceTest.php`
- `tests/Unit/Services/CQF/NonMotor/LOBs/YachtCQFQuoteStorageServiceTest.php`
- `tests/Unit/Services/CQF/NonMotor/LOBs/BikeCQFQuoteStorageServiceTest.php`
- `tests/Unit/Services/CQF/NonMotor/LOBs/BusinessCQFQuoteStorageServiceTest.php`

---

## Base Class Change

Add a `formatPolicyDate()` helper to `BaseCQFQuoteStorageService` for use in mappers:

```php
protected function formatPolicyDate(?string $date): ?string
{
    return $date ? Carbon::parse($date)->format(config('constants.DATE_FORMAT_ONLY')) : null;
}
```

`copyableAttributes()` and `alignCopiedLobRowWithRenewalPersonalQuote()` are kept for now — they are still used by `copyCarQuoteToBikeQuoteDetail()` (Bike/Car migration path).

---

## Mapper Pattern (per LOB)

Each LOB storage service gains one protected method. The `copyXxxQuoteDetail()` method replaces the three-step chain with a single call:

**Before:**
```php
$data = $this->copyableAttributes($oldLob->getAttributes(), $newQuote->id, $newQuote->uuid, $newQuote->code);
$data = $this->alignCopiedLobRowWithRenewalPersonalQuote($data, $newQuote, $oldLob);
$data['some_field'] = null;
LobModel::create($data);
```

**After:**
```php
LobModel::create($this->mapLobRenewalDetail($oldLob, $newQuote));
```

---

## Mapper Field Lists Per LOB

### Cycle (`mapLobRenewalDetail(CycleQuote $oldLob, PersonalQuote $newQuote): array`)
`cycle_quote_request` has only 16 columns — very minimal LOB table.

```
Identity (new quote):   personal_quote_id, uuid, code
From new PersonalQuote: quote_status_id, transaction_approved_at
Carry from old:         cycle_make, cycle_model, year_of_manufacture_id,
                        accessories, has_accident, has_good_condition
Null (omitted):         risk_score, quote_batch_id, compliance_comments, quote_link
```

---

### Pet (`mapLobRenewalDetail(PetQuote $oldLob, PersonalQuote $newQuote): array`)

```
Identity (new quote):   personal_quote_id, uuid, code
From new PersonalQuote: source, quote_status_id, advisor_id, renewal_batch_id,
                        previous_quote_policy_number, previous_quote_policy_premium,
                        previous_quote_policy_commission,
                        previous_policy_start_date (formatPolicyDate),
                        previous_policy_expiry_date (formatPolicyDate)
From old LOB id:        previous_quote_id = $oldLob->id
Carry from old:         first_name, last_name, email, mobile_no, gender, dob,
                        lang, customer_id, nationality_id,
                        no_of_pets_to_insure, type_of_pet1, breed_of_pet1,
                        ilivein_accommodation_type_id, iam_possesion_type_id,
                        is_microchipped, microchip_no, is_neutered,
                        is_mixed_breed, has_injury, pet_type_id
Incremented:            pet_age_id → incrementPetAgeId($oldLob->pet_age_id)
Null (omitted):         everything else (premium, policy_*, payment_*, etc.)
```

---

### Home (`mapLobRenewalDetail(HomeQuote $oldLob, PersonalQuote $newQuote): array`)

```
Identity (new quote):   personal_quote_id, uuid, code
From new PersonalQuote: source, quote_status_id, advisor_id, assignment_type,
                        renewal_batch_id, previous_quote_policy_number,
                        previous_quote_policy_premium, previous_quote_policy_commission,
                        previous_advisor_id,
                        previous_policy_start_date (formatPolicyDate),
                        previous_policy_expiry_date (formatPolicyDate),
                        transaction_type_id, transaction_approved_at,
                        currently_insured_with (→ $newQuote->currentlyInsuredWith?->text)
Hardcoded:              aml_status → AMLStatusCode::AMLPending
From old LOB id:        previous_quote_id = $oldLob->id
Carry from old:         first_name, last_name, email, mobile_no, gender, dob, lang,
                        customer_id, nationality_id,
                        ilivein_accommodation_type_id, iam_possesion_type_id,
                        owner_occupancy_type_id, coverage_type_id,
                        building_value, contents_value_id, personal_belongings_value_id,
                        sub_area_id, is_property_rented_holiday_home,
                        previous_building_aed, previous_contents_aed,
                        previous_personal_belongings_aed,
                        has_contents, has_personal_belongings, has_building,
                        contents_aed, personal_belongings_aed, building_aed,
                        company_name, company_address
Null (omitted):         everything else (premium*, price_*, policy_*, payment_*, aml_status_id,
                        insurer_*, kyc_decision, renewal_upload_*, etc.)
```

---

### Yacht (`mapLobRenewalDetail(YachtQuote $oldLob, PersonalQuote $newQuote): array`)

```
Identity (new quote):   personal_quote_id, uuid, code
From new PersonalQuote: source, quote_status_id, advisor_id, renewal_batch_id,
                        previous_quote_policy_number, previous_quote_policy_premium,
                        previous_quote_policy_commission, previous_advisor_id,
                        previous_policy_start_date (formatPolicyDate),
                        previous_policy_expiry_date (formatPolicyDate),
                        transaction_approved_at
From old LOB id:        previous_quote_id = $oldLob->id
Carry from old:         first_name, last_name, email, mobile_no, gender, dob, lang,
                        customer_id, nationality_id,
                        boat_details, engine_details, claim_experience,
                        sum_insured_value, use, operator_experience
Null (omitted):         premium, policy_*, payment_*, renewal_batch, quote_batch_id,
                        aml_status_id, risk_score, compliance_comments, quote_link,
                        other_email_addresses, renewal_import_code, pa_id, etc.
```

---

### Bike (`mapLobRenewalDetail(BikeQuote $oldLob, PersonalQuote $newQuote): array`)

```
Identity (new quote):   personal_quote_id, uuid, code
From new PersonalQuote: source, quote_status_id, advisor_id, assignment_type,
                        renewal_batch_id, previous_quote_policy_number,
                        previous_quote_policy_premium, previous_quote_policy_commission,
                        previous_advisor_id,
                        previous_policy_start_date (formatPolicyDate),
                        previous_policy_expiry_date (formatPolicyDate),
                        transaction_approved_at,
                        currently_insured_with (→ $newQuote->currentlyInsuredWith?->text)
From old LOB id:        previous_quote_id = $oldLob->id
Carry from old:         first_name, last_name, email, mobile_no, gender, dob, lang,
                        customer_id, nationality_id,
                        bike_company_to_insure, year_of_manufacture,
                        make_id, model_id, model_detail_id,
                        cubic_capacity, emirate_of_registration_id,
                        bike_value_tier, chassis_number,
                        vehicle_type_id, seat_capacity,
                        bike_type_insurance_id
Incremented:            uae_license_held_for_id → incrementLicenseHeldForId($oldLob->uae_license_held_for_id)
                        back_home_license_held_for_id → incrementLicenseHeldForId($oldLob->back_home_license_held_for_id, backHome: true)
Null:                   bike_value, claim_history_id, has_ncd_supporting_documents,
                        insurance_type_id (legacy), premium, policy_*, payment_*,
                        renewal_batch, etc.
```

> **Note:** `copyCarQuoteToBikeQuoteDetail()` (Car→Bike migration path) is **not changed** in this refactor — it keeps `copyableAttributes + alignCopiedLobRowWithRenewalPersonalQuote`.

---

### Business (`mapLobRenewalDetail(BusinessQuote $oldLob, PersonalQuote $newQuote): array`)

```
Identity (new quote):   personal_quote_id = $newQuote->id, uuid, code
From new PersonalQuote: source, quote_status_id, advisor_id, assignment_type,
                        renewal_batch_id, previous_quote_policy_number,
                        previous_quote_policy_premium, previous_quote_policy_commission,
                        previous_advisor_id,
                        previous_policy_start_date (formatPolicyDate),
                        previous_policy_expiry_date (formatPolicyDate),
                        transaction_type_id, transaction_approved_at
Hardcoded:              aml_status → AMLStatusCode::AMLPending
From old LOB id:        previous_quote_id = $oldLob->id
Carry from old:         first_name, last_name, email, mobile_no, gender, dob, lang,
                        customer_id, nationality_id,
                        business_type_of_insurance_id, company_name, brief_details,
                        business_cover_type_id, communication_mode_id,
                        number_of_employees, time_to_contact,
                        boat_details, engine_details, claims_experience,
                        sum_insured_value, use, operators_experience, interest,
                        contact_person_designation,
                        emirate_of_registration_id, company_address, turnover_aed,
                        health_plan_type_id, number_of_categories,
                        has_existing_group_policy, group_medical_type_id,
                        is_branch_applicable, support_user_id,
                        sub_source_id, sub_source_options_id,
                        branch_id, company_activity_type_id, emirates_id
Null (omitted):         premium*, price_*, policy_*, payment_*, insurer_*,
                        kyc_decision, renewal_batch, quote_batch_id,
                        aml_status_id, insurer_aml_status, etc.
```

---

## Test Strategy

For each LOB, update the existing `copyXxxQuoteDetail` test to:
1. Verify fields in the "carry from old" list are present on the created record
2. Verify fields in the "null" list (premium, policy_number, insurance_provider_id, etc.) are null
3. Verify lead fields (source, quote_status_id, advisor_id) come from the new PersonalQuote, not the old

Tests use the existing `Closure::bind()` pattern to access protected methods.

---

## Verification

```bash
doppler run -- php artisan test --compact tests/Unit/Services/CQF/
vendor/bin/pint --dirty --format agent
```
