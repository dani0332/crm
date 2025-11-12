<?php

namespace App\Pipes\Allocation\Cyber;

use App\Enums\ApplicationStorageEnums;
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
        $testMode = getAppStorageValueByKey(ApplicationStorageEnums::CYBER_ALLOCATION_TEST_MODE);
        
        if ($testMode == 1) {
            return $this->getTestModeAdvisorEmails();
        }
        
        return $this->getProductionModeAdvisorEmails();
    }

    private function getTestModeAdvisorEmails(): array
    {
        $emails = getAppStorageValueByKey(ApplicationStorageEnums::CYBER_ADVISORS_TEST, useCache: true);
        
        if (empty($emails)) {
            LoggerService::warning(self::class.' - No test Cyber advisors found in app storage');
            
            return [];
        }
        
        $emails = explode(',', $emails);
        $emails = array_map('trim', $emails);
        $emails = array_filter($emails, fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL));
        $emails = array_values($emails);
        
        LoggerService::info(self::class.' - TEST MODE: Cyber advisors fetched', extra: [
            'emailCount' => count($emails),
            'emails' => $emails,
        ]);
        
        return $emails;
    }

    private function getProductionModeAdvisorEmails(): array
    {
        $emails = getAppStorageValueByKey(ApplicationStorageEnums::CYBER_ADVISORS, useCache: true);
        
        if (empty($emails)) {
            LoggerService::warning(self::class.' - No production Cyber advisors found in app storage');
            
            return [];
        }
        
        $emails = explode(',', $emails);
        $emails = array_map('trim', $emails);
        $emails = array_filter($emails, fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL));
        $emails = array_values($emails);
        
        if (empty($emails)) {
            LoggerService::warning(self::class.' - No valid Cyber advisor emails found in app storage');
            
            return [];
        }
        
        $primaryEmail = $emails[0];
        $backupEmails = array_slice($emails, 1);
        
        $isOnLeave = $this->isUserOnLeave($primaryEmail, addUnavailable: true);
        
        if ($isOnLeave) {
            LoggerService::info(self::class.' - PRODUCTION: Primary advisor is on SICK or LEAVE, assigning to backups', extra: [
                'primaryEmail' => $primaryEmail,
                'backupCount' => count($backupEmails),
                'backupEmails' => $backupEmails,
            ]);
            
            return $backupEmails;
        }
        
        LoggerService::info(self::class.' - PRODUCTION: Assigning to primary advisor', extra: [
            'primaryEmail' => $primaryEmail,
        ]);
        
        return [$primaryEmail];
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

