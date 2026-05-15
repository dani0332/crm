<?php

declare(strict_types=1);

namespace App\Pipes\Allocation\Pqa;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\UserStatusEnum;
use App\Exceptions\Allocation\AllocationException;
use App\Models\BusinessQuote;
use App\Models\User;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\AllocationService;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;

abstract class BasePqaAllocationPipe extends AllocationService
{
    public const NOT_FOUND = Response::HTTP_NOT_FOUND;
    public const OK = Response::HTTP_OK;
    public const SERVER_ERROR = Response::HTTP_INTERNAL_SERVER_ERROR;

    protected AllocationRequest $allocationRequest;
    protected ?BusinessQuote $lead = null;

    protected function setRequest(AllocationRequest $allocationRequest, bool $startLogging = true): void
    {
        $this->allocationRequest = $allocationRequest;

        if ($startLogging) {
            LoggerService::startQuoteLogging(
                $this->allocationRequest->getRefID(),
                LoggerFeatureEnum::ALLOCATION,
            );
        }

        if ($lead = $this->allocationRequest->getLead()) {
            $this->lead = $lead;
        }
    }

    protected function throw(string $message, int $code = 500): never
    {
        throw new AllocationException($message, $code);
    }

    protected function getPqaQuoteTypeId()
    {
        return QuoteTypes::BUSINESS->id();
    }

    /**
     * @return Builder<User>
     */
    protected function getPreQualificationAdvisorBaseQuery(int $onlineStatus)
    {
        return User::query()
            ->select('users.id as user_id')
            ->join('pqa_lead_allocation_config as pqa', 'pqa.user_id', '=', 'users.id')
            ->join('model_has_roles as mhr', 'mhr.model_id', '=', 'users.id')
            ->join('roles as r', 'r.id', '=', 'mhr.role_id')
            ->where('mhr.model_type', User::class)
            ->where('users.status', $onlineStatus)
            ->where(function ($query) {
                $query->whereRaw('pqa.allocation_count < pqa.max_capacity')
                    ->orWhere('pqa.max_capacity', -1);
            })
            ->where('r.name', RolesEnum::PreQualificationAdvisor)
            ->where('pqa.quote_type_id', $this->getPqaQuoteTypeId())
            ->orderByRaw('pqa.last_allocated IS NULL, pqa.last_allocated ASC')
            ->orderBy('pqa.id', 'asc');
    }

    /**
     * @return list<int>
     */
    protected function getOnlineStatusesInOrder(): array
    {
        $statuses = [
            UserStatusEnum::ONLINE,
            UserStatusEnum::OFFLINE,
            UserStatusEnum::UNAVAILABLE,
        ];

        if (! $this->isBusinessHours()) {
            $statuses[] = UserStatusEnum::MANUAL_OFFLINE;
        }

        return $statuses;
    }

    protected function findAvailablePreQualificationAdvisor(): ?User
    {
        foreach ($this->getOnlineStatusesInOrder() as $status) {
            LoggerService::info(self::class.' - searching PQA with status '.$status);

            $record = $this->getPreQualificationAdvisorBaseQuery($status)->first();

            if ($record) {
                return User::query()->find($record->user_id);
            }
        }

        return null;
    }
}
