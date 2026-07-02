<?php

namespace App\Services\EmailServices;

use App\Enums\ApplicationStorageEnums;
use App\Enums\BusinessTypeOfInsuranceIdEnum;
use App\Enums\EnvEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\TiersEnum;
use App\Enums\WorkflowTypeEnum;
use App\Exports\FailedIlaLeadsExport;
use App\Services\Logger\LoggerService;
use Maatwebsite\Excel\Facades\Excel;

class FailedILAEmailService
{
    protected function getManagerEmailsByQuoteType($quoteType)
    {
        $storageKey = match ($quoteType->value) {
            QuoteTypes::CAR->value => ApplicationStorageEnums::FAILED_ILA_MANAGERS_CAR,
            QuoteTypes::JETSKI->value => ApplicationStorageEnums::FAILED_ILA_MANAGERS_JETSKI,
            QuoteTypes::BIKE->value => ApplicationStorageEnums::FAILED_ILA_MANAGERS_BIKE,
            QuoteTypes::HEALTH->value => ApplicationStorageEnums::FAILED_ILA_MANAGERS_HEALTH,
            QuoteTypes::TRAVEL->value => ApplicationStorageEnums::FAILED_ILA_MANAGERS_TRAVEL,
            QuoteTypes::LIFE->value => ApplicationStorageEnums::FAILED_ILA_MANAGERS_LIFE,
            QuoteTypes::SAVINGS->value => ApplicationStorageEnums::FAILED_ILA_MANAGERS_SAVINGS,
            QuoteTypes::HOME->value => ApplicationStorageEnums::FAILED_ILA_MANAGERS_HOME,
            QuoteTypes::CORPLINE->value => ApplicationStorageEnums::FAILED_ILA_MANAGERS_CORPLINE,
            QuoteTypes::YACHT->value => ApplicationStorageEnums::FAILED_ILA_MANAGERS_YACHT,
            QuoteTypes::PET->value => ApplicationStorageEnums::FAILED_ILA_MANAGERS_PET,
            QuoteTypes::CYCLE->value => ApplicationStorageEnums::FAILED_ILA_MANAGERS_CYCLE,
            QuoteTypes::GROUP_MEDICAL->value => ApplicationStorageEnums::FAILED_ILA_MANAGERS_GROUP_MEDICAL,
            default => [],
        };

        $emails = [];
        if ($storageKey) {
            // Fetch from app storage, expect a comma-separated list
            $emailsString = getAppStorageValueByKey($storageKey, useCache: true);
            if ($emailsString) {
                $emails = array_values(array_filter(array_map('trim', explode(',', $emailsString))));
            }
        }

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

        LoggerService::info(self::class." - sendFailedIlaEmails - Failed ILA leads count: {$leadsCount} for quote type: {$quoteType->value}");
        if ($leadsCount === 0) {
            LoggerService::info(self::class." - sendFailedIlaEmails - No failed ILA leads found for quote type: {$quoteType->value}");

            return;
        }

        LoggerService::info(self::class.' - sendFailedIlaEmails - Sending failed ILA emails to managers: '.implode(', ', $managerEmails));
        app(WebEngageService::class)->sendEvent(WorkflowTypeEnum::SEND_FAILED_ILA_EMAILS, $this->buildFailedIlaEmailData($quoteType, $managerEmails, $leadsCount));
        LoggerService::info(self::class.' - sendFailedIlaEmails - WebEngage event triggered successfully');
    }

