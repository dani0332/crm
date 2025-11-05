<?php

namespace App\Pipes\Allocation\Cyber;

use App\Enums\RolesEnum;
use App\Enums\UserStatusEnum;
use App\Models\User;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;

class FetchAvailableAdvisorPipe extends BaseAllocationPipe
{
    public function handle(AllocationRequest $request, Closure $next)
    {
        LoggerService::info(self::class.' - Starting to fetch available Cyber advisor');

        $this->setRequest($request);

        $advisor = $this->fetchAvailableAdvisor();

        if (! $advisor) {
            LoggerService::info(self::class.' - No Cyber advisor available for allocation');
            $this->allocationRequest->markAsFailed();
            $this->throw('Advisor not found', self::NOT_FOUND);
        }

        LoggerService::info(self::class.' - Cyber advisor found successfully', extra: [
            'advisorId' => $advisor->id,
            'advisorName' => $advisor->name,
            'advisorEmail' => $advisor->email,
        ]);

        $this->allocationRequest->setAdvisor($advisor);

        return $next($request);
    }

    protected function fetchAvailableAdvisor()
    {
        LoggerService::info(self::class.' - Fetching Cyber advisor using hardcoded email list');

        $emails = $this->getAdvisorEmails();

        if (empty($emails)) {
            LoggerService::info(self::class.' - No hardcoded emails configured for Cyber allocation');

            return null;
        }

        LoggerService::info(self::class.' - Searching for Cyber advisors', extra: [
            'emailCount' => count($emails),
            'emails' => $emails,
        ]);

        $statusOrder = $this->getOnlineStatusesInOrder();

        foreach ($statusOrder as $status) {
            LoggerService::info(self::class." - Trying to find Cyber advisor with status: {$status}");

            $advisorRecord = $this->getAdvisorBaseQuery(
                onlineStatus: $status,
                teamId: null,
                roles: [RolesEnum::CyberAdvisor],
                isBuyLead: false,
                skipMaxCapCheck: true
            )
                ->whereIn('users.email', $emails)
                ->logRawSql()
                ->first();

            if ($advisorRecord) {
                $advisor = User::find($advisorRecord->user_id);

                if ($advisor) {
                    LoggerService::info(self::class." - Found Cyber advisor with status: {$status}", extra: [
                        'advisorId' => $advisor->id,
                        'advisorEmail' => $advisor->email,
                        'advisorStatus' => $status,
                        'capacityCheckSkipped' => true,
                    ]);

                    return $advisor;
                }
            }
        }

        LoggerService::info(self::class.' - No Cyber advisor found across all status levels');

        return null;
    }

    private function getAdvisorEmails(): array
    {
        LoggerService::info(self::class.' - Getting hardcoded email list for Cyber advisors');

        $smitha = 'smitha.chandran@insurancemarket.ae';
        $neil = 'neil.rama@insurancemarket.ae';
        $fahad = 'fahadhussain2020@gmail.com';

        $emails = [$fahad];

        // $isOnLeave = $this->isUserOnLeave($smitha, addUnavailable: true);

        // if ($isOnLeave) {
        //     $emails = [$neil];
        // } else {
        //     $emails = [$smitha];
        // }

        LoggerService::info(self::class.' - Hardcoded emails retrieved', extra: [
            'emailCount' => count($emails),
            'emails' => $emails,
        ]);

        return $emails;
    }
}

