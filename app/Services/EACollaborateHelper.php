<?php

namespace App\Services;

use App\Enums\EaModelEnum;
use App\Enums\LeadSourceEnum;
use Illuminate\Support\Facades\Auth;

class EACollaborateHelper
{
    public static function applyEAIMCRMSource(array &$payload): void
    {
        if (request()->input('ea_model') !== 'collaborate') {
            return;
        }

        $payload['source'] = LeadSourceEnum::EA_IMCRM;
        $payload['eaModel'] = EaModelEnum::Collaborate->value;
        $payload['leadGeneratorId'] = Auth::id();
        $payload['advisorId'] = Auth::id();
    }
}
