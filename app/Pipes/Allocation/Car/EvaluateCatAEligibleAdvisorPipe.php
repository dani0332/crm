<?php

namespace App\Pipes\Allocation\Car;

use App\Enums\PermissionsEnum;
use App\Enums\UserStatusEnum;
use App\Models\BuyLeadRequest;
use App\Models\Tier;
use App\Models\User;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;

class EvaluateCatAEligibleAdvisorPipe extends BaseAllocationPipe
{
    use Carable;

    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        $tier = $request->getTier();

        $advisor = $this->evaluateCatAEligibleAdvisor($tier);

        if (! $advisor) {
            LoggerService::warning('No advisor found');

            $this->allocationRequest->markAsFailed();

            $this->throw('Advisor assignment is in progress and will be assigned shortly', self::OK);
        }

        $this->allocationRequest->setAdvisor($advisor);

        $this->verifyIfAdvisorIsSameAsPreviousAdvisor($advisor);

        return $next($request);
    }

    private function evaluateCatAEligibleAdvisor(Tier $tier)
    {
        $advisor = $this->fetchAdvisor($tier);

        if ($advisor) {
            return User::find($advisor->user_id);
        }

        return null;
    }

    private function fetchAdvisor(Tier $tier)
    {
        $statusOrder = $this->getOnlineStatusesInOrder();

        foreach ($statusOrder as $status) {
            $advisor = $this->getCATANationalitiesAdvisorByStatus($status, $tier);

            if ($advisor) {
                LoggerService::info(self::class."::getCATANationalitiesAdvisorByStatus - Eligble Advisor {$advisor->user_id} found with the availability status of: ".UserStatusEnum::getUserStatusText($status));

                return $advisor;
            }

            LoggerService::info(self::class.'::getCATANationalitiesAdvisorByStatus - No Advisor was found with the availability status of: '.UserStatusEnum::getUserStatusText($status));
        }

        return null;
    }

    private function getCATANationalitiesAdvisorByStatus($status, Tier $tier)
    {
        LoggerService::info(self::class."::getCATANationalitiesAdvisorByStatus - CAT A Nationality allocation for tier: {$tier->name} and status: {$status}");
        $buyLeadRequestedUserIds = BuyLeadRequest::getCatAUserIds($this->allocationRequest->isSIC());
        $userIdsWithPermission = User::permission(PermissionsEnum::BUY_LEADS_REVIVAL)->pluck('id')->toArray();
        LoggerService::info(self::class.'::getCATANationalitiesAdvisorByStatus - user ids with permission are: '.json_encode($userIdsWithPermission));

        // Filter user IDs to get only those present in both buyLeadRequestedUserIds and userIdsWithPermission
        $userIds = array_values(array_intersect($buyLeadRequestedUserIds, $userIdsWithPermission));

        LoggerService::info(self::class.'::getCATANationalitiesAdvisorByStatus - user ids are: '.json_encode($userIds));

        $advisor = $this->getBaseQuery($status, $userIds)
            ->where('buy_lead_status', true)
            ->where(function ($query) {
                $query->whereRaw('buy_lead_cat_a_allocation_count < buy_lead_max_capacity')->orWhere('buy_lead_max_capacity', '=', -1);
            })
            ->orderBy('buy_lead_last_allocated')
            ->logRawSql()
            ->first();

        if ($advisor) {
            $this->allocationRequest->set('hasCatABuyLeadRequest', true);

            LoggerService::info(self::class."::getCATANationalitiesAdvisorByStatus - CAT A Buy Lead Advisor {$advisor->user_id} found");

            $buyLeadRequest = BuyLeadRequest::getCatARequest($this->allocationRequest->getQuoteType(), $this->allocationRequest->isSIC(), $advisor->user_id);
            $this->allocationRequest->setBuyLeadRequest($buyLeadRequest);

            LoggerService::info("Cat A Nationality Buy Lead Request {$buyLeadRequest->id} found for advisor ID: {$advisor->user_id}");
            $this->allocationRequest->getBuyLeadRequest()->startProcessing();

            return $advisor;
        }

        return null;
    }
}
