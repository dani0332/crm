# AML module documentation (Blanka)

This document describes the Anti–Money Laundering (AML) / KYC screening module: business intent, main flows, and where implementation lives in this repository. It complements in-code comments and is aimed at engineers and product owners.

## Purpose

Eligible leads pass through AML screening before certain downstream steps (for example transaction approval). The module:

1. Collects **individual** or **entity** identity data appropriate to the line of business.
2. Calls **Bridger** (LexisNexis Bridger Insight) to screen names and related data.
3. Records results and an **automated decision line** (Pass, Escalated, Sent For Review, Rejected, etc.).
4. When automation does not clear the lead, **compliance** users review matches and record a **compliance decision** that determines whether the lead can proceed.

## Customer types

### Individual

- Screening targets the **insured person** (and related members where applicable).
- Supporting data: insured records, `insured_kyc`, `kyc_logs`, customer links (`customer_insured`).
- CRM helpers: `AMLInsuredService` (e.g. insured + KYC for display), `get-insured-details` endpoint.

### Entity

- Screening targets **legal entities** linked to the quote (trade license, entity master data).
- Flows differ from individual: lookup by trade license, link entity to quote.
- CRM helpers: `AMLEntityService` (`fetchEntityByTradeLicense`, `linkEntityToQuote`), routes `aml-fetch-entity`, `link-entity-details`.

## Automated screening outcome (Bridger)

After Bridger returns, the application derives statuses such as:

- **Pass** — no blocking match in line with configured rules.
- **Escalated** / **Sent For Review** — manual compliance review required.
- **Rejected** — screening failed from an automation perspective; progression is blocked unless compliance overrides appropriately.

These labels align with `AMLDecisionStatusEnum` and the mapping in `AMLDecisionStatusEnum::amlStatusEvaluation()`.

## Quote-level AML state (`aml_status`)

The quote stores a high-level AML outcome in `aml_status`, using values from `AMLStatusCode`, notably:

- `AML_PENDING`
- `AML_SCREENING_CLEARED`
- `AML_SCREENING_FAILED`

Insurer-side AML uses **separate** constants (e.g. Pending / Cleared / Failed under insurer screening). Treat them as a parallel track where the product requires insurer checks.

## Compliance workflow (after escalation)

When matches require human review, compliance users work from CRM screens backed by `AML` records, `kyc_logs`, and processed results (see `AMLResultsProcessor`).

Typical compliance outcomes:

| Decision                    | Effect (conceptual)                                                                                    |
| --------------------------- | ------------------------------------------------------------------------------------------------------ |
| **False positive**          | Match dismissed; treated as **Pass** in evaluation mapping; lead can move forward when AML is cleared. |
| **True match, accept risk** | Risk accepted; treated as **Pass**; AML can be cleared; lead proceeds.                                 |
| **True match, reject risk** | **Rejected**; lead must not proceed; AML remains failed.                                               |

Implementation touches `AMLDecisionStatusEnum` values such as `FALSE_POSITIVE`, `TRUE_MATCH_ACCEPT_RISK`, `TRUE_MATCH_REJECT_RISK`, and persistence on `kyc_logs` (e.g. `decision`, notes, PEP/sanctions fields). Updates may also sync with external portal APIs (`AMLService::updateAMLDecisionLexisNexis` and related paths).

## Key HTTP entry points (`AMLController`)

Routes live under the **`kyc`** prefix (see `routes/web.php`). Examples:

| Action                           | Illustrative route                                    | Role                                                                                         |
| -------------------------------- | ----------------------------------------------------- | -------------------------------------------------------------------------------------------- |
| AML inbox / filters              | `GET /kyc/aml`                                        | `index` — Inertia `Aml/Index`; data via `AMLQueryService`                                    |
| Quote AML detail                 | `GET /kyc/aml/{quoteTypeId}/details/{quoteRequestId}` | `amlQuoteDetails` — `AMLQuoteDetailsService`                                                 |
| Screening result / compliance UI | `GET /kyc/aml/{aml}/{insuredId?}/{customerId?}`       | `show` — `AMLDisplayService::prepareShowData()`                                              |
| Run screening / refresh          | `GET .../quoteUpdate`                                 | `quoteUpdate` — prepares data, runs `BridgerAMLJob`, updates status logs                     |
| Compliance / portal decision     | `GET .../quoteStatusUpdate/{quoteTypeCode}`           | `quoteStatusUpdate` — LexisNexis portal and `updateAMLStatusAgainstDecision` when applicable |
| Export                           | `GET /kyc/export`                                     | `export` — `AMLExportService` (permission-gated)                                             |

Additional JSON endpoints include insured lookup, entity fetch/link, Bridger response POST, skip Bridger, CTF export, insurer quote details, and travel AML requirement checks.

