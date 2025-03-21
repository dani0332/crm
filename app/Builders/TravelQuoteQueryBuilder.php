<?php

namespace App\Builders;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\TravelQuote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TravelQuoteQueryBuilder extends BaseQuoteQueryBuilder
{
    public function __construct()
    {
        parent::__construct(TravelQuote::class);
    }

    public function buildGrid(): Builder
    {
        return $this->baseQuery([
            'id',
            'uuid',
            'code',
            'first_name',
            'last_name',
            'direction_code',
            'coverage_code',
            'policy_number',
            'source',
            'quote_status_id',
            'advisor_id',
            'previous_advisor_id',
            'previous_quote_id',
            'policy_expiry_date',
            'renewal_batch',
            'renewal_import_code',
            'previous_quote_policy_number',
            'previous_quote_policy_premium',
            'device',
            'plan_id',
            'policy_start_date',
            'policy_issuance_date',
            'customer_id',
            'parent_duplicate_quote_id',
            'is_ecommerce',
            'policy_booking_date',
            'insurer_quote_number',
            'policy_issuance_status_id',
            'policy_issuance_status_other',
            'sic_advisor_requested',
            'aml_status',
            'insurance_provider_id',
            'insurer_aml_status',
            'renewal_batch_id',
            'previous_policy_expiry_date',
            'dob',
            'nationality_id',
            'transaction_approved_at',
            'created_at',
            'updated_at',
            'assignment_type',
            'gender',
            'premium'
        ], [
            'nationality:id,country_name',
            'advisor:id,name,email,mobile_no,landline_no',
            'travelQuoteRequestDetail',
            'travelQuoteRequestDetail.lostReason',
            'customer:id,emirates_id_expiry_date,receive_marketing_updates',
            'quoteRequestEntityMapping:id,quote_request_id,entity_id,entity_type_code',
            'quoteRequestEntityMapping.entity:id,code,trade_license_no,company_name,company_address,industry_type_code,emirate_of_registration_id',
            'quotePlan',
            'paymentStatus:id,text',
            'payment:id,paymentable_id,paymentable_type,authorized_at',
            'insuranceProvider:id,text,code',
            'quoteStatus:id,text',
            'plan:id,text',
            'currentlyLocatedIn',
            'renewalBatch:id,name'
        ]);
    }

    public function applyFilters(Builder $query)
    {
        $query
            ->filterBy('code')
            ->matchBy('first_name')
            ->matchBy('last_name')
            ->filterBy('email')
            ->filterBy('mobile_no')
            ->filterBy('policy_number')
            ->filterBy('previous_quote_policy_premium')
            ->filterIn('quote_status', 'quote_status_id')
            ->filterIn('insurance_provider_ids', 'insurance_provider_id')
            ->filterIn('payment_status', 'payment_status_id')
            ->filterIn('renewal_batches', 'renewal_batch_id')
            ->filterIn('insurer_api_status_id')
            ->filterBy('currently_insured_with')
            ->filterBy('is_cold', 'is_cold', 1)
            ->filterByDate('travel_start_date', 'start_date')
            ->filterByDate('next_followup_date')
            ->filterByDate('next_followup_date_end', 'next_followup_date', false)
            ->filterByDate('previous_policy_expiry_date')
            ->filterByDate('previous_policy_expiry_date_end', 'previous_policy_expiry_date', false)
            ->filterByDate('policy_expiry_date', 'previous_policy_expiry_date')
            ->filterByDate('policy_expiry_date_end', 'previous_policy_expiry_date', false)
            ->filterBy('sic_advisor_requested', ignoreAll: true)
            ->filterBy('is_ecommerce', isBool: true)
            ->filterIn('insurer_aml_status')
            ->filterIn('amlStatus')
            ->filterIn('plan_name', 'plan_id')
            ->filterBy('source')
            ->filterByDateRange('transaction_approved_dates', 'transaction_approved_at')
            ->filterByDateRange('advisor_assigned_date')
            ->filterBySegment('segment_filter', QuoteTypeId::Travel)
            ->when(request()->filled('previous_quote_policy_number'), function ($query) {
                $query->where(fn($q) => $q->filtpolicy_expiry_dateerBy('previous_quote_policy_number')->orWhere->filterBy('previous_quote_policy_number', 'policy_number'));
            })
            ->when(Auth::user()->isSpecificTeamAdvisor('Travel'), function ($query) {
                $query->filterBy('advisor_id', Auth::user()->id);
            })
            ->when(request()->filled('is_renewal') && request('is_renewal') == 'Yes', function ($query) {
                $query->whereNotNull('previous_quote_policy_number');
            })
            ->when(request()->filled('is_renewal') && request('is_renewal') == 'No', function ($query) {
                $query->whereNull('previous_quote_policy_number');
            })
            ->when(Auth::user()->can(PermissionsEnum::SEARCH_INSURER_TAX_INVOICE_NUMBER) && request()->filled('insurer_tax_invoice_number'), function ($query) {
                $query->whereRelation('payments', 'insurer_tax_number', request('insurer_tax_invoice_number'));
            })
            ->when(Auth::user()->can(PermissionsEnum::SEARCH_INSURER_COMMISSION_TAX_INVOICE_NUMBER) && request()->filled('insurer_commission_tax_invoice_number'), function ($query) {
                $query->whereRelation('payments', 'insurer_commmission_invoice_number', request('insurer_commission_tax_invoice_number'));
            })
            ->when(
                request()->filled('email') && ! request()->filled('code') && ! request()->filled('first_name') && ! request()->filled('last_name') && ! request()->filled('quote_status_id') && ! request()->filled('mobile_no'),
                fn($q) => $q->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake]),
            )
            ->when($this->shouldApplyDatesFilter() && ! request()->filled('created_at_start'), function ($query) {
                $query->filterByToday();
            })
            ->when($this->shouldApplyDatesFilter() && request()->filled('created_at_start') && request()->filled('created_at_end'), function ($query) {
                $query->whereBetween('created_at', [$this->parseDate(request('created_at_start'), true), $this->parseDate(request('created_at_end'), false)]);
            })
            ->when(request()->filled('created_at') && request()->filled('created_at_end'), function ($query) {
                $query->whereBetween('created_at', [$this->parseDate(request('created_at'), true), $this->parseDate(request('created_at_end'), false)]);
            })
            ->when(request()->filled('last_modified_date'), function ($query) {
                $query->filterByDateRange('last_modified_date', 'updated_at');
            })
            ->when(
                request()->filled('sortBy'),
                fn($q) => $q->orderBy(request('sortBy'), request('sortType')),
                fn($q) => $q->orderBy('created_at', 'DESC'),
            );
    }

    public function processGridData(): Builder
    {
        $query = $this->buildGrid();

        $this->applyFilters($query);

        return $query;
    }
}
