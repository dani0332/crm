<?php

namespace App\Http\Requests;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\SendPolicyTypeEnum;
use App\Models\Payment;
use App\Models\PaymentSplits;
use App\Rules\PlaceholderPrimaryEmail;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Contracts\Validation\ValidationRule;
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
     * @return array<string, ValidationRule|array<mixed>|string>
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
        $sendPolicyType = $this->input('send_policy_type');
        $modelType = $this->input('model_type');
        $quote = $this->getQuoteObject($modelType, $this->input('quote_id'));

        if ($sendPolicyType == SendPolicyTypeEnum::CUSTOMER) {
            $validator->after(function ($validator) use ($quote) {
                if ($quote) {
                    if (! $quote?->advisor_id) {
                        $validator->errors()->add('error', 'Please select advisor');
                    }

                    if (! $quote?->email) {
                        $validator->errors()->add('error', 'Customer email is required');
                    }
                } else {
                    $validator->errors()->add('error', 'Quote not found');
                }

                if (PlaceholderPrimaryEmail::hasPlaceholderPrimaryEmail($quote ?: null)) {
                    $validator->errors()->add('error', PlaceholderPrimaryEmail::message());
                }
            });
        }

        if ($sendPolicyType == SendPolicyTypeEnum::SAGE) {
            if (! request()->has('through_automation') && ! auth()->user()->canany([PermissionsEnum::SEND_AND_BOOK_POLICY_BUTTON, PermissionsEnum::BOOK_POLICY_BUTTON])) {
                return response()->json(['errors' => [
                    'message' => 'You are not authorized to perform this action',
                ]], 403);
            }
            $validator->after(function ($validator) use ($quote) {
                if ($quote) {
                    if (PlaceholderPrimaryEmail::hasPlaceholderPrimaryEmail($quote ?: null)) {
                        $validator->errors()->add('value', PlaceholderPrimaryEmail::message());
                    }

                    if ($quote->quote_status_id == QuoteStatusEnum::POLICY_BOOKING_FAILED && ! request()->has('through_automation') && ! auth()->user()->can(PermissionsEnum::BOOKING_FAILED_EDIT)) {
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

                        $isQuoteTypeCar = strtolower(request()->model_type) === strtolower(QuoteTypes::CAR->value);
                        if ($isQuoteTypeCar && empty($payment->commmission_percentage)) {
                            $validator->errors()->add('value', 'Commission percentage is required');
                        }
                        if ($isQuoteTypeCar && empty($payment->commission_vat)) {
                            $validator->errors()->add('value', 'Commission VAT is required');
                        }
                        if ($isQuoteTypeCar && empty($payment->commission)) {
                            $validator->errors()->add('value', 'Total commission is required');
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
