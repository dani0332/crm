<?php

namespace App\Exports;

use App\Services\CRUDService;
use App\Services\HealthQuoteService;
use App\Traits\ExcelExportable;
use Carbon\Carbon;

class HealthQuotesExport
{
    use ExcelExportable;

    private $genderOptions;

    public function __construct()
    {
        $this->genderOptions = app(CRUDService::class)->getGenderOptions();
    }

    public function collection()
    {
        return app(HealthQuoteService::class)->getGridData()->get();
    }

    public function headings(): array
    {
        return [
            'Ref-ID',
            'FIRST NAME',
            'LAST NAME',
            'LEAD STATUS',
            'ADVISOR',
            'ADVISOR EMAIL',
            'WC ADVISOR',
            'CREATED DATE',
            'LAST MODIFIED DATE',
            'HEALTH TEAM TYPE',
            'TRANSAPP CODE',
            'LOST REASON',
            'STARTING FROM',
            'PREMIUM',
            'POLICY NUMBER',
            'SOURCE',
            'LEAD TYPE',
            'SALARY BAND',
            'MEMBER CATEGORY',
            'CURRENTLY INSURED WITH',
            'IS ECOMMERCE',
            'Device',
            'Gender',
            'Nationality',
            'Age Bands',
            'Emirates of Visa',
            'FOR WHOM DO YOU REQUIRE HEALTH INSURANCE?',
            'TYPE OF PLAN',
            'Provider Name',
            'RENEWAL BATCH',
            'PREVIOUS POLICY EXPIRY DATE',
            'PREVIOUS POLICY PREMIUM',
            'PREVIOUS POLICY NUMBER',
            'TRANSACTION APPROVED DATE',
            'BOOKING DATE',
            'PAYMENT STATUS',
            'ADVISOR CAR TEAM(s)',
        ];
    }

    public function map($quote): array
    {
        return [
            $quote->code,
            $quote->first_name,
            $quote->last_name,
            $quote->quoteStatus?->text,
            $quote->advisor?->name,
            $quote->advisor?->email,
            $quote->wcAdvisor?->name,
            date(config('constants.datetime_format'), strtotime($quote->created_at)),
            date(config('constants.datetime_format'), strtotime($quote->updated_at)),
            $quote->health_team_type,
            $quote->healthQuoteRequestDetail?->transapp_code,
            $quote->healthQuoteRequestDetail->lostReason?->text,
            $quote->price_starting_from,
            $quote->premium,
            $quote->policy_number,
            $quote->source,
            $quote->healthLeadType?->text,
            $quote->salaryBand?->text,
            $quote->memberCategory?->text,
            $quote->currentProvider?->text,
            $quote->is_ecommerce ? 'Yes' : 'No',
            $quote->device,
            $this->genderOptions[$quote->gender] ?? '',
            $quote->nationality?->text,
            Carbon::parse($quote->dob)->age,
            $quote->emirate?->text,
            $quote->customer_type,
            $quote->plan?->text,
            $quote->insuranceProvider?->text,
            $quote->renewalBatch?->name,
            $quote->previous_policy_expiry_date_formatted,
            $quote->previous_quote_policy_premium ? $quote->previous_quote_policy_premium : '',
            $quote->previous_quote_policy_number ? $quote->previous_quote_policy_number : '',
            $quote->transaction_approved_at ? date(config('constants.datetime_format'), strtotime($quote->transaction_approved_at)) : '',
            $quote->policy_booking_date ? date(config('constants.datetime_format'), strtotime($quote->policy_booking_date)) : '',
            $quote->payment_status?->payment_status_text ?? 'N/A',
            $quote->CarTeams ?? 'N/A',
        ];
    }
}
