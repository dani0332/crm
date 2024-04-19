<?php

namespace App\Traits;

use App\Enums\QuoteStatusEnum;
use Illuminate\Support\Facades\Config;

trait QuoteModelTrait
{
    /**
     * @return mixed|void
     */
    public function scopeWithFakeLeadCriteria($query, $totalLeadsCount = false)
    {
        if ((! empty(request()->quote_status_id) && request()->quote_status_id != QuoteStatusEnum::Fake)) {

            return;
        }

        if ($totalLeadsCount) {
            return $query->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate]);
        }

        if (! request()->hasAny(['code', 'mobile_no', 'email', 'first_name', 'last_name', 'previous_quote_policy_number', 'renewal_batch', 'previous_quote_policy_number_text'])) {

            return $query->where('quote_status_id', '<>', QuoteStatusEnum::Fake);
        }
    }

    /**
     * @return string
     */
    public function getCreatedAtAttribute($table)
    {
        return $this->asDateTime($table)->timezone(config('app.timezone'))->format(Config::get('constants.datetime_format'));
    }

    /**
     * @return string
     */
    public function getUpdatedAtAttribute($table)
    {
        return $this->asDateTime($table)->timezone(config('app.timezone'))->format(Config::get('constants.datetime_format'));
    }
}
