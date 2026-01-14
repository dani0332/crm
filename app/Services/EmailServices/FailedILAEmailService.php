<?php

namespace App\Services\EmailServices;

use App\Enums\ApplicationStorageEnums;
use App\Enums\BusinessTypeOfInsuranceIdEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\WorkflowTypeEnum;
use App\Exports\FailedIlaLeadsExport;
use App\Models\User;
use App\Services\BirdService;
use App\Services\Logger\LoggerService;
use Maatwebsite\Excel\Facades\Excel;
use App\Enums\TiersEnum;

class FailedILAEmailService
{
    protected function getManagerEmailsByQuoteType($quoteType)
    {

        // Use match expression to map quote type to role name for type-safe matching
        // Map QuoteType to static email arrays as per business mapping.
        $emails = match ($quoteType->value) {
            QuoteTypes::CAR->value => [
                'veeral.joshi@insurancemarket.ae',
                'arsalan.khan@insurancemarket.ae',
                'jerin.mathew@insurancemarket.ae',
            ],
            QuoteTypes::HEALTH->value => [
                'murryell.tuppil@insurancemarket.ae',
                'farjad.ahmed@insurancemarket.ae',
                'agatha.alicdan@insurancemarket.ae',
            ],
            QuoteTypes::TRAVEL->value => [
                'ashmy.arackal@insurancemarket.ae',
            ],
            QuoteTypes::LIFE->value, 
            QuoteTypes::SAVINGS->value => [
                'divya.mandke@insurancemarket.ae',
                'komal.rajput@afia.ae',
            ],
            QuoteTypes::HOME->value, 
            QuoteTypes::CORPLINE->value, 
            QuoteTypes::YACHT->value, 
            QuoteTypes::PET->value, 
            QuoteTypes::CYCLE->value => [
                'divya.mandke@insurancemarket.ae',
            ],
            QuoteTypes::GROUP_MEDICAL->value => [
                'rachit.jhamb@insurancemarket.ae',
            ],
            default => [],
        };

        return $emails;

    }

    public function sendFailedIlaEmails(QuoteTypes $quoteType)
    {
        // Fetch leads created today (from midnight to now)
        $managerEmails = $this->getManagerEmailsByQuoteType($quoteType) ?? [];
        if (empty($managerEmails)) {
            LoggerService::warning(self::class." - sendFailedIlaEmails - No managers found for quote type: {$quoteType->value}");

            return;
        }

        $leadsCount = $this->getFailedILALeads($quoteType->value, justCount: true);

        if ($leadsCount === 0) {
            LoggerService::info(self::class." - sendFailedIlaEmails - No failed ILA leads found for quote type: {$quoteType->value}");

            return;
        }

        LoggerService::info(self::class.' - sendFailedIlaEmails - Sending failed ILA emails to managers: '.implode(', ', $managerEmails));
        $birdSendFailedIlaEmailsWorkflow = getAppStorageValueByKey(ApplicationStorageEnums::BIRD_SEND_FAILED_ILA_EMAILS_WORKFLOW, useCache: true);
        if ($birdSendFailedIlaEmailsWorkflow) {
            LoggerService::info(self::class.' - sendFailedIlaEmails - Triggering web hook request for workflow: '.$birdSendFailedIlaEmailsWorkflow);
            app(BirdService::class)->triggerWebHookRequest($birdSendFailedIlaEmailsWorkflow, $this->buildFailedIlaEmailData($quoteType, $managerEmails));
            LoggerService::info(self::class.' - sendFailedIlaEmails - Web hook request triggered successfully');
        } else {
            LoggerService::warning(self::class.' - sendFailedIlaEmails - Workflow not found');
        }
    }

    public function buildFailedIlaEmailData($quoteType, $managerEmails)
    {
        return (object) [
            'managerEmails' => $managerEmails,
            'quoteType' => $quoteType,
            'workflowType' => WorkflowTypeEnum::SEND_FAILED_ILA_EMAILS,
            'dateOfAttempt' => now()->format('Y-m-d'),
            'fileDownloadUrl' => route('export-failed-ila-leads', ['quoteType' => $quoteType]),
        ];
    }

