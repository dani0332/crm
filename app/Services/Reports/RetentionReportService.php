<?php

namespace App\Services\Reports;

use App\Enums\GenericRequestEnum;
use App\Enums\PermissionsEnum;
use App\Enums\quoteBusinessTypeCode;
use App\Enums\quoteStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\RetentionReportEnum;
use App\Enums\RolesEnum;
use App\Enums\TravelQuoteEnum;
use App\Models\QuoteType;
use App\Models\UserManager;
use App\Repositories\QuoteTypeRepository;
use App\Services\ApplicationStorageService;
use Carbon\Carbon;
use App\Services\BaseService;
use App\Services\DropdownSourceService;
use App\Traits\TeamHierarchyTrait;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\GetUserTreeTrait;
use DateTime;
use Illuminate\Support\Facades\Auth;

class RetentionReportService extends BaseService
{
    use TeamHierarchyTrait, GenericQueriesAllLobs, GetUserTreeTrait;

    private $dateFormat;
    private $policyExpiryColumnName;
    private $paginateData;
    
    public function __construct() {
        $this->dateFormat = config('constants.DB_DATE_FORMAT_MATCH');
        // Test DB
        $this->policyExpiryColumnName = 'policy_expiry_date';
        // Stage DB
        // $this->policyExpiryColumnName = 'renewal_expiry_date';
        $this->paginateData = 12;
    }

