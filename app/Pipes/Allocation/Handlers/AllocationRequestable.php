<?php

namespace App\Pipes\Allocation\Handlers;

use App\Models\BuyLeadRequest;
use App\Models\NationalityAllocationConfiguration;
use App\Models\Tier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait AllocationRequestable
{
    public function setLead(Model $lead)
    {
        $this->set('lead', $lead);
    }

    public function getLead()
    {
        return $this->get('lead');
    }

    public function setAdvisor(User $advisor)
    {
        $this->set('advisor', $advisor);
    }

    public function getAdvisor(): ?User
    {
        return $this->get('advisor');
    }

    public function setNationalityConfig(NationalityAllocationConfiguration $config)
    {
        $this->set('nationality_config', $config);
    }

    public function hasNationalityConfig()
    {
        return ! empty($this->get('nationality_config', null));
    }

    public function setAdvisorIDs(array $advisorIds)
    {
        $this->set('advisor_ids', $advisorIds);
    }

    public function getAdvisorIDs()
    {
        return $this->get('advisor_ids', []);
    }

    public function setBuyLeadRequest(BuyLeadRequest $buyLeadRequest)
    {
        if ($buyLeadRequest) {
            $this->markAsBuyLead();
        }

        $this->set('buy_lead_request', $buyLeadRequest);
    }

    public function getBuyLeadRequest(): ?BuyLeadRequest
    {
        return $this->get('buy_lead_request');
    }

    public function setTier(Tier $tier)
    {
        $this->set('tier', $tier);
    }

    public function getTier(): ?Tier
    {
        return $this->get('tier');
    }

    public function excludedAdvisorIds(array $advisorIds)
    {
        $excludedAdvisorIds = $this->getExcludedAdvisorIds();

        $excludedAdvisorIds = array_merge($excludedAdvisorIds, $advisorIds);

        $this->set('excluded_advisor_ids', $excludedAdvisorIds);
    }

    public function getExcludedAdvisorIds()
    {
        return $this->get('excluded_advisor_ids', []);
    }

    public function hasExcludedAdvisorIds()
    {
        return ! empty($this->getExcludedAdvisorIds());
    }

    public function resetNationalityConfig()
    {
        $this->set('nationality_config', null);
    }
}