    public function getFailedILALeads($quoteType, bool $justCount = false)
    {
        switch ($quoteType) {
            case QuoteTypes::CAR->value:
                $leads = $this->getCarFailedILALeads($justCount);
                break;
            case QuoteTypes::BIKE->value:
                $leads = $this->getPersonalFailedILALeads(QuoteTypes::BIKE, $justCount);
                break;
            case QuoteTypes::HEALTH->value:
                $leads = $this->getHealthFailedILALeads($justCount);
                break;
            case QuoteTypes::LIFE->value:
                $leads = $this->getPersonalFailedILALeads(QuoteTypes::LIFE, $justCount);
                break;
            case QuoteTypes::TRAVEL->value:
                $leads = $this->getTravelFailedILALeads($justCount);
                break;
            case QuoteTypes::HOME->value:
                $leads = $this->getPersonalFailedILALeads(QuoteTypes::HOME, $justCount);
                break;
            case QuoteTypes::PET->value:
                $leads = $this->getPersonalFailedILALeads(QuoteTypes::PET, $justCount);
                break;
            case QuoteTypes::CYCLE->value:
                $leads = $this->getPersonalFailedILALeads(QuoteTypes::CYCLE, $justCount);
                break;
            case QuoteTypes::SAVINGS->value:
                $leads = $this->getPersonalFailedILALeads(QuoteTypes::SAVINGS, $justCount);
                break;
            case QuoteTypes::GROUP_MEDICAL->value:
                $leads = $this->getBusinessFailedILALeads(BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL, $justCount);
                break;
            case QuoteTypes::CORPLINE->value:
                $leads = $this->getBusinessFailedILALeads(justCount: $justCount);
                break;
            case QuoteTypes::YACHT->value:
                $leads = $this->getPersonalFailedILALeads(QuoteTypes::YACHT, $justCount);
                break;
            case QuoteTypes::JETSKI->value:
                $leads = $this->getPersonalFailedILALeads(QuoteTypes::JETSKI, $justCount);
                break;

            default:
                $leads = $justCount ? 0 : collect();
                break;
        }

        return $leads;
    }

