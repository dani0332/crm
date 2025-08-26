<?php

namespace App\Traits;

use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\Payment;

trait QuotePaymentable
{
    public function hasOneOfPaymentStatus(array $statuses)
    {
        return $this->payments->count() > 0 && $this->payments->some(fn (Payment $payment) => in_array($payment->payment_status_id, $statuses));
    }

    public function isPaymentDeclinedOrFailed()
    {
        return $this->hasOneOfPaymentStatus(PaymentStatusEnum::getDeclinedOrFailedStatuses());
    }

    public function isPaymentAuthorizedOrCapturedOrPaid()
    {
        return $this->hasOneOfPaymentStatus(PaymentStatusEnum::getPaidStatuses());
    }

    public function isPaymentLinkRequested()
    {
        return $this->quote_status_id == QuoteStatusEnum::PaymentLinkRequestedByCustomer;
    }

    public function isAdvisorRequestedOrAuthorizedOrLinkRequested()
    {
        return $this->sic_advisor_requested == 1 || $this->isPaymentAuthorizedOrCapturedOrPaid() || $this->isPaymentLinkRequested();
    }

    public function isAdvisorRequestedOrAuthorizedOrRequestedLinkOrDeclined()
    {
        return $this->isAdvisorRequestedOrAuthorizedOrLinkRequested() || $this->isPaymentDeclinedOrFailed();
    }

    public function isPaymentAuthorizedOrLinkRequestedOrDeclined()
    {
        return $this->isPaymentAuthorizedOrCapturedOrPaid() || $this->isPaymentDeclinedOrFailed() || $this->isPaymentLinkRequested();
    }

    public function scopePaymentLinkRequested($q)
    {
        $q->where('quote_status_id', QuoteStatusEnum::PaymentLinkRequestedByCustomer);
    }

    public function scopeHasOneOfPaidStatus($q)
    {
        $q->whereHas('payments', function ($query) {
            $query->whereIn('payment_status_id', PaymentStatusEnum::getPaidStatuses());
        });
    }

    public function scopeHasOneOfDeclinedOrFailedStatus($q)
    {
        $q->whereHas('payments', function ($query) {
            $query->whereIn('payment_status_id', PaymentStatusEnum::getDeclinedOrFailedStatuses());
        });
    }

    public function scopeHasPaidOrDeclinedStatus($q)
    {
        $q->whereHas('payments', function ($query) {
            $query->whereIn('payment_status_id', PaymentStatusEnum::getPaidStatuses());
            $query->orWhereIn('payment_status_id', PaymentStatusEnum::getDeclinedOrFailedStatuses());
        });
    }

    public function scopeAdvisorRequestedOrPaymentAuthorized($q)
    {
        $q->where(function ($sq) {
            $sq->where('sic_advisor_requested', 1)->orWhere->hasOneOfPaidStatus()->orWhere->paymentLinkRequested();
        });
    }

    public function scopeAdvisorRequestedOrPaymentAuthorizedOrDeclined($q)
    {
        $q->where(function ($sq) {
            $sq->where('sic_advisor_requested', 1)->orWhere->hasPaidOrDeclinedStatus()->orWhere->paymentLinkRequested();
        });
    }
}
