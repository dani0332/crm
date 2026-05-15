<?php

declare(strict_types=1);

namespace App\Services\PqaAllocation;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteTypes;
use App\Http\Requests\PqaAllocationRequest;
use App\Models\BusinessQuote;
use App\Models\User;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use App\Strategies\Allocations\PreQualificationAdvisorAllocation;
use Exception;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class PqaAllocationService
{
    public function processPqaAllocation(PqaAllocationRequest $request): JsonResponse
    {
        $quoteTypeId = (int) $request->input('quoteTypeId');
        $quoteUuid = (string) $request->input('quoteUUID');
        $reAssignPqaAdvisor = (bool) $request->input('reAssignPqaAdvisor', false);

        $lead = QuoteTypes::getName($quoteTypeId)?->model()?->where('uuid', $quoteUuid)?->first();

        if ($lead instanceof BusinessQuote) {
            LoggerService::startQuoteLogging($lead, LoggerFeatureEnum::ALLOCATION);
        }

        LoggerService::info(self::class.' - PQA allocation started for '.$quoteUuid);

        $responsePayload = $this->executeAllocation($quoteUuid, $reAssignPqaAdvisor);

        LoggerService::info(self::class.' - PQA allocation ended for '.$quoteUuid);

        return apiResponse($responsePayload['data'], Response::HTTP_OK, $responsePayload['message']);
    }

    /**
     * @return array{data: array<string, mixed>, message: string}
     */
    public function executeAllocation(string $quoteUuid, bool $overrideAdvisorId = false): array
    {
        $response = (new PreQualificationAdvisorAllocation($quoteUuid, $overrideAdvisorId))->execute();

        $status = $response['status'] ?? Response::HTTP_INTERNAL_SERVER_ERROR;
        $message = $response['message'] ?? '';

        if (($response['assignedPqaAdvisorId'] ?? 0) === 0) {
            $message = $message !== '' ? $message : 'Pre Qualification Advisor not found';
        }

        return [
            'data' => [
                'assignedPqaAdvisorId' => $response['assignedPqaAdvisorId'] ?? 0,
                'pqaAdvisorName' => $response['pqaAdvisorName'] ?? null,
                'pqaAdvisorEmail' => $response['pqaAdvisorEmail'] ?? null,
                'pqaAdvisorPhone' => $response['pqaAdvisorPhone'] ?? null,
                'pqaAdvisorLandLine' => $response['pqaAdvisorLandLine'] ?? null,
                'status' => $status,
            ],
            'message' => $message,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveAllocationResponse(AllocationRequest $request, ?Exception $exception = null): array
    {
        $this->releaseAllocationLock($request);

        $lead = $request->getLead();

        $isAllocated = $request->isAllocated();
        $isSameAdvisor = $request->isSameAdvisor();
        $isAlreadyAssigned = ! empty($lead?->pq_advisor_id);

        if ($isAllocated || $isSameAdvisor || $isAlreadyAssigned) {
            $advisor = $request->getAdvisor() ?? ($lead?->pq_advisor_id ? User::query()->find($lead->pq_advisor_id) : null);

            if ($isAllocated) {
                $message = 'Pre Qualification Advisor assigned successfully!';
            } elseif ($isSameAdvisor) {
                $message = 'Pre Qualification Advisor already assigned to this lead';
            } else {
                $message = 'Pre Qualification Advisor already assigned';
            }

            $landLine = ! empty($advisor?->landline_no) ? formatLandlineDisplay($advisor->landline_no) : '';
            $whatsAppNumber = ! empty($advisor?->mobile_no) ? formatMobileNo($advisor->mobile_no) : '';

            return [
                'assignedPqaAdvisorId' => $advisor?->id ?? $lead?->pq_advisor_id,
                'pqaAdvisorName' => $advisor?->name,
                'pqaAdvisorEmail' => $advisor?->email,
                'pqaAdvisorPhone' => $whatsAppNumber,
                'pqaAdvisorLandLine' => $landLine,
                'message' => $message,
                'status' => Response::HTTP_OK,
            ];
        }

        return [
            'assignedPqaAdvisorId' => 0,
            'message' => $exception ? $exception->getMessage() : 'PQA allocation failed',
            'status' => $exception ? $exception->getCode() : Response::HTTP_INTERNAL_SERVER_ERROR,
        ];
    }

    private function releaseAllocationLock(AllocationRequest $request): void
    {
        $lock = $request->get('pqa_allocation_lock');

        if ($lock instanceof Lock) {
            $lock->release();
        }
    }
}
