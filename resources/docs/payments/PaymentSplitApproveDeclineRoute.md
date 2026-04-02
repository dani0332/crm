# Route: split-payment approve / decline (child split)

**Index:** [All payment endpoints ~440–454](./PaymentEndpointsWeb440-454.md)

This endpoint verifies or declines a **single child split** (`payment_splits` row) from Manage Payments: it updates the split (and aggregates to the master payment), optionally posts a Sage prepayment receipt, reconciles the parent payment status, and refreshes lead status. It is **not** the same as **master** approve/capture (`master-payment-approve-capture`, same permission name but different URL).

**Route** (`routes/web.php`):

| Method | Path                                                  | Name               | Controller                                     | Middleware                          |
| ------ | ----------------------------------------------------- | ------------------ | ---------------------------------------------- | ----------------------------------- |
| `POST` | `/payments/{quoteType}/split-payment-approve-decline` | `approve-payments` | `CentralController@splitPaymentApproveDecline` | `auth` stack + `check_route_access` |

`{quoteType}` is the LOB segment (e.g. `Health`, `Car`). The body identifies the split (`splitPaymentId`), quote (`quote_id`, `modelType`), and approve vs decline flags.

---

## Permissions (`check_route_access`)

Middleware: `App\Http\Middleware\CheckRouteAccess`.

- It reads the **current route name** and checks `auth()->user()->can($routeName)` against Spatie **permission names** (same string as the route name).
- Users with **Admin** or **Engineering** (`RolesEnum`) **skip** the permission check.

| Route name         | Spatie permission (DB `permissions.name`) | `PermissionsEnum` constant         |
| ------------------ | ----------------------------------------- | ---------------------------------- |
| `approve-payments` | `approve-payments`                        | `PermissionsEnum::ApprovePayments` |

**Note:** The same route name `approve-payments` is also used for **master** approve/capture and **retry** payment. All three require the **`approve-payments`** permission.

### Extra authorization in `SplitPaymentUpdateRequest` (`withValidator`)

After base rules pass, **approval** is further gated by collection type (unless the INPL branch short-circuits):

| Condition                                                        | Requirement                                                                                               |
| ---------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------- |
| Approve + **broker** collection (`collection_type` === `broker`) | User must have `payment-verification-collected-by-broker` (`PAYMENT_VERIFICATION_COLLECTED_BY_BROKER`).   |
| Approve + **insurer** collection                                 | User must have `payment-verification-collected-by-insurer` (`PAYMENT_VERIFICATION_COLLECTED_BY_INSURER`). |

**Insure Now Pay Later (`InsureNowPayLater`):** If the user has `inpl-approver` (`INPL_APPROVER`), the request loads the split; when the split’s method is INPL, the validator **returns without** adding broker/insurer errors (see `SplitPaymentUpdateRequest` for exact flow).

---

## What the controller does

`splitPaymentApproveDecline(SplitPaymentUpdateRequest $request)`:

1. Delegates to `PaymentRepository::splitPaymentApproveDecline($request)` → **`fetchSplitPaymentApproveDecline`** (via `BaseRepository::__callStatic`: static `splitPaymentApproveDecline` → instance `fetchSplitPaymentApproveDecline`).
2. Returns **`redirect()->back()->with('success', $message)`** with flash message **`Payment Verified`** (approve) or **`Payment Declined`** (decline).

No JSON body; errors from validation or `vAbort` (Sage failure) surface as normal Laravel error handling.

---

## What the repository includes (`fetchSplitPaymentApproveDecline`)

### 1. Logging and loading

- `LoggerFeatureEnum::APPROVE_DECLINE_CHILD_PAYMENT`.
- Loads **`PaymentSplits`** by `splitPaymentId`; reads **master** `Payment` (`$splitPayment->payment`) and **quote** (`$masterPayment->paymentable`).

### 2. Sage prepayment (outside the DB transaction)

Runs only when **all** hold: request is **approve**, split is **not** already `PAID`, Sage is enabled, `SageApiService::shouldCreateAndSchedulePostPrepayment($quote, $splitPayment)` is true, and the branch is **not** Abu Dhabi (`isAbuDhabiBranch`).

Then **`SageApiService::createARPrepaymentPremiumReceipt`** is called. On failure, **`vAbort($message)`** stops the request (no DB transaction commit for the main flow).

