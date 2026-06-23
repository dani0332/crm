<?php

namespace App\Pipes\Allocation\Cyber;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Enums\RolesEnum;
use App\Models\User;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;

class FetchAvailableAdvisorPipe extends BaseAllocationPipe
{
    private const WARNING_NO_ADVISORS_FOUND = 'No Cyber advisors found in app storage';
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

        if ($this->allocationRequest->get('isCHSAdvisor')) {
            LoggerService::info(self::class.' - CHS Advisor is required');

            if (app()->environment('production')) {
                return User::select('users.id as user_id')->chs()->first();
            }

            // Non-production environments use test/UAT email from app storage
            $happinessUserEmail = getAppStorageValueByKey(ApplicationStorageEnums::CYBER_HAPPINESS_SUPPORT_USER_EMAIL, useCache: true);

            return User::select('users.id as user_id')
                ->where('users.email', $happinessUserEmail)
                ->first();
        }

        // EA_IMCRM: bypass hardcoded email routing — permission gate in getAdvisorBaseQuery handles filtering
        if ($this->lead?->source === LeadSourceEnum::EA_IMCRM) {
            LoggerService::info(self::class.' - EA_IMCRM lead detected, bypassing hardcoded email routing and using permission gate');

            return $this->getAdvisorBaseQuery(
                onlineStatus: $onlineStatus,
                teamId: null,
                roles: [RolesEnum::CyberAdvisor],
                isBuyLead: false
            )->first();
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

    private function getAdvisorEmails(): array
    {
        $emails = $this->parseAdvisorEmails(
            ApplicationStorageEnums::CYBER_ADVISORS,
            self::WARNING_NO_ADVISORS_FOUND
        );

        if (empty($emails)) {
            LoggerService::warning(self::class.' - '.self::WARNING_NO_VALID_ADVISOR_EMAILS);

            return [];
        }

        $primaryEmail = $emails[0];
        $backupEmails = array_slice($emails, 1);

        $isOnLeave = $this->isUserOnLeave($primaryEmail, addUnavailable: true);

        if ($isOnLeave) {
            LoggerService::info(self::class.' - Primary advisor is on SICK or LEAVE, assigning to backups', extra: [
                'primaryEmail' => $primaryEmail,
                'backupCount' => count($backupEmails),
                'backupEmails' => $backupEmails,
            ]);

            return $backupEmails;
        }

        LoggerService::info(self::class.' - Assigning to primary advisor', extra: [
            'primaryEmail' => $primaryEmail,
        ]);

        return [$primaryEmail];
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