    public function buildFailedIlaEmailData($quoteType, $managerEmails, $leadsCount = null): array
    {
        $primaryEmail = count($managerEmails) > 0 ? $managerEmails[0] : '';

        return [
            'customerId' => $primaryEmail,
            'firstName' => '',
            'lastName' => '',
            'customerEmail' => $primaryEmail,
            'customerMobile' => '',
            'leadsCount' => $leadsCount,
            'quoteUID' => '',
            // The first email is advisor, the rest are managers.
            'advisorEmail' => $primaryEmail ?: null,
            'managerEmails' => count($managerEmails) > 0 ? array_slice($managerEmails, 1) : [],
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
        $model = $quoteType->model();
        $tableName = $model->getTable();

        return $model
            ->when(
                $quoteType === QuoteTypes::HEALTH,
                fn ($query) => $query->whereBetween("{$tableName}.created_at", [now()->subMonths(3)->startOfDay(), now()->endOfDay()])

            )
            ->when(
                $quoteType === QuoteTypes::CAR,
                fn ($query) => $query->whereBetween("{$tableName}.created_at", [now()->startOfDay(), now()->subMinutes(2)->toDateTimeString()])
            )
            ->when(
                ! in_array($quoteType, [QuoteTypes::CAR, QuoteTypes::HEALTH], true),
                fn ($query) => $query->whereBetween("{$tableName}.created_at", [now()->startOfDay(), now()->endOfDay()])
            )
            ->whereNull("{$tableName}.advisor_id")
            ->whereNotIn("{$tableName}.quote_status_id", [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate, QuoteStatusEnum::Lost])
            ->whereNotIn("{$tableName}.source", [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD, LeadSourceEnum::INSLY, LeadSourceEnum::REVIVAL])
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
                $query->whereNotIn('business_type_of_insurance_id', [BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL])
                    ->whereNotNull('health_plan_type_id')
                    ->whereNotNull('number_of_employees');
            })
            ->when(
                $justCount,
                fn ($query) => $query->count(),
                fn ($query) => $query->get(),
            );
    }

    public function getHealthFailedILALeads(bool $justCount = false)
    {
        // Allowed ECOM sources (insurancemarket.ae variants, CALL_DESK, INSURANCE_WALLET)
        $ecomSources = [
            LeadSourceEnum::INSURANCE_MARKET,
            LeadSourceEnum::CALL_DESK,
            LeadSourceEnum::INSURANCE_WALLET,
        ];

        return $this->getBaseQuery(QuoteTypes::HEALTH)
            ->select('id', 'code', 'uuid', 'first_name', 'last_name', 'created_at', 'quote_status_id', 'paid_at', 'lead_allocation_failed_at')
            ->whereNotNull('price_starting_from')
            ->where(function ($query) use ($ecomSources) {
                // ECOM leads criteria
                $query->where(function ($ecomQuery) use ($ecomSources) {
                    $ecomQuery->where(function ($sourceQuery) use ($ecomSources) {
                        // Match exact sources or environment-based source (insurancemarket.ae for prod, alfred.ae for others)
                        $sourceQuery->whereIn('source', $ecomSources)
                            ->orWhere('source', 'LIKE', '%'.(config('constants.APP_ENV') == EnvEnum::PRODUCTION ? LeadSourceEnum::INSURANCE_MARKET : LeadSourceEnum::ALFRED_AE).'%');
                    })
                        ->where(function ($ecomCriteria) {
                            // SIC leads with advisor_requested = Yes
                            $ecomCriteria->where(function ($sicQuery) {
                                $sicQuery->where('sic_advisor_requested', 1);
                            })
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
                        });
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
            ->when(
                $quoteType === QuoteTypes::HOME,
                fn ($query) => $query->whereNotIn('source', [LeadSourceEnum::REVIVAL_SHORT, LeadSourceEnum::REVIVAL_ANNUAL, LeadSourceEnum::REVIVAL_REPLIED, LeadSourceEnum::REVIVAL_PAID]),
            )
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
            ->select('car_quote_request.id', 'car_quote_request.code', 'car_quote_request.uuid', 'car_quote_request.first_name', 'car_quote_request.last_name', 'car_quote_request.created_at', 'car_quote_request.quote_status_id', 'car_quote_request.paid_at', 'car_quote_request.lead_allocation_failed_at')
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
