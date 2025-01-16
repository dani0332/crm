<?php

namespace App\Services\Traits;

use App\Enums\QuoteTypes;
use App\Enums\TeamNameEnum;
use App\Models\CarQuote;

trait Inboundable
{
    protected function handleCarAllocation(CarQuote $lead, $teamId = null)
    {
        info("handleCarAllocation: Going to Assign Advisor to uuid: {$lead->uuid}");
        if ($lead->advisor_id) {
            info("handleCarAllocation: Lead already has an advisor assigned: {$lead->uuid} - Advisor ID: {$lead->advisor_id}");
        } else {
            $response = QuoteTypes::CAR->allocate($lead->uuid, $teamId);
            $assignedAdvisorId = $response['advisorId'] ?? '';
            info("handleCarAllocation: Allocation Executed for lead: {$lead->uuid} and assignedAdvisorId: {$assignedAdvisorId}");
        }
    }

    public function handleSicReplyToILA(CarQuote $lead)
    {
        info("handleCarAllocation: Received: {$lead->uuid}");

        if ($lead->advisor_id) {
            info("handleSicReplyToILA: Lead already has an advisor assigned: {$lead->uuid} - Advisor ID: {$lead->advisor_id}");

            return;
        }

        if (! $lead->isSIC(QuoteTypes::CAR)) {
            info("handleSicReplyToILA: Lead is not SIC: {$lead->uuid}");

            return;
        }

        // assign advisor to the lead either Oragnic or Unassisted 2.0 advisor
        $this->handleCarAllocation(
            $lead,
            getTeamId($lead->isPaymentAuthorized() ? TeamNameEnum::SIC_UNASSISTED : TeamNameEnum::ORGANIC)
        );
    }
}
