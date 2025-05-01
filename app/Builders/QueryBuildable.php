<?php

namespace App\Builders;

use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;

trait QueryBuildable
{
    use TeamHierarchyTrait;

    protected function parseDate($date, $isStartOfDay)
    {
        if ($date && $date != '') {
            if ($isStartOfDay) {
                return Carbon::parse($date)->startOfDay()->toDateTimeString();
            } else {
                return Carbon::parse($date)->endOfDay()->toDateTimeString();
            }
        }
    }

    protected function shouldApplyDatesFilter()
    {
        return empty(request('email')) &&
                empty(request('mobile_no')) &&
                empty(request('code')) &&
                empty(request('renewal_batch')) &&
                empty(request('quote_batch_id')) &&
                empty(request('payment_due_date')) &&
                empty(request('booking_date')) &&
                empty(request('previous_quote_policy_number')) &&
                empty(request('insurer_tax_invoice_number')) &&
                empty(request('insurer_commission_tax_invoice_number'));
    }
}
