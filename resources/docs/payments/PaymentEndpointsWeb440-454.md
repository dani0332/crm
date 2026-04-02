# Payment POST endpoints (`web.php` ~440–454)

All routes below live inside the authenticated group (`auth`, `last_login_check`). `{quoteType}` is the LOB segment (e.g. `Health`, `Car`).

`check_route_access` uses the **route name** as the Spatie permission string, except **Admin** / **Engineering**, which bypass the check.

---

## Summary table

| # | Path | Route name | `check_route_access` | Permission (if middleware) | Controller | Backend entrypoint (typical) |
|---|------|------------|----------------------|----------------------------|------------|------------------------------|
| 1 | `…/split-payment-approve-decline` | `approve-payments` | yes | `approve-payments` (`ApprovePayments`) | `splitPaymentApproveDecline` | `PaymentRepository::fetchSplitPaymentApproveDecline` |
| 2 | `…/master-payment-approve-capture` | `approve-payments` | yes | same | `masterPaymentApproveCapture` | `PaymentRepository::fetchMasterPaymentApproveCapture` → `handlePaymentApprove` / `handlePaymentDecline` |
| 3 | `…/migrate-payment` | `payment-edit` | yes | `payment-edit` (`PaymentsEdit`) | `migratePayment` | `PaymentRepository::fetchMigratePayments` → `SplitPaymentService::migratePayments` |
| 4 | `…/update-total-price` | `temp-update-totalprice` | yes | `temp-update-totalprice` (`TEMP_UPDATE_TOTALPRICE`) | `updateTotalPrice` | `PaymentRepository::fetchUpdateTotalPrice` |
| 5 | `…/store-new` | `payment-create` | yes | `payment-create` (`PaymentsCreate`) | `storeNewPayment` | `PaymentRepository::fetchCreateNewPayment` |
| 6 | `…/update-new` | `payment-edit` | yes | `payment-edit` (`PaymentsEdit`) | `updateNewPayment` | `PaymentRepository::fetchUpdateNewPayment` |
| 7 | `…/retry-payment` | `approve-payments` | yes | `approve-payments` (`ApprovePayments`) | `retrySplitPayment` | `SplitPaymentService::processSplitPaymentApprove` (via `CcPaymentProcess`) |
| 8 | `…/delete-split-payment` | `payment-edit` | yes | `payment-edit` (`PaymentsEdit`) | `deleteSplitPayment` | `SplitPaymentService::deleteSplitPayment` |
| 9 | `…/void-payment` | `payments-void` | yes | `payments-void` (`PAYMENTS_VOID`) | `voidPayment` | `CentralService::voidPayment` |
| 10 | `…/remove-insurer-payment-link` | `payments-remove-insurer-payment-link` | **no** | *Authenticated only* | `removeInsurerPaymentLink` | `CentralService::removeInsurerPaymentLink` |
| 11 | `…/payments-capture-validation` | `capture-validation` | **no** | *Authenticated only* | `paymentsCaptureValidtion` | `CentralService::capturePaymentValidation` |
| 12 | `…/delete-payment` | `payments-delete` | **no** | *Authenticated only* | `deletePayment` | `CentralService::deletePayment` |
| 13 | `…/check-insurer-receipt-number` | `check-insurer-receipt-number` | **no** | *Authenticated only* | `checkInsurerReceiptNumber` | `CentralService::checkInsurerReceiptNumber` |

Rows **10–13** rely on **session auth only**; there is no `can(<route name>)` check in middleware. Add or enforce permissions in the controller/service if you need stricter access.

---

## Duplicate route names (Laravel)

Several routes intentionally share the **same name** (same permission string for `check_route_access`):

- **`approve-payments`:** child split approve/decline, **master** approve/capture/decline, **retry** payment.
- **`payment-edit`:** migrate, **update-new**, **delete-split**.

`route('name')` can only resolve one URL per name; prefer `route('approve-payments', …)` only when unambiguous, or use explicit paths in clients.

---

## One-line purpose per endpoint

1. **Child split approve/decline** — Verify or decline a **split**; update split + master + documents; optional Sage prepayment; `setMasterPaymentStatus`. [Detail](./PaymentSplitApproveDeclineRoute.md)

2. **Master approve/capture** — Parent-level approve/capture/decline for the **master payment** (not per-split).

3. **Migrate payment** — Move legacy payment structure into the new payment model for the quote.

4. **Update total price** — Adjust master `total_price` + partial-captured status and lead status (used in specific capture flows).

5. **Store new** — Create master payment + splits + status log (new payment structure). [Detail](./PaymentStoreUpdateRoutes.md)

6. **Update new** — Edit existing master payment + splits (full or locked). [Detail](./PaymentStoreUpdateRoutes.md)

7. **Retry payment** — Re-run processing for a failed **CC** `CcPaymentProcess` job.

8. **Delete split** — Remove a child split (and related cleanup via service).

9. **Void payment** — Void payment JSON flow (`LoggerFeatureEnum::VOID_PAYMENT`).

10. **Remove insurer payment link** — Clear IPL link on split; redirect back with flash.

11. **Capture validation** — GIG/insurer capture validation (premium vs amount); returns JSON for the modal.

12. **Delete payment** — Delete **parent** payment (`DELETE_PARENT_PAYMENT` logging).

13. **Check insurer receipt** — Uniqueness / validation of insurer receipt number for the quote type.

---

## Form requests (where used)

| Controller method | Request class |
|-------------------|----------------|
| `splitPaymentApproveDecline` | `SplitPaymentUpdateRequest` |
| `masterPaymentApproveCapture` | `SplitPaymentApproveRequest` |
| `migratePayment` | `MigratePaymentsRequest` |
| `updateTotalPrice` | `UpdateTotalPriceRequest` |
| `storeNewPayment` | `StorePaymentRequest` |
| `updateNewPayment` | `UpdatePaymentRequest` |
| `retrySplitPayment` | `RetrySplitPaymentRequest` |
| `deleteSplitPayment` | `DeleteSplitPaymentRequest` |
| `voidPayment` | inline / `Request` |
| `removeInsurerPaymentLink` | `Request` |
| `paymentsCaptureValidtion` | `PaymentCaptureValidtionRequest` |
| `deletePayment` | validated inline in controller |
| `checkInsurerReceiptNumber` | validated inline |

---

## Related UI doc

[PaymentTableComponents.md](./PaymentTableComponents.md) — Vue table, modals, and composables.