    /**
     * Retrieves report data based on LOB & other request parameters.
     *
     * @return array
     */
    public function getReportData($request, $isExport=false)
    {
        // Get the quote type or LOB
        $quoteType = $this->getQuoteType($request);

        // Get the relevant LOB model
        $quoteModel = $this->getModelObject($quoteType);

        // If the model object is not found or the user is not an advisor or manager and no permission, return an empty array
        if (!$quoteModel || !$this->isAdvisorManager()) {
            return [];
        }

        // Build the query based on the model object and request parameters
        $query = $this->buildQuery($quoteModel, $request);

        if (!$isExport){
            // Paginate the query results and retain the query string
            $reportData = $query->paginate($this->paginateData)->withQueryString();
        } else {
            $reportData = $query->get();
        }

        // Add some new column into report date and return the result
        return $this->formatReportData($reportData);
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
        if (auth()->user()->isAdmin()){
            return true;
        }
         // Check if the user is a manager or deputy and lacks the permission to view the manager retention report
         if (
            (auth()->user()->isManagerOrDeputy() && !Auth::user()->can(PermissionsEnum::MANAGER_RETENTION_REPORT_VIEW)) ||
            // Check if the user is an advisor and lacks the permission to view the advisor retention report
            (auth()->user()->isAdvisor() && !Auth::user()->can(PermissionsEnum::ADVISOR_RETENTION_REPORT_VIEW))
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
    private function buildQuery($quoteModel, $request)
    {
        // Initialize the query with the necessary select statements and joins
        $query = $quoteModel::query()
            ->selectRaw("MONTHNAME({$this->policyExpiryColumnName}) as `month`,
                users.name as `advisor_name`,
                count(*) as total,
                SUM(CASE WHEN quote_status_id = ".QuoteStatusEnum::Lost." THEN 1 ELSE 0 END) as lost,
                SUM(CASE WHEN quote_status_id IN (".QuoteStatusEnum::Fake.", ".QuoteStatusEnum::Duplicate.") THEN 1 ELSE 0 END) as invalid,
                SUM(CASE WHEN quote_status_id = ".QuoteStatusEnum::PolicyBooked." THEN 1 ELSE 0 END) as sales, advisor_id")
            ->join('users', 'advisor_id', '=', 'users.id');
    
        // Apply general filters to the query based on the request parameters
        $this->applyFilters($query, $request);
        // Group the query results by advisor name
        $query->groupBy('users.name');
        return $query;
    }

    /**
     * Applies various filters to the query based on the request parameters.
     *
     * @return void
     */
    private function applyFilters($query, $request)
    {
        // Apply date range filters to the query
        $this->applyDateFilters($query, $request);
    
        // Apply team-related filters to the query
        $this->applyTeamFilters($query, $request);
    
        // Apply advisor-related filters to the query
        $this->applyAdvisorFilters($query, $request);
    
        // Apply quote type filters to the query
        $this->applyQuoteTypeFilters($query, $request);
    
        // Apply permission-based filters to the query
        $this->applyPermissionFilters($query, $request);
    
        // Apply personal quote filter
        $this->applyPersonalQuoteFilter($query, $request);

        // Apply Business quote filter
        $this->applyBusinessQuoteFilter($query, $request);

        // Apply additional filters based on the 'displayBy' parameter in the request
        if (isset($request['displayBy'])){
            if ($request['displayBy'] === RetentionReportEnum::BATCH) {
                $this->applyFilterForBatch($query, $request);
            } elseif ($request['displayBy'] === RetentionReportEnum::MONTHLY) {
                $this->applyFilterByMonth($query, $request);
            }
        }
    }

    /**
    * Applies a filter to the query to include only personal quotes.
    * @return void
    */
    private function applyPersonalQuoteFilter($query, $filters){
        $quoteType = $this->getQuoteType($filters);
        $isPersonalQuote = checkPersonalQuotes($quoteType);
        if ( $isPersonalQuote){
            $quoteTypeData = QuoteType::where('code', $quoteType)->first();
            $query->where('quote_type_id', $quoteTypeData->id);
        }
    }

    /**
    * Applies a filter to the query to include only business quotes.
    *
    * @return void
    */
    private function applyBusinessQuoteFilter($query, $filters){
        $quoteType = $this->getQuoteType($filters);
        if ($quoteType === quoteTypeCode::GroupMedical){
            $query->leftJoin('business_type_of_insurance as bit', 'business_type_of_insurance_id', '=', 'bit.id')
		        ->where('bit.text', '=', quoteTypeCode::GroupMedical);
        } elseif  ( $quoteType === quoteTypeCode::CORPLINE){
            $query->leftJoin('business_type_of_insurance as bit', 'business_type_of_insurance_id', '=', 'bit.id')
                ->where('bit.text', '!=', quoteTypeCode::GroupMedical);
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
     * @return void
     */
    private function applyDateFilters($query, $request)
    {
        // Check if 'policyExpiryDate' or 'month' is not set in the request
        if (!isset($request['policyExpiryDate']) && !isset($request['month'])) {
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

            $this->applyFilterForBatch($query, $request);
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
            if (!empty($request['insurance_type']) && $request['insurance_type'] != '') {
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
        if (auth()->user()->isAdmin()){
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
    private function applyFilterForBatch($query, $request)
    {
        // Select batch name, start date, and end date from the quote_batches table
        $query->selectRaw("quote_batches.name as batch, quote_batches.start_date, quote_batches.end_date")
            ->join('quote_batches', 'quote_batch_id', '=', 'quote_batches.id');

        // Check if 'policyExpiryDate' parameter is set in the request
        if (isset($request['policyExpiryDate'])) {
            // Parse the start and end dates from the request
            $startDate = Carbon::parse($request['policyExpiryDate'][0])->startOfDay();
            $endDate = Carbon::parse($request['policyExpiryDate'][1])->endOfDay();
            // Apply the date range filter to the query
            $query->whereBetween($this->policyExpiryColumnName, [$startDate->format($this->dateFormat), $endDate->format($this->dateFormat)]);
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
        if (isset($request['month'])){
            // Get the start and end dates for the specified month
            $monthDates = $this->getMonthDatesByNumber(Carbon::now()->format('y'), $request['month']);
            // Apply the date range filter to the query
            $query->whereBetween($this->policyExpiryColumnName, [$monthDates['start_date'], $monthDates['end_date']]);
        }
    }

    /**
     * Gets the start and end dates for a given month and year.
     * Returns an array with formatted start and end dates or an error message if the month number is invalid.
     *
     * @return array
     */
    private function getMonthDatesByNumber($year, $monthNumber)
    {
        // Create DateTime objects for the start and end dates of the month
        $startDate = new DateTime("$year-$monthNumber-01");
        $endDate = clone $startDate;
        $endDate->modify('last day of this month');

        // Return the formatted start and end dates
        return [
            'start_date' => $startDate->format($this->dateFormat),
            'end_date' => $endDate->format($this->dateFormat)
        ];
    }

    /**
     * Formats the report data by calculating and adding volume net retention and volume gross retention.
     * Iterates through the report data and calculates the retention percentages.
     *
     * @return array .
     */
    private function formatReportData($reportData)
    {
        $aggregatedData= $this->getFooterData($reportData);
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
        $products = $this->getUserProducts(auth()->user()->id);
        // Check if the user has any products and set the product name excluding "Car"
        foreach ($products as $product) {
            if ($product->name !== quoteTypeCode::Car) {
                $productName = $product->name;
                break;
            }
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
        // Determine the quote type based on the request or user's product name
        $quoteType = $this->getQuoteType($request);
        // Get the model class for the quote type
        $quoteModelClass = $this->getModelObject($quoteType);

        // Return an empty array if the model class is not found
        if (!$quoteModelClass) {
            return [];
        }

        // Instantiate the quote model and get the table name
        $quoteModel = new $quoteModelClass();
        $tableName = $quoteModel->getTable();

        // Construct the query to retrieve retention leads data
        $query = $quoteModel::query()
            ->selectRaw("{$tableName}.code, CONCAT({$tableName}.first_name, ' ', {$tableName}.last_name) as fullName, quote_status.text as quoteStatusName, price_with_vat as price, policy_expiry_date")
            ->join('users', 'advisor_id', '=', 'users.id')
            ->join('quote_status', 'quote_status.id', "{$tableName}.quote_status_id");

        // Apply filters to the query based on the request parameters
        $this->applyFilters($query, $request->all());
        $this->applyFiltersToQuery($query, $request);

        // Return the paginated results with query string
        return $query->paginate($this->paginateData)->withQueryString();
    }

    /**
     * Calculates and returns the footer data for the report.
     * This method aggregates the report data, calculates the total valid entries,
     * and computes the volume net retention and volume gross retention percentages.
     * @return array 
     */
    public function getFooterData($reportData){
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
            'total' => $isReportDataExist ? $reportData->sum('total'): 0,
            'sales' => $isReportDataExist ? $reportData->sum('sales'): 0,
            'invalid' => $isReportDataExist ? $reportData->sum('invalid'): 0,
            'lost' => $isReportDataExist ? $reportData->sum('lost'): 0
        ];
    }

    private function calculateAdvisorRetentionPercentage($avgVolumeNetRetention, $volumeNetRetention){
        return number_format((((double)$volumeNetRetention) - ((double)$avgVolumeNetRetention)) , 2) . '%' ;
    }
    /**
     * Calculates the retention percentage based on sales and total values.
     *
     * @return string 
     */
    private function calculateRetentionPercentage($sales, $total)
    {
        return ($total != 0) ? number_format(($sales / $total) * 100, 2) . '%' : '0.00%';
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
            $firstRetentionReporData = $retentionReportData->first();
            if ($firstRetentionReporData && $firstRetentionReporData->getAttribute('batch') !== null) {
                $isShowBatchColumn = true;
            }
        }
        return $isShowBatchColumn;
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
        $dropdownSourceService = new DropdownSourceService();

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
            quoteTypeCode::Bike => PermissionsEnum::BIKE_CONVERSION_REPORT,
            quoteTypeCode::Health => PermissionsEnum::HEALTH_CONVERSION_REPORT,
            quoteTypeCode::Travel => PermissionsEnum::TRAVEL_CONVERSION_REPORT,
            quoteTypeCode::Pet => PermissionsEnum::PET_CONVERSION_REPORT,
            quoteTypeCode::Cycle => PermissionsEnum::CYCLE_CONVERSION_REPORT,
            quoteTypeCode::Yacht => PermissionsEnum::YACHT_CONVERSION_REPORT,
            quoteTypeCode::Life => PermissionsEnum::LIFE_CONVERSION_REPORT,
            quoteTypeCode::Home => PermissionsEnum::HOME_CONVERSION_REPORT,
        ];

        // Filter the LOBs based on the user's permissions
        $lobs = array_filter($lobs, function ($permission) {
            return Auth::user()->can($permission);
        });

        // Retrieve the list of LOBs from the repository and filter them based on the user's permissions
        $lobs = QuoteTypeRepository::GetList()
            ->filter(function ($lob) use ($lobs) {
                return array_key_exists($lob->code, $lobs);
            })
            ->pluck('code', 'text')
            ->toArray();

        // Add corporate line insurance to the LOBs if the user has the necessary permission
        if (Auth::user()->can(PermissionsEnum::CORPLINE_CONVERSION_REPORT)) {
            $lobs = array_merge(['CorpLine Insurance' => quoteTypeCode::CORPLINE], $lobs);
        }

        // Add group medical insurance to the LOBs if the user has the necessary permission
        if (Auth::user()->can(PermissionsEnum::GROUPMEDICAL_CONVERSION_REPORT)) {
            $lobs = array_merge(['Group Medical Insurance' => quoteTypeCode::GroupMedical], $lobs);
        }

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
                    quoteTypeCode::Health,
                ],
            ],
            'insurance_type' => [
                'lobs' => [
                    quoteTypeCode::CORPLINE,
                ],
            ],
            'view_by' => [
                'can_view' => $canView,
            ],
            'select_month' => [
                'can_view' => $canView,
            ],
            'select_batch' => [
                'can_view' => $canView,
            ],
            'policy_expiry_date' => [
                'can_view' => $canView,
            ],
        ];
    }
}