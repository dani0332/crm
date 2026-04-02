# Routes: `store-new` & `update-new` payments

**Index:** [All payment endpoints ~440–454](./PaymentEndpointsWeb440-454.md)

Defines the HTTP layer for **creating** and **updating** the “new payment structure” (parent `payments` row + `payment_splits`) from the CRM Manage Payments UI.

**Routes** (`routes/web.php`):

| Method | Path                               | Name             | Controller                           | Middleware                          |
| ------ | ---------------------------------- | ---------------- | ------------------------------------ | ----------------------------------- |
| `POST` | `/payments/{quoteType}/store-new`  | `payment-create` | `CentralController@storeNewPayment`  | `auth` stack + `check_route_access` |
| `POST` | `/payments/{quoteType}/update-new` | `payment-edit`   | `CentralController@updateNewPayment` | same                                |

`{quoteType}` is the LOB segment (e.g. `Health`, `Car`); validation requests use it with `quote_id`, `modelType`, `payment` payload, etc.

---

## Permissions (`check_route_access`)

Middleware: `App\Http\Middleware\CheckRouteAccess`.

- It reads the **current route name** (`$request->route()->getName()`) and checks `auth()->user()->can($routeName)` against Spatie **permission names** (same string as the route name).
- Users with **Admin** or **Engineering** (`RolesEnum`) **skip** the permission check and are always allowed.

| Route name       | Spatie permission (DB `permissions.name`) | `PermissionsEnum` constant        |
| ---------------- | ----------------------------------------- | --------------------------------- |
| `payment-create` | `payment-create`                          | `PermissionsEnum::PaymentsCreate` |
| `payment-edit`   | `payment-edit`                            | `PermissionsEnum::PaymentsEdit`   |

So: **create** requires the **`payment-create`** permission; **update** requires **`payment-edit`**.

**Note:** Other payment POST routes in `web.php` also use the name `payment-edit` (e.g. migrate / delete split). They rely on the **same** `payment-edit` permission—assign that permission to roles that should perform any of those actions.

---

## What the controller does

Both actions return a **redirect back** with flash `success` or `error` (Inertia/non-JSON flow).

- **`storeNewPayment(StorePaymentRequest $request)`** — Delegates to `PaymentRepository::createNewPayment($request)`.  
  Internally the repository class implements **`fetchCreateNewPayment`**; static calls use `BaseRepository::__callStatic`, which prefixes `fetch` (so `createNewPayment` → `fetchCreateNewPayment`).

- **`updateNewPayment(UpdatePaymentRequest $request)`** — Starts feature logging (`LoggerFeatureEnum::UPDATE_PAYMENT`), then `PaymentRepository::updateNewPayment` → **`fetchUpdateNewPayment`**.

No extra logic in the controller beyond validation + repository + redirect.

---

## What the repository includes (high level)

### Create (`fetchCreateNewPayment`)

- Resolves the quote (or send-update) model, builds the **master payment** attributes (totals, frequency, collection type, discount, method, status `NEW` / `CREDIT_APPROVED` for credit approval, gateway ids, etc.).
- Derives **payment `code`** from quote code and existing payment count (special rules for send-update / child leads).
- **Transaction:** `payments()->create(...)`, then **`addPaymentSplits`** (creates `payment_splits`, links documents, sets child statuses via `SplitPaymentService`), **`PaymentStatusLog`** row, optional **quote `quote_status_id` → Payment pending** when not send-update / not IPL-only edge cases.
- **Observers** run on create (see below).

### Update (`fetchUpdateNewPayment`)

- Loads payment by `paymentCode`.
- **`isPaymentLocked`:** only a **limited** field set (notes, reasons, credit approval, sometimes parent `payment_methods_code` for proforma/credit-approval rules via `shouldUpdateParentPaymentMethod`).
- **Full update:** master fields + **`updatePaymentSplits`** (and optional trashed document cleanup). Uses **deadlock retry** wrapper for the transaction.

---

## Observers (relevant detail)

Registered in `AppServiceProvider`:

- `Payment::observe(PaymentObserver::class)`
- `PaymentSplits::observe(PaymentSplitsObserver::class)`

### `PaymentObserver`

| Event         | Behaviour                                                                                                                                                                                                                                                  |
| ------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **`created`** | **`updatePriceVat`:** resolves quote model type (including `PersonalQuote` vs morph class), calls `SplitPaymentService::calculateMasterPriceAndVat`, then **`Payment::withoutEvents`** updates `price_vat_applicable` and `price_vat` on the same payment. |
| **`updated`** | If **`total_price`** changed → same VAT helper. If **upfront** frequency and **`payment_status_id`** changed to **`PAID`** → **`touch()`** first split (so split `updated_at` moves; interacts with split-level logic).                                    |

### `PaymentSplitsObserver`

| Event         | Behaviour                                                                                                                                                                                                                                                                     |
| ------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **`created`** | **`updateSplitPriceVat`:** loads master payment + quote, adjusts first split amount by master **discount** when `sr_no === 1`, calls `SplitPaymentService::calculatePriceAndVat`, then **`PaymentSplits::withoutEvents`** updates split `price_vat_applicable` / `price_vat`. |
| **`updated`** | Same VAT path when **`payment_amount`** is dirty.                                                                                                                                                                                                                             |

So: **store/update** paths that create or change **payments** or **splits** will **recalculate VAT columns** on those models without re-firing observer loops (`withoutEvents`).

---

## Related requests

- `App\Http\Requests\StorePaymentRequest` — validation for create.
- `App\Http\Requests\UpdatePaymentRequest` — validation for update (includes lock/edit rules as applicable).

For the full **Manage Payments** UI tree, see [PaymentTableComponents.md](./PaymentTableComponents.md).

For **split approve / decline** (`split-payment-approve-decline`, route `approve-payments`), see [PaymentSplitApproveDeclineRoute.md](./PaymentSplitApproveDeclineRoute.md).
