# Payment POST endpoints (`web.php` ~439–455)

All routes below live inside the authenticated group (`auth`, `last_login_check`). `{quoteType}` is the LOB segment (e.g. `Health`, `Car`).

`check_route_access` uses the **route name** as the Spatie permission string (`auth()->user()->can($routeName)`), except **Admin** / **Engineering**, which bypass the check. See `App\Http\Middleware\CheckRouteAccess`.

---

## Summary table

| #   | Path                               | Route name                             | `check_route_access` | Permission (if middleware)                          | Controller                    | Backend entrypoint (typical)                          |
| --- | ---------------------------------- | -------------------------------------- | -------------------- | --------------------------------------------------- | ----------------------------- | ----------------------------------------------------- |
| 1   | `…/split-payment-approve-decline`  | `approve-payments`                     | yes                  | `approve-payments` (`ApprovePayments`)              | `splitPaymentApproveDecline`  | `PaymentRepository::fetchSplitPaymentApproveDecline`  |
| 2   | `…/master-payment-approve-capture` | `approve-payments`                     | yes                  | same                                                | `masterPaymentApproveCapture` | `PaymentRepository::fetchMasterPaymentApproveCapture` |
| 3   | `…/migrate-payment`                | `payment-edit`                         | yes                  | `payment-edit` (`PaymentsEdit`)                     | `migratePayment`              | `PaymentRepository::fetchMigratePayments`             |
| 4   | `…/update-total-price`             | `temp-update-totalprice`               | yes                  | `temp-update-totalprice` (`TEMP_UPDATE_TOTALPRICE`) | `updateTotalPrice`            | `PaymentRepository::fetchUpdateTotalPrice`            |
| 5   | `…/store-new`                      | `payment-create`                       | yes                  | `payment-create` (`PaymentsCreate`)                 | `storeNewPayment`             | `PaymentRepository::fetchCreateNewPayment`            |
| 6   | `…/update-new`                     | `payment-edit`                         | yes                  | `payment-edit` (`PaymentsEdit`)                     | `updateNewPayment`            | `PaymentRepository::fetchUpdateNewPayment`            |
| 7   | `…/retry-payment`                  | `approve-payments`                     | yes                  | `approve-payments` (`ApprovePayments`)              | `retrySplitPayment`           | `SplitPaymentService::processSplitPaymentApprove`     |
| 8   | `…/delete-split-payment`           | `payment-edit`                         | yes                  | `payment-edit` (`PaymentsEdit`)                     | `deleteSplitPayment`          | `SplitPaymentService::deleteSplitPayment`             |
| 9   | `…/void-payment`                   | `payments-void`                        | yes                  | `payments-void` (`PAYMENTS_VOID`)                   | `voidPayment`                 | `CentralService::voidPayment`                         |
| 10  | `…/remove-insurer-payment-link`    | `payments-remove-insurer-payment-link` | **no**               | _Authenticated only_                                | `removeInsurerPaymentLink`    | `CentralService::removeInsurerPaymentLink`            |
| 11  | `…/payments-capture-validation`    | `capture-validation`                   | **no**               | _Authenticated only_                                | `paymentsCaptureValidtion`    | `CentralService::capturePaymentValidation`            |
| 12  | `…/delete-payment`                 | `payments-delete`                      | **no**               | _Authenticated only_                                | `deletePayment`               | `CentralService::deletePayment`                       |
| 13  | `…/check-insurer-receipt-number`   | `check-insurer-receipt-number`         | **no**               | _Authenticated only_                                | `checkInsurerReceiptNumber`   | `CentralService::checkInsurerReceiptNumber`           |

Rows **10–13** have **no** `check_route_access`; only logged-in users. Tighten access in controller/policy if needed.

---

## Duplicate route names

- **`approve-payments`:** routes **1, 2, 7** (child split, master approve/capture, retry).
- **`payment-edit`:** routes **3, 6, 8** (migrate, update-new, delete split).

Same permission string applies to every route that shares the name. Named route generation (`route('approve-payments')`) is ambiguous when multiple URLs share one name—prefer explicit paths or unique names in future refactors.

---

## Detailed descriptions

### 1. `POST …/split-payment-approve-decline` (`approve-payments`)

**Purpose:** Approve or decline **one child split** (`payment_splits`): set capture/decline fields, optional Sage AR prepayment, bump master `captured_amount`, attach/receipt documents, then **`setMasterPaymentStatus`** + **`SplitPaymentService::updateLeadStatus`**.

**Controller:** `splitPaymentApproveDecline` → `PaymentRepository::splitPaymentApproveDecline` → **`fetchSplitPaymentApproveDecline`**. **Response:** redirect back, flash **Payment Verified** / **Payment Declined**.

