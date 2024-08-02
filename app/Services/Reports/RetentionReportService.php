<?php

namespace App\Services\Reports;

use App\Enums\PermissionsEnum;
use App\Enums\quoteBusinessTypeCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\RetentionReportEnum;
use App\Models\UserManager;
use Carbon\Carbon;
use App\Services\BaseService;
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
    
    public function __construct() {
        $this->dateFormat = config('constants.DB_DATE_FORMAT_MATCH');
        // Test DB
        $this->policyExpiryColumnName = 'policy_expiry_date';
        // Stage DB
        // $this->policyExpiryColumnName = 'renewal_expiry_date';
    }

    /**
     * Retrieves report data based on LOB & other request parameters.
     *
     * @return array
     */
    public function getReportData($request)
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

        // Paginate the query results and retain the query string
        $reportData = $query->paginate(12)->withQueryString();

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
    
        if ($lob === quoteTypeCode::GroupMedical) {
            // Filter for group medical line of business
            $query->where('business_type_of_insurance_id', quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical));
        }
    
        if ($lob === quoteTypeCode::Travel) {
            // Filter for travel line of business
            if (!empty($request['insurance_type']) && $request['insurance_type'] != '') {
                $query->where('direction_code', $request['insurance_type']);
            }
        }
    
        if ($lob === quoteTypeCode::Life) {
            // Filter for life insurance line of business
            if (!empty($request['insurance_type']) && $request['insurance_type'] != '') {
                $query->where('tenure_of_insurance_id', $request['insurance_type']);
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
        // Get the start and end dates for the specified month
        $monthDates = $this->getMonthDatesByNumber(Carbon::now()->format('y'), $request['month']);
        // Apply the date range filter to the query
        $query->whereBetween($this->policyExpiryColumnName, [$monthDates['start_date'], $monthDates['end_date']]);
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
        // Iterate through each report in the report data
        foreach ($reportData as $report) {
            $sales = $report->sales;
            $invalid = $report->invalid;
            $total = $report->total;

            // Calculate the total valid entries
            $totalInvalid = $total - $invalid;

            // Calculate volume net retention percentage
            $volumeNetRetention = ($totalInvalid != 0) ? ($sales / $totalInvalid) : 0;
            // Calculate volume gross retention percentage
            $volumeGrossRetention = ($total != 0) ? ($sales / $total) : 0;

            // Format and add the retention percentages to the report
            $report->volume_net_retention = number_format($volumeNetRetention * 100, 2) . '%';
            $report->volume_gross_retention = number_format($volumeGrossRetention * 100, 2) . '%';
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
        // Check if the user has any products and set the product name
        if (count($products) != 0) {
            $productName = $products[0]->name;
        }
        return $productName;
    }

    /**
     * Retrieves retention leads data based on the request parameters.
     * Determines the quote type, constructs the query, applies filters, and returns paginated results.
     *
     * @param \Illuminate\Http\Request $request The request object containing the filter parameters.
     * @return \Illuminate\Pagination\LengthAwarePaginator The paginated retention leads data.
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
        return $query->paginate(12)->withQueryString();
    }
}