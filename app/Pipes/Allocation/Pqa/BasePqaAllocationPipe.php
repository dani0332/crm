<?php

declare(strict_types=1);

namespace App\Pipes\Allocation\Pqa;

use App\Enums\BusinessTypeOfInsuranceIdEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\TeamTypeEnum;
use App\Enums\UserStatusEnum;
use App\Exceptions\Allocation\AllocationException;
use App\Models\User;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\AllocationService;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;

abstract class BasePqaAllocationPipe extends AllocationService
{
    public const NOT_FOUND = Response::HTTP_NOT_FOUND;
    public const OK = Response::HTTP_OK;
    public const SERVER_ERROR = Response::HTTP_INTERNAL_SERVER_ERROR;

    protected AllocationRequest $allocationRequest;
    protected ?Model $lead = null;

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

    protected function getPqaQuoteTypeId(): int
    {
        return (int) $this->allocationRequest->getQuoteType()->id();
    }

    /**
     * @return Builder<User>
     */
    protected function getPreQualificationAdvisorBaseQuery(int $onlineStatus)
    {
        $productType = TeamTypeEnum::PRODUCT;
        $corplineName = QuoteTypes::CORPLINE->value;
        $groupMedicalName = QuoteTypes::GROUP_MEDICAL->value;
        $businessQuoteTypeId = (int) QuoteTypes::BUSINESS->id();

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
            ->when(
                $this->getPqaQuoteTypeId() === $businessQuoteTypeId && $this->lead !== null,
                function ($query) use ($productType, $corplineName, $groupMedicalName) {
                    $productName = $this->lead->business_type_of_insurance_id == BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL
                        ? $groupMedicalName
                        : $corplineName;

                    $query->whereExists(function ($sub) use ($productType, $productName) {
                        $sub->selectRaw('1')
                            ->from('user_products as up_m')
                            ->join('teams as t_m', 't_m.id', '=', 'up_m.product_id')
                            ->whereColumn('up_m.user_id', 'users.id')
                            ->where('t_m.type', $productType)
                            ->whereRaw("UPPER(t_m.name) = UPPER('{$productName}')");
                    });
                },
                function ($query) use ($productType, $corplineName, $groupMedicalName, $businessQuoteTypeId) {
                    $query->whereExists(function ($sub) use ($productType, $corplineName, $groupMedicalName, $businessQuoteTypeId) {
                        $sub->selectRaw('1')
                            ->from('user_products as up_m')
                            ->join('teams as t_m', 't_m.id', '=', 'up_m.product_id')
                            ->whereColumn('up_m.user_id', 'users.id')
                            ->where('t_m.type', $productType)
                            ->whereRaw("(
                                (UPPER(t_m.name) IN (UPPER('{$corplineName}'), UPPER('{$groupMedicalName}')) AND pqa.quote_type_id = {$businessQuoteTypeId})
                                OR
                                (UPPER(t_m.name) NOT IN (UPPER('{$corplineName}'), UPPER('{$groupMedicalName}')) AND pqa.quote_type_id = (
                                    SELECT qt_m.id FROM quote_type qt_m WHERE UPPER(qt_m.code) = UPPER(t_m.name) LIMIT 1
                                ))
                            )");
                    });
                }
            )
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

            $query = $this->getPreQualificationAdvisorBaseQuery($status);

            LoggerService::info(self::class.' - PQA query: '.$query->toRawSql());

            $record = $query->first();

            if ($record) {
                return User::query()->find($record->user_id);
            }
        }

        return null;
    }
}
