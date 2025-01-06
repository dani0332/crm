<?php

namespace App\Strategies;

use App\Enums\BusinessTypeOfInsuranceIdEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\LookupsEnum;
use App\Enums\ManagementReportCategoriesEnum;
use App\Enums\ManagementReportTypeEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\Department;
use App\Models\LeadSource;
use App\Models\Lookup;
use App\Models\Team;
use App\Services\ApplicationStorageService;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
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

        return [
            'maxDays' => $maxDays,
            'leadSources' => $leadSources,
            'teams' => $teams,
            'reportCategories' => $reportCategories,
            'transactionTypes' => $transactionTypes,
            'departments' => $departments,
            'lobs' => $lobs,
        ];
    }
    public function applyFilters($query, $request, $endorsementsQuery = false, $isSSR = false)
    {
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

        $departments = $request['department_id'] ?? [];
        $departments = is_array($departments) ? $request['department_id'] : [$departments];
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
        $lobsIds = $lobs->map(fn ($item) => (
            in_array($item, [quoteTypeCode::CORPLINE, quoteTypeCode::GroupMedical])
                ? QuoteTypeId::Business
                : QuoteTypes::getIdFromValue($item
                )))
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

        $query->whereIn('personal_quotes.quote_type_id', $lobsIds);
    }

    protected function getDateFilter($query, $request, $fieldName, $filterKey, $secondOptionalFieldName = null)
    {
        if ($request[$filterKey] != null) {
            if (is_array($request[$filterKey])) {
                $dates = [];
                foreach ($request[$filterKey] as $key => $dateString) {
                    $carbonDate = Carbon::parse($dateString);
                    if ($key == 0) {
                        $dates[$key] = $carbonDate->startOfDay()->format(config('constants.DB_DATE_FORMAT_MATCH'));
                    } else {
                        $dates[$key] = $carbonDate->endOfDay()->format(config('constants.DB_DATE_FORMAT_MATCH'));
                    }
                }
                $request[$filterKey] = $dates;
            } else {
                $carbonDate = Carbon::parse($request[$filterKey]);
                $dates = $carbonDate->startOfDay();
                $request[$filterKey] = $dates;
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
            $query->whereBetween($fieldName, $dateRange);
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
            $this->getDateFilter($query, $request, $field, 'policyBookDate');
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
                    $this->getDateFilter($query, $request, 'p.policy_expiry_date', 'policyExpiredDate');
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
                    $this->getDateFilter($query, $request, 'send_update_logs.booking_date', 'policyBookDate');
                } elseif ($this->isReportType($request, ManagementReportTypeEnum::PAID_TRANSACTIONS)) {
                    $this->getDateFilter($query, $request, 'ps.verified_at', 'paymentDate');
                }
                break;

            case ManagementReportCategoriesEnum::ACTIVE_POLICIES:
                if ($this->isReportType($request, ManagementReportTypeEnum::ACTIVE_POLICIES)) {
                    $dateFilter = $request['createdAt'] ?? now()->startOfDay()->format(config('constants.DATE_FORMAT_ONLY'));
                    $query->where(function ($query) use ($dateFilter) {
                        $query->where('policy_start_date', '>=', $dateFilter)
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
            if ($item[$request->groupBy] === $endorsement->{$request->groupBy}) {
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
            $found = $reportData->contains($request->groupBy, $endorsement->{$request->groupBy});
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
            2 => 'home.show',
            3 => 'health.show',
            4 => 'life-quotes-show',
            5 => 'business.show',
            6 => 'bike-quotes-show',
            7 => 'yacht-quotes-show',
            8 => 'travel.show',
            9 => 'pet-quotes-show',
            10 => 'cycle-quotes-show',
            11 => 'jetski-quotes-show',
        ];

        $routeName = $types[$quoteTypeID];
        if ($quoteTypeID == 5 && $btoi == 5) {
            $routeName = 'amt.show';
        }

        return $routeName;
    }
}
