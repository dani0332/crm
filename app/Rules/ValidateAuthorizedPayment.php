<?php

namespace App\Rules;

use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PermissionsEnum;
use App\Models\Payment;
use Illuminate\Contracts\Validation\Rule;

class ValidateAuthorizedPayment implements Rule
{
    private ?string $code;
    private ?object $quoteModel;

    public function __construct(?string $code = null, ?object $quoteModel = null)
    {
        $this->code = $code;
        $this->quoteModel = $quoteModel;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        // Use the value passed to the validation rule (the actual code field value)
        $code = $value ?: $this->code;
        
        if (!$code) {
            return true; // No code provided, validation passes
        }
        
        $payment = Payment::where('code', $code)->with('paymentSplits')->first();

        // If no payment exists, validation passes
        if (!$payment) {
            return true;
        }

        // Check if there is any authorized payment split (Credit Card, status: AUTHORIZED, CAPTURED, or PAID)
        $hasAnyAuthorizedPayment = $payment->paymentSplits()
            ->where('payment_method', PaymentMethodsEnum::CreditCard)
            ->whereIn('payment_status_id', [
                PaymentStatusEnum::AUTHORISED,
                PaymentStatusEnum::CAPTURED,
                PaymentStatusEnum::PAID
            ])
            ->exists();

        // If there's an authorized payment, check if restricted fields are being changed
        if ($hasAnyAuthorizedPayment && $this->quoteModel && auth()->user()->can(PermissionsEnum::PLAN_DETAILS_EDIT)) {
            $request = request()->all();
            
            // Fields that should not be changed if payment is authorized
            $fieldsToCheck = [
                'insurance_provider_id',
                'price_vat_applicable',
                'price_vat_not_applicable',
                'provider_code',
            ];

            // Check if any restricted field is being changed
            foreach ($fieldsToCheck as $field) {
                if (isset($request[$field]) && isset($this->quoteModel->$field) && $request[$field] != $this->quoteModel->$field) {
                    return false; // Field change detected, validation fails
                }
            }
            
            // If no field changes detected, validation passes (user can still make other changes)
            return true;
        }

        // If there's an authorized payment but no quote model or no permission, validation fails
        if ($hasAnyAuthorizedPayment) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        // Use the code from the current request context
        $code = request()->code ?: $this->code;
        
        if (!$code) {
            return 'Payment code is required for validation.';
        }
        
        $payment = Payment::where('code', $code)->with('paymentSplits')->first();

        if (!$payment) {
            return 'Payment not found for the provided code.';
        }

        $hasAnyAuthorizedPayment = $payment->paymentSplits()
            ->where('payment_method', PaymentMethodsEnum::CreditCard)
            ->whereIn('payment_status_id', [
                PaymentStatusEnum::AUTHORISED,
                PaymentStatusEnum::CAPTURED,
                PaymentStatusEnum::PAID
            ])
            ->exists();

        if (!$hasAnyAuthorizedPayment) {
            return 'No authorized payment found for this lead.';
        }

        // If quote model is provided and user has permission, check for specific field changes
        if ($this->quoteModel && $this->quoteModel && auth()->user()->can(PermissionsEnum::PLAN_DETAILS_EDIT)) {
            $request = request()->all();
            
            $fieldsToCheck = [
                'insurance_provider_id',
                'price_vat_applicable',
                'price_vat_not_applicable',
                'provider_code',
            ];

            foreach ($fieldsToCheck as $field) {
                if (isset($request[$field]) && isset($this->quoteModel->$field) && $request[$field] != $this->quoteModel->$field) {
                    return "The value of {$field} cannot be changed as this lead is linked to an authorized payment.";
                }
            }
        }

        return 'This lead is linked to an authorized payment. Please void the existing payment before switching to another plan.';
    }
} 