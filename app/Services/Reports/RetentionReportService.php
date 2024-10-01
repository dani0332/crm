<?php

namespace App\Services\Reports;

use App\Enums\GenericRequestEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\MonthNameEnum;
use App\Enums\PermissionsEnum;
use App\Enums\quoteBusinessTypeCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\RetentionReportEnum;
use App\Enums\RolesEnum;
use App\Models\PersonalQuote;
use App\Models\RenewalBatch;
use App\Models\UserManager;
use App\Repositories\QuoteTypeRepository;
use App\Services\ApplicationStorageService;
use App\Services\BaseService;
use App\Services\DropdownSourceService;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\GetUserTreeTrait;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class RetentionReportService extends BaseService
{
    use GenericQueriesAllLobs, GetUserTreeTrait, TeamHierarchyTrait;

    private $dateFormat;
    private $policyExpiryColumnName;
    private $monthColumnName;

    public function __construct()
    {
        $this->dateFormat = config('constants.DATE_FORMAT_ONLY');
        $this->policyExpiryColumnName = 'personal_quotes.previous_policy_expiry_date';
        if (request()['displayBy'] === RetentionReportEnum::MONTHLY) {
            $this->monthColumnName = $this->policyExpiryColumnName;
        } else {
            $this->monthColumnName = 'renewal_batches.start_date';
        }
    }

    /**
     * Retrieves report data based on LOB & other request parameters.
     *
     * @return array
     */
    public function getReportData($request, $isExport = false)
    {
        // If the model object is not found or the user is not an advisor or manager and no permission, return an empty array
        if ($this->getQuoteType($request) == null || ! $this->isAdvisorManager()) {
            return [[], []];
        }

        // Build the query based on the model object and request parameters
        $query = $this->buildQuery($request);
        $allData = $query->get();

        $aggregatedData = $this->getSummarizedData($allData);

        if (! $isExport) {
            // Paginate the query results and retain the query string
            $reportData = $query->paginate(12)->withQueryString();
        } else {
            $reportData = $allData;
        }

        // Add some new column into report date and return the result
        return [
            $this->formatReportData($reportData, $aggregatedData),
            $aggregatedData,
        ];
    }

    /**
     * Retrieves the quote type from the request or defaults to the user's product name.
     *
     * @return string
     */
    private function getQuoteType($request)
    {
        // Return the 'lob' parameter from the request if it exists, otherwise return the user's product name
        return $request['lob'] ?? $this->getUserPorductName();
    }

    /**
     * Checks if the authenticated user is either a manager or an advisor with the appropriate permissions.
     *
     * @return bool
     */
    private function isAdvisorManager()
    {
        if (auth()->user()->isAdmin()) {
            return true;
        }
        // Check if the user is a manager or deputy and lacks the permission to view the manager retention report
        if (
            (auth()->user()->isManagerOrDeputy() && ! Auth::user()->can(PermissionsEnum::MANAGER_RETENTION_REPORT_VIEW)) ||
            // Check if the user is an advisor and lacks the permission to view the advisor retention report
            (auth()->user()->isAdvisor() && ! Auth::user()->can(PermissionsEnum::ADVISOR_RETENTION_REPORT_VIEW))
        ) {
            return false;
        }

        return true;

    }

    /**
     * Builds the query for retrieving retention report data based on the provided model and request parameters.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildQuery($request)
    {
        // Initialize the query with the necessary select statements and joins
        $query = PersonalQuote::query()
            ->selectRaw("renewal_batch_id, renewal_batch, MONTHNAME({$this->monthColumnName}) as `month`,
                users.name as `advisor_name`,
                count(*) as total,
                SUM(CASE WHEN quote_status_id = ".QuoteStatusEnum::Lost.' THEN 1 ELSE 0 END) as lost,
                SUM(CASE WHEN quote_status_id IN ('.QuoteStatusEnum::Fake.', '.QuoteStatusEnum::Duplicate.') THEN 1 ELSE 0 END) as invalid,
                SUM(CASE WHEN quote_status_id = '.QuoteStatusEnum::PolicyBooked.' THEN 1 ELSE 0 END) as sales, advisor_id')
            ->join('users', 'advisor_id', '=', 'users.id');

        // Apply general filters to the query based on the request parameters
        $this->applyFilters($query, $request);
        // Group the query results by advisor name
        $query->groupBy('users.id', 'month');

        // Sort the results in chronological order by month
        $months = implode("', '", MonthNameEnum::all());
        $query->orderByRaw("FIELD(MONTHNAME({$this->policyExpiryColumnName}), '{$months}')");

        return $query;
    }

    /**
     * Applies various filters to the query based on the request parameters.
     *
     * @return void
     */
    private function applyFilters($query, $request, $isDetailsFilter = false)
    {
        // Apply permission-based filters to the query
        $this->applyPermissionFilters($query, $request);

        // Apply advisor-related filters to the query
        $this->applyAdvisorFilters($query, $request);

        // Apply line of business filters
        $this->applyLineOfBusinessFilters($query, $request);

        // Apply date range filters to the query
        $this->applyDateFilters($query, $request, $isDetailsFilter);

        // Apply team-related filters to the query
        $this->applyTeamFilters($query, $request);

        // Apply quote type filters to the query
        $this->applyQuoteTypeFilters($query, $request);

        // Apply additional filters based on the 'displayBy' parameter in the request
        $this->applyDisplayByFilters($query, $request, $isDetailsFilter);
    }

    /**
     * Applies filters based on the 'displayBy' parameter in the request.
     *
     * @return void
     */
    private function applyDisplayByFilters($query, $request, $isDetailsFilter)
    {
        if (isset($request['displayBy'])) {
            if ($request['displayBy'] === RetentionReportEnum::BATCH) {
                $this->applyFilterForBatch($query, $request, $isDetailsFilter);
            } elseif ($request['displayBy'] === RetentionReportEnum::MONTHLY) {
                $this->applyFilterByMonth($query, $request);
            }
        }
    }

    /**
     * Applies line of business filters to the query.
     *
     * @return void
     */
    private function applyLineOfBusinessFilters($query, $request)
    {
        $lob = $quoteType = $this->getQuoteType($request);
        $lob = in_array($lob, [quoteTypeCode::GroupMedical, quoteTypeCode::CORPLINE]) ? quoteTypeCode::Business : $lob;
        $lobId = QuoteTypeRepository::where('code', $lob)->first();
        $query->where('personal_quotes.quote_type_id', $lobId->id);

        $query->where('source', LeadSourceEnum::RENEWAL_UPLOAD);
        if (in_array($quoteType, [quoteTypeCode::GroupMedical, quoteTypeCode::CORPLINE])) {
            if ($quoteType == quoteTypeCode::GroupMedical) {
                $query->where('business_type_of_insurance_id', '=', quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical));
            } elseif ($quoteType == quoteTypeCode::CORPLINE) {
                $query->where('business_type_of_insurance_id', '!=', quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical));
            }
        }
    }

    /**
     * Applies various filters to the query based on the provided filters object.
     * This filters effect only when we get report details
     *
     * @return void
     */
    private function applyFiltersToQuery($query, $filters)
    {
        // Apply advisor ID filter if it is set in the filters object
        if (isset($filters->advisor_id)) {
            $query->where('advisor_id', $filters->advisor_id);
        }

        if (($filters->displayBy == null || $filters->displayBy == RetentionReportEnum::BATCH) && $filters->renewal_batch_id) {
            $query->where('renewal_batch_id', $filters->renewal_batch_id);
        }

        // Apply type-based filters if the type is set in the filters object
        if (isset($filters->type)) {
            switch ($filters->type) {
                case RetentionReportEnum::LOST:
                    // Filter for lost quotes
                    $query->where('quote_status_id', QuoteStatusEnum::Lost);
                    break;
                case RetentionReportEnum::INVALID:
                    // Filter for invalid quotes (fake or duplicate)
                    $query->whereIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate]);
                    break;
                case RetentionReportEnum::SALES:
                    // Filter for sales (policy booked)
                    $query->where('quote_status_id', QuoteStatusEnum::PolicyBooked);
                    break;
            }
        }
    }

    /**
     * Applies date filters to the query based on the request parameters.
     * If no specific date filters are provided, it defaults to filtering by the previous month start date to the next month end date.
     * This method will work once there is no display by filter setup
     *
     * @return void
     */
    private function applyDateFilters($query, $request, $isDetailsFilter)
    {
        // Check if 'policyExpiryDate' or 'month' is not set in the request
        if (! isset($request['displayBy']) && ! isset($request['policyExpiryDate'])) {
            $currentDate = Carbon::now();

            // Calculate the start date of the previous month
            $previousMonthStartDate = $currentDate->copy()->subMonth()->startOfMonth();

            // Calculate the end date of the next month
            $nextMonthEndDate = $currentDate->copy()->addMonth()->endOfMonth();

            // Format the dates according to the specified date format
            $previousMonthStartDateFormatted = $previousMonthStartDate->format($this->dateFormat);
            $nextMonthEndDateFormatted = $nextMonthEndDate->format($this->dateFormat);

            // Apply the date range filter to the query
            $query->whereBetween($this->policyExpiryColumnName, [$previousMonthStartDateFormatted, $nextMonthEndDateFormatted]);

            // Apply quote batch start and end date filter
            $query->whereBetween('renewal_batches.start_date', [$previousMonthStartDateFormatted, $nextMonthEndDateFormatted]);

            $this->applyDefaultFilterForBatch($query, $request, $isDetailsFilter);
        }
    }

    /**
     * Applies team filters to the query based on the request parameters.
     * Filters the query to include only users who belong to the specified teams.
     *
     * @return void
     */
    private function applyTeamFilters($query, $request)
    {
        // Check if 'teams' parameter is set and contains values
        if (isset($request['teams']) && count($request['teams']) > 0) {
            $value = $request['teams'];
            // Apply the team filter to the query
            $query->whereIn('users.id', function ($query) use ($value) {
                $query->distinct()
                    ->select('users.id')
                    ->from('users')
                    ->join('user_team', 'user_team.user_id', 'users.id')
                    ->join('teams', 'teams.id', 'user_team.team_id')
                    ->whereIn('teams.id', $value);
            });
        }
    }

    /**
     * Applies advisor filters to the query based on the request parameters.
     * Filters the query to include only the specified advisors.
     *
     * @return void
     */
    private function applyAdvisorFilters($query, $request)
    {
        // Check if advisors parameter is set and contains values
        if (isset($request['advisors']) && count($request['advisors']) > 0) {
            // Apply the advisor filter to the query
            $query->whereIn('advisor_id', $request['advisors']);
        }
    }

    /**
     * Applies quote type filters to the query based on the request parameters.
     * Filters the query based on the line of business (LOB) and insurance type.
     *
     * @return void
     */
    private function applyQuoteTypeFilters($query, $request)
    {
        // Get the line of business (LOB) from the request or default to the user's product name
        $lob = $this->getQuoteType($request);

        // Apply filters based on the LOB
        if ($lob === quoteTypeCode::CORPLINE) {
            // Filter for corporate line of business
            if (! empty($request['insurance_type']) && $request['insurance_type'] != '') {
                $query->where('business_type_of_insurance_id', $request['insurance_type']);
            } else {
                $query->where('business_type_of_insurance_id', '!=', quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical));
            }
        }
    }

    /**
     * Applies permission filters to the query based on the user's role and permissions.
     * Filters the query to include only the data the user is allowed to view.
     *
     * @return void
     */
    private function applyPermissionFilters($query, $request)
    {
        if (auth()->user()->isAdmin()) {
            return true;
        }
        // Check if the user is a manager or deputy and has the permission to view the manager retention report
        if (auth()->user()->isManagerOrDeputy() && Auth::user()->can(PermissionsEnum::MANAGER_RETENTION_REPORT_VIEW)) {
            $assigneeIds = UserManager::where('manager_id', Auth::user()->id)->get()->pluck('user_id')->toArray();
            $query->whereIn('users.id', $assigneeIds);
        }
        // Check if the user is an advisor and has the permission to view the advisor retention report
        elseif (auth()->user()->isAdvisor() && Auth::user()->can(PermissionsEnum::ADVISOR_RETENTION_REPORT_VIEW)) {
            // Filter the query to include only the data for the current advisor
            $query->where('users.id', auth()->user()->id);
        }
    }

    /**
     * Applies batch filters to the query based on the request parameters.
     * Filters the query to include data from specific quote batches and policy expiry dates.
     *
     * @return void
     */
    private function applyDefaultFilterForBatch($query, $request, $isDetailsFilter = false)
    {
        // Select batch name, start date, and end date from the renewal_batches table
        $query->selectRaw('renewal_batches.id, renewal_batches.name as batch, renewal_batches.start_date, renewal_batches.end_date')
            ->join('renewal_batches', 'renewal_batch_id', '=', 'renewal_batches.id');

        // Check if 'policyExpiryDate' parameter is set in the request
        if (isset($request['policyExpiryDate'])) {
            // Parse the start and end dates from the request
            $startDate = Carbon::parse($request['policyExpiryDate'][0])->startOfDay();
            $endDate = Carbon::parse($request['policyExpiryDate'][1])->endOfDay();

            $startDate = $startDate->format($this->dateFormat);
            $endDate = $endDate->format($this->dateFormat);
            // Apply the date range filter to the query
            $query->whereBetween($this->policyExpiryColumnName, [$startDate, $endDate]);
            $query->whereBetween('renewal_batches.start_date', [$startDate, $endDate]);
        }

        if (! $isDetailsFilter) {
            $query->groupBy('renewal_batches.id');
        }
    }

    /**
     * Applies batch filters to the query based on the request parameters.
     * Filters the query to include data from specific quote batches and policy expiry dates.
     *
     * @return void
     */
    private function applyFilterForBatch($query, $request, $isDetailsFilter = false)
    {
        // Select batch name, start date, and end date from the renewal_batches table
        $query->selectRaw('renewal_batches.id, renewal_batches.name as batch, renewal_batches.start_date, renewal_batches.end_date')
            ->join('renewal_batches', 'renewal_batch_id', '=', 'renewal_batches.id');

        // Check if 'policyExpiryDate' parameter is set in the request
        if (isset($request['batch'])) {
            $query->whereIn('renewal_batches.id', $request['batch']);
        }

        if (! $isDetailsFilter) {
            $query->groupBy('renewal_batches.id');
        }
    }

    /**
     * Applies month filters to the query based on the request parameters.
     * Filters the query to include data for policies expiring in the specified month.
     *
     * @return void
     */
    private function applyFilterByMonth($query, $request)
    {
        if (isset($request['policyExpiryDate'])) {
            $startDate = Carbon::parse($request['policyExpiryDate'][0])->startOfDay();
            $endDate = Carbon::parse($request['policyExpiryDate'][1])->endOfDay();

            $startDate = $startDate->format($this->dateFormat);
            $endDate = $endDate->format($this->dateFormat);
            // Apply the date range filter to the query
            $query->whereBetween($this->policyExpiryColumnName, [$startDate, $endDate]);
        }
    }


    /**
     * Formats the report data by calculating and adding volume net retention and volume gross retention.
     * Iterates through the report data and calculates the retention percentages.
     *
     * @return array .
     */
    private function formatReportData($reportData, $aggregatedData)
    {
        $avgVolumeNetRetention = $aggregatedData['volume_net_retention'];
        // Iterate through each report in the report data
        foreach ($reportData as $report) {
            $totalInvalid = $report->total - $report->invalid;

            // Calculate and format retention percentages
            $report->volume_net_retention = $this->calculateRetentionPercentage(
                $report->sales,
                $totalInvalid
            );
            $report->volume_gross_retention = $this->calculateRetentionPercentage(
                $report->sales,
                $report->total
            );

            $report->relative_retention = $this->calculateAdvisorRetentionPercentage(
                $avgVolumeNetRetention,
                $report->volume_net_retention
            );
        }

        // Return the formatted report data
        return $reportData;
    }

    /**
     * Retrieves the product name for the authenticated user.
     * Fetches the user's products and returns the name of the first product.
     *
     * @return string The name of the first product associated with the user.
     */
    public function getUserPorductName()
    {
        $productName = '';
        // Get the products associated with the authenticated user
        $products = $this->getUserProducts(auth()->user()->id)->where('name', '!=', quoteTypeCode::Car);
        if (count($products) === 1) {
            $productName = $products->first()->name;
        }

        return $productName;
    }

    /**
     * Retrieves retention leads data based on the request parameters.
     * Determines the quote type, constructs the query, applies filters, and returns paginated results.
     *
     * @return array
     */
    public function getRetentionLeadsData($request)
    {
        // Instantiate the table name
        $tableName = 'personal_quotes';

        // Will remove renewal_batch_id from select statement once will push on prod as it is not required
        // Construct the query to retrieve retention leads data
        $query = PersonalQuote::query()
            ->selectRaw("{$tableName}.uuid, {$tableName}.code, CONCAT({$tableName}.first_name, ' ', {$tableName}.last_name) as fullName, quote_status.text as quoteStatusName, price_with_vat as price, {$this->policyExpiryColumnName} as  previous_policy_expiry_date, renewal_batch_id")
            ->join('users', 'advisor_id', '=', 'users.id')
            ->join('quote_status', 'quote_status.id', "{$tableName}.quote_status_id");

        // Apply filters to the query based on the request parameters
        $this->applyFilters($query, $request->all(), true);
        $this->applyFiltersToQuery($query, $request);

        // Return the paginated results with query string
        return $query->paginate(12)->withQueryString();
    }

    /**
     * Calculates and returns the footer data for the report.
     * This method aggregates the report data, calculates the total valid entries,
     * and computes the volume net retention and volume gross retention percentages.
     *
     * @return array
     */
    public function getSummarizedData($reportData)
    {
        $aggregatedData = $this->aggregateReportData($reportData);

        // Calculate the total valid entries
        $totalInvalid = $aggregatedData['total'] - $aggregatedData['invalid'];

        // Calculate and format retention percentages
        $volumeNetRetention = $this->calculateRetentionPercentage(
            $aggregatedData['sales'],
            $totalInvalid
        );

        $volumeGrossRetention = $this->calculateRetentionPercentage(
            $aggregatedData['sales'],
            $aggregatedData['total']
        );

        return [
            'total' => $aggregatedData['total'],
            'sales' => $aggregatedData['sales'],
            'lost' => $aggregatedData['lost'],
            'invalid' => $aggregatedData['invalid'],
            'volume_net_retention' => $volumeNetRetention,
            'volume_gross_retention' => $volumeGrossRetention,
        ];
    }

    /**
     * Aggregates the report data by summing up the total, sales, invalid, and lost values.
     *
     * @return array
     */
    private function aggregateReportData($reportData)
    {
        $isReportDataExist = count($reportData) !== 0;

        return [
            'total' => $isReportDataExist ? $reportData->sum('total') : 0,
            'sales' => $isReportDataExist ? $reportData->sum('sales') : 0,
            'invalid' => $isReportDataExist ? $reportData->sum('invalid') : 0,
            'lost' => $isReportDataExist ? $reportData->sum('lost') : 0,
        ];
    }

    private function calculateAdvisorRetentionPercentage($avgVolumeNetRetention, $volumeNetRetention)
    {
        return number_format((((float) $volumeNetRetention) - ((float) $avgVolumeNetRetention)), 2).'%';
    }
    /**
     * Calculates the retention percentage based on sales and total values.
     *
     * @return string
     */
    private function calculateRetentionPercentage($sales, $total)
    {
        return ($total != 0) ? number_format(($sales / $total) * 100, 2).'%' : '0.00%';
    }

    /**
     * Retrieves the filter options for the retention report.
     *
     * @return array
     */
    public function getFilterOptions()
    {
        $maxDays = ApplicationStorageService::getValueByKeyName(GenericRequestEnum::MAX_DAYS);

        $advisors = [];
        $teams = [];

        // Get lines of business (LOB) based on user permissions
        $lobs = $this->getLobByPermissions();

        // Create an instance of DropdownSourceService to fetch dropdown data
        $dropdownSourceService = new DropdownSourceService;

        // Retrieve and filter business insurance types, excluding 'groupMedical'
        $businessInsuranceType = $dropdownSourceService->getDropdownSource('business_type_of_insurance_id')
            ->filter(function ($type) {
                return $type['text'] != quoteBusinessTypeCode::groupMedical;
            })
            ->map(function ($type) {
                return ['value' => $type['id'], 'label' => $type['text']];
            })
            ->toArray();

        // Re-index the array to ensure it starts from 0
        $businessInsuranceType = array_values($businessInsuranceType);

        // Define the insurance types with the filtered business insurance types
        $insuranceType = [
            quoteTypeCode::CORPLINE => $businessInsuranceType,
        ];

        // Return the filter options as an associative array
        return [
            'lob' => $lobs,
            'maxDays' => $maxDays,
            'advisors' => $advisors,
            'teams' => $teams,
            'insurance_type' => $insuranceType,
        ];
    }

    /**
     * Retrieves the lines of business (LOB) based on the user's permissions.
     *
     * @return array
     */
    public function getLobByPermissions()
    {
        // Define the initial lines of business (LOB) with their corresponding permission constants
        $lobs = [
            quoteTypeCode::Bike => quoteTypeCode::Bike,
            quoteTypeCode::Health => quoteTypeCode::Health,
            quoteTypeCode::Travel => quoteTypeCode::Travel,
            quoteTypeCode::Pet => quoteTypeCode::Pet,
            quoteTypeCode::Cycle => quoteTypeCode::Cycle,
            quoteTypeCode::Yacht => quoteTypeCode::Yacht,
            quoteTypeCode::Life => quoteTypeCode::Life,
            quoteTypeCode::Home => quoteTypeCode::Home,
            quoteTypeCode::CORPLINE => quoteTypeCode::CORPLINE,
            quoteTypeCode::GroupMedical => quoteTypeCode::GroupMedical,

        ];

        // Return the filtered and augmented LOBs
        return $lobs;
    }

    /**
     * Retrieves the filter options based on the lines of business (LOB) and user roles.
     *
     * @return array
     */
    public function getFiltersByLob()
    {
        // Determine the visibility of each LOB based on the user's roles
        $canView = [
            quoteTypeCode::Bike => ! Auth::user()->hasRole(RolesEnum::BikeAdvisor),
            quoteTypeCode::Health => ! Auth::user()->hasRole(RolesEnum::RMAdvisor),
            quoteTypeCode::Travel => ! Auth::user()->hasRole(RolesEnum::TravelAdvisor),
            quoteTypeCode::Pet => ! Auth::user()->hasRole(RolesEnum::PetAdvisor),
            quoteTypeCode::Cycle => ! Auth::user()->hasRole(RolesEnum::CycleAdvisor),
            quoteTypeCode::Yacht => ! Auth::user()->hasRole(RolesEnum::YachtAdvisor),
            quoteTypeCode::Life => ! Auth::user()->hasRole(RolesEnum::LifeAdvisor),
            quoteTypeCode::Home => ! Auth::user()->hasRole(RolesEnum::HomeAdvisor),
            quoteTypeCode::CORPLINE => ! Auth::user()->hasRole(RolesEnum::CorpLineAdvisor),
            quoteTypeCode::GroupMedical => ! Auth::user()->hasRole(RolesEnum::GMAdvisor),
        ];

        // Return the filter options with their visibility settings
        return [
            'advisors' => [
                'can_view' => $canView,
            ],
            'teams' => [
                'can_view' => $canView,
                'lobs' => [
                    quoteTypeCode::CORPLINE,
                ],
            ],
            'insurance_type' => [
                'lobs' => [
                    quoteTypeCode::CORPLINE,
                ],
            ],
            'previous_policy_expiry_date' => [
                'can_view' => $canView,
            ],
        ];
    }
    /**
     * Determines whether the batch column should be shown in the report.
     * This method checks the first item in the provided retention report data to see if it has a 'batch' attribute.
     * If the 'batch' attribute is present and not null, the method returns true, indicating that the batch column should be shown.
     *
     * @return bool
     */
    public function isShowBatchColumn($retentionReportData)
    {
        $isShowBatchColumn = false;
        if (count($retentionReportData) != 0) {
            $firstRetentionReportData = $retentionReportData->first();
            if ($firstRetentionReportData && $firstRetentionReportData->getAttribute('batch') !== null) {
                $isShowBatchColumn = true;
            }
        }

        return $isShowBatchColumn;
    }

    /**
     * Get batches by date range.
     *
     * @return array An associative array of batches with formatted date ranges.
     */
    public function getBatchByDates($request)
    {
        // Parse the start and end dates from the request and set them to the start and end of the day
        $startDate = Carbon::parse($request['policyExpiryDate'][0])->startOfDay();
        $endDate = Carbon::parse($request['policyExpiryDate'][1])->endOfDay();
    
        // Format the start and end dates according to the specified date format
        $startDate = $startDate->format($this->dateFormat);
        $endDate = $endDate->format($this->dateFormat);
    
        // Apply the date range filter to the query
        $batches = RenewalBatch::select('name', 'start_date', 'end_date', 'id')
            ->whereNull('quote_type_id')
            ->whereBetween('start_date', [$startDate, $endDate]);
    
        // Order the batches by ID and format the results
        $batches = $batches->orderBy('id')
            ->get()
            ->map(function ($batch) {
                // Get the date display format from the configuration
                $dateFormat = config('constants.DATE_DISPLAY_FORMAT');
                // Format the start and end dates of the batch
                $start_date = Carbon::parse($batch->start_date)->format($dateFormat);
                $end_date = Carbon::parse($batch->end_date)->format($dateFormat);
    
            // Return an associative array with the batch 'name' and 'id'
            return [
                    'id' => $batch->id,
                    'name' => $batch->name.'-('.$start_date.' to '.$end_date.')',
                ];
            })
            ->toArray();
    
        return $batches;
    }
}
