# Customer Upload Queue — Implementation Notes

## What Was Built

Customer Excel uploads are processed in the background via `ProcessCustomerUploadJob` instead of blocking the HTTP request. The frontend subscribes to a Pusher channel on page load and shows a loader until the job broadcasts completion.

### Key files

| File | Role |
|---|---|
| `app/Jobs/ProcessCustomerUploadJob.php` | Queue job — runs the import, dispatches SQS jobs, fires broadcast |
| `app/Imports/CustomersImport.php` | Maatwebsite Excel import — processes rows via `OnEachRow` |
| `app/Events/CustomerUploadCompleted.php` | `ShouldBroadcastNow` event — fires on job success or failure |
| `resources/js/inertia/Pages/Customer/Upload.vue` | Frontend — subscribes on `onMounted`, shows loader while `isProcessing` |
| `resources/js/workers/pusher.worker.js` | SharedWorker — singleton Pusher connection shared across browser tabs |

---

## Issues Encountered & Fixes Applied

### 1. Frontend loader never stopped (broadcast missed)

**Root cause:** Pusher channel subscription is async (~300–700ms to confirm). The subscription was set up in `onBefore` (just before the HTTP request), so fast-completing jobs broadcast before the channel was confirmed.

**Fix:** Moved `subscribeToUpload()` to `onMounted` — the channel is established on page load before the user even fills the form. Added an `isProcessing` guard to ignore stale broadcasts when no upload is in progress.

---

### 2. `PDOException` for MySQL lock timeout not caught

**Root cause:** Lock wait timeout (MySQL error 1205) thrown from inside Maatwebsite Excel's `OnEachRow` row processing arrives as a raw `PDOException`, not wrapped in Laravel's `QueryException`. The original catch block only caught `QueryException`, so the exception propagated and consumed a retry.

**Fix:** Changed catch to `catch (QueryException|\PDOException $e)`.

---

### 3. Lock timeout root cause — concurrent DB writes

**Root cause:** `ExtendCustomerSubscriptionViaSQS::dispatch()` was called inside `onRow()` for every row. Horizon workers immediately picked up those jobs and ran them concurrently with the still-running import — both hitting the `customers` table simultaneously → lock wait timeout.

This issue did **not** exist with direct (synchronous) upload because the SQS jobs only queue during the HTTP request and Horizon does not process them until after the request completes.

**Fix:** Removed dispatch from `onRow()`. Customers are now collected into `$customersToExtend[]` during the import. After `Excel::import()` returns (all DB writes committed), `ProcessCustomerUploadJob` dispatches `ExtendCustomerSubscriptionViaSQS` in chunks of 50 with a 10-second stagger between chunks.

---

## Remaining / Unresolved Issues

### A. `$tries = 2` is too low — not a reliable fix

With `$tries = 2`, two lock-timeout retries exhaust all attempts and trigger `MaxAttemptsExceededException` before `handle()` even runs. The original value was `$tries = 5`.

Simply increasing `$tries` is a workaround, not a fix — if contention persists across all retries the job still fails. A reliable fix requires eliminating contention at the source, not increasing retry budget. The chunked-dispatch fix (issue 3) should eliminate the primary contention source, but this needs to be verified with a real upload.

**Status:** Unresolved.

---

### B. `QuoteCustomer` always saved with `customer_id = 0`

**Root cause:** In `CustomersImport::onRow()`, `$customerId = 0` is initialised but never updated to `$updateCustomer->id` after save.

```php
$customerId = 0;
// ... $updateCustomer->save() ...
$newQuoteCustomer->customer_id = $customerId; // always 0
```

**Status:** Unresolved. Fix is one-line (`$updateCustomer->id` instead of `$customerId`), pending confirmation.

---

### C. Import is not idempotent on retry

`CustomersImport` uses `OnEachRow` without `WithTransactions`, so rows committed before a mid-import failure remain in the DB. If the job retries, those rows are processed again (UPDATE for existing customers — mostly harmless, but `QuoteCustomer` records will be duplicated for already-processed rows).

**Status:** Known limitation. Not yet addressed.
