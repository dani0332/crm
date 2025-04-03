<?php

namespace App\Traits;

use App\Enums\PaymentMethodsEnum;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasPaymentsTrait
{
    /**
     * Get all payments for this model
     *
     * @return MorphMany
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'paymentable');
    }

    /**
     * Check if any payment has IPL in its splits
     *
     * @return bool
     */
    public function hasInsurerPaymentLink(): bool
    {
        return $this->payments()
            ->whereHas('paymentSplits', function ($query) {
                $query->where('payment_method', PaymentMethodsEnum::InsurerPaymentLink);
            })
            ->exists();
    }

    /**
     * Get all payments that have IPL splits
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPaymentsWithInsurerPaymentLink()
    {
        return $this->payments()
            ->whereHas('PaymentSplits', function ($query) {
                $query->where('payment_method', PaymentMethodsEnum::InsurerPaymentLink);
            })
            ->get();
    }

    /**
     * Get all IPL payment splits across all payments
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllInsurerPaymentLinkSplits()
    {
        $payments = $this->getPaymentsWithInsurerPaymentLink();
        
        return $payments->flatMap(function ($payment) {
            return $payment->paymentSplits()
                ->where('payment_method', PaymentMethodsEnum::InsurerPaymentLink)
                ->get();
        });
    }
} 