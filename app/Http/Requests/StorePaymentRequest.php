<?php

namespace App\Http\Requests;

use App\Enums\PaymentGatewayIdEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Models\FtcEmailLog;
use App\Models\InsuranceProvider;
use App\Models\Payment;
use App\Repositories\PaymentRepository;
use App\Services\BrokerCommissionService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
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
        $rules = [
            'modelType' => 'required',
            'quote_id' => 'required|numeric',
        ];

        if ($this->input('new_payment_structure') === true) {
            $rules = [
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
                'payment.collection_date' => 'required|date',
                'payment.discount_value' => 'nullable|numeric|min:0',
                'payment.payment_methods' => 'required|string',
                'payment.payment_splits.*.sr_no' => 'required|integer|min:1',
                'payment.payment_splits.*.payment_amount' => 'required|numeric',
                'payment.payment_splits.*.payment_method' => 'required|string',
                'payment.payment_splits.*.due_date' => 'required|date',
                'send_update_id' => 'nullable|integer',
            ];
        }

        return $rules;
    }

    /**
     * validate quote record
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $quoteModel = $this->getQuoteObject(request()->modelType, request()->quote_id);
            if (! $quoteModel) {
                $validator->errors()->add('quote', 'Quote Not Exists');
            } else {

                $expectedPaymentCode = $quoteModel->code;
                if (! empty(request()->send_update_id) || ! empty($quoteModel->parent_duplicate_quote_id)) {
                    // Payment follow-up count is now iterative (uuid-(nth+1)) and not dependent on the count of payments in the quote
                    // Count will be iterative for each payment added through the send update or Child lead

                    if (! empty(request()->send_update_id)) {
                        $paymentAlreadyExistsForSU = Payment::where('send_update_log_id', request()->send_update_id)->count();
                        if ($paymentAlreadyExistsForSU > 0) {
                            $validator->errors()->add('payment', 'Payment Already Added');
                        }
                    }

                    if (! empty($quoteModel->parent_duplicate_quote_id)) {
                        // This will check the double tab case and if the lead created through duplicate functionality
                        $paymentAlreadyExists = $quoteModel->payments->count();

                        // This condition check if the payment already exists and the payment is not added through send update
                        if ($paymentAlreadyExists > 0 && empty(request()->send_update_id)) {
                            $validator->errors()->add('payment', 'Payment Already Added');
                        }
                    }

                    $mainLeadCode = implode('-', array_slice(explode('-', $quoteModel->code), 0, 2));
                    $paymentCount = app(PaymentRepository::class)->getPaymentsCountByLeadCode($mainLeadCode);
                    $expectedPaymentCode = ($paymentCount > 0) ? $mainLeadCode.'-'.$paymentCount : $mainLeadCode;
                }

                if (request()->input('payment.collection_type') == 'insurer' && request()->input('sendFTCEmail') == true) {
                    $paymentSplit = request()->input('payment.payment_splits');
                    foreach ($paymentSplit as $split) {
                        if (isset($split['insurer_payment_link']) && $split['insurer_payment_link']) {
                            $linkUsed = FtcEmailLog::where('quote_trackable_id', '!=', request()->input('quote_id'))->where('link', $split['insurer_payment_link'])->exists();
                            if ($linkUsed) {
                                $validator->errors()->add('insurer_payment_link', 'You have already sent this payment link for another lead. Please verify and ensure each lead is sent a unique link to avoid processing errors');
                            }
                            $isPaymentLinkEnabled = $this->checkInsuranceProviderPaymentGateway($quoteModel);
                            if (! $isPaymentLinkEnabled) {
                                $validator->errors()->add('insurer_payment_link', 'Current insurance provider is not supported for this payment gateway. Please verify that the insurance provider is supported for this payment gateway or broker commission is enabled for this insurance provider and plan.');
                            } else {
                                request()->merge(['payment_gateway_id' => PaymentGatewayIdEnum::PAYMENT_GATEWAY_PL]);
                                request()->merge(['cc_payment_gateway' => strtoupper(PaymentGatewayIdEnum::PAYMENT_GATEWAY_PL_TEXT)]);
                            }
                        }
                    }
                }

                $paymentAlreadyExistsCount = Payment::where('code', $expectedPaymentCode)->count();
                if ($paymentAlreadyExistsCount > 0) {
                    $validator->errors()->add('payment', 'Payment Already Added');
                }
            }
            // check if the user is authorized to apply discount
            if (request()->input('payment.discount_value') > 0 && auth()->user()->cannot(PermissionsEnum::PAYMENTS_DISCOUNT_ADD)) {
                $validator->errors()->add('value', 'Not Authorized to Add Discount');
            }
            // check if the user is authorized to apply credit approval
            if (request()->input('payment.credit_approval') != '' && auth()->user()->cannot(PermissionsEnum::PAYMENTS_CREDIT_APPROVAL_ADD)) {
                $validator->errors()->add('value', 'Not Authorized to Add Credit Approval');
            }
        });
    }

    private function checkInsuranceProviderPaymentGateway($quoteModel)
    {
        $insuranceProviderId = request()->input('insurance_provider_id');
        $insurerProvider = InsuranceProvider::where('id', $insuranceProviderId)->first();
        $quoteTypeId = QuoteTypes::getIdFromValue(request()->input('modelType'));
        $businessTypeId = $quoteModel->business_type_of_insurance_id ?? null;
        $planId = request()->input('plan_id') ?? null;
        [, $brokerCommission, , $isPaymentLinkEnabled] = app(BrokerCommissionService::class)->fetchBrokerCommission($quoteTypeId, $insuranceProviderId, $businessTypeId, $planId);
        if ($isPaymentLinkEnabled) {
            return true;
        }

        return $insurerProvider->payment_gateway_id == PaymentGatewayIdEnum::PAYMENT_GATEWAY_PL;
    }
}
