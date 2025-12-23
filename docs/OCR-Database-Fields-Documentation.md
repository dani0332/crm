# OCR Database Fields Documentation - CAR LOB Only

This document outlines all database fields that are updated by OCR processing for each document type **specifically for CAR Line of Business (LOB)**. Only actual database fields are listed (metadata fields like `ocr_processed_at`, `ocr_model`, `ocr_provider` are excluded).

**Scope:** This documentation covers only document types enabled for CAR quotes as defined in `OCRDocumentTypeEnum::getEnabledTypes(QuoteTypes::CAR)`.

---

## 1. Driving License (DL)

**Document Type Code:** `DL`

### Tables Updated:

#### `vehicle_driver_details`

- `driver_license_number`
- `driver_license_issue_date`
- `driver_license_expiry_date`
- `driver_license_issue_place`
- `traffic_code_number`
- `driver_first_name`
- `driver_last_name`
- `driver_dob`
- `driver_gender`
- `nationality_id` (converted from nationality_string)

---

## 2. Emirates ID (IDC)

**Document Type Code:** `IDC`

### Tables Updated:

#### `insured`

- `customer_type` (set to 'Individual')
- `first_name` (extracted from full name)
- `last_name` (extracted from full name)
- `dob`
- `nationality_id` (converted from nationality string)
- `gender`
- `id_type` (set to 'emiratesId')
- `id_number` (EID number)

#### `insured_kyc`

- `country_of_residence` (converted from country to nationality_id)
- `place_of_birth` (converted from nationality to nationality_id)
- `id_issuance_date`
- `id_expiry_date`
- `issuance_place`
- `residential_address` (auto-generated from issuance_place + ', UAE')
- `employer_company_name` (from sponsor field)
- `source_of_income` (set to 'EMPLOYED' if sponsor exists)
- `job_title` (mapped from occupation via lookup)
- `id_type` (synced from insured table)
- `id_number` (synced from insured table)
- `first_name` (synced from insured table)
- `last_name` (synced from insured table)

#### `vehicle_driver_details`

- `driver_gender`

#### `customer_insured` (relationship table - created if needed)

- `customer_id`
- `insured_id`
- `quote_type_id`
- `quote_request_id`

---

## 3. Mulkiya / Registration Certificate (RC)

**Document Type Code:** `RC`

### Tables Updated:

#### `vehicle_driver_details`

- `vehicle_plate_number`
- `vehicle_plate_code`
- `first_registration_date`
- `vehicle_color`
- `vehicle_engine_number`
- `bank_name`
- `bank_loan` (set to 1 if bank_name exists, else 0)
- `traffic_code_number`

#### `car_quote_request_details`

- `chassis_number`

#### `car_quotes`

- `policy_expiry_date`

#### `registration_certificates`

- `place_of_issue`
- `expiry_date`
- `owner`
- `nationality_id` (converted from nationality_string)
- `mortgage_by`
- `notes`
- `insured_with`
- `insurance_type`
- `model`
- `vehicle_class`
- `vehicle_type`
- `origin`
- `number_of_passengers`
- `gross_vehicle_weight`
- `empty_weight`
- `ocr_done_by`
- `doc_type`
- `provider_id`
- `traffic_code_number`
- `policy_expiry_date`
- `chassis_number`

---

## 4. Tax Invoice (TI)

**Document Type Code:** `TI`

### Tables Updated:

#### `car_quotes` (or quote table based on quote type)

- `price_with_vat`
- `price_vat_applicable`
- `vat` (only for regular quotes, calculated or from OCR)
- `policy_issuance_date`

#### `payments`

- `insurer_invoice_date`
- `insurer_tax_number`
- `tax_invoice_number`

**Note:** For Send Update logs, the same fields are updated on the `send_update_logs` table instead of `car_quotes`.

---

## 5. Tax Invoice Raised By Buyer (TIB)

**Document Type Code:** `TIB`

### Tables Updated:

#### `car_quotes` (for Send Update only)

- `insurer_commission_invoice_number`
- `commission_vat_applicable`

#### `payments` (for regular quotes)

- `commission_vat`
- `commission`
- `commmission_percentage` (calculated)
- `insurer_commmission_invoice_number`
- `commission_vat_applicable`

**Note:** For regular quotes, this also triggers `SplitPaymentService->updateCommissionSchedule()` which updates the commission schedule table.

---

## 6. Certificate of Issuance (PC)

**Document Type Code:** `PC`

### Tables Updated:

#### `car_quotes` (or quote table based on quote type)

- `policy_number`
- `policy_start_date`
- `policy_expiry_date`
- `policy_issuance_date` (set to current timestamp if not already set)

---

## 7. Motor Insurance Policy Schedule (MPS)

**Document Type Code:** `MPS`

### Tables Updated:

#### `car_quotes` (or quote table based on quote type)

- `insurer_quote_number` (only for specific providers)

**Note:** This is a minimal implementation that only updates `insurer_quote_number` for certain providers. For CAR LOB, this is the only field updated by MPS.

---

## Summary by Table

### `vehicle_driver_details`

Updated by: **DL, IDC, RC**

- Driver license fields (DL)
- Driver gender (IDC)
- Vehicle plate and registration info (RC)

### `insured`

Updated by: **IDC**

- Personal information from Emirates ID

### `insured_kyc`

Updated by: **IDC**

- KYC information from Emirates ID

### `car_quote_request_details`

Updated by: **RC**

- Chassis number

### `car_quotes`

Updated by: **TI, TIB, PC, MPS, RC**

- Pricing fields (TI)
- Commission fields (TIB)
- Policy dates and numbers (PC)
- Policy expiry (RC)
- Insurer quote number (MPS)

### `payments`

Updated by: **TI, TIB**

- Invoice dates and numbers (TI)
- Commission fields (TIB)

### `registration_certificates`

Updated by: **RC**

- All registration certificate specific fields

### `customer_insured`

Updated by: **IDC**

- Relationship linking customer to insured

---

## CAR LOB Document Types Summary

The following 7 document types are enabled for CAR LOB OCR processing:

1. **DL** - Driving License
2. **IDC** - Emirates ID
3. **RC** - Registration Certificate (Mulkiya)
4. **TI** - Tax Invoice
5. **TIB** - Tax Invoice Raised By Buyer
6. **PC** - Certificate of Issuance
7. **MPS** - Motor Insurance Policy Schedule

**Note:** Policy Schedule (PS) is NOT enabled for CAR LOB - it's only for GROUP_MEDICAL and HOME quote types.

---

## Notes

1. **Nationality Conversion:** Many document types extract nationality as a string but convert it to `nationality_id` before saving to the database.

2. **Name Splitting:** Full names are split into `first_name` and `last_name` using the first word as first name and remaining words as last name.

3. **Gender Formatting:** Gender values are normalized to 'Male' or 'Female' format.

4. **Date Formatting:** All dates are parsed and formatted to proper date format before saving.

5. **Provider-Specific Fields:** Some fields are only updated if enabled for the specific insurance provider (checked via `isFieldEnabled()`).

6. **Send Update vs Regular Quotes:** Some document types have different behavior for Send Update logs vs regular quotes, updating different fields or tables accordingly.

7. **CAR LOB Specific:** All fields documented here are specific to CAR quote processing. Other LOBs (Home, Group Medical) may have different document types and field mappings.
