<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class TravelQuoteEnum extends Enum
{
    const TRAVEL_UAE_INBOUND = 'travelUaeInbound';
    const TRAVEL_UAE_OUTBOUND = 'travelUaeOutbound';
    const COVERAGE_CODE_SINGLE_TRIP = 'singleTrip';
    const COVERAGE_CODE_ANNUAL_TRIP = 'annualTrip';
    const COVERAGE_CODE_MULTI_TRIP = 'multiTrip';
    const REGION_COVER_ID_UAE = 3;
    const CURRENTLY_LOCATED_ID_UAE = 1;
    const LOCATION_UAE_TEXT = 'UAE';
    const LOCATION_UNITED_ARAB_EMIRATES_TEXT = 'United Arab Emirates';
    const LOCATION_OUTSIDE_UAE = 'Outside UAE';
    const REVIVAL = 'Revival';
    const IMCRM_BOOKING = 'imcrm_booking';
    const IN_BOUND = 'inbound';
    const OUT_BOUND = 'outbound';

    // Alliance Travel Direction code
    const ALLIANCE_IN_BOUND = 'inbound';
    const ALLIANCE_OUT_BOUND = 'outbound';
}
