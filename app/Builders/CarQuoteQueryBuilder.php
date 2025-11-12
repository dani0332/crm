<?php

namespace App\Builders;

use App\Enums\CarRegistrationType;
use App\Enums\GenericRequestEnum;
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
            'sub_source_id',
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
            'lead_assignment_trigger',
            'customer_id',
            'insurance_provider_id',
            'api_issuance_status_id',
            'insurer_api_status_id',
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
            'customer:id,pcp_tag',
            'quoteTags:quote_uuid,name',
            'insuranceProvider:id,text',
            'subSource:id,text',
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

        // Helper method to get filter value from requestParams or request object
        $getFilterValue = function ($filterName) use ($requestParams) {
            if (! empty($requestParams) && isset($requestParams[$filterName])) {
                return $requestParams[$filterName];
            }

            return request($filterName);
        };

        // Helper method to check if filter value exists
        $hasFilterValue = function ($filterName) use ($requestParams) {
            if (! empty($requestParams) && isset($requestParams[$filterName])) {
                $value = $requestParams[$filterName];

                return ! empty($value) || (is_array($value) && count($value) > 0);
            }

            return request()->filled($filterName);
        };

        $query
            ->when(
                ! $hasFilterValue('email') && ! $hasFilterValue('code') && ! $hasFilterValue('first_name') && ! $hasFilterValue('last_name') && ! $hasFilterValue('quote_status_id') && ! $hasFilterValue('mobile_no'),
                fn ($q) => $q->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate]),
            )
            ->filterBy('code', requestParams: $requestParams)
            ->filterIn('quote_batch_id', requestParams: $requestParams)
            ->matchBy('first_name', requestParams: $requestParams)
            ->matchBy('last_name', requestParams: $requestParams)
            ->filterBy('email', requestParams: $requestParams)
            ->filterBy('mobile_no', requestParams: $requestParams)
            ->filterBy('payment_status_id', requestParams: $requestParams)
            ->filterBy('is_ecommerce', isBool: true, requestParams: $requestParams)
            ->filterIn('quote_status_id', requestParams: $requestParams)
            ->filterIn('insurer_aml_status', requestParams: $requestParams)
            ->filterIn('tier_id', requestParams: $requestParams)
            ->filterBy('vehicle_type_id', requestParams: $requestParams)
            ->filterBy('car_type_insurance_id', requestParams: $requestParams)
            ->filterBy('renewal_batch', requestParams: $requestParams)
            ->filterBy('currently_insured_with', requestParams: $requestParams)
            ->filterByDate('policy_expiry_date', 'previous_policy_expiry_date', requestParams: $requestParams)
            ->filterByDate('policy_expiry_date_end', 'previous_policy_expiry_date', false, requestParams: $requestParams)
            ->filterBy('assignment_type', ignoreAll: true, requestParams: $requestParams)
            ->filterByTeams($getFilterValue('teams'))
            ->filterByAdvisors($getFilterValue('advisor_id'))
            ->filterByDateRange('transaction_approved_dates', 'transaction_approved_at', requestParams: $requestParams)
            ->filterBySegment($getFilterValue('segment_filter'), QuoteTypeId::Car)
            ->filterBy('sic_advisor_requested', ignoreAll: true, requestParams: $requestParams)
            ->filterByPaymentDueDates('payment_due_date')
            ->filterByAdvisorAssignedDates('carQuoteRequestDetail', ['advisor_assigned_date', 'advisor_assigned_date_end'])
            ->filterByPrivateClient(request('private_client'))
            ->when($hasFilterValue('previous_quote_policy_number'), function ($query) use ($requestParams) {
                $query->where(fn ($q) => $q->filterBy('previous_quote_policy_number', requestParams: $requestParams)->orWhere->filterBy('previous_quote_policy_number', 'policy_number', requestParams: $requestParams));
            })
            ->when($hasFilterValue('insurer_tax_invoice_number'), function ($query) use ($getFilterValue) {
                $query->whereRelation('payments', 'insurer_tax_number', $getFilterValue('insurer_tax_invoice_number'));
            })
            ->when($hasFilterValue('insurer_commission_tax_invoice_number'), function ($query) use ($getFilterValue) {
                $query->whereRelation('payments', 'insurer_commmission_invoice_number', $getFilterValue('insurer_commission_tax_invoice_number'));
            })
            ->filterByDateRange('booking_date', 'policy_booking_date', requestParams: $requestParams)
            ->when($hasFilterValue('authorize_date'), function ($query) use ($getFilterValue) {
                $authorizedAtRange = $getFilterValue('authorize_date');

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
            ->when($hasFilterValue('captured_date'), function ($query) use ($getFilterValue) {
                $capturedAtRange = $getFilterValue('captured_date');

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
            ->when($this->shouldApplyDatesFilter($requestParams) && ! $hasFilterValue('created_at_start'), function ($query) {
                $query->filterByToday();
            })
            ->when($this->shouldApplyDatesFilter($requestParams) && $hasFilterValue('created_at_start') && $hasFilterValue('created_at_end'), function ($query) use ($getFilterValue) {
                $query->whereBetween('created_at', [$this->parseDate($getFilterValue('created_at_start'), true), $this->parseDate($getFilterValue('created_at_end'), false)]);
            })
            ->when(
                $hasFilterValue('sortBy'),
                fn ($q) => $q->orderBy($this->getOrderByColumn($requestParams), $getFilterValue('sortType')),
                fn ($q) => $q->orderBy('created_at', 'DESC'),
            )
            ->when($hasFilterValue('registration_type'), function ($query) use ($getFilterValue) {
                $query->where('registration_type', $getFilterValue('registration_type'));
            })
            ->when($hasFilterValue('vehicle_use'), function ($query) use ($getFilterValue) {
                $query->where('registration_type', CarRegistrationType::COMPANY);
                $query->whereIn('vehicle_use', $getFilterValue('vehicle_use'));
            })
            ->when($hasFilterValue('company_name'), function ($query) use ($getFilterValue) {
                $query->where('company_name', $getFilterValue('company_name'));
            })
            ->when($hasFilterValue('api_issuance_status_id'), function ($query) use ($getFilterValue) {
                $apiIssuanceStatusIds = (array) $getFilterValue('api_issuance_status_id');

                $hasBlank = in_array(GenericRequestEnum::API_ISSUANCE_STATUS_ID_BLANK, $apiIssuanceStatusIds);
                $otherIds = array_diff($apiIssuanceStatusIds, [GenericRequestEnum::API_ISSUANCE_STATUS_ID_BLANK]);

                $query->where(function ($q) use ($hasBlank, $otherIds) {
                    if ($hasBlank) {
                        $q->where(function ($subQuery) {
                            $subQuery->whereNull('api_issuance_status_id')
                                ->orWhere('api_issuance_status_id', '');
                        });
                    }

                    if (! empty($otherIds)) {
                        if ($hasBlank) {
                            $q->orWhereIn('api_issuance_status_id', $otherIds);
                        } else {
                            $q->whereIn('api_issuance_status_id', $otherIds);
                        }
                    }
                });
            })
            ->when($hasFilterValue('insurer_api_status_id'), function ($query) use ($getFilterValue) {
                $insurerApiStatusIds = (array) $getFilterValue('insurer_api_status_id');

                $query->whereIn('insurer_api_status_id', $insurerApiStatusIds);
            });
    }

    public function processGridData($requestParams): Builder
    {
        $query = $this->buildGrid();

        $this->applyFilters($query, $requestParams);

        return $query;
    }
}
