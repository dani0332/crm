<?php

namespace App\Builders;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\CarQuote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

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

    public function applyFilters(Builder $query)
    {
        $query->when(
            ! request()->filled('email') && ! request()->filled('code') && ! request()->filled('first_name') && ! request()->filled('last_name') && ! request()->filled('quote_status_id') && ! request()->filled('mobile_no'),
            fn ($q) => $q->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate]),
        )
            ->when(request('code'), function ($query) {
                $query->where('code', request('code'));
            })
            ->when(request('quote_batch_id'), function ($query) {
                $query->where('quote_batch_id', request('quote_batch_id'));
            })
            ->when(request('first_name'), function ($query) {
                $query->where('first_name', 'like', '%'.request('first_name').'%');
            })
            ->when(request('last_name'), function ($query) {
                $query->where('last_name', 'like', '%'.request('last_name').'%');
            })
            ->when(! request()->filled('code') && ! request()->filled('email') && ! request()->filled('mobile_no') && ! request()->filled('created_at') && ! request()->filled('payment_due_date') && ! request()->filled('booking_date') && ! request()->filled('previous_quote_policy_number') && ! request()->filled('renewal_batch') && ! request()->filled('insurer_tax_invoice_number') && ! request()->filled('insurer_commission_tax_invoice_number') && ! request()->filled('created_at_start'), function ($query) {
                $query->whereBetween('created_at', [$this->parseDate(now(), true), $this->parseDate(now(), false)]);
            })
            ->when(empty(request()->email) && empty(request()->code) && empty(request()->renewal_batch) && empty(request()->quote_batch_id) && empty(request()->payment_due_date) && empty(request()->booking_date) && ! isset(request()->previous_quote_policy_number) && ! isset(request()->insurer_tax_invoice_number) && ! isset(request()->insurer_commission_tax_invoice_number) && request()->filled('created_at_start') && request()->filled('created_at_end'), function ($query) {
                $query->whereBetween('created_at', [$this->parseDate(request('created_at_start'), true), $this->parseDate(request('created_at_end'), false)]);
            })
            ->when(request('email'), function ($query) {
                $query->where('email', request('email'));
            })
            ->when(request('mobile_no'), function ($query) {
                $query->where('mobile_no', request('mobile_no'));
            })
            ->when(request()->filled('advisor_assigned_date') && ! request()->filled('advisor_assigned_date_end'), function ($query) {
                $query->whereIn('id', function ($query) {
                    $query->select('car_quote_request_id')
                        ->from('car_quote_request_detail')
                        ->where('advisor_assigned_date', '>=', $this->parseDate(request('advisor_assigned_date'), true));
                });
            })
            ->when(! request()->filled('advisor_assigned_date') && request()->filled('advisor_assigned_date_end'), function ($query) {
                $query->whereIn('id', function ($query) {
                    $query->select('car_quote_request_id')
                        ->from('car_quote_request_detail')
                        ->where('advisor_assigned_date', '<=', $this->parseDate(request('advisor_assigned_date_end'), false));
                });
            })
            ->when(request()->filled('advisor_assigned_date') && request()->filled('advisor_assigned_date_end'), function ($query) {
                $query->whereIn('id', function ($query) {
                    $query->select('car_quote_request_id')
                        ->from('car_quote_request_detail')
                        ->whereBetween('advisor_assigned_date', [$this->parseDate(request('advisor_assigned_date'), true), $this->parseDate(request('advisor_assigned_date_end'), false)]);
                });
            })
            ->when(request('payment_status_id'), function ($query) {
                $query->where('payment_status_id', request('payment_status_id'));
            })
            ->when(request('is_ecommerce'), function ($query) {
                $query->where('is_ecommerce', request('is_ecommerce'));
            })
            ->when(request('quote_status_id') && is_array(request('quote_status_id')), function ($query) {
                $query->whereIn('quote_status_id', request('quote_status_id'));
            })
            ->when(request('insurer_aml_status') && is_array(request('insurer_aml_status')), function ($query) {
                $query->whereIn('insurer_aml_status', request('insurer_aml_status'));
            })
            ->when(request('tier_id') && is_array(request('tier_id')), function ($query) {
                $query->whereIn('tier_id', request('tier_id'));
            })
            ->when(request('vehicle_type_id'), function ($query) {
                $query->where('vehicle_type_id', request('vehicle_type_id'));
            })
            ->when(request('car_type_insurance_id'), function ($query) {
                $query->where('car_type_insurance_id', request('car_type_insurance_id'));
            })
            ->when(request('renewal_batch'), function ($query) {
                $query->where('renewal_batch', request('renewal_batch'));
            })
            ->when(request('currently_insured_with'), function ($query) {
                $query->where('currently_insured_with', request('currently_insured_with'));
            })
            ->when(request('previous_quote_policy_number'), function ($query) {
                $query->where('previous_quote_policy_number', request('previous_quote_policy_number'));
                $query->orWhere('policy_number', request('previous_quote_policy_number'));
            })
            ->when(request()->filled('policy_expiry_date'), function ($query) {
                $query->where('previous_policy_expiry_date', '>=', $this->parseDate(request('policy_expiry_date'), true));
            })
            ->when(request()->filled('policy_expiry_date_end'), function ($query) {
                $query->where('previous_policy_expiry_date', '<=', $this->parseDate(request('policy_expiry_date_end'), false));
            })
            ->when(request('assignment_type') && strtolower(request('assignment_type')) != 'all', function ($query) {
                $query->where('assignment_type', request('assignment_type'));
            })
            ->when(request('teams') && is_array(request('teams')), function ($query) {
                $query->whereIn('advisor_id', function ($query) {
                    $query->select('user_id')
                        ->from('user_team')
                        ->whereIn('team_id', request('teams'));
                });
            })
            ->when(request('advisor_id') && is_array(request('advisor_id')), function ($query) {
                if (in_array('-1', request('advisor_id')) || in_array(-1, request('advisor_id'))) {
                    $query->whereNull('advisor_id');
                } else {
                    $query->whereIn('advisor_id', request('advisor_id'));
                }
            })
            ->when(request('transaction_approved_dates'), function ($query) {
                [$start, $end] = request('transaction_approved_dates');

                $start = $this->parseDate($start, true);
                $end = $this->parseDate($end, false);

                $query->whereBetween('transaction_approved_at', [$start, $end]);
            })
            ->filterBySegment(request('segment_filter'), QuoteTypeId::Car)
            ->when(request('sic_advisor_requested') && strtolower(request('sic_advisor_requested')) != 'all', function ($query) {
                $query->where('sic_advisor_requested', request('sic_advisor_requested'));
            })
            ->when(request('payment_due_date'), function ($query) {
                [$start, $end] = request('payment_due_date');

                $start = $this->parseDate($start, true);
                $end = $this->parseDate($end, false);

                $query->whereIn('id', function ($q) use ($start, $end) {
                    $q->select('paymentable_id')
                        ->from('payments')
                        ->where('paymentable_type', CarQuote::class)
                        ->whereBetween('payment_due_date', [$start, $end]);
                });
            })
            ->when(Auth::user()->can(PermissionsEnum::SEARCH_INSURER_TAX_INVOICE_NUMBER) && request()->filled('insurer_tax_invoice_number'), function ($query) {
                $query->whereHas('payments', function ($q) {
                    $q->where('insurer_tax_number', request('insurer_tax_invoice_number'));
                });
            })
            ->when(Auth::user()->can(PermissionsEnum::SEARCH_INSURER_COMMISSION_TAX_INVOICE_NUMBER) && request()->filled('insurer_commission_tax_invoice_number'), function ($query) {
                $query->whereHas('payments', function ($q) {
                    $q->where('insurer_commmission_invoice_number', request('insurer_commission_tax_invoice_number'));
                });
            })
            ->when(request('booking_date'), function ($query) {
                [$start, $end] = request('booking_date');

                $start = $this->parseDate($start, true);
                $end = $this->parseDate($end, false);

                $query->whereBetween('policy_booking_date', [$start, $end]);
            })
            ->when(
                request()->filled('sortBy'),
                fn ($q) => $q->orderBy(request('sortBy'), request('sortType')),
                fn ($q) => $q->orderBy('created_at', 'DESC'),
            );
    }

    public function processGridData(): Builder
    {
        $query = $this->buildGrid();

        $this->applyFilters($query);

        return $query;
    }
}
