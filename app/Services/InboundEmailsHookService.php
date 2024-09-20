<?php

namespace App\Services;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Factories\AllocationFactory;
use App\Models\CarQuote;
use App\Models\DttRevival;
use App\Models\TravelQuote;
use Exception;
use Illuminate\Http\Response;

//Scheduled to delete 1st April 2024
class InboundEmailsHookService extends BaseService
{
    private function verifyAuthorization()
    {
        $authUser = config('constants.INBOUND_WEBHOOK_BASIC_AUTH_USER_NAME');
        $authPass = config('constants.INBOUND_WEBHOOK_BASIC_AUTH_PASSWORD');

        if (request('basicAuthUsername') === $authUser && request('basicAuthPassword') === $authPass) {
            info(self::class.' - verifyAuthorization: Authorized Access');

            return true;
        }

        return false;
    }

    public function process()
    {
        try {
            info(self::class.' - process: Webhook Received - Verifying Auth...');

            if (! $this->verifyAuthorization()) {
                info(self::class.' - process: Unauthorized Access');

                return apiResponse([], Response::HTTP_UNAUTHORIZED, 'Unauthorized Access');
            }

            $inbound = new \Postmark\Inbound(file_get_contents('php://input'));

            $subject = $inbound->Subject();
            info(self::class." - process: Webhook Received with Subject: {$subject}");

            $data = getQuoteUsingSubject($subject);

            return $this->resolveLead($subject, $data);
        } catch (Exception $e) {
            info(self::class.' - process: Exception occurred', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return apiResponse([], Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    private function resolveLead($subject, $data)
    {
        if ($data) {
            [$quoteType, $uuid] = $data;

            $lead = $quoteType->model()::where('uuid', $uuid)->first();

            if ($lead) {
                return match ($quoteType) {
                    QuoteTypes::CAR => $this->handleCar($lead),
                    QuoteTypes::TRAVEL => $this->handleTravel($lead),
                    default => apiResponse([], Response::HTTP_UNPROCESSABLE_ENTITY, "Unhandled quote type: {$quoteType->value}")
                };
            }

            info(self::class." - resolveLead: Lead not found for uuid: {$uuid}");

            return apiResponse([], Response::HTTP_NOT_FOUND, "Lead not found for uuid: {$uuid}");
        }

        info(self::class." - resolveLead: uuid not found in subject: {$subject}");

        return apiResponse([], Response::HTTP_NOT_FOUND, "UUID & Quote Type could not be extracted from subject: {$subject}");
    }

    private function handleCar(CarQuote $lead)
    {
        if ($lead->source == LeadSourceEnum::REVIVAL) {
            info(self::class." - handleCar: Going to update Car Quote for uuid {$lead->uuid}");
            $lead->update(['source' => LeadSourceEnum::REVIVAL_REPLIED]);
            DttRevival::where('uuid', $lead->uuid)->update(['reply_received' => 1]);
            info(self::class." - handleCar: Car Quote Source updated for Revival for uuid {$lead->uuid}");

            return apiResponse([], Response::HTTP_OK, 'Car Source Updated Successfully!');
        } else {
            try {
                info(self::class." - handleCar: Going to handle Car Quote for uuid {$lead->uuid}");
                (new ApiService)->sicReplyToILA($lead);

                return apiResponse([], Response::HTTP_OK, 'Car Handled for SIC to ILA Successfully!');
            } catch (\Exception $e) {
                info(self::class." - handleCar: Error occurred in SIC Reply to ILA for uuid {$lead->uuid}");

                return apiResponse([], Response::HTTP_INTERNAL_SERVER_ERROR, 'Something went wrong!');
            }
        }
    }

    private function handleTravel(TravelQuote $lead)
    {
        info(self::class." - handleTravel: Going to Assign Advisor to uuid: {$lead->uuid}");

        if ($lead->advisor_id) {
            info(self::class." - handleTravel: Lead already has an advisor assigned: {$lead->uuid}");

            return apiResponse([], Response::HTTP_OK, 'Lead already has an advisor assigned!');
        }

        info(self::class." - handleTravel: AllocationFactory Strategy Executing for lead: {$lead->uuid}");
        $allocationStrategy = AllocationFactory::createStrategy(QuoteTypeId::Travel, $lead->uuid);
        $assignedAdvisorId = $allocationStrategy->executeSteps();
        info(self::class." - handleTravel: AllocationStrategy Executed for lead: {$lead->uuid} and assignedAdvisorId: {$assignedAdvisorId}");

        return apiResponse([], Response::HTTP_OK, 'Lead Assigned to Advisor Successfully!');
    }
}
