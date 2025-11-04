<?php

namespace App\Traits;

use App\Models\CustomerVerificationDetail;
use App\Models\VehicleDriverDetail;
use App\Models\RegistrationCertificate;
use App\Enums\QuoteTypes;

trait CarQuoteTrait
{
    public function getCustomerVerificationDetailCount(int $carQuoteId): int
    {
        return CustomerVerificationDetail::where('quote_type_id', QuoteTypes::CAR->id())
            ->where('quotable_id', $carQuoteId)
            ->count();
    }

    public function getVehicleDriverDetailCount(int $carQuoteId): int
    {
        return VehicleDriverDetail::where('quoteable_type', QuoteTypes::CAR->modelClass())
            ->where('quoteable_id', $carQuoteId)
            ->count();
    }

    public function getRegistrationCertificateCount(int $carQuoteId): int
    {
        return RegistrationCertificate::where('certificatable_type', QuoteTypes::CAR->modelClass())
            ->where('certificatable_id', $carQuoteId)
            ->count();
    }
}