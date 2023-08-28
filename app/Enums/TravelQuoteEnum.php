<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class TravelQuoteEnum extends Enum
{
    const TRAVELUAEINBOUND = 'travelUaeInbound';
    const TRAVELUAEOUTBOUND = 'travelUaeOutbound';
    const COVERAGECODESINGLETRIP = 'singleTrip';
    const COVERAGECODEMULTITRIP = 'multiTrip';
    const COVERAGECODEANNUALTRIPSTRING = 'Annual Trip';
    const COVERAGECODESINGLETRIPSTRING = 'Single Trip';
    const COVERAGECODEMULTITRIPSTRING = 'Multi Trip';
    const COVERAGECODEANNUALTRIP = 'annualTrip';
    const TRAVELUAEINBOUNDSTRING = 'To the UAE (Inbound)';
    const TRAVELUAEOUTBOUNDSTRING = 'Outside UAE (Outbound)';
}
