<?php

namespace App\Pipes\Allocation\Cyber;

use App\Enums\RolesEnum;
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

        $teamId = $this->allocationRequest->getTeamId();

        if ($teamId) {
            LoggerService::info(self::class.' - Fetching Cyber advisor using team-based allocation (Hapex)', extra: [
                'teamId' => $teamId,
            ]);
        } else {
            LoggerService::info(self::class.' - Fetching Cyber advisor using hardcoded email list');
        }

        $advisor = $this->findAvailableAdvisor($teamId);

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

    protected function getAdvisorByStatus($onlineStatus, $teamId)
    {
        LoggerService::info(self::class." - Searching for Cyber advisor with status: {$onlineStatus}");

        if ($teamId) {
            return $this->getAdvisorForHapexTeam($onlineStatus, $teamId);
        }

        return $this->getAdvisorByHardcodedEmails($onlineStatus);
    }

    protected function getAdvisorForHapexTeam($onlineStatus, $teamId)
    {
        $advisorRecord = $this->getAdvisorBaseQuery(
            onlineStatus: $onlineStatus,
            teamId: $teamId,
            roles: [RolesEnum::TravelHapex],
            isBuyLead: false
        )
            ->logRawSql()
            ->first();

        if ($advisorRecord) {
            LoggerService::info(self::class.' - Found Cyber Hapex advisor', extra: [
                'advisorId' => $advisorRecord->user_id,
                'status' => $onlineStatus,
                'teamId' => $teamId,
            ]);
        }

        return $advisorRecord;
    }

    protected function getAdvisorByHardcodedEmails($onlineStatus)
    {
        $emails = $this->getAdvisorEmails();

        if (empty($emails)) {
            LoggerService::info(self::class.' - No hardcoded emails configured for Cyber allocation');

            return null;
        }

        $advisorRecord = $this->getAdvisorBaseQuery(
            onlineStatus: $onlineStatus,
            teamId: null,
            roles: [RolesEnum::CyberAdvisor],
            isBuyLead: false
        )
            ->whereIn('users.email', $emails)
            ->logRawSql()
            ->first();

        if ($advisorRecord) {
            LoggerService::info(self::class.' - Found Cyber advisor from hardcoded email list', extra: [
                'advisorId' => $advisorRecord->user_id,
                'status' => $onlineStatus,
            ]);
        } else {
            LoggerService::info(self::class.' - No available Cyber advisor found', extra: [
                'status' => $onlineStatus,
                'hardcodedEmails' => $emails,
            ]);
        }

        return $advisorRecord;
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