    private function getBaseQuery(QuoteTypes $quoteType)
    {
        return $quoteType->model()
            ->when(
                $quoteType === QuoteTypes::HEALTH,
                fn ($query) => $query->whereBetween('created_at', [now()->subMonths(3)->startOfDay(), now()->endOfDay()])
            )
            ->when(
                $quoteType === QuoteTypes::CAR,
                fn ($query) => $query->whereBetween('created_at', [now()->startOfDay(), now()->subMinutes(2)->toDateTimeString()])
            )
            ->when(
                !in_array($quoteType, [QuoteTypes::CAR, QuoteTypes::HEALTH], true),
                fn ($query) => $query->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])
            )
            ->whereNull('advisor_id')
            ->whereNotNull('lead_allocation_failed_at')
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate, QuoteStatusEnum::Lost])
            ->whereNotIn('source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD, LeadSourceEnum::INSLY, LeadSourceEnum::REVIVAL])
            ->with('quoteStatus');
    }

    public function getBusinessFailedILALeads($businessTypeOfInsuranceId = null, bool $justCount = false)
    {
        return $this->getBaseQuery(QuoteTypes::BUSINESS)
            ->select('id', 'code', 'uuid', 'first_name', 'last_name', 'created_at', 'quote_status_id')
            ->when($businessTypeOfInsuranceId, function ($query) use ($businessTypeOfInsuranceId) {
                $query->where('business_type_of_insurance_id', $businessTypeOfInsuranceId);
            })
            ->when(! $businessTypeOfInsuranceId, function ($query) {
                $query->whereNotIn('business_type_of_insurance_id', [BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL]);
            })
            ->when(
                $justCount,
                fn ($query) => $query->count(),
                fn ($query) => $query->get(),
            );
    }

    public function getHealthFailedILALeads(bool $justCount = false)
    {
       
        return $this->getBaseQuery(QuoteTypes::HEALTH)
            ->select('id', 'code', 'uuid', 'first_name', 'last_name', 'created_at', 'quote_status_id', 'paid_at', 'lead_allocation_failed_at')
            ->whereNotNull('price_starting_from')
            ->where(function ($query) {
                $query->where(function ($ecomQuery) {
                        // SIC leads with advisor_requested = Yes
                        $ecomQuery->where('sic_advisor_requested', 1)
                            // OR Non-SIC Ecom inquiry leads (no SIC tag in quote_tags)
                            ->orWhere(function ($nonSicQuery) {
                                $nonSicQuery->isNonSICLead(QuoteTypes::HEALTH);
                            })
                            // OR PEC marked with Plan selected
                            ->orWhere(function ($pecQuery) {
                                $pecQuery->hasPecTag()
                                    ->whereNotNull('plan_id');
                            })
                            // OR Clicked proceed with application (payment link requested or authorized)
                            ->orWhereIn('quote_status_id', [
                                QuoteStatusEnum::ApplicationPending,
                                QuoteStatusEnum::PaymentLinkRequestedByCustomer,
                            ]);
                    })
                    // OR REVIVAL leads (replied to OCB email or source = REVIVAL_REPLIED)
                    ->orWhere('source', LeadSourceEnum::REVIVAL_REPLIED);
            })
            ->when(
                $justCount,
                fn ($query) => $query->count(),
                fn ($query) => $query->get(),
            );
    }

    public function getTravelFailedILALeads(bool $justCount = false)
    {
        return $this->getBaseQuery(QuoteTypes::TRAVEL)
            ->select('id', 'code', 'uuid', 'first_name', 'last_name', 'created_at', 'quote_status_id', 'paid_at', 'lead_allocation_failed_at')
            ->isNonSICLead(QuoteTypes::TRAVEL)
            ->when(
                $justCount,
                fn ($query) => $query->count(),
                fn ($query) => $query->get(),
            );
    }

    public function getPersonalFailedILALeads(QuoteTypes $quoteType, bool $justCount = false)
    {
        return $this->getBaseQuery($quoteType)
            ->where('quote_type_id', $quoteType->id())
            ->isNonSICLead($quoteType)
            ->select('id', 'code', 'uuid', 'first_name', 'last_name', 'created_at', 'quote_status_id', 'paid_at', 'lead_allocation_failed_at')
            ->when(
                $justCount,
                fn ($query) => $query->count(),
                fn ($query) => $query->get(),
            );
    }

    public function getCarFailedILALeads(bool $justCount = false)
    {
        return $this->getBaseQuery(QuoteTypes::CAR)
        ->leftJoin('tiers', 'tiers.id', 'car_quote_request.tier_id')
        ->where('tiers.name', '!=', TiersEnum::TIER_R)
        ->whereNotIn('car_quote_request.uuid', function ($query) { // to remove from the query tags table to exlude SIC records from the result set
            $query->distinct()
                ->select('quote_uuid')
                ->from('quote_tags')
                ->join('quote_type', 'quote_type.id', 'quote_tags.quote_type_id')
                ->where('quote_tags.name', 'SIC')
                ->where('quote_type.code', QuoteTypes::CAR->value);
        })
        ->select('id', 'code', 'uuid', 'first_name', 'last_name', 'created_at', 'quote_status_id', 'paid_at', 'lead_allocation_failed_at')
        ->when(
            $justCount,
            fn ($query) => $query->count(),
            fn ($query) => $query->get(),
        );
    }

    public function exportFailedIlaLeads($quoteType)
    {
        $leads = $this->getFailedILALeads($quoteType);
        $totalLeads = count($leads);
        LoggerService::info(self::class.' - exportFailedIlaLeads - Total leads: '.$totalLeads);

        $fileName = now()->format('Y-m-d_H-i-s').'-failed_ila_leads.xlsx';
        $export = new FailedIlaLeadsExport($leads);

        // Return both the streamed file response and total leads as array, following consistent API structure
        return [
            'file' => Excel::download($export, $fileName),
            'total_leads' => $totalLeads,
        ];
    }
}
