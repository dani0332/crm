<?php

namespace App\Builders;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\TravelQuoteEnum;
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
            'travel_quote_request.id',
            'uuid',
            'travel_quote_request.code',
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
            'travel_quote_request.created_at',
            'travel_quote_request.updated_at',
            'assignment_type',
            'gender',
            'premium',
            'payment_status_id',
            'currently_located_in_id',
            'api_issuance_status_id',
            'insurer_api_status_id',
            'start_date',
            'end_date',
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
            'currentlyLocatedIn:id,text',
            'renewalBatch:id,name',
        ]);
    }

    public function applyFilters(Builder $query, $requestParams = [])
    {
        $user = null;

        if (Auth::check() && empty($requestParams['user'])) {
            $user = Auth::user();
            $requestParams = collect(request()->all());
        } elseif (! empty($requestParams['user'])) {
            /* For queue when session data isn't present */
            $user = $requestParams['user'];
            $requestParams = collect($requestParams);
        }

        $query
            ->filterBy('code', value: $requestParams['code'] ?? null)
            ->matchBy('first_name', value: $requestParams['first_name'] ?? null)
            ->matchBy('last_name', value: $requestParams['last_name'] ?? null)
            ->filterBy('email', value: $requestParams['email'] ?? null)
            ->filterBy('mobile_no', value: $requestParams['mobile_no'] ?? null)
            ->filterBy('policy_number', value: $requestParams['policy_number'] ?? null)
            ->filterBy('previous_quote_policy_premium', value: $requestParams['previous_quote_policy_premium'] ?? null)
            ->filterIn('quote_status_id', value: $requestParams['quote_status_id'] ?? null)
            ->filterIn('insurance_provider_ids', 'insurance_provider_id', value: $requestParams['insurance_provider_ids'] ?? null)
            ->filterBy('payment_status_id', value: $requestParams['payment_status_id'] ?? null)
            ->filterIn('renewal_batches', 'renewal_batch_id', value: $requestParams['renewal_batches'] ?? null)
            ->filterIn('insurer_api_status_id', value: $requestParams['insurer_api_status_id'] ?? null)
            ->filterBy('currently_insured_with', value: $requestParams['currently_insured_with'] ?? null)
            ->filterBy('is_cold', 'is_cold', 1)

            ->filterByDate('travel_start_date', 'start_date', value: $requestParams['travel_start_date'] ?? null)
            ->filterByDate('next_followup_date', value: $requestParams['next_followup_date'] ?? null)
            ->filterByDate('next_followup_date_end', 'next_followup_date', false, value: $requestParams['next_followup_date_end'] ?? null)
            ->filterByDate('previous_policy_expiry_date', value: $requestParams['previous_policy_expiry_date'] ?? null)
            ->filterByDate('previous_policy_expiry_date_end', 'previous_policy_expiry_date', false, value: $requestParams['previous_policy_expiry_date_end'] ?? null)
            ->filterByDate('policy_expiry_date', 'previous_policy_expiry_date', value: $requestParams['policy_expiry_date'] ?? null)
            ->filterByDate('policy_expiry_date_end', 'previous_policy_expiry_date', false, value: $requestParams['policy_expiry_date_end'] ?? null)

            ->filterBy('sic_advisor_requested', value: $requestParams['sic_advisor_requested'] ?? null, ignoreAll: true)
            ->filterBy('is_ecommerce', value: $requestParams['is_ecommerce'] ?? null, isBool: true)
            ->filterIn('insurer_aml_status', value: $requestParams['insurer_aml_status'] ?? null)
            ->filterIn('amlStatus', 'aml_status', value: $requestParams['amlStatus'] ?? null)
            ->filterIn('plan_name', 'plan_id', value: $requestParams['plan_name'] ?? null)
            ->filterBy('source', value: $requestParams['source'] ?? null)
            ->filterByAdvisors($requestParams->get('advisor_id'))
            ->filterByDateRange('transaction_approved_dates', 'transaction_approved_at', value: $requestParams['transaction_approved_dates'] ?? null)
            ->filterByDateRange('advisor_assigned_date', value: $requestParams['advisor_assigned_date'] ?? null)
            ->filterBySegment('travel_quote_request',$requestParams)
            ->when(!empty($requestParams->get('previous_quote_policy_number')), function ($query) use ($requestParams) {
                $query->where(fn ($q) => $q->filterBy('previous_quote_policy_number')->orWhere->filterBy('previous_quote_policy_number', 'policy_number'));
            })
            ->when($user->isSpecificTeamAdvisor('Travel'), function ($query) use($user) {
                $query->filterBy('advisor_id', $user->id);
            })
            ->when(!empty($requestParams->get('is_renewal')) && $requestParams->get('is_renewal') == 'Yes', function ($query) {
                $query->whereNotNull('previous_quote_policy_number');
            })
            ->when(!empty($requestParams->get('is_renewal')) && $requestParams->get('is_renewal') == 'No', function ($query) {
                $query->whereNull('previous_quote_policy_number');
            })
            ->when($user->can(PermissionsEnum::SEARCH_INSURER_TAX_INVOICE_NUMBER) && !empty($requestParams->get('insurer_tax_invoice_number')), function ($query) use ($requestParams) {
                $query->whereRelation('payments', 'insurer_tax_number', $requestParams->get('insurer_tax_invoice_number'));
            })
            ->when($user->can(PermissionsEnum::SEARCH_INSURER_COMMISSION_TAX_INVOICE_NUMBER) && !empty($requestParams->get('insurer_commission_tax_invoice_number')), function ($query) use ($requestParams) {
                $query->whereRelation('payments', 'insurer_commmission_invoice_number', $requestParams->get('insurer_commission_tax_invoice_number'));
            })
            ->when(
                !empty($requestParams->get('email')) && empty($requestParams->get('code')) && empty($requestParams->get('first_name')) && empty($requestParams->get('last_name')) && empty($requestParams->get('quote_status_id')) && empty($requestParams->get('mobile_no')),
                fn ($q) => $q->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake]),
            )
            ->when($this->shouldApplyDatesFilter() && empty($requestParams->get('last_modified_date')) && empty($requestParams->get('created_at_start')) && empty($requestParams->get('renewal_batches')) && empty($requestParams->get('policy_expiry_date')) && empty($requestParams->get('policy_expiry_date_end')), function ($query) {
                $query->filterByToday();
            })
            ->when($this->shouldApplyDatesFilter() && empty($requestParams->get('renewal_batches')) && !empty($requestParams->get('created_at_start')) && !empty($requestParams->get('created_at_end')), function ($query) use ($requestParams) {
                $query->whereBetween('created_at', [$this->parseDate($requestParams->get('created_at_start'), true), $this->parseDate($requestParams->get('created_at_end'), false)]);
            })
            ->when(!empty($requestParams->get('last_modified_date')), function ($query) {
                $query->filterByDateRange('last_modified_date', 'updated_at');
            })
            ->when(!empty($requestParams->get('coverage_code')), function ($q) use ($requestParams) {
                $q->where(function ($q) use ($requestParams) {
                    $q->where('coverage_code', $requestParams->get('coverage_code'))
                        ->orWhere(function ($qInner) use ($requestParams) {
                            $qInner->when($requestParams->get('coverage_code') == TravelQuoteEnum::COVERAGE_CODE_SINGLE_TRIP, function ($qInner) {
                                $qInner->where('days_cover_for', '<', 93);
                            })->when(in_array($requestParams->get('coverage_code'), [TravelQuoteEnum::COVERAGE_CODE_ANNUAL_TRIP, TravelQuoteEnum::COVERAGE_CODE_MULTI_TRIP]), function ($qInner) {
                                $qInner->where('days_cover_for', '>', 92);
                            });
                        });
                });
            })
            ->when(!empty($requestParams->get('direction_code')), function ($q) use ($requestParams) {
                $q->when($requestParams->get('direction_code') == TravelQuoteEnum::TRAVEL_UAE_OUTBOUND, function ($q) use ($requestParams) {
                    $q->where(function ($q) use ($requestParams) {
                        $q->where('direction_code', $requestParams->get('direction_code'))
                            ->orWhere(function ($qInner) {
                                $qInner->where('currently_located_in_id', TravelQuoteEnum::CURRENTLY_LOCATED_ID_UAE)
                                    ->where('region_cover_for_id', '!=', TravelQuoteEnum::REGION_COVER_ID_UAE);
                            });
                    });
                })->when($requestParams->get('direction_code') == TravelQuoteEnum::TRAVEL_UAE_INBOUND, function ($q) use ($requestParams) {
                    $q->where(function ($q) use ($requestParams) {
                        $q->where('direction_code', $requestParams->get('direction_code'))
                            ->orWhere('region_cover_for_id', TravelQuoteEnum::REGION_COVER_ID_UAE);
                    });
                });
            })
            ->when($requestParams->get('api_issuance_status_id'), function ($q) use ($requestParams) {
                $apiIssuanceStatusIds = (array) $requestParams->get('api_issuance_status_id');

                $q->when(in_array('blank', $apiIssuanceStatusIds), function ($q) use ($apiIssuanceStatusIds) {
                    $q->where(function ($subQuery) use ($apiIssuanceStatusIds) {
                        $subQuery->whereNull('api_issuance_status_id')
                            ->orWhere('api_issuance_status_id', '');

                        $subQuery->when(count($apiIssuanceStatusIds) > 1, function ($subQuery) use ($apiIssuanceStatusIds) {
                            $subQuery->orWhereIn('api_issuance_status_id', $apiIssuanceStatusIds);
                        });
                    });
                }, function ($q) use ($apiIssuanceStatusIds) {
                    $q->whereIn('api_issuance_status_id', $apiIssuanceStatusIds);
                });
            })
            ->when(
                !empty($requestParams->get('sortBy')),
                fn ($q) => $q->orderBy($requestParams->get('sortBy'), $requestParams->get('sortType')),
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
