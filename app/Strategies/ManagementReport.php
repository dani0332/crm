<?php

namespace App\Strategies;

use App\Enums\ApplicationStorageEnums;
use App\Enums\BranchEnum;
use App\Enums\BusinessTypeOfInsuranceIdEnum;
use App\Enums\EmirateEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\LookupsEnum;
use App\Enums\ManagementReportCategoriesEnum;
use App\Enums\ManagementReportTypeEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\SendUpdateLogStatusEnum;
use App\Models\Branch;
use App\Models\Department;
use App\Models\LeadSource;
use App\Models\Lookup;
use App\Models\Team;
use App\Services\ApplicationStorageService;
use App\Services\LookupService;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ManagementReport
{
    use TeamHierarchyTrait;

    // each report will have its own implementation of this method
    public function map($quote): array
    {
        return [];
    }

    public function getFilterOptions()
    {
        $user = auth()->user();
        $maxDays = ApplicationStorageService::getValueByKeyName(GenericRequestEnum::MAX_DAYS);
        if ($user->isDepartmentManager()) {
            $user->load('departments.teams');
            $teamIds = $user->departments->reduce(function ($carry, $department) {
                return $carry->merge(
                    $department->teams->pluck('team_id')
                );
            }, collect());

            $teamIds = $teamIds->all();
            $departments = $user->departments;
        } else {
            $teamIds = $this->getUserTeams($user->id)->pluck('id');
            $departments = Department::active()
                ->orderBy('name')
                ->get();
        }

        $teams = Team::whereIn('id', $teamIds)
            ->select('name', 'id')
            ->orderBy('name')
            ->active()
            ->get()
            ->keyBy('id')
            ->map(fn ($users) => $users->name)
            ->toArray();

        $lobs = $this->getUserProducts($user->id)->pluck('name');

        $reportCategories = [];
        foreach (ManagementReportCategoriesEnum::asArray() as $value) {
            $reportCategories[] = ['label' => $value, 'value' => $value];
        }

        $transactionTypes = Lookup::where('key', LookupsEnum::TRANSACTION_TYPES)
            ->get()
            ->map(fn ($item) => ['label' => $item->text, 'value' => $item->text])
            ->prepend(['label' => 'All', 'value' => ''], 'value')
            ->sortBy('label')
            ->values()
            ->toArray();

        $leadSources = LeadSource::query()
            ->select('name')
            ->where('is_active', 1)
            ->whereNotNull('name')
            ->orderBy('name')
            ->get()
            ->keyBy('name')
            ->map(fn ($users) => $users->name)
            ->toArray();

        $subSources = app(LookupService::class)->getSubSource()
            ->sortBy('id')
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'text' => $item->text,
                    'code' => $item->code,
                    'description' => $item->description,
                    'childs' => $item->childs
                        ->where('is_active', 1)
                        ->sortBy('text')
                        ->map(function ($c) {
                            return [
                                'id' => $c->id,
                                'text' => $c->text,
                                'description' => $c->description,
                            ];
                        })
                        ->values()
                        ->toArray(),
                ];
            })
            ->values()
            ->toArray();

        $branches = Branch::query()
            ->select('id', 'name')
            ->active()
            ->get();

        return [
            'maxDays' => $maxDays,
            'leadSources' => $leadSources,
            'teams' => $teams,
            'reportCategories' => $reportCategories,
            'transactionTypes' => $transactionTypes,
            'departments' => $departments,
            'lobs' => $lobs,
            'subSources' => $subSources,
            'branches' => $branches,
        ];
    }
    public function applyFilters($query, $request, $endorsementsQuery = false, $isSSR = false)
    {
        if (! Auth::check()) {
            $user = $request['user'] ?? null;
            unset($request['user']);
            Auth::login($user);
            DB::setDefaultConnection('mysql_read');

        }
        $this->applyDateFilters($query, $request, $endorsementsQuery);

        if (isset($request['transactionType'])) {
            $transactionTypes = Lookup::where('key', LookupsEnum::TRANSACTION_TYPES);
            $typeCode = $request['transactionType'];
            if ($typeCode !== null) {
                $type = $transactionTypes->where('text', $typeCode)->first();
                if ($type !== null) {
                    $typeId = $type->id;
                    $query->where('personal_quotes.transaction_type_id', $typeId);
                }
            }
        }

        $teams = $request['teams'] ?? [];
        $user = auth()->user();
        if ($user->isDepartmentManager() && empty($teams)) {
            $user->load('departments.teams');
            $teamIds = $user->departments->flatMap(function ($department) {
                return $department->teams->pluck('team_id');
            });
            $teams = $teamIds->isEmpty() ? [] : $teamIds->all();
        }
        $query = $this->filterTeams($query, $teams, $isSSR);

        if (isset($request['subTeams']) && ! empty($request['subTeams'])) {
            $query->whereIn('u.sub_team_id', $request['subTeams']);
        }

        $this->applyAdditionalFilters($query, $request);

        return $query;
    }

    private function applyAdditionalFilters($query, $request)
    {
        if (! empty($request['leadSources'])) {
            $query->whereIn('personal_quotes.source', $request['leadSources']);
        }

        // Sub Source filter: allow filtering by codes sent from UI
        if (! empty($request['subSources'])) {
            $codes = is_array($request['subSources']) ? $request['subSources'] : [$request['subSources']];
            $query->whereIn('personal_quotes.sub_source_id', $codes);
        }

        // Sub Source Option filter: gate to Sales Detail only (for now)
        if (! empty($request['sub_source_options_id'])) {
            $ids = is_array($request['sub_source_options_id']) ? $request['sub_source_options_id'] : [$request['sub_source_options_id']];
            $query->whereIn('personal_quotes.sub_source_options_id', $ids);
        }

        $departments = $request['department_id'] ?? [];
        $departments = is_array($departments) ? $request['department_id'] : [$departments];
        $pcpTag = $request['pcp_tag'] ?? [];
        $pcpTag = is_array($pcpTag) ? $request['pcp_tag'] : [$pcpTag];
        $user = auth()->user();
        if ($user->isDepartmentManager() && empty($departments)) {
            $departments = $user->departments->pluck('id');
        }

        if (! empty($departments) || $user->isDepartmentManager()) {
            $query->whereIn('u.department_id', $departments);
        }

        if (isset($request['includeCancelledPolicies']) && ! empty($request['includeCancelledPolicies']) && $request['includeCancelledPolicies'] == 'No') {
            $query->where('personal_quotes.quote_status_id', '!=', QuoteStatusEnum::PolicyCancelled);
        }

        $lobs = collect($request['lob']);
        if ($lobs->isEmpty()) {
            $lobs = $this->getUserProducts($user->id)->pluck('name');
        }
        $lobs = $lobs->map(fn ($item) => quoteTypeCode::getQuoteTypeCodeFromProductName($item));
        $lobsIds = $lobs->map(fn ($item) => (
            in_array($item, [quoteTypeCode::CORPLINE, quoteTypeCode::GroupMedical])
                ? QuoteTypeId::Business
                : QuoteTypes::getIdFromValue($item
                )))
            ->filter()
            ->toArray();
        $lobs = $lobs->toArray();

        if (in_array(quoteTypeCode::GroupMedical, $lobs) && ! in_array(quoteTypeCode::CORPLINE, $lobs)) {
            $query->where(function ($query) {
                $query->where('personal_quotes.business_type_of_insurance_id', BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL)
                    ->orWhereNull('personal_quotes.business_type_of_insurance_id');
            });
        } elseif (! in_array(quoteTypeCode::GroupMedical, $lobs) && in_array(quoteTypeCode::CORPLINE, $lobs)) {
            $query->where(function ($query) {
                $query->where('personal_quotes.business_type_of_insurance_id', '!=', BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL)
                    ->orWhereNull('personal_quotes.business_type_of_insurance_id');
            });
        }

        $query->when(! empty($pcpTag) && ! in_array('all', $pcpTag), function ($q) use ($pcpTag) {
            $filteredTags = array_diff($pcpTag, ['no']);
            $hasNoTag = in_array('no', $pcpTag);

            $q->when($hasNoTag, function ($subQ) use ($filteredTags) {
                $subQ->whereNull('pcp_tag')
                    ->when(! empty($filteredTags), fn ($q) => $q->orWhereIn('pcp_tag', $filteredTags));
            }, fn ($q) => $q->whereIn('pcp_tag', $pcpTag));
        });

        $lob = isset($request['lob']) ? (is_array($request['lob']) ? $request['lob'] : [$request['lob']]) : [];
        if (! empty($lob) && in_array(quoteTypeCode::Health, $lob) && isset($request['pec_flag']) && $request['pec_flag'] !== 'all') {
            if ($request['pec_flag'] == '1') {
                $query->whereExists(function ($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('health_quote_request')
                        ->whereColumn('health_quote_request.id', 'personal_quotes.quote_id')
                        ->where('personal_quotes.quote_type_id', '=', QuoteTypeId::Health)
                        ->whereNotNull('health_quote_request.pec_marked_at');
                });
            } else {
                $query->whereExists(function ($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('health_quote_request')
                        ->whereColumn('health_quote_request.id', 'personal_quotes.quote_id')
                        ->where('personal_quotes.quote_type_id', '=', QuoteTypeId::Health)
                        ->whereNull('health_quote_request.pec_marked_at');
                });
            }
        }

        $query->when(! empty($request['branch']), function ($q) use ($request) {
            // Normalize branch to array to handle both scalar and array inputs
            $branches = is_array($request['branch']) ? $request['branch'] : [$request['branch']];

            if (in_array('not_applicable', $branches)) {
                $q->where('personal_quotes.is_branch_applicable', 0);
            } elseif (in_array('not_assigned', $branches)) {
                $q->where('personal_quotes.is_branch_applicable', 1)
                    ->whereNull('b.id');
            } else {
                $q->where('personal_quotes.is_branch_applicable', 1)
                    ->whereIn('b.id', $branches);
            }

        });

        $query->whereIn('personal_quotes.quote_type_id', $lobsIds);
    }

    protected function getDateFilter($query, $request, $fieldName, $filterKey, $secondOptionalFieldName = null, $isEndorsements = false)
    {
        if ($request[$filterKey] != null) {
            if (is_array($request[$filterKey])) {
                $dates = [];
                foreach ($request[$filterKey] as $key => $dateString) {
                    // Validate date before parsing
                    if (isValidDate($dateString)) {
                        $carbonDate = Carbon::parse($dateString);
                        if ($key == 0) {
                            $dates[$key] = $carbonDate->startOfDay()->format(config('constants.DB_DATE_FORMAT_MATCH'));
                        } else {
                            $dates[$key] = $carbonDate->endOfDay()->format(config('constants.DB_DATE_FORMAT_MATCH'));
                        }
                    } else {
                        // Provide default date if null
                        if ($key == 0) {
                            $dates[$key] = today()->startOfDay()->format(config('constants.DB_DATE_FORMAT_MATCH'));
                        } else {
                            $dates[$key] = today()->endOfDay()->format(config('constants.DB_DATE_FORMAT_MATCH'));
                        }
                    }
                }
                $request[$filterKey] = $dates;
            } else {
                // Validate date before parsing
                if (isValidDate($request[$filterKey])) {
                    $carbonDate = Carbon::parse($request[$filterKey]);
                    $dates = $carbonDate->startOfDay();
                    $request[$filterKey] = $dates;
                } else {
                    // Provide default date if null
                    $request[$filterKey] = today()->startOfDay();
                }
            }
        }
        $dateRange = $request[$filterKey] ?? [
            today()->startOfDay()->format(config('constants.DB_DATE_FORMAT_MATCH')),
            today()->endOfDay()->format(config('constants.DB_DATE_FORMAT_MATCH')),
        ];

        if ($secondOptionalFieldName) {
            $query->where(function ($query) use ($fieldName, $dateRange, $secondOptionalFieldName) {
                $query->whereBetween($fieldName, $dateRange)
                    ->orWhereBetween($secondOptionalFieldName, $dateRange);
            });
        } else {
            $includeFailedBookings = ApplicationStorageService::getValueByKeyName(ApplicationStorageEnums::MR_INCLUDE_FAILED_BOOKINGS);
            $failedBookingDateFrom = ApplicationStorageService::getValueByKeyName(ApplicationStorageEnums::MR_FAILED_BOOKING_DATE_FROM);

            if ($isEndorsements && $includeFailedBookings && $filterKey == 'policyBookDate') {

                $query->where(function ($query) use ($fieldName, $dateRange, $failedBookingDateFrom) {
                    $query->whereBetween($fieldName, $dateRange)
                        ->orWhere(function ($query) use ($failedBookingDateFrom, $dateRange) {
                            $query->where('send_update_logs.status', SendUpdateLogStatusEnum::UPDATE_BOOKING_FAILED)
                                ->whereExists(function ($query) use ($failedBookingDateFrom, $dateRange) {
                                    $query->select(DB::raw(1))
                                        ->from('send_update_status_logs')
                                        ->whereColumn('send_update_status_logs.send_update_log_id', 'send_update_logs.id')
                                        ->where('send_update_status_logs.current_status', SendUpdateLogStatusEnum::UPDATE_BOOKING_FAILED)
                                        ->where('send_update_status_logs.created_at', '>=', $failedBookingDateFrom)
                                        ->whereBetween('send_update_status_logs.created_at', $dateRange);
                                });
                        });
                });

            } elseif ($includeFailedBookings && $filterKey == 'policyBookDate') {
                $query->where(function ($query) use ($fieldName, $dateRange, $failedBookingDateFrom) {
                    $query->whereBetween($fieldName, $dateRange)
                        ->orWhere(function ($query) use ($failedBookingDateFrom, $dateRange) {
                            $query->whereBetween('personal_quotes.quote_status_date', $dateRange)
                                ->where('personal_quotes.quote_status_date', '>=', $failedBookingDateFrom)
                                ->where('personal_quotes.quote_status_id', QuoteStatusEnum::POLICY_BOOKING_FAILED);
                        });
                });
            } else {
                $query->whereBetween($fieldName, $dateRange);
            }
        }
    }

    private function isReportType($request, $type)
    {
        return $request['reportType'] == $type;
    }

    private function applySaleFilters($query, $request, $endorsementsQuery)
    {
        if ($this->isReportType($request, ManagementReportTypeEnum::BOOKED_POLICIES)) {
            $field = $endorsementsQuery ? 'send_update_logs.booking_date' : 'personal_quotes.policy_booking_date';
            $this->getDateFilter($query, $request, $field, 'policyBookDate', null, $endorsementsQuery);
        } elseif ($this->isReportType($request, ManagementReportTypeEnum::APPROVED_TRANSACTIONS)) {
            $this->getDateFilter($query, $request, 'p.payment_due_date', 'paymentDueDate', 'ps.due_date');
        } elseif ($this->isReportType($request, ManagementReportTypeEnum::PAID_TRANSACTIONS)) {
            $this->getDateFilter($query, $request, 'ps.verified_at', 'paymentDate');
        }
    }

    private function applyDateFilters($query, $request, $endorsementsQuery)
    {
        switch ($request['reportCategory']) {
            case ManagementReportCategoriesEnum::SALE_SUMMARY:
            case ManagementReportCategoriesEnum::SALE_DETAIL:
                $this->applySaleFilters($query, $request, $endorsementsQuery);
                break;

            case ManagementReportCategoriesEnum::ENDING_POLICIES:
                if ($this->isReportType($request, ManagementReportTypeEnum::EXPIRING_POLICIES)) {
                    $this->getDateFilter($query, $request, 'personal_quotes.policy_expiry_date', 'policyExpiredDate');
                }
                break;

            case ManagementReportCategoriesEnum::TRANSACTION:
                if ($this->isReportType($request, ManagementReportTypeEnum::APPROVED_TRANSACTIONS)) {
                    $this->getDateFilter($query, $request, 'p.payment_due_date', 'paymentDueDate', 'ps.due_date');
                } elseif ($this->isReportType($request, ManagementReportTypeEnum::BOOKED_POLICIES)) {
                    $this->getDateFilter($query, $request, 'personal_quotes.policy_booking_date', 'policyBookDate');
                } elseif ($this->isReportType($request, ManagementReportTypeEnum::PAID_TRANSACTIONS)) {
                    $this->getDateFilter($query, $request, 'ps.verified_at', 'paymentDate');
                }
                break;

            case ManagementReportCategoriesEnum::ENDORSEMENT:
                if ($this->isReportType($request, ManagementReportTypeEnum::APPROVED_TRANSACTIONS)) {
                    $this->getDateFilter($query, $request, 'send_update_logs.invoice_date', 'paymentDueDate', 'ps.due_date');
                } elseif ($this->isReportType($request, ManagementReportTypeEnum::BOOKED_POLICIES)) {
                    $this->getDateFilter($query, $request, 'send_update_logs.booking_date', 'policyBookDate', null, true);
                } elseif ($this->isReportType($request, ManagementReportTypeEnum::PAID_TRANSACTIONS)) {
                    $this->getDateFilter($query, $request, 'ps.verified_at', 'paymentDate');
                }
                break;

            case ManagementReportCategoriesEnum::ACTIVE_POLICIES:
                if ($this->isReportType($request, ManagementReportTypeEnum::ACTIVE_POLICIES)) {
                    $dateFilter = $request['createdAt'] ?? now()->startOfDay()->format(config('constants.DATE_FORMAT_ONLY'));
                    $query->where(function ($query) use ($dateFilter) {
                        $query->where('personal_quotes.policy_start_date', '>=', $dateFilter)
                            ->orWhere('p.policy_expiry_date', '<=', $dateFilter);
                    });
                }
                break;

            case ManagementReportCategoriesEnum::INSTALLMENT:
                if ($this->isReportType($request, ManagementReportTypeEnum::APPROVED_TRANSACTIONS)) {
                    $this->getDateFilter($query, $request, 'ps.due_date', 'paymentDueDate');
                } elseif ($this->isReportType($request, ManagementReportTypeEnum::PAID_TRANSACTIONS)) {
                    $this->getDateFilter($query, $request, 'ps.verified_at', 'paymentDate');
                }
                break;

            default:
                break;
        }
    }

    protected function filterTeams($query, $teams, $isSSR = false)
    {
        if ($teams && ! is_array($teams)) {
            $teams = [$teams];
        }

        if (! $isSSR) {
            if ((! empty($teams) && count($teams) > 0) || auth()->user()->isDepartmentManager()) {
                $query->whereIn('t.id', $teams);
            }

            return $query;
        }

        if ((! empty($teams) && count($teams) > 0) || auth()->user()->isDepartmentManager()) {
            $query->whereIn('u.id', function ($query) use ($teams) {
                $query->select('user_team.user_id')
                    ->from('user_team')
                    ->whereIn('user_team.team_id', $teams);
            });
        }

        return $query;
    }

    public function getUtmGroup($request, $query)
    {
        $utmGroup = null;

        if (isset($request['utmGroupBy']) && ! empty($request['utmGroupBy'])) {
            switch ($request['utmGroupBy']) {
                case 'UTM Campaign':
                    $query->addSelect('pqd.utm_campaign');
                    $query->whereNotNull('pqd.utm_campaign');

                    $utmGroup = 'pqd.utm_campaign';
                    break;
                case 'UTM Source':
                    $query->addSelect('pqd.utm_source');
                    $query->whereNotNull('pqd.utm_source');

                    $utmGroup = 'pqd.utm_source';
                    break;
                case 'UTM Medium':
                    $query->addSelect('pqd.utm_medium');
                    $query->whereNotNull('pqd.utm_medium');

                    $utmGroup = 'pqd.utm_medium';
                    break;

                default:
                    $utmGroup = null;
                    break;
            }
        }

        return $utmGroup;
    }

    private function initializeSums($handle, $nonIntegarIndexes, $headers, $data)
    {
        $sums = array_fill(0, count($headers), 0.00);

        $data = collect($data);
        foreach ($data as $index => $quote) {

            fputcsv($handle, $this->map($quote));

            // Update sums
            foreach ($this->map($quote) as $index => $value) {
                if (! in_array($index, $nonIntegarIndexes)) {
                    $floatValue = (float) str_replace(',', '', $value);
                    $sums[$index] += $floatValue;
                } elseif ($index == 0) {
                    $sums[$index] = 'TOTAL';
                } else {
                    $sums[$index] = 'N/A';
                }
            }

        }

        return $sums;
    }

    /**
     * download csv export file function
     *
     * @param [type] $fileName
     * @param [type] $data
     * @param  array  $headers
     * @param  array  $nonIntegarIndexes
     * @return void
     */
    public function download($fileName, $data, $headers = [], $nonIntegarIndexes = [])
    {
        return new StreamedResponse(function () use ($data, $headers, $nonIntegarIndexes) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            // Prepare an array to hold the sums
            $sums = $this->initializeSums($handle, $nonIntegarIndexes, $headers, $data);

            $formattedSums = [];

            // Format the sums to always show up to two decimal places
            foreach ($sums as $index => &$sum) {
                if (! in_array($index, $nonIntegarIndexes)) {
                    $formattedSums[$index] = number_format($sum, 2);
                } else {
                    $formattedSums[$index] = $sum;
                }
            }
            // Add a row for the sums
            fputcsv($handle, $formattedSums);

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'.csv"',
        ]);
    }

    /**
     * mapper sub query to get sub type of business quotes function
     *
     * @param [type] $item
     * @return void
     */
    public function businessSubTypeMapper($item)
    {
        $businessTypeOfInsurance = DB::table('business_quote_request')
            ->join('business_type_of_insurance', 'business_quote_request.business_type_of_insurance_id', '=', 'business_type_of_insurance.id')
            ->where('business_quote_request.code', $item->code)
            ->select('business_type_of_insurance.text')
            ->first();
        $item->sub_type_line_of_business = $businessTypeOfInsurance ? $businessTypeOfInsurance->text : 'N/A';

        return $item;
    }

    private static function mapEndorsementsToReport($item, $endorsementData, $request)
    {
        foreach ($endorsementData as $endorsement) {
            if ($item[$request->groupBy] === $endorsement->{$request->groupBy} && $item->branch_name === $endorsement->branch_name) {
                $item->total_endorsements = $endorsement->total_endorsements ?? 0;
                $item->total_transaction = $item->total_policies + $item->total_endorsements;
                $item->endorsements_amount = (float) $endorsement->total_endorsement_amount;
                $item->commission_vat_applicable = (float) $item->commission_vat_applicable + (float) $endorsement->commission_vat_applicable;
                $item->commission_vat = (float) $item->commission_vat + (float) $endorsement->commission_vat;
                $item->commission_vat_not_applicable = (float) $item->commission_vat_not_applicable + (float) $endorsement->commission_vat_not_applicable;
                $item->total_price =
                    ($item->total_price ? (float) $item->total_price : 0) +
                    ($endorsement->total_endorsement_amount ? (float) $endorsement->total_endorsement_amount : 0);
            }
        }

        return $item;
    }

    /**
     * process endorsements data function
     *
     * @param [type] $reportData
     * @param [type] $endorsementData
     * @param [type] $request
     * @return void
     */
    public static function processEndorsementsData($reportData, $endorsementData, $request)
    {
        /**
         * map the endorsement data to the report data
         */
        $reportData = $reportData->map(function ($item) use ($request, $endorsementData) {
            return self::mapEndorsementsToReport($item, $endorsementData, $request);
        });

        /**
         * check if there are any endorsements that are not in the report data
         */
        foreach ($endorsementData as $endorsement) {
            $found = $reportData->contains(function ($item) use ($request, $endorsement) {
                return $item->{$request->groupBy} === $endorsement->{$request->groupBy} && $item->branch_name === $endorsement->branch_name;
            });
            if (! $found) {
                $endorsement->total_policies = 0;
                $endorsement->endorsements_amount = (float) $endorsement->total_endorsement_amount;
                $endorsement->total_transaction = $endorsement->total_endorsements;
                $endorsement->total_price = (float) $endorsement->total_endorsement_amount;
                $endorsement->commission_vat_applicable = (float) $endorsement->commission_vat_applicable;
                $endorsement->commission_vat = (float) $endorsement->commission_vat;
                $endorsement->commission_vat_not_applicable = (float) $endorsement->commission_vat_not_applicable;
                $reportData->push($endorsement);
            }
        }

        return $reportData;
    }

    /**
     * @param  array  $values
     * @param  string  $separater
     * @return string|null
     */
    protected function concatValues($values, $separater)
    {
        $val = implode($separater, array_filter($values, function ($value) {
            return ! empty($value);
        }));

        return ! empty($val) ? $val : null;
    }

    protected function getQuoteRouteName($quoteTypeID, $btoi)
    {
        $types = [
            1 => 'car.show',
            2 => 'home-quotes-show',
            3 => 'health.show',
            4 => 'life-quotes-show',
            5 => 'business.show',
            6 => 'bike-quotes-show',
            7 => 'yacht-quotes-show',
            8 => 'travel.show',
            9 => 'pet-quotes-show',
            10 => 'cycle-quotes-show',
            11 => 'jetski-quotes-show',
            18 => 'savings-quotes-show',
            19 => 'cyber-quotes-show',
            20 => 'device-quotes-show',
        ];

        $routeName = $types[$quoteTypeID];
        if ($quoteTypeID == 5 && $btoi == 5) {
            $routeName = 'amt.show';
        }

        return $routeName;
    }

    protected function getPaymentMappingCTE(): string
    {
        $quoteTypesUsingQuoteId = [
            QuoteTypeId::Car,
            QuoteTypeId::Health,
            QuoteTypeId::Travel,
            QuoteTypeId::Business,
        ];

        $quoteIdConditions = implode(',', $quoteTypesUsingQuoteId);

        return "
            SELECT
                pq.id,
                CASE
                    WHEN pq.quote_type_id IN ({$quoteIdConditions}) THEN pq.quote_id
                    ELSE pq.id
                END AS payment_join_id,

                CASE
                    WHEN pq.quote_type_id = ".QuoteTypeId::Car." THEN 'App\\\\Models\\\\CarQuote'
                    WHEN pq.quote_type_id = ".QuoteTypeId::Health." THEN 'App\\\\Models\\\\HealthQuote'
                    WHEN pq.quote_type_id = ".QuoteTypeId::Travel." THEN 'App\\\\Models\\\\TravelQuote'
                    WHEN pq.quote_type_id = ".QuoteTypeId::Business." THEN 'App\\\\Models\\\\BusinessQuote'
                    ELSE 'App\\\\Models\\\\PersonalQuote'
                END AS payment_join_type
            FROM personal_quotes pq
        ";
    }

    public function paymentJoin($query, $additionalConditions = null, $alias = 'p', $joinType = 'join', $cteJoin = 'join')
    {
        $cte = $this->getPaymentMappingCTE();
        $query->withExpression('personal_quotes_mapped', $cte);

        // Join the CTE to create the mapping based on the CTE join type
        $query->{$cteJoin}('personal_quotes_mapped as pqm', 'pqm.id', '=', 'personal_quotes.id');

        // Then join payments using the mapped fields
        $query->{$joinType}("payments as {$alias}", function ($join) use ($additionalConditions, $alias) {
            $join->on("{$alias}.paymentable_id", '=', 'pqm.payment_join_id')
                ->on("{$alias}.paymentable_type", '=', 'pqm.payment_join_type')
                ->whereNull("{$alias}.send_update_log_id");

            // Apply additional conditions if provided
            if ($additionalConditions && is_callable($additionalConditions)) {
                $additionalConditions($join);
            }
        });
    }

    protected function getBranchMappingCTE(): string
    {
        $now = now()->format('Y-m-d H:i:s');
        $healthQuoteType = QuoteTypeId::Health;
        $businessQuoteType = QuoteTypeId::Business;
        $deviceQuoteType = QuoteTypeId::Device;
        $groupMedicalId = BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL;
        $abuDhabiEmirate = EmirateEnum::ABU_DHABI;
        $abuDhabiBranch = BranchEnum::ABU_DHABI->value;
        $dubaiBranch = BranchEnum::DUBAI->value;

        return "
            SELECT
                pq.id,
                COALESCE(
                    pq.branch_id,
                    oc.target_branch_id,
                    CASE
                        WHEN pq.advisor_id IS NOT NULL AND pq.quote_type_id = {$healthQuoteType} AND hqr.emirate_of_your_visa_id = {$abuDhabiEmirate}
                            THEN {$abuDhabiBranch}
                        WHEN pq.advisor_id IS NOT NULL AND pq.business_type_of_insurance_id = {$groupMedicalId} AND bqr.emirate_of_registration_id = {$abuDhabiEmirate}
                            THEN {$abuDhabiBranch}
                        WHEN pq.quote_type_id = {$deviceQuoteType}
                            THEN {$dubaiBranch}
                        ELSE ub.branch_id
                    END
                ) AS resolved_branch_id
            FROM personal_quotes pq
            LEFT JOIN user_branches ub ON ub.user_id = pq.advisor_id
                AND ub.is_primary = 1
                AND ub.status = 1
                AND pq.branch_id IS NULL
            LEFT JOIN branch_override_config oc ON oc.source_branch_id = ub.branch_id
                AND oc.quote_type_id = pq.quote_type_id
                AND oc.start_date < '{$now}'
                AND (oc.end_date IS NULL OR oc.end_date > '{$now}')
                AND pq.branch_id IS NULL
                AND (pq.business_type_of_insurance_id IS NULL OR pq.business_type_of_insurance_id != {$groupMedicalId})
            LEFT JOIN health_quote_request hqr ON hqr.id = pq.quote_id
                AND pq.quote_type_id = {$healthQuoteType}
                AND pq.branch_id IS NULL
            LEFT JOIN business_quote_request bqr ON bqr.id = pq.quote_id
                AND pq.quote_type_id = {$businessQuoteType}
                AND pq.branch_id IS NULL
        ";
    }

    protected function branchJoin($query): void
    {
        $branchMappingCte = $this->getBranchMappingCTE();
        $query->withExpression('branch_mapped', $branchMappingCte);

        $query->leftJoin('branch_mapped as bm', 'bm.id', '=', 'personal_quotes.id')
            ->leftJoin('branches as b', 'b.id', '=', 'bm.resolved_branch_id');
    }
}