## Service layer map

### `AMLService` (`app/Services/AMLService.php`)

Large orchestration service: migration-aware quote resolution (`isDataMigrated`, personal vs legacy tables), screening preparation (`prepareScreeningData`, `processInsuredDataForScreening`), PA assignment (`updatePAId`), KYC compliance persistence (`saveKYCComplianceQuestions`), AML status after decisions (`updateAMLStatusAgainstDecision`), listing/export query builders (`getAMLData`, `buildAmlCftReportQuery`), insurer AML (`insurerAMLScreening`), and car-specific extras (vehicle/driver details, automation gates).

### `app/Services/AML/*`

| Class                       | Responsibility                                                                      |
| --------------------------- | ----------------------------------------------------------------------------------- |
| `AMLQueryService`           | Paginated AML list queries per quote type; personal quote migration table selection |
| `AMLQuoteDetailsService`    | Detail page payload for a quote under AML                                           |
| `AMLDisplayService`         | Show page: AML model, processed results, quote + insured context, enums for UI      |
| `AMLResultsProcessor`       | Shape/normalize Bridger-style results for display                                   |
| `AMLInsuredService`         | Insured + KYC resolution for individual context                                     |
| `AMLEntityService`          | Entity lookup and linking                                                           |
| `AMLInsurerService`         | Insurer AML integration surface used by controller                                  |
| `AMLExportService`          | AML log export                                                                      |
| `AMLLookupsService`         | Lookup-driven AML data                                                              |
| `AMLBusinessPayloadService` | Business LOB screening payloads                                                     |

### Async / integration

- **`BridgerAMLJob`** — runs `BridgerInsightService::searchAMLResult()` with token, payload, quote context, customer type, automation flag.
- **`BridgerInsightService`** — Bridger API interaction and persistence of screening outcome (see service for request/response handling).

## Logging and auditing

- Use `LoggerService::startQuoteLogging` / `LoggerService::info` with `LoggerFeatureEnum::AML_SCREENING` for traceability.
- Manual audit paths exist for automation vs human processing (e.g. `AMLService::saveManualAuditLog`, config `audit.console`).

## Permissions

AML listing and sensitive actions use Spatie permissions (e.g. `PermissionsEnum::AMLList` on `aml.index`). Export uses `DATA_EXTRACTION`. Always preserve or extend permission checks when adding routes.

## Frontend

Inertia pages under the `Aml/*` namespace (e.g. `Aml/Index`, `Aml/DetailPage`, `Aml/Show`) consume the props built in `AMLController` and AML services. For Vue/Inertia patterns, use the **inertia-vue-development** skill.

## Related documentation

- Activity logging: `docs/ACTIVITY_LOGGING.md`
- Travel-specific AML checks: `checkMissingTravelAmlRequirement` on `AMLController`

## Historic KYC logs vs `customer_insured` (production hardening)

`kyc_logs` (exposed as the `AML` model) can exist for a quote even when there is **no active `customer_insured`** row for that `quote_type_id` and `quote_request_id` (historic data or incomplete linkage). In that case the AML **detail** page cannot resolve an insured for compliance actions; the **View** link on `Aml/DetailPage.vue` could emit a URL with the literal segment `null`, which led to failures in `AMLController::show` → `AMLDisplayService::prepareShowData()` / insured loading (see Sentry issue 7384911772 / BLANKA-4QD).

**Behaviour in code (current):**

- The **AML list** (`AMLQueryService::getAMLQuotes()`) is **not** filtered by `customer_insured`; quotes can appear even when there is no active mapping for insured-based AML detail links.
- **`AMLController::show()`** forwards optional `{insuredId}` / `{customerId}` route segments into `AMLDisplayService::prepareShowData()` and `AMLInsuredService::getInsuredWithKyc()` as **`mixed`** (values come from the URL as strings). `getInsuredWithKyc()` only checks truthiness before `Insured::find()`, so a literal string `"null"` is still truthy and can produce incorrect lookups; `customerId` is passed through to Inertia as provided unless normalized elsewhere.
- **`Aml/DetailPage.vue`** should only emit **View** URLs with real numeric ids and omit optional trailing segments when `customerId` is absent.

**Possible hardening (not required by current code):** optional `whereExists` on active `customer_insured` in `getAMLQuotes()`, and/or normalizing route segments in AML display/insured services so `"null"` and non-numeric values never reach `Insured::find()` or Inertia as bogus ids. **Documentation rule:** describe only helpers and methods that exist in the repo (e.g. verify signatures on `AMLController` and under `app/Services/AML/` before referring to them).

---

_Last updated to reflect the AML controller, `AMLService`, and `app/Services/AML` layout in this repository._
