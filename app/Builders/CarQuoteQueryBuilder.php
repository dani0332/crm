<?php

namespace App\Builders;

use App\Enums\CarRegistrationType;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\CarQuote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
            'previous_policy_start_date',
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
            'registration_type',
            'vehicle_use',
            'company_name as car_company_name',
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
        if (! Auth::check()) {
            $user = $requestParams['user'] ?? null;
            unset($requestParams['user']);
            Auth::login($user);
            DB::setDefaultConnection('mysql_read');
            request()->merge($requestParams);
        }

        $query->when(
            ! request()->filled('email') && ! request()->filled('code') && ! request()->filled('first_name') && ! request()->filled('last_name') && ! request()->filled('quote_status_id') && ! request()->filled('mobile_no'),
            fn ($q) => $q->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate]),
        )
            ->filterBy('code')
            ->filterIn('quote_batch_id')
            ->matchBy('first_name')
            ->matchBy('last_name')
            ->filterBy('email')
            ->filterBy('mobile_no')
            ->filterBy('payment_status_id')
            ->filterBy('is_ecommerce', isBool: true)
            ->filterIn('quote_status_id')
            ->filterIn('insurer_aml_status')
            ->filterIn('tier_id')
            ->filterBy('vehicle_type_id')
            ->filterBy('car_type_insurance_id')
            ->filterBy('renewal_batch')
            ->filterBy('currently_insured_with')
            ->filterByDate('policy_expiry_date', 'previous_policy_expiry_date')
            ->filterByDate('policy_expiry_date_end', 'previous_policy_expiry_date', false)
            ->filterBy('assignment_type', ignoreAll: true)
            ->filterByTeams(request('teams'))
            ->filterByAdvisors(request('advisor_id'))
            ->filterByDateRange('transaction_approved_dates', 'transaction_approved_at')
            ->filterBySegment(request('segment_filter'), QuoteTypeId::Car)
            ->filterBy('sic_advisor_requested', ignoreAll: true)
            ->filterByPaymentDueDates('payment_due_date')
            ->filterByAdvisorAssignedDates('carQuoteRequestDetail', ['advisor_assigned_date', 'advisor_assigned_date_end'])
            ->when(request()->filled('previous_quote_policy_number'), function ($query) {
                $query->where(fn ($q) => $q->filterBy('previous_quote_policy_number')->orWhere->filterBy('previous_quote_policy_number', 'policy_number'));
            })
            ->when(Auth::user()->can(PermissionsEnum::SEARCH_INSURER_TAX_INVOICE_NUMBER) && request()->filled('insurer_tax_invoice_number'), function ($query) {
                $query->whereRelation('payments', 'insurer_tax_number', request('insurer_tax_invoice_number'));
            })
            ->when(Auth::user()->can(PermissionsEnum::SEARCH_INSURER_COMMISSION_TAX_INVOICE_NUMBER) && request()->filled('insurer_commission_tax_invoice_number'), function ($query) {
                $query->whereRelation('payments', 'insurer_commmission_invoice_number', request('insurer_commission_tax_invoice_number'));
            })
            ->filterByDateRange('booking_date', 'policy_booking_date')
            ->when($this->shouldApplyDatesFilter() && ! request()->filled('created_at_start'), function ($query) {
                $query->filterByToday();
            })
            ->when($this->shouldApplyDatesFilter() && request()->filled('created_at_start') && request()->filled('created_at_end'), function ($query) {
                $query->whereBetween('created_at', [$this->parseDate(request('created_at_start'), true), $this->parseDate(request('created_at_end'), false)]);
            })
            ->when(
                request()->filled('sortBy'),
                fn ($q) => $q->orderBy($this->getOrderByColumn(), request('sortType')),
                fn ($q) => $q->orderBy('created_at', 'DESC'),
            )
            ->when(request()->filled('registration_type'), function ($query) {
                $query->where('registration_type', request('registration_type'));
            })
            ->when(request()->filled('vehicle_use'), function ($query) {
                $query->where('registration_type', CarRegistrationType::COMPANY);
                $query->whereIn('vehicle_use', request('vehicle_use'));
            })
            ->when(request()->filled('company_name'), function ($query) {
                $query->where('company_name', request('company_name'));
            });
    }

    public function processGridData($requestParams): Builder
    {
        $query = $this->buildGrid();

        $this->applyFilters($query, $requestParams);

        return $query;
    }
}
