<?php

namespace App\Pipes\Allocation\Car;

use App\Enums\LeadSourceEnum;
use App\Models\CarQuoteRequestDetail;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use App\Services\SendEmailCustomerService;
use Closure;

class AssignLeadPipe extends BaseAllocationPipe
{
    /**
     * Handle the incoming request.
     */
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        if ($this->allocationRequest->get('isAdvisorAlreadyAssigned')) {
            $this->allocationRequest->markAsAllocated();

            return $next($request);
        }

        $this->assign(function () {
            $this->sendWhatsappNotificationToCustomer();
        });

        return $next($request);
    }

    private function sendWhatsappNotificationToCustomer()
    {
        $advisor = $this->allocationRequest->getAdvisor();

        if ($this->lead->source != LeadSourceEnum::RENEWAL_UPLOAD && ! $advisor->isAi()) {
            app(SendEmailCustomerService::class)->sendWhatsappNotificationToCustomer($this->lead, $advisor->id);
        }
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