**Request:** `SplitPaymentUpdateRequest` — rules + `withValidator` for broker/insurer verification permissions and INPL branch.

**Full write-up:** [PaymentSplitApproveDeclineRoute.md](./PaymentSplitApproveDeclineRoute.md)

---

### 2. `POST …/master-payment-approve-capture` (`approve-payments`)

**Purpose:** **Parent-level** transaction: approve/capture across splits, or **decline** the master payment and update quote/send-update status.

**Controller:** `masterPaymentApproveCapture` → `PaymentRepository::fetchMasterPaymentApproveCapture`.

**Repository (`fetchMasterPaymentApproveCapture`):**

- If **`is_declined`:** **`handlePaymentDecline`** — updates master payment decline fields; sets quote to **TransactionDeclined** or send-update **TRANSACTION_DECLINE**; returns message `'Transaction declined'`.
- If **`is_approved`:** **`handlePaymentApprove`** —
  - When **`is_capture`:** loops `collection_amount` by split `sr_no`, calls **`SplitPaymentService::processSplitPaymentApprove`** per non-`PAID` split.
  - Then **`SplitPaymentService::processMasterPaymentApprove`** for the master payment.

**Request:** `SplitPaymentApproveRequest` — `is_approved`, `is_declined`, `is_capture`, `collection_amount[]`, `collection_type`, `send_update_id`, etc. **`withValidator`:** quote must exist (main send-update vs lead); if `is_approved`, user must **`can(ApprovePayments)`** (extra guard on top of route middleware).

**Response:** `back()` with success or error flash.

**Note:** `payment_code` is required by repository methods; ensure the client sends it (not listed in the FormRequest `rules()` array—confirm payload conventions in your app).

---

### 3. `POST …/migrate-payment` (`payment-edit`)

**Purpose:** Migrate an **old** payment record for the quote into the **new** payment structure (used when moving legacy data to the current model).

**Controller:** `migratePayment` → `LoggerFeatureEnum::MIGRATE_PAYMENT` → `PaymentRepository::fetchMigratePayments`.

**Repository:** Loads quote by `model_type` / `quote_id`, finds payment by `payment_code`, calls **`SplitPaymentService::migratePayments`**. Returns **JSON** `{ message: 'Payment Migrated Successfully' }` or `{ error: '...' }`.

**Request:** `MigratePaymentsRequest` — `model_type`, `payment_code`, `quote_id`; validates quote exists.

---

### 4. `POST …/update-total-price` (`temp-update-totalprice`)

**Purpose:** **Capture-flow helper:** set master payment **`total_price`**, reset **`is_approved`**, set status to **`PARTIAL_CAPTURED`**, then **`SplitPaymentService::updateLeadStatus`**.

**Controller:** `updateTotalPrice` → `PaymentRepository::fetchUpdateTotalPrice` → returns **JSON** success/error.

**Request:** `UpdateTotalPriceRequest` — `model_type`, `payment_code`, `quote_id`, `total_price`; validates quote.

---

### 5. `POST …/store-new` (`payment-create`)

**Purpose:** Create a **new** master payment + splits + status log (and optionally move quote to payment pending).

**Controller:** `storeNewPayment` → `PaymentRepository::fetchCreateNewPayment`. Redirect back with success/error flash.

**Full write-up:** [PaymentStoreUpdateRoutes.md](./PaymentStoreUpdateRoutes.md) (permissions, repository, observers).

---

### 6. `POST …/update-new` (`payment-edit`)

**Purpose:** Update an existing **new-structure** payment (full or locked partial update) plus splits.

**Controller:** `updateNewPayment` → `LoggerFeatureEnum::UPDATE_PAYMENT` → `PaymentRepository::fetchUpdateNewPayment`. Redirect back with flash.

**Full write-up:** [PaymentStoreUpdateRoutes.md](./PaymentStoreUpdateRoutes.md)

---

### 7. `POST …/retry-payment` (`approve-payments`)

**Purpose:** Retry a **CC** (or related) background job after a failed **`CcPaymentProcess`**: loads job by `payment_process_job_id`, resolves split, calls **`SplitPaymentService::processSplitPaymentApprove`** with retry flag.

**Controller:** `retrySplitPayment` → `LoggerFeatureEnum::RETRY_SPLIT_PAYMENT` → redirect back success/error.

**Request:** `RetrySplitPaymentRequest` — `model_type`, `quote_id`, `payment_process_job_id` (must exist on `cc_payment_processes`).

---

### 8. `POST …/delete-split-payment` (`payment-edit`)

**Purpose:** Delete a **single split** (and associated cleanup) via **`SplitPaymentService::deleteSplitPayment`**.

**Controller:** `deleteSplitPayment` → `LoggerFeatureEnum::DELETE_SPLIT_PAYMENT` → returns **JSON** from the service.

