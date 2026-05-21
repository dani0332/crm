<?php

namespace App\Services;

use App\Enums\LeadSourceEnum;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\PersonalQuote;

class EAManagerService
{
    public function pendingRejectionsCount(): int
    {
        $rejected = fn ($q) => $q->whereNotNull('ea_assigned_advisor_rejected_at')
            ->orWhereNotNull('ea_expert_advisor_rejected_at');

        return CarQuote::where('source', LeadSourceEnum::EA_IMCRM)->where($rejected)->count()
            + HealthQuote::where('source', LeadSourceEnum::EA_IMCRM)->where($rejected)->count()
            + PersonalQuote::where('source', LeadSourceEnum::EA_IMCRM)->where($rejected)->count();
    }
}
