<?php

namespace App\Pipes\Allocation\Car;

use App\Enums\LeadSourceEnum;
use App\Models\CarQuoteRequestDetail;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use App\Services\SendEmailCustomerService;
use Closure;
use Illuminate\Support\Facades\DB;

class AssignLeadPipe extends BaseAllocationPipe
{
    /**
     * Handle the incoming request.
     */
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        $advisor = $this->allocationRequest->getAdvisor();

        DB::beginTransaction();

        try {
            $this->assign();

            // Send WhatsApp notification for non-renewal leads
            if ($this->lead->source != LeadSourceEnum::RENEWAL_UPLOAD) {
                app(SendEmailCustomerService::class)->sendWhatsappNotificationToCustomer($this->lead, $advisor->id);
            }

            $this->allocationRequest->markAsAllocated();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            LoggerService::error($e->getMessage(), exception: $e);
            $this->allocationRequest->markAsFailed();
            $this->throw('Lead allocation failed: '.$e->getMessage(), self::SERVER_ERROR);
        }

        return $next($request);
    }

    protected function updateQuoteDetail()
    {
        // Log information about the update operation.
        LoggerService::info('About to update car quote detail record');

        $carQuoteDetail = CarQuoteRequestDetail::where('car_quote_request_id', $this->lead->id)->first();
        $oldAdvisorAssignedDate = $carQuoteDetail->advisor_assigned_date ?? '';
        $this->upsertQuoteDetail($this->lead->id, CarQuoteRequestDetail::class, 'car_quote_request_id');

        // Return the old advisor assigned date, if applicable.
        return $oldAdvisorAssignedDate;
    }
}
