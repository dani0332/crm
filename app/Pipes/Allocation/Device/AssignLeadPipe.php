<?php

namespace App\Pipes\Allocation\Device;

use App\Models\PersonalQuoteDetail;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;

class AssignLeadPipe extends BaseAllocationPipe
{
    public function handle(AllocationRequest $request, Closure $next)
    {
        LoggerService::info(self::class.' - Starting Device lead assignment process');

        $this->setRequest($request);

        $advisor = $this->allocationRequest->getAdvisor();

        if (! $advisor) {
            LoggerService::info(self::class.' - No advisor available in AssignLeadPipe - cannot proceed with assignment');
            $this->allocationRequest->markAsFailed();
            $this->throw('Advisor not found', self::OK);
        }

        $this->assign();

        LoggerService::info(self::class.' - Device lead assigned successfully', extra: [
            'leadUuid' => $this->lead->uuid,
            'advisorId' => $advisor->id,
            'advisorName' => $advisor->name,
            'advisorEmail' => $advisor->email,
        ]);

        return $next($request);
    }

    protected function updateQuoteDetail()
    {
        LoggerService::info(self::class.' - About to update device quote detail record');

        $quoteDetail = PersonalQuoteDetail::where('personal_quote_id', $this->lead->id)->first();
        $oldAdvisorAssignedDate = $quoteDetail?->advisor_assigned_date ?? '';
        $this->upsertQuoteDetail($this->lead->id, PersonalQuoteDetail::class, 'personal_quote_id');

        return $oldAdvisorAssignedDate;
    }
}
