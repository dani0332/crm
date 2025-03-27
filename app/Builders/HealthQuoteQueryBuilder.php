<?php

namespace App\Builders;

use App\Enums\DefaultAdvisorEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\HealthQuote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class HealthQuoteQueryBuilder extends BaseQuoteQueryBuilder
{
    public function __construct()
    {
        parent::__construct(HealthQuote::class);
    }

    public function buildGrid(): Builder
    {
        return $this->baseQuery([
            'health_quote_request.id',
            'uuid',
            'health_quote_request.code',
            'first_name',
            'last_name',
            'source',
            'health_team_type',
            'premium',
            'policy_number',
            'marital_status_id',
            'quote_status_id',
            'advisor_id',
            'previous_advisor_id',
            'lead_type_id',
            'previous_quote_id',
            'salary_band_id',
            'member_category_id',
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
            'currently_insured_with_id',
            'parent_duplicate_quote_id',
            'is_ecommerce',
            'price_starting_from',
            'policy_booking_date',
            'insurer_quote_number',
            'policy_issuance_status_id',
            'policy_issuance_status_other',
            'stale_at',
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
        ], [
            'maritalStatus:id,text',
            'healthCoverFor:id,text',
            'nationality:id,text',
            'emirate:id,text',
            'advisor:id,name,email,mobile_no,landline_no',
            'previousAdvisor:id,name',
            'healthQuoteRequestDetail:id,health_quote_request_id,next_followup_date,transapp_code,notes,insly_id,lost_reason_id',
            'healthLeadType:id,text',
            'healthQuoteRequestDetail.lostReason:id,text',
            'salaryBand:id,text',
            'memberCategory:id,text',
            'renewalBatch:id,name',
            'insured:id,first_name,last_name',
            'customer:id,emirates_id_expiry_date,receive_marketing_updates',
            'quoteRequestEntityMapping:id,quote_request_id,entity_id,entity_type_code',
            'quoteRequestEntityMapping.entity:id,code,trade_license_no,company_name,company_address,industry_type_code,emirate_of_registration_id',
            'quotePlan',
            'paymentStatus:id,text',
            'payment:id,paymentable_id,paymentable_type,authorized_at',
            'insuranceProvider:id,text,code',
            'quoteStatus:id,text',
            'wcAdvisor:id,name',
            'memberCategory:id,text',
            'insuranceProvider:id,text',
            'plan:id,text',
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
            ->filterBy('sub_team', 'health_team_type')
            ->filterIn('quote_status', 'quote_status_id')
            ->filterIn('payment_status', 'payment_status_id')
            ->filterIn('renewal_batches', 'renewal_batch_id')
            ->filterBy('currently_insured_with')
            ->filterBy('is_cold', 'is_cold', 1)
            ->filterByDate('next_followup_date')
            ->filterByDate('next_followup_date_end', 'next_followup_date', false)
            ->filterByAdvisors(request('advisor_id'))
            ->filterBy('assignment_type', ignoreAll: true)
            ->filterBy('sic_advisor_requested', ignoreAll: true)
            ->filterBy('is_ecommerce', isBool: true)
            ->filterIn('insurer_aml_status')
            ->filterByDateRange('transaction_approved_dates', 'transaction_approved_at')
            ->filterBySegment('segment_filter', QuoteTypeId::Health)
            ->filterByPaymentDueDates('payment_due_date')
            ->filterByDateRange('booking_date', 'policy_booking_date')
            ->filterByAdvisorAssignedDates('healthQuoteRequestDetail', ['assigned_to_date_start', 'assigned_to_date_end'], verifyQuoteStatus: true)
            ->filterByDateRange('last_modified_date', 'updated_at')
            ->when(request()->filled('previous_quote_policy_number'), function ($query) {
                $query->where(fn($q) => $q->filterBy('previous_quote_policy_number')->orWhere->filterBy('previous_quote_policy_number', 'policy_number'));
            })
            ->when(request()->filled('policy_expiry_date') && request()->filled('policy_expiry_date_end'), function ($query) {
                $query->where(fn($q) => $q->filterByDate('policy_expiry_date')
                    ->filterByDate('previous_policy_expiry_date', 'policy_expiry_date_end', false));
            })
            ->when(Auth::user()->isSpecificTeamAdvisor('Health') || Auth::user()->isSpecificTeamAdvisor('EBP') || Auth::user()->isSpecificTeamAdvisor('RM'), function ($query) {
                $query->filterBy('advisor_id', Auth::user()->id);
            })
            ->when(is_array(request('advisors')) && in_array(DefaultAdvisorEnum::UNASSIGNED, request('advisors')), function ($query) {
                $query->whereNull('advisor_id');
            })
            ->when(is_array(request('advisors')) && ! in_array(DefaultAdvisorEnum::UNASSIGNED, request('advisors')), function ($query) {
                $query->filterIn('advisors', 'advisor_id');
            })
            ->when(request('is_renewal') == 'Yes', function ($query) {
                $query->whereNotNull('previous_quote_policy_number');
            })
            ->when(request('stale_at'), function ($query) {
                $query->whereNotNull('stale_at');
            })
            ->when(request('is_renewal') != 'Yes', function ($query) {
                $query->whereNull('previous_quote_policy_number');
            })
            ->when(Auth::user()->can(PermissionsEnum::SEARCH_INSURER_TAX_INVOICE_NUMBER) && request()->filled('insurer_tax_invoice_number'), function ($query) {
                $query->whereRelation('payments', 'insurer_tax_number', request('insurer_tax_invoice_number'));
            })
            ->when(Auth::user()->can(PermissionsEnum::SEARCH_INSURER_COMMISSION_TAX_INVOICE_NUMBER) && request()->filled('insurer_commission_tax_invoice_number'), function ($query) {
                $query->whereRelation('payments', 'insurer_commmission_invoice_number', request('insurer_commission_tax_invoice_number'));
            })
            ->when(
                ! request()->filled('email') && ! request()->filled('code') && ! request()->filled('first_name') && ! request()->filled('last_name') && ! request()->filled('quote_status_id') && ! request()->filled('mobile_no'),
                fn($q) => $q->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake]),

            )
            ->when(
                request()->filled('email') && request()->filled('email') == '',
                fn($q) => $q->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake]),

            )
            ->when($this->shouldApplyDatesFilter() && ! request()->filled('created_at_start'), function ($query) {
                $query->filterByToday();
            })
            ->when($this->shouldApplyDatesFilter() && request()->filled('created_at_start') && request()->filled('created_at_end'), function ($query) {
                $query->whereBetween('created_at', [$this->parseDate(request('created_at_start'), true), $this->parseDate(request('created_at_end'), false)]);
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
