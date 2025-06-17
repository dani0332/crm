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

    protected function shouldApplyDatesFilter($requestParams = [])
    {
        // Helper method to get filter value from requestParams or request object
        $getFilterValue = function ($filterName) use ($requestParams) {
            if (! empty($requestParams) && isset($requestParams[$filterName])) {
                return $requestParams[$filterName];
            }

            return request($filterName);
        };

        return empty($getFilterValue('email')) &&
                empty($getFilterValue('mobile_no')) &&
                empty($getFilterValue('code')) &&
                empty($getFilterValue('renewal_batch')) &&
                empty($getFilterValue('quote_batch_id')) &&
                empty($getFilterValue('payment_due_date')) &&
                empty($getFilterValue('booking_date')) &&
                empty($getFilterValue('previous_quote_policy_number')) &&
                empty($getFilterValue('insurer_tax_invoice_number')) &&
                empty($getFilterValue('insurer_commission_tax_invoice_number'));
    }
}
