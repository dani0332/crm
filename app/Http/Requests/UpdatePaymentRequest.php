<?php

namespace App\Http\Requests;

use App\Enums\PaymentGatewayIdEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\FtcEmailLog;
use App\Models\Payment;
use App\Models\PaymentSplits;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class UpdatePaymentRequest extends FormRequest
{
    use GenericQueriesAllLobs;

    /**
     * Quote from {@see getQuoteObject()} for this request; `false` when not found; unset until {@see rules()} or resolution in {@see withValidator()}.
     *
     * @var Model|false|null
     */
    private mixed $quoteModel = null;

    private ?Payment $existingPayment = null;

    /** @var Collection<int, PaymentSplits>|null */
    private ?Collection $existingSplits = null;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $this->quoteModel = $this->getQuoteObject($this->input('modelType'), $this->input('quote_id'));

        /*
         * Collection date and split due dates must be on or after today for quotes that are not
         * yet Policy Booked. For Policy Booked leads, payment updates may reference historical
         * collection or instalment dates (on or before policy inception); requiring
         * `after_or_equal:today` would block legitimate edits. CU: 86exmagnm
         *
         * Additionally, collection_date skips after_or_equal:today when the master payment
         * is already paid/partially paid/captured/partial captured. Per-split due_date
         * rules below still check each split's own status individually.
         */
        $isPolicyBooked = $this->quoteModel !== false
            && (int) $this->quoteModel->quote_status_id === (int) QuoteStatusEnum::PolicyBooked;

        // Raw DB values — must include both the canonical (PAID/PARTIALLY_PAID) and their DB aliases
        // (CAPTURED/PARTIAL_CAPTURED) because the getPaymentStatusIdAttribute accessor transforms
        // CAPTURED→PAID and PARTIAL_CAPTURED→PARTIALLY_PAID on read.
        $paidStatuses = [
            PaymentStatusEnum::PAID,
            PaymentStatusEnum::PARTIALLY_PAID,
            PaymentStatusEnum::CAPTURED,
            PaymentStatusEnum::PARTIAL_CAPTURED,
        ];

        $this->existingPayment ??= Payment::where('code', $this->paymentCode)->first();
        $this->existingSplits ??= $this->existingPayment?->paymentSplits ?? collect();

        $masterPaymentStatusId = $this->existingPayment
            ? (int) $this->existingPayment->getRawOriginal('payment_status_id')
            : null;

        $hasPaidMasterPayment = $masterPaymentStatusId !== null
            && \in_array($masterPaymentStatusId, $paidStatuses, true);

        $collectionDateRule = ($isPolicyBooked || $hasPaidMasterPayment)
            ? 'required|date'
            : 'required|date|after_or_equal:today';

        $rules = [
            'modelType' => 'required',
            'quote_id' => 'required|numeric',
            'plan_id' => 'nullable|integer',
            'insurance_provider_id' => 'nullable|integer',
            'payment.total_price' => 'required|numeric|min:0',
            'payment.notes' => 'nullable|string',
            'payment.custom_reason' => 'nullable|string',
            'payment.discount_reason' => 'nullable|string',
            'payment.discount_custom_reason' => 'nullable|string',
            'payment.discount_type' => 'nullable|string',
            'payment.frequency' => 'required|string|in:upfront,monthly,quarterly,semi_annual,split_payments,custom',
            'payment.collection_type' => 'required|string|in:broker,insurer',
            'payment.total_amount' => 'required|numeric|min:0',
            'payment.collection_date' => $collectionDateRule,
            'payment.discount_value' => 'nullable|numeric|min:0',
            'payment.payment_methods' => 'required|string',
            'payment.payment_splits.*.sr_no' => 'required|integer|min:1',
            'payment.payment_splits.*.payment_amount' => 'required|numeric',
            'payment.payment_splits.*.payment_method' => 'required|string',
            'payment.payment_splits.*.due_date' => Rule::forEach(function ($_, $attribute) use ($isPolicyBooked, $paidStatuses) {
                if ($isPolicyBooked) {
                    return ['required', 'date'];
                }

                // attribute = "payment.payment_splits.{index}.due_date"
                $index = explode('.', $attribute)[2] ?? null;
                $srNo = $index !== null ? (int) $this->input("payment.payment_splits.{$index}.sr_no") : 0;

                if ($srNo > 0) {
                    $dbSplit = $this->existingSplits?->firstWhere('sr_no', $srNo);

                    if ($dbSplit && in_array((int) $dbSplit->getRawOriginal('payment_status_id'), $paidStatuses)) {
                        return ['required', 'date'];
                    }
                }

                return ['required', 'date', 'after_or_equal:today'];
            }),
        ];

        return $rules;
    }

    /**
     * validate quote record
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $payment = Payment::where('code', $this->paymentCode)->first();
            $quoteModel = $this->quoteModel ?? $this->getQuoteObject($this->input('modelType'), $this->input('quote_id'));

            // check if the user is authorized to apply discount
            if (request()->input('payment.discount_value') > 0 && $payment->discount_value != request()->input('payment.discount_value') && auth()->user()->cannot(PermissionsEnum::PAYMENTS_DISCOUNT_ADD)) {
                $validator->errors()->add('value', 'Not Authorized to Add Discount');
            }
            // check if the user is authorized to apply credit approval
            if (request()->input('payment.credit_approval') != '' && $payment->credit_approval != request()->input('payment.credit_approval') && auth()->user()->cannot(PermissionsEnum::PAYMENTS_CREDIT_APPROVAL_ADD)) {
                $validator->errors()->add('value', 'Not Authorized to Add Credit Approval');
            }
            if (request()->input('payment.collection_type') == 'insurer' && request()->input('sendFTCEmail') == true) {
                $paymentSplit = request()->input('payment.payment_splits');
                foreach ($paymentSplit as $split) {
                    if (isset($split['insurer_payment_link']) && $split['insurer_payment_link']) {
                        $linkUsed = FtcEmailLog::where('quote_trackable_id', '!=', $payment->paymentable_id)->where('link', $split['insurer_payment_link'])->exists();
                        if ($linkUsed) {
                            $validator->errors()->add('insurer_payment_link', 'You have already sent this payment link for another lead. Please verify and ensure each lead is sent a unique link to avoid processing errors');
                        }
                        $isPaymentLinkEnabled = $this->checkInsuranceProviderPaymentGateway(request()->input('insurance_provider_id'), $quoteModel, request()->input('modelType'));
                        if (! $isPaymentLinkEnabled) {
                            $validator->errors()->add('insurer_payment_link', 'Current insurance provider is not supported for this payment gateway. Please verify that the insurance provider is supported for this payment gateway or broker commission is enabled for this insurance provider and plan.');
                        } else {
                            request()->merge(['payment_gateway_id' => PaymentGatewayIdEnum::PAYMENT_GATEWAY_PL]);
                            request()->merge(['cc_payment_gateway' => strtoupper(PaymentGatewayIdEnum::PAYMENT_GATEWAY_PL_TEXT)]);
                        }
                    }
                }
            }

            if (request()->input('isPolicyIssuanceDiscount') && auth()->user()->cannot(PermissionsEnum::PAYMENTS_DISCOUNT_EDIT)) {
                $validator->errors()->add('value', 'Not Authorized to Edit Discount');
            }

        });
    }
}
