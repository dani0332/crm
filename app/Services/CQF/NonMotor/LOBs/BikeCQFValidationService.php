<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypeId;
use App\Models\CarQuote;
use App\Models\PersonalQuote;
use App\Services\CQF\BaseCQFValidationService;
use Illuminate\Database\Eloquent\Model;

class BikeCQFValidationService extends BaseCQFValidationService
{
    public function isDuplicateQuote(Model $quote): bool
    {
        if ($quote instanceof CarQuote) {
            $previousPersonalQuoteId = PersonalQuote::where('uuid', $quote->uuid)->value('id');

            if ($previousPersonalQuoteId === null) {
                return false;
            }

            return PersonalQuote::where('previous_quote_id', $previousPersonalQuoteId)
                ->where('previous_quote_policy_number', $quote->policy_number)
                ->where('previous_policy_expiry_date', $quote->policy_expiry_date)
                ->where('source', LeadSourceEnum::RENEWAL_UPLOAD)
                ->where('quote_type_id', QuoteTypeId::Bike)
                ->exists();
        }

        return parent::isDuplicateQuote($quote);
    }
}
