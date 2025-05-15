<?php

namespace App\Pipes\Allocation\Car;

use App\Enums\AssignmentTypeEnum;
use App\Enums\LeadSourceEnum;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\CarAllocationService;
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

        $lead = $this->allocationRequest->getLead();
        $advisor = $this->allocationRequest->getAdvisor();
        $tier = $this->allocationRequest->get('tier');

        DB::beginTransaction();

        try {
            $carAllocationService = app(CarAllocationService::class);
            $carAllocationService->processLeadAssignment(
                $lead,
                $advisor->id,
                $tier,
                AssignmentTypeEnum::SYSTEM_ASSIGNED
            );

            // Send WhatsApp notification for non-renewal leads
            if ($lead->source != LeadSourceEnum::RENEWAL_UPLOAD) {
                app(SendEmailCustomerService::class)->sendWhatsappNotificationToCustomer($lead, $advisor->id);
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
}
