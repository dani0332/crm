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
    private const WARNING_NO_TEST_ADVISORS_FOUND = 'No test Cyber advisors found in app storage';
    private const WARNING_NO_PRODUCTION_ADVISORS_FOUND = 'No production Cyber advisors found in app storage';
    private const WARNING_NO_VALID_ADVISOR_EMAILS = 'No valid Cyber advisor emails found in app storage';

    public function handle(AllocationRequest $request, Closure $next)
    {
        LoggerService::info(self::class.' - Starting to fetch available Cyber advisor');

        $this->setRequest($request);

        if ($this->allocationRequest->get('isCHSAdvisor')) {
            LoggerService::info(self::class.' - CHS Advisor is required for Cyber lead');

            $advisor = $this->findAvailableAdvisor(teamId: null);

            if (! $advisor) {
                LoggerService::warning(self::class.' - CHS Advisor not found');
                $this->allocationRequest->markAsFailed();
                $this->throw('CHS Advisor not found', self::NOT_FOUND);
            }

            LoggerService::info(self::class.' - CHS Advisor found successfully', extra: [
                'advisorId' => $advisor->id,
                'advisorName' => $advisor->name,
                'advisorEmail' => $advisor->email,
            ]);

            $this->allocationRequest->setAdvisor($advisor);

            return $next($request);
        }

        LoggerService::info(self::class.' - Cyber lead with SIC request - Fetching advisor using hardcoded email list');

        $advisor = $this->findAvailableAdvisor(teamId: null);

        if (! $advisor) {
            if ($this->isTestMode()) {
                LoggerService::info(self::class.' - No Cyber advisor available for allocation - Trying backup advisor (TEST MODE)');

                $backupAdvisor = $this->getBackupAdvisor();

                if ($backupAdvisor) {
                    LoggerService::info(self::class.' - Backup advisor found and assigned', extra: [
                        'advisorId' => $backupAdvisor->id,
                        'advisorName' => $backupAdvisor->name,
                        'advisorEmail' => $backupAdvisor->email,
                    ]);

                    $this->allocationRequest->setAdvisor($backupAdvisor);

                    return $next($request);
                }

                LoggerService::info(self::class.' - No Cyber advisor available for allocation (including backup)');
            } else {
                LoggerService::info(self::class.' - No Cyber advisor available for allocation (PRODUCTION MODE - backup advisor not used)');
            }

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

        if ($this->allocationRequest->get('isCHSAdvisor')) {
            LoggerService::info(self::class.' - getAdvisorsByStatus: CHS Advisors is required');

            if ($this->isTestMode()) {
                LoggerService::info(self::class.' - TEST MODE: Using test Happiness User email for CHS advisor');
                return User::select('users.id as user_id')
                    ->where('users.email', CyberAllocation::HAPPINESS_SUPPORT_USER_EMAIL)
                    ->first();
            }

            LoggerService::info(self::class.' - PRODUCTION MODE: Using Production CHS advisor');
            return User::select('users.id as user_id')->chs()->first();
        }

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

    private function isTestMode(): bool
    {
        $testMode = getAppStorageValueByKey(ApplicationStorageEnums::CYBER_ALLOCATION_TEST_MODE, useCache: true);

        return $testMode == 1;
    }

    private function getAdvisorEmails(): array
    {
        if ($this->isTestMode()) {
            return $this->getTestModeAdvisorEmails();
        }

        return $this->getProductionModeAdvisorEmails();
    }

    private function getTestModeAdvisorEmails(): array
    {
        $emails = $this->parseAdvisorEmails(
            ApplicationStorageEnums::CYBER_ADVISORS_TEST,
            self::WARNING_NO_TEST_ADVISORS_FOUND
        );

        LoggerService::info(self::class.' - TEST MODE: Cyber advisors fetched', extra: [
            'emailCount' => count($emails),
            'emails' => $emails,
        ]);

        return $emails;
    }

    private function getProductionModeAdvisorEmails(): array
    {
        $emails = $this->parseAdvisorEmails(
            ApplicationStorageEnums::CYBER_ADVISORS,
            self::WARNING_NO_PRODUCTION_ADVISORS_FOUND
        );

        if (empty($emails)) {
            LoggerService::warning(self::class.' - '.self::WARNING_NO_VALID_ADVISOR_EMAILS);

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

    private function getBackupAdvisor(): ?User
    {
        $backupEmail = 'diya.lekhwani@myalfred.com';

        LoggerService::info(self::class.' - Fetching backup advisor by email', extra: [
            'email' => $backupEmail,
        ]);

        $user = User::where('email', $backupEmail)
            ->whereHas('roles', function ($query) {
                $query->where('name', RolesEnum::CyberAdvisor);
            })
            ->first();

        if (! $user) {
            LoggerService::warning(self::class.' - Backup advisor not found in database', extra: [
                'email' => $backupEmail,
            ]);
        }

        return $user;
    }

    private function parseAdvisorEmails($storageKey, string $emptyWarningMessage): array
    {
        $emails = getAppStorageValueByKey($storageKey, useCache: true);

        if (empty($emails)) {
            LoggerService::warning(self::class." - {$emptyWarningMessage}");

            return [];
        }

        $emails = explode(',', $emails);
        $emails = array_map('trim', $emails);
        $emails = array_filter($emails, fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL));
        $emails = array_values($emails);

        return $emails;
    }
}
