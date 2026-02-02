<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Enums\LeadSourceEnum;
use App\Models\CarQuote;
use App\Models\PersonalQuote;
use App\Services\CQF\BaseCQFValidationService;
use App\Services\CQF\NonMotor\Traits\PersonalQuoteDuplicateCheckTrait;
use Illuminate\Database\Eloquent\Model;

class BikeCQFValidationService extends BaseCQFValidationService
{
    use PersonalQuoteDuplicateCheckTrait;

    public function isDuplicateQuote(Model $quote): bool
    {
        if ($quote instanceof CarQuote) {
            return PersonalQuote::where('previous_quote_id', $quote->id)
                ->where('previous_quote_policy_number', $quote->policy_number)
                ->where('previous_policy_expiry_date', $quote->policy_expiry_date)
                ->where('source', LeadSourceEnum::RENEWAL_UPLOAD)
                ->where('quote_type_id', \App\Enums\QuoteTypeId::Bike)
                ->exists();
        }

        if (! $quote instanceof PersonalQuote) {
            return false;
        }

        return PersonalQuote::where('previous_quote_id', $quote->id)
            ->where('previous_quote_policy_number', $quote->policy_number)
            ->where('previous_policy_expiry_date', $quote->policy_expiry_date)
            ->where('source', LeadSourceEnum::RENEWAL_UPLOAD)
            ->exists();
    }
}
