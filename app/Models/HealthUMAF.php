<?php

namespace App\Models;

use App\Enums\InsuranceProviderEnum;
use Illuminate\Support\Arr;

class HealthUMAF extends BaseMongoModel
{
    protected $table = 'health-umaf-responses';

    /**
     * Whether the UMAF response has non-STP rating.
     * Uses stp_rating from MongoDB: is_non_stp (bool) or case_type === "NON_STP".
     */
    public function isNonStp(): bool
    {
        $stpRating = $this->getAttribute('stp_rating') ?? [];

        return Arr::get($stpRating, 'is_non_stp', false) === true;
    }

    public function isADNIC()
    {
        return $this->getAttribute('provider_code') === InsuranceProviderEnum::ADNIC->value;
    }
}
