<?php

namespace App\Http\Requests;

use App\Enums\PaymentFrequency;
use App\Enums\PaymentStatusEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\Payment;
use App\Models\PaymentSplits;
use App\Services\SageApiService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Foundation\Http\FormRequest;

class SendBookPolicyRequest extends FormRequest
{
    use GenericQueriesAllLobs;
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'model_type' => 'required',
            'quote_id' => 'required',
            'send_policy_type' => 'required',
            'is_send_policy' => 'nullable',
            'transaction_payment_status' => 'nullable',
            'modelType' => 'nullable',
        ];
    }

    public function withValidator($validator)
    {

        if (request()->send_policy_type == 'sage') {
            if (! auth()->user()->canany([PermissionsEnum::SEND_AND_BOOK_POLICY_BUTTON, PermissionsEnum::BOOK_POLICY_BUTTON])) {
                return response()->json(['errors' => [
                    'message' => 'You are not authorized to perform this action',
                ]], 403);
            }
            $validator->after(function ($validator) {
                // check for quote records if exists
                $quote = $this->getQuoteObject(request()->model_type, request()->quote_id);

                if ($quote) {
                    if ($quote->quote_status_id == QuoteStatusEnum::POLICY_BOOKING_FAILED && ! auth()->user()->can(PermissionsEnum::BOOKING_FAILED_EDIT)) {
                        $validator->errors()->add('error', 'Policy Booking Failed! Please contact finance for correction of details');
                    }
                    $isDuplicateOrCIRLead = ! empty($quote->parent_duplicate_quote_id);
                    $payment = Payment::where('code', $quote->code)->whereNull('send_update_log_id')->first();
                    $getPaymentAgainstCode = $quote->code;

                    if ($isDuplicateOrCIRLead && empty($payment)) {
                        $payment = Payment::where([
                            'paymentable_id' => $quote->id,
                            'paymentable_type' => $quote->getMorphClass(),
                        ])->whereNull('send_update_log_id')->first();
                        $getPaymentAgainstCode = $payment->code;
                    }

                    $payment = Payment::where('code', $getPaymentAgainstCode)->whereNull('send_update_log_id')->first();
                    $paymentSplit = PaymentSplits::where('code', $getPaymentAgainstCode)->first();
                    $splits = PaymentSplits::where('code', $getPaymentAgainstCode)->get();

                    // Blow code is for checking if payment and payment split record exists or not which is required for sage

                    if ($payment && $paymentSplit) {
                        if (empty($payment->insurer_invoice_date)) {
                            $validator->errors()->add('value', 'Insurer Invoice date is required');
                        }
                        if (empty($payment->insurer_tax_number)) {
                            $validator->errors()->add('value', 'Insurer tax invoice number is required');
                        }
                        if (empty($payment->insurer_commmission_invoice_number)) {
                            $validator->errors()->add('value', 'Insurer Commission Invoice Number is required');
                        }
                        if (empty($payment->commission_vat_not_applicable) && empty($payment->commission_vat_applicable)) {
                            $validator->errors()->add('value', 'Commission (VAT NOT APPLICABLE) OR Commission (VAT APPLICABLE) is required');
                        }

                        $isPaymentNotUpfrontOrSplit = ! in_array($payment->frequency, [PaymentFrequency::UPFRONT, PaymentFrequency::SPLIT_PAYMENTS]);
                        $isPaymentPaidOrCaptured = in_array($splits[0]['payment_status_id'], [PaymentStatusEnum::PAID, PaymentStatusEnum::CAPTURED]);
                        $isPaymentUpfrontOrSplitAndPaidOrCaptured = $isPaymentNotUpfrontOrSplit && $isPaymentPaidOrCaptured;
                        $isQuoteFallUnderSkippableCriteria = (new SageApiService)->skipApplyPrepaymentsForSpecificLeads($quote, $payment, $splits);
                        if (! $isQuoteFallUnderSkippableCriteria['status']) {
                            if ($isPaymentUpfrontOrSplitAndPaidOrCaptured) {
                                if (! empty($splits)) {
                                    $isSageReceiptIdEmpty = empty($splits[0]->sage_reciept_id);
                                    if ($isSageReceiptIdEmpty) {
                                        $validator->errors()->add('value', 'Payment sage reciept id can not be null');
                                    }
                                }
                            }

                            if (strtolower($payment->invoicePaymentStatus) == PaymentFrequency::PAID && in_array($payment->frequency, [PaymentFrequency::SPLIT_PAYMENTS, PaymentFrequency::PAID])) {
                                if (! empty($splits)) {
                                    foreach ($splits as $item) {
                                        if (empty($item->sage_reciept_id)) {
                                            $validator->errors()->add('value', 'Payment sage reciept id can not be null');
                                        }
                                    }
                                }
                            }
                        }

                    } else {
                        $validator->errors()->add('value', 'Payment Not found');
                    }

                    // Check parent Lead Status not in Cancellation Pending state.
                    $parentQuoteCode = count(explode('-', $getPaymentAgainstCode)) > 2 ? $quote->parent_duplicate_quote_id : false;
                    if ($parentQuoteCode) {
                        $parentQuote = $this->getQuoteObjectBy(request()->model_type, $parentQuoteCode, 'code');
                        if ($parentQuote) {
                            if ($parentQuote->quote_status_id == QuoteStatusEnum::CancellationPending) {
                                $validator->errors()->add('value', 'Cancellation for '.$parentQuoteCode.' is still pending');
                            }
                        } else {
                            $validator->errors()->add('value', 'Parent Quote Not found');
                        }
                    }
                } else {
                    $validator->errors()->add('value', 'Quote Not found');
                }
            });
        }
    }
}
