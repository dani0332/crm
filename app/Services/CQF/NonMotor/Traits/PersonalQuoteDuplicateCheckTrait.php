<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\Traits;

use App\Enums\LeadSourceEnum;
use App\Models\PersonalQuote;
use Illuminate\Database\Eloquent\Model;

trait PersonalQuoteDuplicateCheckTrait
{
    public function isDuplicateQuote(Model $quote): bool
    {
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
