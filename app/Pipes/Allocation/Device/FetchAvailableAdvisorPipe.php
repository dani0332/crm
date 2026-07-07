<?php

namespace App\Pipes\Allocation\Device;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Enums\RolesEnum;
use App\Models\User;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use App\Strategies\Allocations\DeviceAllocation;
use Closure;

class FetchAvailableAdvisorPipe extends BaseAllocationPipe
{
    private const WARNING_NO_SMART_PHONE_ADVISORS_FOUND = 'No Smart Phone advisors found in app storage';
    private const WARNING_NO_VALID_ADVISOR_EMAILS = 'No valid Smart Phone advisor emails found in app storage';

    public function handle(AllocationRequest $request, Closure $next)
    {
        LoggerService::info(self::class.' - Starting to fetch available Smart Phone advisor');

        $this->setRequest($request);
        LoggerService::info(self::class.' - Smart Phone lead with SIC request - Fetching advisor using hardcoded email list');

        $advisor = $this->findAvailableAdvisor(teamId: null);
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

        if (! $advisor) {
            LoggerService::info(self::class.' - No Smart Phone advisor available for allocation');
            $this->allocationRequest->markAsFailed();
            $this->throw('Advisor not found', self::NOT_FOUND);
        }

        LoggerService::info(self::class.' - Smart Phone advisor found successfully', extra: [
            'advisorId' => $advisor->id,
            'advisorName' => $advisor->name,
            'advisorEmail' => $advisor->email,
        ]);

        $this->allocationRequest->setAdvisor($advisor);

        return $next($request);
    }

    protected function getAdvisorByStatus($onlineStatus, $teamId)
    {
        LoggerService::info(self::class." - Searching for Smart Phone advisor with status: {$onlineStatus}");

        if ($this->allocationRequest->get('isCHSAdvisor')) {
            LoggerService::info(self::class.' - CHS Advisor is required');
            // remove this when going to production and use the production CHS advisor
            $happinessUserEmail = getAppStorageValueByKey(ApplicationStorageEnums::SMART_PHONE_HAPPINESS_SUPPORT_USER_EMAIL, useCache: true);

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
                roles: [RolesEnum::SmartPhoneAdvisor],
                isBuyLead: false
            )->first();
        }

        return $this->getAdvisorByHardcodedEmails($onlineStatus);
    }

    protected function getAdvisorByHardcodedEmails($onlineStatus)
    {
        $emails = $this->getAdvisorEmails();

        if (empty($emails)) {
            LoggerService::info(self::class.' - No hardcoded emails configured for Smart Phone allocation');

            return null;
        }

        $advisorRecord = $this->getAdvisorBaseQuery(
            onlineStatus: $onlineStatus,
            teamId: null,
            roles: [RolesEnum::SmartPhoneAdvisor],
            isBuyLead: false
        )
            ->whereIn('users.email', $emails)
            ->logRawSql()
            ->first();

        if ($advisorRecord) {
            LoggerService::info(self::class.' - Found Smart Phone advisor from hardcoded email list', extra: [
                'advisorId' => $advisorRecord->user_id,
                'status' => $onlineStatus,
            ]);
        } else {
            LoggerService::info(self::class.' - No available Smart Phone advisor found', extra: [
                'status' => $onlineStatus,
                'hardcodedEmails' => $emails,
            ]);
        }

        return $advisorRecord;
    }

    private function getAdvisorEmails(): array
    {
        $emails = $this->parseAdvisorEmails(
            ApplicationStorageEnums::SMART_PHONE_ADVISORS,
            self::WARNING_NO_SMART_PHONE_ADVISORS_FOUND
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
    private function getHappinessUser(): ?User
    {
        $email = DeviceAllocation::HAPPINESS_SUPPORT_USER_EMAIL;

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