**Request:** `DeleteSplitPaymentRequest` — `model_type`, `quote_id`, `payment_split_id`, `payment_status_id`, `code`; validates quote + split eligibility (frequency/status rules in the request).

---

### 9. `POST …/void-payment` (`payments-void`)

**Purpose:** **Void** an authorised TAP (or supported gateway) payment via **Marshall** API: only **`PAYMENT_GATEWAY_TAP`** is allowed in `CentralService::voidPayment`; builds payload with quote UUID / send-update type id; posts to `/payment/{gateway}/cancel`.

**Controller:** `voidPayment` → `LoggerFeatureEnum::VOID_PAYMENT` → `CentralService::voidPayment` → **JSON** `{ status, message }`.

**Request:** `Request` (no dedicated FormRequest) — expects `payment_code`, `quote_uuid`, `quote_type_id`, optional `send_update_log_id` (see controller usage).

---

### 10. `POST …/remove-insurer-payment-link` (`payments-remove-insurer-payment-link`)

**Purpose:** **IPL cleanup:** set quote to **InNegotiation**, clear **`insurer_payment_link`** on all relevant splits returned by **`getAllInsurerPaymentLinkSplits`** on the quote.

**Controller:** `removeInsurerPaymentLink` → `CentralService::removeInsurerPaymentLink` → **redirect** back with success/error flash.

**Middleware:** none beyond `auth`. Expects `quoteType`, `quoteId` (and any other fields) as used by `getQuoteObject`.

---

### 11. `POST …/payments-capture-validation` (`capture-validation`)

**Purpose:** **GIG capture validation** before capture: if policy automation is off (e.g. some Car quotes), returns success without external call; otherwise **`Ken::request('/capture-payment-validation', 'put', …)`** with `quoteUID`, `quoteTypeId`, `captureAmount`.

**Controller:** `paymentsCaptureValidtion` → `LoggerFeatureEnum::CAPTURE_PAYMENT_VALIDATION` → `CentralService::capturePaymentValidation` → **JSON** `{ response: … }`.

**Request:** `PaymentCaptureValidtionRequest` — `modelType`, `uuid`, `captureAmount` (controller also passes `paymentCode`, `quoteCode` from request).

---

### 12. `POST …/delete-payment` (`payments-delete`)

**Purpose:** Delete a **parent** `TravelQuote` **child** payment in specific conditions: payment must be **`paymentable_type` = `TravelQuote`**, status in **PENDING / NEW / DRAFT / OVERDUE**, quote must have **≥ 2 payments** and **above-age members** (Travel product rules). Deletes splits (and split documents), **PaymentStatusHistory**, then payment.

**Controller:** `deletePayment` → `LoggerFeatureEnum::DELETE_PARENT_PAYMENT` → `CentralService::deletePayment` → **JSON**.

**Validation:** inline — `payment_id`, `payment_code` required.

---

### 13. `POST …/check-insurer-receipt-number` (`check-insurer-receipt-number`)

**Purpose:** **Uniqueness check** for **`insurer_receipt_number`** on **`payment_splits`** globally: `count > 0` → receipt already used.

**Controller:** `checkInsurerReceiptNumber` → validates `insurer_receipt_number` → `CentralService::checkInsurerReceiptNumber` → **JSON** `{ status, message }`.

**Note:** `quoteType` is a **route parameter**; the service method does not scope by quote—only global duplicate detection.

---

## Form requests (quick reference)

| Controller method             | Request class                    |
| ----------------------------- | -------------------------------- |
| `splitPaymentApproveDecline`  | `SplitPaymentUpdateRequest`      |
| `masterPaymentApproveCapture` | `SplitPaymentApproveRequest`     |
| `migratePayment`              | `MigratePaymentsRequest`         |
| `updateTotalPrice`            | `UpdateTotalPriceRequest`        |
| `storeNewPayment`             | `StorePaymentRequest`            |
| `updateNewPayment`            | `UpdatePaymentRequest`           |
| `retrySplitPayment`           | `RetrySplitPaymentRequest`       |
| `deleteSplitPayment`          | `DeleteSplitPaymentRequest`      |
| `voidPayment`                 | `Request`                        |
| `removeInsurerPaymentLink`    | `Request`                        |
| `paymentsCaptureValidtion`    | `PaymentCaptureValidtionRequest` |
| `deletePayment`               | inline validation                |
| `checkInsurerReceiptNumber`   | inline validation                |

---

## Related docs

- [Payment table UI](./PaymentTableComponents.md)
- [Store / update new payment](./PaymentStoreUpdateRoutes.md)
- [Child split approve / decline](./PaymentSplitApproveDeclineRoute.md)
