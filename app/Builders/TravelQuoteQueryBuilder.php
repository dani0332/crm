<?php

namespace App\Builders;

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
            'travel_quote_request.payment_status_id',
            'currently_located_in_id',
            'api_issuance_status_id',
            'insurer_api_status_id',
            'start_date',
            'end_date',
            'days_cover_for',
            'lead_assignment_trigger',
            'parent_id',
        ], [
            'nationality:id,country_name',
            'advisor:id,name,email,mobile_no,landline_no',
            'travelQuoteRequestDetail',
            'travelQuoteRequestDetail.lostReason',
            'customer:id,emirates_id_expiry_date,receive_marketing_updates,pcp_tag',
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
            'quoteTags:quote_uuid,name',
            'parent:id,code',
            'child:id,code,parent_id',
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

        $query
            ->filterBy('code', requestParams: $requestParams)
            ->matchBy('first_name', requestParams: $requestParams)
            ->matchBy('last_name', requestParams: $requestParams)
            ->filterBy('email', requestParams: $requestParams)
            ->filterBy('mobile_no', requestParams: $requestParams)
            ->filterBy('policy_number', requestParams: $requestParams)
            ->filterBy('previous_quote_policy_premium', requestParams: $requestParams)
            ->filterIn('quote_status_id', requestParams: $requestParams)
            ->filterIn('insurance_provider_ids', 'insurance_provider_id', requestParams: $requestParams)
            ->filterBy('payment_status_id', requestParams: $requestParams)
            ->filterIn('renewal_batches', 'renewal_batch_id', requestParams: $requestParams)
            ->filterIn('insurer_api_status_id', requestParams: $requestParams)
            ->filterBy('currently_insured_with', requestParams: $requestParams)
            ->filterBy('is_cold', 'is_cold', 1, requestParams: $requestParams)
            ->filterByDate('travel_start_date', 'start_date', requestParams: $requestParams)
            ->filterByDate('next_followup_date', requestParams: $requestParams)
            ->filterByDate('next_followup_date_end', 'next_followup_date', false, requestParams: $requestParams)
            ->filterByDate('previous_policy_expiry_date', requestParams: $requestParams)
            ->filterByDate('previous_policy_expiry_date_end', 'previous_policy_expiry_date', false, requestParams: $requestParams)
            ->filterByDate('policy_expiry_date', 'previous_policy_expiry_date', requestParams: $requestParams)
            ->filterByDate('policy_expiry_date_end', 'previous_policy_expiry_date', false, requestParams: $requestParams)
            ->filterBy('sic_advisor_requested', ignoreAll: true, requestParams: $requestParams)
            ->filterBy('is_ecommerce', isBool: true, requestParams: $requestParams)
            ->filterIn('insurer_aml_status', requestParams: $requestParams)
            ->filterIn('amlStatus', 'aml_status', requestParams: $requestParams)
            ->filterIn('plan_name', 'plan_id', requestParams: $requestParams)
            ->filterBy('source', requestParams: $requestParams)
            ->filterByAdvisors($this->getFilterValue('advisor_id', $requestParams))
            ->filterByDateRange('transaction_approved_dates', 'transaction_approved_at', requestParams: $requestParams)
            ->filterByAdvisorAssignedDates('travelQuoteRequestDetail', 'advisor_assigned_date')
            ->filterBySegment('travel_quote_request')
            ->filterByPrivateClient(request('private_client'))
            ->when($this->hasFilterValue('authorize_date', $requestParams), function ($query) use ($requestParams) {
                $authorizedAtRange = $this->getFilterValue('authorize_date', $requestParams);

                // Handle authorize_date as an array of two dates [start_date, end_date]
                if (is_array($authorizedAtRange) && count($authorizedAtRange) >= 2) {
                    $startDate = $authorizedAtRange[0];
                    $endDate = $authorizedAtRange[1];

                    if ($startDate && $endDate) {
                        $query->whereHas('payment', function ($paymentQuery) use ($startDate, $endDate) {
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
                        $query->whereHas('payment', function ($paymentQuery) use ($startDate, $endDate) {
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
            ->filterByDate('travel_end_date', 'end_date', false)
            ->filterBy('assignment_type', ignoreAll: true)
            ->when($this->hasFilterValue('is_renewal', $requestParams) && $this->getFilterValue('is_renewal', $requestParams) == 'Yes', function ($query) {
                $query->whereNotNull('previous_quote_policy_number');
            })
            ->when($this->hasFilterValue('is_renewal', $requestParams) && $this->getFilterValue('is_renewal', $requestParams) == 'No', function ($query) {
                $query->whereNull('previous_quote_policy_number');
            })
            ->when(
                $this->hasFilterValue('email', $requestParams) && ! $this->hasFilterValue('code', $requestParams) && ! $this->hasFilterValue('first_name', $requestParams) && ! $this->hasFilterValue('last_name', $requestParams) && ! $this->hasFilterValue('quote_status_id', $requestParams) && ! $this->hasFilterValue('mobile_no', $requestParams),
                fn ($q) => $q->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake]),
            )
            ->when($this->shouldApplyDefaultDatesFilter($requestParams) && ! $this->hasFilterValue('last_modified_date', $requestParams) && ! $this->hasFilterValue('created_at_start', $requestParams) && ! $this->hasFilterValue('renewal_batches', $requestParams) && ! $this->hasFilterValue('policy_expiry_date', $requestParams) && ! $this->hasFilterValue('policy_expiry_date_end', $requestParams), function ($query) {
                $query->filterByToday();
            })
            ->when($this->shouldApplyDefaultDatesFilter($requestParams) && ! $this->hasFilterValue('renewal_batches', $requestParams) && $this->hasFilterValue('created_at_start', $requestParams) && $this->hasFilterValue('created_at_end', $requestParams), function ($query) use ($requestParams) {
                $query->whereBetween('created_at', [$this->parseDate($this->getFilterValue('created_at_start', $requestParams), true), $this->parseDate($this->getFilterValue('created_at_end', $requestParams), false)]);
            })
            ->when($this->hasFilterValue('last_modified_date', $requestParams), function ($query) use ($requestParams) {
                $query->filterByDateRange('last_modified_date', 'updated_at', requestParams: $requestParams);
            })
            ->when($this->hasFilterValue('coverage_code', $requestParams), function ($q) use ($requestParams) {
                $q->where(function ($q) use ($requestParams) {
                    $q->where('coverage_code', $this->getFilterValue('coverage_code', $requestParams))
                        ->orWhere(function ($qInner) use ($requestParams) {
                            $qInner->when($this->getFilterValue('coverage_code', $requestParams) == TravelQuoteEnum::COVERAGE_CODE_SINGLE_TRIP, function ($qInner) {
                                $qInner->where('days_cover_for', '<', 93);
                            })->when(in_array($this->getFilterValue('coverage_code', $requestParams), [TravelQuoteEnum::COVERAGE_CODE_ANNUAL_TRIP, TravelQuoteEnum::COVERAGE_CODE_MULTI_TRIP]), function ($qInner) {
                                $qInner->where('days_cover_for', '>', 92);
                            });
                        });
                });
            })
            ->when($this->hasFilterValue('direction_code', $requestParams), function ($q) use ($requestParams) {
                $q->when($this->getFilterValue('direction_code', $requestParams) == TravelQuoteEnum::TRAVEL_UAE_OUTBOUND, function ($q) use ($requestParams) {
                    $q->where(function ($q) use ($requestParams) {
                        $q->where('direction_code', $this->getFilterValue('direction_code', $requestParams))
                            ->orWhere(function ($qInner) {
                                $qInner->where('currently_located_in_id', TravelQuoteEnum::CURRENTLY_LOCATED_ID_UAE)
                                    ->where('region_cover_for_id', '!=', TravelQuoteEnum::REGION_COVER_ID_UAE);
                            });
                    });
                })->when($this->getFilterValue('direction_code', $requestParams) == TravelQuoteEnum::TRAVEL_UAE_INBOUND, function ($q) use ($requestParams) {
                    $q->where(function ($q) use ($requestParams) {
                        $q->where('direction_code', $this->getFilterValue('direction_code', $requestParams))
                            ->orWhere('region_cover_for_id', TravelQuoteEnum::REGION_COVER_ID_UAE);
                    });
                });
            })
            ->when($this->getFilterValue('api_issuance_status_id', $requestParams), function ($q) use ($requestParams) {
                $apiIssuanceStatusIds = (array) $this->getFilterValue('api_issuance_status_id', $requestParams);

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
                $this->hasFilterValue('sortBy', $requestParams),
                fn ($q) => $q->orderBy($this->getOrderByColumn(), $this->getFilterValue('sortType', $requestParams)),
                fn ($q) => $q->orderBy('created_at', 'DESC'),
            )
            ->when($this->hasFilterValue('age_group', $requestParams), function ($q) use ($requestParams) {
                $ageGroups = (array) $this->getFilterValue('age_group', $requestParams);

                // If 'all' is selected, no filtering is needed
                if (in_array('all', $ageGroups)) {
                    return;
                }

                if (in_array('both', $ageGroups)) {
                    // Show only records with parent-child relationships
                    // This includes both parents (records that have children) and children (records with parent_id)
                    $q->where(function ($subQuery) {
                        $subQuery->whereNotNull('parent_id') // Child records
                            ->orWhereExists(function ($query) {
                                $query->select(DB::raw(1))
                                    ->from('travel_quote_request as child')
                                    ->whereColumn('child.parent_id', 'travel_quote_request.id');
                            }); // Parent records that have children
                    });
                } else {
                    // Exclude records with parent-child relationships
                    $q->whereNull('parent_id') // Not a child
                        ->whereNotExists(function ($query) {
                            $query->select(DB::raw(1))
                                ->from('travel_quote_request as child')
                                ->whereColumn('child.parent_id', 'travel_quote_request.id');
                        }); // Not a parent

                    // Handle age group filtering with OR logic for multiple selections
                    $ageConditions = [];
                    if (in_array('0_64', $ageGroups)) {
                        $ageConditions[] = 'TIMESTAMPDIFF(YEAR, dob, CURDATE()) < 65';
                    }
                    if (in_array('65_plus', $ageGroups)) {
                        $ageConditions[] = 'TIMESTAMPDIFF(YEAR, dob, CURDATE()) >= 65';
                    }

                    if (! empty($ageConditions)) {
                        $q->whereRaw('('.implode(' OR ', $ageConditions).')');
                    }
                }
            });
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

    /**
     * Helper method to determine if default date filters should be applied
     */
    private function shouldApplyDefaultDatesFilter($requestParams = []): bool
    {
        return empty($this->getFilterValue('email', $requestParams)) &&
                empty($this->getFilterValue('mobile_no', $requestParams)) &&
                empty($this->getFilterValue('code', $requestParams)) &&
                empty($this->getFilterValue('renewal_batch', $requestParams)) &&
                empty($this->getFilterValue('quote_batch_id', $requestParams)) &&
                empty($this->getFilterValue('payment_due_date', $requestParams)) &&
                empty($this->getFilterValue('booking_date', $requestParams)) &&
                empty($this->getFilterValue('previous_quote_policy_number', $requestParams)) &&
                empty($this->getFilterValue('insurer_tax_invoice_number', $requestParams)) &&
                empty($this->getFilterValue('insurer_commission_tax_invoice_number', $requestParams));
    }

    public function processGridData($requestParams): Builder
    {
        $query = $this->buildGrid();

        $this->applyFilters($query, $requestParams);

        LoggerService::sql('TravelQuoteQueryBuilder Grid Data', $query);

        return $query;
    }
}
