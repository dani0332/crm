<?php

namespace App\Builders;

use App\Enums\DefaultAdvisorEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\HealthQuote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
            'sub_source_id',
            'health_team_type',
            'notional_team',
            'premium',
            'policy_number',
            'support_user_id',
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
            'health_quote_request.created_at',
            'health_quote_request.updated_at',
            'assignment_type',
            'gender',
            'emirate_of_your_visa_id',
            'pec_marked_at',
            'branch_id',
            'is_branch_applicable',
            'health_plan_type_id',
        ], [
            'maritalStatus:id,text',
            'healthCoverFor:id,text',
            'nationality:id,text',
            'emirate:id,text',
            'advisor:id,name,email,mobile_no,landline_no',
            'advisor.primaryBranch',
            'branch:id,name',
            'previousAdvisor:id,name',
            'healthQuoteRequestDetail:id,health_quote_request_id,next_followup_date,transapp_code,notes,insly_id,lost_reason_id,advisor_assigned_date',
            'healthLeadType:id,text',
            'healthQuoteRequestDetail.lostReason:id,text',
            'salaryBand:id,text',
            'memberCategory:id,text',
            'renewalBatchModel:id,name',
            'insured:id,first_name,last_name',
            'customer:id,emirates_id_expiry_date,receive_marketing_updates,pcp_tag',
            'quoteRequestEntityMapping:id,quote_request_id,entity_id,entity_type_code',
            'quoteRequestEntityMapping.entity:id,code,trade_license_no,company_name,company_address,industry_type_code,emirate_of_registration_id',
            'quotePlan',
            'paymentStatus:id,text',
            'payments:id,paymentable_id,paymentable_type,authorized_at',
            'insuranceProvider:id,text,code',
            'quoteStatus:id,text',
            'wcAdvisor:id,name',
            'supportUser:id,name',
            'memberCategory:id,text',
            'plan:id,text',
            'subSource:id,text',
        ]);
    }

    public function applyFilters(Builder $query, $requestParams = [])
    {
        if (! Auth::check()) {
            $user = $requestParams['user'] ?? null;
            unset($requestParams['user']);
            Auth::login($user);
            DB::setDefaultConnection('mysql_read');
            request()->merge($requestParams);
        }

        $hasQuoteStatusFilter = $this->hasFilterValue('quote_status', $requestParams) || $this->hasFilterValue('quote_status_id', $requestParams);

        $query
            ->filterBy('code', requestParams: $requestParams)
            ->matchBy('first_name', requestParams: $requestParams)
            ->matchBy('last_name', requestParams: $requestParams)
            ->filterBy('email', requestParams: $requestParams)
            ->filterBy('mobile_no', requestParams: $requestParams)
            ->filterBy('policy_number', requestParams: $requestParams)
            ->filterBy('previous_quote_policy_premium', requestParams: $requestParams)
            ->filterBy('sub_team', 'health_team_type', requestParams: $requestParams)
            ->filterIn('quote_status', 'quote_status_id', requestParams: $requestParams)
            ->filterIn('payment_status_id', requestParams: $requestParams)
            ->filterIn('renewal_batches', 'renewal_batch_id', requestParams: $requestParams)
            ->filterBy('currently_insured_with', requestParams: $requestParams)
            ->filterBy('is_cold', 'is_cold', 1, requestParams: $requestParams)
            ->filterByDate('next_followup_date', requestParams: $requestParams)
            ->filterByDate('next_followup_date_end', 'next_followup_date', false, requestParams: $requestParams)
            ->filterByAdvisors($this->getFilterValue('advisor_id', $requestParams) ?? $this->getFilterValue('advisors', $requestParams))
            ->filterBy('assignment_type', ignoreAll: true, requestParams: $requestParams)
            ->filterBy('sic_advisor_requested', ignoreAll: true, requestParams: $requestParams)
            ->filterBy('is_ecommerce', isBool: true, requestParams: $requestParams)
            ->filterIn('emirate_of_your_visa_id', requestParams: $requestParams)
            ->filterIn('insurer_aml_status', requestParams: $requestParams)
            ->filterByDateRange('transaction_approved_dates', 'transaction_approved_at', requestParams: $requestParams)
            ->filterBySegment()
            ->filterByPaymentDueDates('payment_due_date')
            ->filterByDateRange('booking_date', 'policy_booking_date', requestParams: $requestParams)
            ->filterByAdvisorAssignedDates(
                'healthQuoteRequestDetail',
                ['assigned_to_date_start', 'assigned_to_date_end'],
                verifyQuoteStatus: ! $hasQuoteStatusFilter,
            )
            ->filterByDateRange('last_modified_date', 'updated_at', requestParams: $requestParams)
            ->filterByPrivateClient(request('private_client'))
            ->when($this->hasFilterValue('authorize_date', $requestParams), function ($query) use ($requestParams) {
                $authorizedAtRange = $this->getFilterValue('authorize_date', $requestParams);

                // Handle authorize_date as an array of two dates [start_date, end_date]
                if (is_array($authorizedAtRange) && count($authorizedAtRange) >= 2) {
                    $startDate = $authorizedAtRange[0];
                    $endDate = $authorizedAtRange[1];

                    if ($startDate && $endDate) {
                        $query->whereHas('payments', function ($paymentQuery) use ($startDate, $endDate) {
                            $paymentQuery->whereBetween('authorized_at', [
                                $this->parseDate($startDate, true),
                                $this->parseDate($endDate, false),
                            ]);
                        });
                    }
                }
            })
            ->when($this->hasFilterValue('captured_date', $requestParams), function ($query) use ($requestParams) {
                $capturedAtRange = $this->getFilterValue('captured_date', $requestParams);

                // Handle captured_date as an array of two dates [start_date, end_date]
                if (is_array($capturedAtRange) && count($capturedAtRange) >= 2) {
                    $startDate = $capturedAtRange[0];
                    $endDate = $capturedAtRange[1];

                    if ($startDate && $endDate) {
                        $query->whereHas('payments', function ($paymentQuery) use ($startDate, $endDate) {
                            $paymentQuery->whereBetween('captured_at', [
                                $this->parseDate($startDate, true),
                                $this->parseDate($endDate, false),
                            ]);
                        });
                    }
                }
            })
            ->when($this->hasFilterValue('previous_quote_policy_number', $requestParams), function ($query) use ($requestParams) {
                $query->where(fn ($q) => $q->filterBy('previous_quote_policy_number', requestParams: $requestParams)->orWhere->filterBy('previous_quote_policy_number', 'policy_number', requestParams: $requestParams));
            })
            ->when($this->hasFilterValue('policy_expiry_date', $requestParams) && $this->hasFilterValue('policy_expiry_date_end', $requestParams), function ($query) use ($requestParams) {
                $query->where(fn ($q) => $q->filterByDate('policy_expiry_date', requestParams: $requestParams)
                    ->filterByDate('previous_policy_expiry_date', 'policy_expiry_date_end', false, requestParams: $requestParams));
            })
            ->when(Auth::user()->isSpecificTeamAdvisor('Health') || Auth::user()->isSpecificTeamAdvisor('EBP') || Auth::user()->isSpecificTeamAdvisor('RM'), function ($query) {
                $query->where('advisor_id', Auth::user()->id);
            })
            ->when($this->hasFilterValue('advisors', $requestParams) && is_array($this->getFilterValue('advisors', $requestParams)) && in_array(DefaultAdvisorEnum::UNASSIGNED, $this->getFilterValue('advisors', $requestParams)), function ($query) {
                $query->whereNull('advisor_id');
            })
            ->when($this->hasFilterValue('advisors', $requestParams) && is_array($this->getFilterValue('advisors', $requestParams)) && ! in_array(DefaultAdvisorEnum::UNASSIGNED, $this->getFilterValue('advisors', $requestParams)), function ($query) use ($requestParams) {
                $query->filterIn('advisors', 'advisor_id', requestParams: $requestParams);
            })
            ->when($this->getFilterValue('is_renewal', $requestParams) == 'Yes', function ($query) {
                $query->whereNotNull('previous_quote_policy_number');
            })
            ->when($this->hasFilterValue('stale_at', $requestParams), function ($query) {
                $query->whereNotNull('stale_at');
            })
            ->when($this->hasFilterValue('is_renewal', $requestParams) && $this->getFilterValue('is_renewal', $requestParams) != 'Yes', function ($query) {
                $query->whereNull('previous_quote_policy_number');
            })
            ->when(Auth::user()->can(PermissionsEnum::SEARCH_INSURER_TAX_INVOICE_NUMBER) && $this->hasFilterValue('insurer_tax_invoice_number', $requestParams), function ($query) use ($requestParams) {
                $query->whereRelation('payments', 'insurer_tax_number', $this->getFilterValue('insurer_tax_invoice_number', $requestParams));
            })
            ->when(Auth::user()->can(PermissionsEnum::SEARCH_INSURER_COMMISSION_TAX_INVOICE_NUMBER) && $this->hasFilterValue('insurer_commission_tax_invoice_number', $requestParams), function ($query) use ($requestParams) {
                $query->whereRelation('payments', 'insurer_commmission_invoice_number', $this->getFilterValue('insurer_commission_tax_invoice_number', $requestParams));
            })
            ->when(
                ! $this->hasFilterValue('email', $requestParams) && ! $this->hasFilterValue('code', $requestParams) && ! $this->hasFilterValue('first_name', $requestParams) && ! $this->hasFilterValue('last_name', $requestParams) && ! $hasQuoteStatusFilter && ! $this->hasFilterValue('mobile_no', $requestParams),
                fn ($q) => $q->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake]),

            )
            ->when(
                $this->hasFilterValue('email', $requestParams) && $this->getFilterValue('email', $requestParams) == '',
                fn ($q) => $q->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake]),

            )
            ->when($this->shouldApplyDatesFilter($requestParams) && ! $this->hasFilterValue('last_modified_date', $requestParams) && ! $this->hasFilterValue('created_at_start', $requestParams) && ! $this->hasFilterValue('renewal_batches', $requestParams) && ! $this->hasFilterValue('policy_expiry_date', $requestParams) && ! $this->hasFilterValue('policy_expiry_date_end', $requestParams), function ($query) {
                $query->filterByToday();
            })
            ->when($this->shouldApplyDatesFilter($requestParams) && ! $this->hasFilterValue('renewal_batches', $requestParams) && $this->hasFilterValue('created_at_start', $requestParams) && $this->hasFilterValue('created_at_end', $requestParams), function ($query) use ($requestParams) {
                $query->whereBetween('created_at', [$this->parseDate($this->getFilterValue('created_at_start', $requestParams), true), $this->parseDate($this->getFilterValue('created_at_end', $requestParams), false)]);
            })
            ->when(request()->has('pec_flag') && request('pec_flag') != 'all', function ($q) {
                if (request('pec_flag') == 1) {
                    $q->hasPecTag();
                } else {
                    $q->whereNull('pec_marked_at');
                }
            })
            ->when(
                $this->hasFilterValue('sortBy', $requestParams),
                fn ($q) => $q->orderBy($this->getOrderByColumn(), $this->getFilterValue('sortType', $requestParams)),
                fn ($q) => $q->orderBy('created_at', 'DESC'),
            );

        // Filter by support user (OE/AE)
        if ($this->hasFilterValue('support_user_id', $requestParams) && is_array($this->getFilterValue('support_user_id', $requestParams))) {
            $ids = $this->getFilterValue('support_user_id', $requestParams);
            $query->whereIn('support_user_id', $ids);
        }
    }

    /**
     * Helper method to get filter value from requestParams or request object
     */
    private function getFilterValue($filterName, $requestParams = [])
    {
        // First check if we have requestParams (for export context)
        if (! empty($requestParams) && isset($requestParams[$filterName])) {
            return $requestParams[$filterName];
        }

        // Fallback to request object
        return request($filterName);
    }

    /**
     * Helper method to check if filter value exists
     */
    private function hasFilterValue($filterName, $requestParams = [])
    {
        // First check if we have requestParams (for export context)
        if (! empty($requestParams) && isset($requestParams[$filterName])) {
            $value = $requestParams[$filterName];

            return ! empty($value) || (is_array($value) && count($value) > 0);
        }

        // Fallback to request object
        return request()->filled($filterName);
    }

    public function processGridData($requestParams = []): Builder
    {
        $query = $this->buildGrid();
        $this->applyFilters($query, $requestParams);

        return $query;
    }
}
