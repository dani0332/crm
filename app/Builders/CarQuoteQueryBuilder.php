<?php

namespace App\Builders;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\CarQuote;
use Illuminate\Database\Eloquent\Builder;

class CarQuoteQueryBuilder extends BaseQuoteQueryBuilder
{
    public function __construct()
    {
        parent::__construct(CarQuote::class);
    }

    public function buildGrid(): Builder
    {
        return $this->baseQuery([
            'id',
            'uuid',
            'code',
            'first_name',
            'last_name',
            'dob',
            'car_value',
            'car_value_tier',
            'additional_notes',
            'nationality_id',
            'year_of_manufacture',
            'year_of_first_registration',
            'is_ecommerce',
            'sic_advisor_requested',
            'premium',
            'source',
            'paid_at',
            'payment_gateway',
            'uae_license_held_for_id',
            'quote_batch_id',
            'car_make_id',
            'car_model_id',
            'vehicle_type_id',
            'current_insurance_status',
            'currently_insured_with',
            'claim_history_id',
            'previous_policy_expiry_date',
            'quote_status_id',
            'payment_status_id',
            'advisor_id',
            'policy_number',
            'is_gcc_standard',
            'is_modified',
            'quote_link',
            'previous_quote_policy_number',
            'previous_quote_policy_premium',
            'renewal_batch',
            'assignment_type',
            'car_type_insurance_id',
            'tier_id',
            'created_at',
            'updated_at',
            'updated_by',
            'policy_booking_date',
            'transaction_approved_at',
            'policy_expiry_date',
            'insurer_aml_status',
            'aml_status',
        ], [
            'payment:id,paymentable_id,paymentable_type,authorized_at',
            'batch:id,name',
            'nationality:id,text',
            'uaeLicenseHeldFor:id,text',
            'claimHistory:id,text',
            'carMake:id,text',
            'carModel:id,text',
            'vehicleType:id,text',
            'carQuoteRequestDetail',
            'carQuoteRequestDetail.lostReason',
            'tier:id,name,cost_per_lead',
            'quoteStatus:id,text',
            'paymentStatus:id,text',
            'quoteViewCount:quote_id,quote_type_id,user_id,visit_count',
            'advisor:id,name',
            'carTypeInsurance:id,text',
        ]);
    }

    public function applyFilters(Builder $query, $requestParams)
    {
        $user = null;
        if (auth()->check() && empty($requestParams)) {
            $user = auth()->user();
            $requestParams = collect(request()->all());
        } elseif (! empty($requestParams)) {
            /* For queue when session data isn't present */
            $user = $requestParams['user'] ?? null;
            $requestParams = collect($requestParams);
        }

        $query->when(
            empty($requestParams['email']) && empty($requestParams['code']) && empty($requestParams['first_name']) && empty($requestParams['last_name']) && empty($requestParams['quote_status_id']) && empty($requestParams['mobile_no']),
            fn ($q) => $q->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate]),
        )

            ->filterBy('code', value: $requestParams['code'] ?? null)
            ->filterIn('quote_batch_id', value: $requestParams['quote_batch_id'] ?? null)
            ->matchBy('first_name', value: $requestParams['first_name'] ?? null)
            ->matchBy('last_name', value: $requestParams['last_name'] ?? null)
            ->filterBy('email', value: $requestParams['email'] ?? null)
            ->filterBy('mobile_no', value: $requestParams['mobile_no'] ?? null)
            ->filterBy('payment_status_id', value: $requestParams['payment_status_id'] ?? null)
            ->filterBy('is_ecommerce', isBool: true, value: $requestParams['is_ecommerce'] ?? null)
            ->filterIn('quote_status_id', value: $requestParams['quote_status_id'] ?? null)
            ->filterIn('insurer_aml_status', value: $requestParams['insurer_aml_status'] ?? null)
            ->filterIn('tier_id', value: $requestParams['tier_id'] ?? null)
            ->filterBy('vehicle_type_id', value: $requestParams['vehicle_type_id'] ?? null)
            ->filterBy('car_type_insurance_id', value: $requestParams['car_type_insurance_id'] ?? null)
            ->filterBy('renewal_batch', value: $requestParams['renewal_batch'] ?? null)
            ->filterBy('currently_insured_with', value: $requestParams['currently_insured_with'] ?? null)

            ->filterByDate('policy_expiry_date', 'previous_policy_expiry_date',value: $requestParams['policy_expiry_date'] ?? null)
            ->filterByDate('policy_expiry_date_end', 'previous_policy_expiry_date', false,value: $requestParams['policy_expiry_date_end'] ?? null)

            ->filterBy('assignment_type', ignoreAll: true,value: $requestParams['assignment_type'] ?? null)
            ->filterByTeams($requestParams['teams'] ?? null)
            ->filterByAdvisors($requestParams['advisor_id'] ?? null)
            ->filterByDateRange('transaction_approved_dates', 'transaction_approved_at',value: $requestParams['transaction_approved_dates'] ?? null)
            ->filterBySegment($requestParams['segment_filter'] ?? null, QuoteTypeId::Car, $requestParams)
            ->filterBy('sic_advisor_requested', ignoreAll: true,value: $requestParams['sic_advisor_requested'] ?? null)
            ->filterByPaymentDueDates('payment_due_date')
            ->filterByAdvisorAssignedDates('carQuoteRequestDetail', ['advisor_assigned_date', 'advisor_assigned_date_end'])
            ->when(!empty($requestParams['previous_quote_policy_number']), function ($query) use ($requestParams) {
                $query->where(fn ($q) => $q->filterBy('previous_quote_policy_number',value: $requestParams['previous_quote_policy_number'] ?? null)->orWhere->filterBy('previous_quote_policy_number', 'policy_number',value: $requestParams['previous_quote_policy_number'] ?? null));
            })
            ->when($user->can(PermissionsEnum::SEARCH_INSURER_TAX_INVOICE_NUMBER) && !empty($requestParams['insurer_tax_invoice_number']), function ($query) use ($requestParams) {
                $query->whereRelation('payments', 'insurer_tax_number', $requestParams['insurer_tax_invoice_number']);
            })
            ->when($user->can(PermissionsEnum::SEARCH_INSURER_COMMISSION_TAX_INVOICE_NUMBER) && !empty($requestParams['insurer_commission_tax_invoice_number']), function ($query) use ($requestParams) {
                $query->whereRelation('payments', 'insurer_commmission_invoice_number', $requestParams['insurer_commission_tax_invoice_number']);
            })
            ->filterByDateRange('booking_date', 'policy_booking_date',value: $requestParams['booking_date'] ?? null)
            ->when($this->shouldApplyDatesFilter() && empty($requestParams['created_at_start']), function ($query) {
                $query->filterByToday();
            })
            ->when($this->shouldApplyDatesFilter() && !empty($requestParams['created_at_start']) && !empty($requestParams['created_at_end']), function ($query) use ($requestParams) {
                $query->whereBetween('created_at', [$this->parseDate($requestParams['created_at_start'], true), $this->parseDate($requestParams['created_at_end'], false)]);
            })
            ->when(
                !empty($requestParams['sortBy']),
                fn ($q) => $q->orderBy($requestParams['sortBy'], $requestParams['sortType']),
                fn ($q) => $q->orderBy('created_at', 'DESC'),
            );
    }

    public function processGridData($requestParams): Builder
    {
        $query = $this->buildGrid();

        $this->applyFilters($query, $requestParams);

        return $query;
    }
}
