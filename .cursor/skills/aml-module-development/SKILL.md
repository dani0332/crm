---
name: aml-module-development
description: >-
  Guides AML/KYC screening work in Blanka: Bridger (LexisNexis) integration, individual vs entity flows,
  compliance decisions, quote aml_status, KYC logs, customer_insured mapping, insurer AML, and Sentry
  BLANKA-4QD-style issues (missing insured mapping, aml.show route params). Activates when editing or
  debugging AMLController, AMLService, AMLQueryService, app/Services/AML/*, BridgerAMLJob,
  BridgerInsightService, aml_status, or routes under /kyc/aml.
license: MIT
metadata:
  author: blanka
---

# AML module development (Blanka)

## When this skill applies

Use for changes touching anti–money laundering screening: CRM AML UI, Bridger API calls, storing or interpreting screening results, compliance user decisions, or gating quote progression on `aml_status`.

## Architecture (where code lives)

| Area | Location |
|------|----------|
| HTTP layer (Inertia + JSON) | `app/Http/Controllers/V2/AMLController.php` |
| Orchestration & cross-LOB logic | `app/Services/AMLService.php` |
| Focused AML services | `app/Services/AML/*.php` |
| Async Bridger call | `app/Jobs/BridgerAMLJob.php` → `BridgerInsightService::searchAMLResult()` |
| Decision / status enums | `AMLDecisionStatusEnum`, `AMLStatusCode`, `CustomerTypeEnum` |
| Routes | `routes/web.php` group `prefix: kyc` (paths like `/kyc/aml/...`) |

Long-form product and technical notes: [docs/AML-Module-Documentation.md](../../../docs/AML-Module-Documentation.md).

## Customer types

- **Individual** — insured person screening; payloads and UI lean on insured + KYC (`AMLInsuredService`, `insured_kyc`, `kyc_logs`).
- **Entity** — trade license / entity linkage (`AMLEntityService`: `fetchEntity`, `linkEntityToQuote`); fields and flows differ from individual.

## Screening pipeline (conceptual)

1. **Prepare** — `AMLService::prepareScreeningData()` validates prerequisites for the quote type and request.
2. **Insured / entity** — `AMLService::processInsuredDataForScreening()` resolves who is screened and builds Bridger payload.
3. **Execute** — `BridgerAMLJob::dispatchSync` (or queued) runs `BridgerInsightService::searchAMLResult()` with API token and member/entity payload.
4. **Interpret** — Responses drive automated decision lines (e.g. Pass, Escalated, Sent For Review, Rejected) via `AMLDecisionStatusEnum::amlStatusEvaluation()` and related logic in `AMLService` / processors.
5. **Persist** — `kyc_logs`, quote `aml_status` (`AMLStatusCode`: pending / cleared / failed), and related audit trails.

## Compliance decisions (after escalation)

Compliance reviews Bridger matches and selects a decision such as:

- **False positive** — treated as Pass in evaluation mapping; lead can proceed when combined with cleared AML.
- **True match, accept risk** — Pass; AML can be cleared so the lead proceeds.
- **True match, reject risk** — Rejected; lead must not proceed; AML remains failed.

Portal/LexisNexis updates may flow through `AMLService::updateAMLDecisionLexisNexis` and `updateAMLStatusAgainstDecision()` (see controller `quoteStatusUpdate` when `decisonsForUpdatePortal` is present).

## Production issue: AML detail / `show` without `customer_insured` (Sentry)

**Symptom (prod):** [Sentry 7384911772](https://insurancemarket-ae.sentry.io/issues/7384911772) / **BLANKA-4QD** — `AMLDisplayService::prepareShowData()` received a non-integer for `$insuredId` (e.g. URL segment literally `null` from `/kyc/aml/{aml}/null/{customerId}`).

**Root cause:** `kyc_logs` rows can exist for a quote from historic or edge flows while **no active `customer_insured`** row exists for that `quote_type_id` + `quote_request_id`. The AML detail page (`Aml/DetailPage.vue`) builds the compliance **View** link from `insuredDetails?.insured?.id`; when mapping is missing, that value is missing and the client puts the string `null` in the path. There is no insured to resolve for screening UI.

**Resolution implemented in app:**

1. **AML inbox list** — `AMLQueryService::getAMLQuotes()` applies `constrainToActiveCustomerInsuredMapping()`: only quotes with an **active** `customer_insured` row matching `quote_request_id` = quote table `id` and `quote_type_id` appear in the paginated AML list. This avoids surfacing quotes that cannot produce a valid insured-based `aml.show` link.
2. **`aml.show` hardening** — `AMLController::show()` passes route segments through `normalizeAmlRouteIntId()` so literal `null` / empty / numeric strings satisfy `?int` for `AMLDisplayService`.
3. **Detail page UI** — `Aml/DetailPage.vue` only renders the compliance **View** button when `insuredId` is set, and omits a trailing `/customerId` segment when `customerId` is null (avoids bogus URLs).

Do **not** remove the list constraint without addressing orphan KYC data or fixing the View link generation in the Vue layer.

## Conventions for agents

- Prefer **small, targeted services** under `app/Services/AML/` for new AML-specific logic; keep `AMLService` for orchestration used across LOBs.
- Use existing **enums** (`AMLDecisionStatusEnum`, `AMLStatusCode`) instead of string literals in new code.
- **Form requests** already exist for several actions (`AMLCheckRequest`, `AMLRequest`, etc.); extend them rather than validating inline in the controller.
- **Logging** — use `LoggerService` with `LoggerFeatureEnum::AML_SCREENING` where screening is involved (see existing controller/service calls).
- **`customer_insured` invariant for AML list** — treat an active quote↔insured mapping as required for AML list rows; align any new AML entry points with `AMLQueryService` filtering.
- Run **Pest tests** for behavioural changes; run `vendor/bin/pint --dirty` on touched PHP.

## Related permissions

AML list and related actions use `PermissionsEnum` entries such as `AMLList` (see `routes/web.php` middleware on AML routes).

## Insurer AML (parallel track)

Some LOBs also run **insurer** AML (`InsurerAMLScreeningJob`, `AMLService::insurerAMLScreening`, insurer status codes on `AMLStatusCode`). Do not conflate insurer AML states with Bridger `aml_status` without checking the quote type and product rules.
