<?php

namespace App\Pipes\Allocation\Cyber;

use App\Enums\RolesEnum;
use App\Models\User;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use App\Strategies\Allocations\CyberAllocation;
use Closure;

class FetchAvailableAdvisorPipe extends BaseAllocationPipe
{
    public function handle(AllocationRequest $request, Closure $next)
    {
        LoggerService::info(self::class.' - Starting to fetch available Cyber advisor');

        $this->setRequest($request);

        if ($this->allocationRequest->shouldAssignToHappinessUser()) {
            LoggerService::info(self::class.' - Paid Cyber lead - Fetching Happiness Support User');
            
            $advisor = $this->getHappinessUser();

            if (! $advisor) {
                LoggerService::warning(self::class.' - Happiness Support User not found');
                $this->allocationRequest->markAsFailed();
                $this->throw('Happiness Support User not found', self::NOT_FOUND);
            }

            LoggerService::info(self::class.' - Happiness Support User found successfully', extra: [
                'advisorId' => $advisor->id,
                'advisorName' => $advisor->name,
                'advisorEmail' => $advisor->email,
            ]);

            $this->allocationRequest->setAdvisor($advisor);

            return $next($request);
        }

        LoggerService::info(self::class.' - Unpaid Cyber lead with SIC request - Fetching advisor using hardcoded email list');

        $advisor = $this->findAvailableAdvisor(teamId: null);

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

        return $this->getAdvisorByHardcodedEmails($onlineStatus);
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

    private function getHappinessUser(): ?User
    {
        $email = CyberAllocation::HAPPINESS_SUPPORT_USER_EMAIL;

        LoggerService::info(self::class.' - Fetching Happiness Support User by email', extra: [
            'email' => $email,
        ]);

        $user = User::where('email', $email)->first();

        if (! $user) {
            LoggerService::warning(self::class.' - Happiness Support User not found in database', extra: [
                'email' => $email,
            ]);
        }

        return $user;
    }
}

