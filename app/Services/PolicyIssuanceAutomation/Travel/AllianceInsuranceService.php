<?php

namespace App\Services\InsuePolicyAutomation\Travel;

use App\Interfaces\PolicyIssuanceInterface;

class AllianceInsuranceService implements PolicyIssuanceInterface
{
    public function handle($process)
    {
        info(__CLASS__.' fn:'.__FUNCTION__.' started ');

        return response()->json(['message' => 'Alliance Insurance Service']);
    }

}
