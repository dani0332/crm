<?php

namespace App\Strategies;

use App\Enums\GenericRequestEnum;
use App\Enums\LookupsEnum;
use App\Enums\ManagementReportCategoriesEnum;
use App\Enums\ManagementReportTypeEnum;
use App\Enums\QuoteStatusEnum;
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

        $maxDays = ApplicationStorageService::getValueByKeyName(GenericRequestEnum::MAX_DAYS);

        $loginUserId = auth()->user()->id;

        $advisors = [];

        $teamIds = $this->getUserTeams($loginUserId);

        $teams = Team::whereIn('id', $teamIds->pluck('id'))
            ->select('name', 'id')
            ->orderBy('name')
            ->where('is_active', 1)
            ->get()
            ->keyBy('id')
            ->map(fn ($users) => $users->name)
            ->toArray();

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
            ->where('is_active', 1)->where('is_applicable_for_rules', 0)
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
        ];
    }
    public function applyFilters($query, $request)
    {
        //secondOptionalFieldName to be used in case of transaction due date, coming from a different table 'payment_splits'
        $dateFilter = function ($fieldName, $filterKey, $secondOptionalFieldName = null) use ($query, $request) {

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
                Carbon::today()->startOfDay()->format(config('constants.DB_DATE_FORMAT_MATCH')),
                Carbon::today()->endOfDay()->format(config('constants.DB_DATE_FORMAT_MATCH')),
            ];

            if ($secondOptionalFieldName) {
                $query->where(function ($query) use ($fieldName, $dateRange, $secondOptionalFieldName) {
                    $query->whereBetween($fieldName, $dateRange)
                        ->orWhereBetween($secondOptionalFieldName, $dateRange);
                });
            } else {
                $query->whereBetween($fieldName, $dateRange);
            }

        };

        switch ($request['reportCategory']) {
            case ManagementReportCategoriesEnum::SALE_SUMMARY:
            case ManagementReportCategoriesEnum::SALE_DETAIL:
                if ($request['reportType'] == ManagementReportTypeEnum::BOOKED_POLICIES) {
                    $dateFilter('personal_quotes.policy_booking_date', 'policyBookDate');
                } elseif ($request['reportType'] == ManagementReportTypeEnum::TRANSACTION_PAYMENTS) {
                    $dateFilter('p.payment_due_date', 'paymentDueDate', 'ps.due_date');
                }
                break;

            case ManagementReportCategoriesEnum::ENDING_POLICIES:
                if ($request['reportType'] == ManagementReportTypeEnum::EXPIRING_POLICIES) {
                    $dateFilter('p.policy_expiry_date', 'policyExpiredDate');
                }
                break;

            case ManagementReportCategoriesEnum::TRANSACTION:
                if ($request['reportType'] == ManagementReportTypeEnum::TRANSACTION_PAYMENTS) {
                    $dateFilter('p.payment_due_date', 'paymentDueDate', 'ps.due_date');
                } elseif ($request['reportType'] == ManagementReportTypeEnum::BOOKED_POLICIES) {
                    $dateFilter('personal_quotes.policy_booking_date', 'policyBookDate');
                }
                break;

            case ManagementReportCategoriesEnum::ENDORSEMENT:
                if ($request['reportType'] == ManagementReportTypeEnum::TRANSACTION_PAYMENTS) {
                    $dateFilter('p.payment_due_date', 'paymentDueDate', 'ps.due_date');
                } elseif ($request['reportType'] == ManagementReportTypeEnum::BOOKED_POLICIES) {
                    $dateFilter('send_update_logs.booking_date', 'policyBookDate');
                }
                break;

            case ManagementReportCategoriesEnum::ACTIVE_POLICIES:
                if ($request['reportType'] == ManagementReportTypeEnum::ACTIVE_POLICIES) {
                    $dateFilter = $request['createdAt'] ?? now()->startOfDay()->format(config('constants.DATE_FORMAT_ONLY'));
                    $query->where(function ($query) use ($dateFilter) {
                        $query->where('policy_start_date', '>=', $dateFilter)
                            ->orWhere('p.policy_expiry_date', '<=', $dateFilter);
                    });
                }
                break;
        }

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

        if (isset($request['teams']) && ! empty($request['teams']) && count($request['teams']) > 0) {
            $value = $request['teams'];
            $query->whereIn('t.id', $value);
        } else {
            $teamIds = $this->getUserTeams(auth()->user()->id);
            $query->whereIn('t.id', $teamIds->pluck('id'));
        }

        if (isset($request['subTeams']) && ! empty($request['subTeams'])) {
            $query->whereIn('u.sub_team_id', $request['subTeams']);
        }

        if (isset($request['leadSources']) && ! empty($request['leadSources'])) {
            $query->whereIn('personal_quotes.source', $request['leadSources']);
        }

        if (isset($request['includeCancelledPolicies']) && ! empty($request['includeCancelledPolicies'])) {
            if ($request['includeCancelledPolicies'] == 'Yes') {
                $query->where('personal_quotes.quote_status_id', QuoteStatusEnum::PolicyCancelled);
            } else {
                $query->where('personal_quotes.quote_status_id', QuoteStatusEnum::PolicyBooked);
            }
        }

        return $query;
    }

    public function getUtmGroup($request, $query)
    {
        if (isset($request['utmGroupBy']) && ! empty($request['utmGroupBy'])) {
            switch ($request['utmGroupBy']) {
                case 'UTM Campaign':
                    $query->addSelect('pqd.utm_campaign');
                    $query->whereNotNull('pqd.utm_campaign');

                    return 'pqd.utm_campaign';
                    break;
                case 'UTM Source':
                    $query->addSelect('pqd.utm_source');
                    $query->whereNotNull('pqd.utm_source');

                    return 'pqd.utm_source';
                    break;
                case 'UTM Medium':
                    $query->addSelect('pqd.utm_medium');
                    $query->whereNotNull('pqd.utm_medium');

                    return 'pqd.utm_medium';
                    break;
            }
        }
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
}
