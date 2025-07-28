<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PermissionsEnum;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;

class ValidateAuthorizedPayment
{
    private ?string $code;
    private ?object $quoteModel;

    public function __construct(?string $code = null, ?object $quoteModel = null)
    {
        $this->code = $code;
        $this->quoteModel = $quoteModel;
    }

    /**
     * Validate authorized payment and add appropriate errors to validator.
     */
    public function validate(Validator $validator, ?string $code = null): void
    {
        // Use the code from constructor if not provided in method parameter
        $codeToValidate = $code ?? $this->code;

        if (! $codeToValidate) {
            return; // No code provided, validation passes
        }

        $payment = Payment::where('code', $codeToValidate)->with('paymentSplits')->first();

        // If no payment exists, validation passes
        if (! $payment) {
            return;
        }

        // Check if there is any authorized payment split (Credit Card, status: AUTHORIZED, CAPTURED, or PAID)
        $hasAnyAuthorizedPayment = $payment->paymentSplits()
            ->where('payment_method', PaymentMethodsEnum::CreditCard)
            ->whereIn('payment_status_id', [
                PaymentStatusEnum::AUTHORISED,
                PaymentStatusEnum::CAPTURED,
                PaymentStatusEnum::PAID,
            ])
            ->exists();

        if (! $hasAnyAuthorizedPayment) {
            return; // No authorized payment found
        }

        // If there's an authorized payment, check if restricted fields are being changed
        if ($this->quoteModel && Auth::user()->can(PermissionsEnum::PLAN_DETAILS_EDIT)) {
            $request = request()->all();

            // Fields that should not be changed if payment is authorized
            $fieldsToCheck = [
                'insurance_provider_id',
                'price_vat_applicable',
                'price_vat_not_applicable',
                'provider_code',
            ];

            // Check if any restricted field is being changed and add specific errors
            foreach ($fieldsToCheck as $field) {
                if (isset($request[$field]) && isset($this->quoteModel->$field) && $request[$field] != $this->quoteModel->$field) {
                    $validator->errors()->add(
                        $field,
                        "The value of {$field} cannot be changed as this lead is linked to an authorized payment."
                    );
                }
            }
        } else {
            // If user does not have permission or quote model is missing, add a general validation error
            $validator->errors()->add(
                'authorized',
                'This lead is linked to an authorized payment. Please void the existing payment before switching to another plan.'
            );
        }
    }
}