### 3. Database work (`handleWithDeadlockRetries`, max 2 retries)

#### Approve path (`is_approved`, split not `PAID`)

- Split status: **`CAPTURED`**, or **`PARTIALLY_PAID`** if `actual_amount` is set and greater than `collection_amount`.
- Updates the split: `collection_amount`, `bank_reference_number`, `payment_status_id`, `payment_allocation_status`, `updated_by`, `verified_at` / `verified_by`, `insurer_receipt_number`.
- **Documents:** For `approved_document_model[sr_no]`, attaches `QuoteDocument` rows to the split or maps document type to receipt via **`mapToReciept`** / `DocumentTypeEnum::RECEIPT`.
- **Master payment:** Increments **`captured_amount`** by `collection_amount`; sets **`payment_allocation_status`**.
- **Broker collection:** **`SplitPaymentService::createReceipt`** (receipt generation for broker-collected flows).

#### Decline path (`is_declined`, split not `PAID`)

- Updates split: **`DECLINED`**, `decline_reason_id`, `decline_custom_reason`, `updated_by`.
- Success message switched to **`Payment Declined`**.

#### Always after approve or decline (when transaction runs)

- **`setMasterPaymentStatus($masterPayment)`** — For **upfront** vs non-upfront frequency, recalculates the **parent** `payment_status_id` from splits (`updateUpfrontStatus` / `updateNonUpfrontStatus`), then **`SplitPaymentService::updateLeadStatus($payment)`** to align the quote/send-update lead status.

---

## Observers (relevant detail)

Same models as [store/update](./PaymentStoreUpdateRoutes.md): `PaymentObserver`, `PaymentSplitsObserver` (`AppServiceProvider`).

| Model / event                 | On this flow                                                                                                                                                                                                                                                                                                                                                                                                                                               |
| ----------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **`PaymentSplits` `updated`** | Approve/decline calls **`$splitPayment->update(...)`**. **`PaymentSplitsObserver`** recalculates split VAT only when **`payment_amount`** is dirty — typical approve/decline updates **status / collection / refs**, not `payment_amount`, so **VAT helper often does not run** here.                                                                                                                                                                      |
| **`Payment` `updated`**       | Master **`update`** changes **`captured_amount`** / allocation (and later **`setMasterPaymentStatus`** may change **`payment_status_id`**). **`PaymentObserver`**: VAT recalculation runs only when **`total_price`** is dirty; **PAID** + upfront path may **`touch()`** first split when `payment_status_id` moves to PAID. So this flow mainly affects **status / captured_amount**, not master VAT fields unless something else changes `total_price`. |

In short: **child approve/decline** is dominated by **status, capture totals, documents, Sage, and lead status** — not the same “create splits → VAT fill” path as **store-new**.

---

## Form request: `SplitPaymentUpdateRequest`

### `rules()`

| Field                     | Rule             |
| ------------------------- | ---------------- |
| `approved_document_model` | optional array   |
| `bank_reference_number`   | nullable string  |
| `collection_amount`       | nullable numeric |
| `customer_id`             | required integer |
| `declined_custom_reason`  | nullable string  |
| `declined_reason`         | nullable integer |
| `is_approved`             | required boolean |
| `is_declined`             | required boolean |
| `modelType`               | required string  |
| `plan_id`                 | required integer |
| `quote_id`                | required integer |
| `splitPaymentId`          | required integer |
| `collection_type`         | required string  |
| `insurer_receipt_number`  | nullable string  |

### `withValidator`

Described above (broker / insurer / INPL).

---

## Related code pointers

- `app/Repositories/PaymentRepository.php` — `fetchSplitPaymentApproveDecline`, `setMasterPaymentStatus`, `updateUpfrontStatus`, `updateNonUpfrontStatus`, `mapToReciept`.
- `app/Services/SplitPaymentService.php` — `createReceipt`, `updateLeadStatus`.
- `app/Services/SageApiService.php` — `shouldCreateAndSchedulePostPrepayment`, `createARPrepaymentPremiumReceipt`, `isSageEnabled`.

**See also:** [Payment store/update routes](./PaymentStoreUpdateRoutes.md), [Payment table UI](./PaymentTableComponents.md).
