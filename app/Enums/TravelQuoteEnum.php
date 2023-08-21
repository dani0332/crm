<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class TravelQuoteEnum extends Enum
{
    const TravelUaeInbound = 'travelUaeInbound';
    const TravelUaeOutbound = 'travelUaeOutbound';
    const CoverageCodeSingleTrip = 'singleTrip';
    const CoverageCodeMultiTrip = 'multiTrip';
    const CoverageCodeAnnualTripString = 'Annual Trip';
    const CoverageCodeSingleTripString = 'Single Trip';
    const CoverageCodeMultiTripString = 'Multi Trip';
    const CoverageCodeAnnualTrip = 'annualTrip';
    const TravelUaeInboundString = 'To the UAE (Inbound)';
    const TravelUaeOutboundString = 'Outside UAE (Outbound)';
}
