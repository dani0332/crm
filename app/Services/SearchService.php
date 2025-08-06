<?php

namespace App\Services;

use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Models\Customer;
use App\Models\PersonalQuote;
use App\Models\Role;
use App\Services\Logger\LoggerService;
use App\Traits\TeamHierarchyTrait;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SearchService extends BaseService
{
    use TeamHierarchyTrait;

    const CLASS_NAME = 'searchService';

    /**
     * Payment date filters
     *
     * @var array
     */
    public $paymentsDateFilters = ['payment_due_date', 'payment_date'];

    /**
     * Get search leads based on request parameters
     *
     * @param  bool  $isEndorsementList  Whether to show an endorsement list
     * @param  bool  $isExport  Whether this is for export
     * @return LengthAwarePaginator|Collection|array
     */
    public function getSearchLeads($isEndorsementList = false, $isExport = false)
    {
        // If no search parameters are provided, return empty results
        if (empty(request()->except('list'))) {
            return [];
        }

        $baseTable = $isEndorsementList ? 'send_update_logs' : 'personal_quotes';

        // Get base select columns
        $selectColumns = $this->getBaseSelectColumns($isEndorsementList);

        // Build query with essential joins and apply filters
        $baseQuery = $this->buildBaseQuery($baseTable, $isEndorsementList);
        $selectColumns = $this->applyJoinsAndFilters($baseQuery, $baseTable, $isEndorsementList);
        $this->applyAuthorizationFilters($baseQuery);

        // Apply sorting with proper index usage
        $sortBy = request()->sortBy ?? 'updated_at';
        $sortType = request()->sortType ?? 'desc';
        $baseQuery->orderBy($baseTable.'.'.$sortBy, $sortType);

        // Handle export specific columns
        if ($isExport) {
            return $this->prepareExportData($baseQuery, $baseTable, $isEndorsementList, $selectColumns);
        }

        // Select columns for normal query
        $baseQuery->select($selectColumns);

        // logger()->debug("toRawSql: " . $baseQuery->toRawSql());

        // Use cursor for better memory usage with large result sets
        return $baseQuery->paginate(15)->withQueryString();
    }

    /**
     * Get base select columns for queries
     *
     * @param  bool  $isEndorsementList  Whether this is for the endorsement list
     */
    private function getBaseSelectColumns(bool $isEndorsementList = false): array
    {
        return [
            'personal_quotes.uuid',
            'personal_quotes.code',
            'personal_quotes.first_name',
            'personal_quotes.last_name',
            'personal_quotes.quote_type_id',
            'quote_type.code as quote_type',
            'business_type_of_insurance.text as business_insurance_type',
            'personal_quotes.business_type_of_insurance_id',
            'personal_quotes.created_at',
            'personal_quotes.policy_expiry_date',
            'personal_quotes.policy_number',
        ];
    }

    /**
     * Build the base query with essential joins
     *
     * @param  string  $baseTable  Base table for the query
     * @param  bool  $isEndorsementList  Whether this is for the endorsement list
     */
    private function buildBaseQuery(string $baseTable, bool $isEndorsementList): Builder
    {
        if ($isEndorsementList) {
            $baseQuery = DB::table($baseTable)
                ->join('quote_type', 'send_update_logs.quote_type_id', 'quote_type.id')
                ->join('personal_quotes', 'personal_quotes.id', 'send_update_logs.personal_quote_id')
                ->join('lookups as cat_lookup', 'send_update_logs.category_id', 'cat_lookup.id')
                ->leftJoin('lookups as opt_lookup', 'send_update_logs.option_id', 'opt_lookup.id')
                ->leftJoin('customer as c', 'personal_quotes.customer_id', 'c.id')
                ->leftJoin('business_type_of_insurance', function ($query) {
                    $query->on('business_type_of_insurance.id', 'personal_quotes.business_type_of_insurance_id');
                    $query->where('personal_quotes.quote_type_id', QuoteTypeId::Business);
                });

            return $baseQuery;
        }

        return DB::table($baseTable)
            ->join('quote_type', 'personal_quotes.quote_type_id', 'quote_type.id')
            ->leftJoin('quote_status', 'personal_quotes.quote_status_id', 'quote_status.id')
            ->leftJoin('customer as c', 'personal_quotes.customer_id', 'c.id')
            ->leftJoin('business_type_of_insurance', function ($query) {
                $query->on('business_type_of_insurance.id', 'personal_quotes.business_type_of_insurance_id');
                $query->where('personal_quotes.quote_type_id', QuoteTypeId::Business);
            });
    }

    /**
     * Apply appropriate joins and filters based on request parameters
     *
     * @param  Builder  $query  Query builder instance
     * @param  string  $baseTable  Base table for the query
     * @param  bool  $isEndorsementList  Whether this is for the endorsement list
     */
    private function applyJoinsAndFilters(Builder $query, string $baseTable, bool $isEndorsementList)
    {
        try {
            // Apply standard table joins from the model
            $this->applyTableJoins($query, request());

            // Apply search filters
            $this->searchQuoteQueryFilters($query, request(), $isEndorsementList);

            // Process columns for an endorsement list
            if ($isEndorsementList) {
                $suSelectColumns = [
                    'send_update_logs.code',
                    'send_update_logs.uuid',
                    'send_update_logs.quote_type_id',
                    'send_update_logs.created_at',
                    'personal_quotes.uuid as quote_uuid',
                    'cat_lookup.text as category',
                    'opt_lookup.text as option',
                    'send_update_logs.notes',
                    'send_update_logs.status',
                ];

                // Get filtered company cases
                $selectColumns = array_merge($this->getBaseSelectColumns($isEndorsementList), $suSelectColumns);
                $query->addSelect($this->getFilteredCompanyCases(request(), $selectColumns));
            } else {
                // Add standard columns
                $selectColumns = array_merge($this->getBaseSelectColumns(), ['personal_quotes.uuid', 'quote_status.text as quote_status']);
                $selectColumns = array_merge($selectColumns, $this->getFilteredCompanyCases(request(), $selectColumns));
                $query->addSelect($selectColumns);
            }

            return $selectColumns;
        } catch (\Exception $e) {
            LoggerService::error(self::CLASS_NAME.' fn:'.__FUNCTION__.' Error applying joins and filters: '.$e->getMessage(), extra: [
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Apply table joins from request
     *
     * @param  Builder  $query  Query builder instance
     * @param  \Illuminate\Http\Request  $request  Request instance
     */
    private function applyTableJoins(Builder $query, $request): void
    {
        PersonalQuote::applyRequestTableJoins($query, $request);
    }

    /**
     * Apply authorization filters based on user role
     *
     * @param  Builder  $query  Query builder instance
     */
    private function applyAuthorizationFilters(Builder $query): void
    {
        $userId = Auth::id();

        if (! $userId) {
            return;
        }

        if ($this->isManagerialRole()) {
            $query->whereIn('personal_quotes.advisor_id', $this->getAdvisorsByManagers());
        }

        if ($this->isAdvisorRole()) {
            $query->where('personal_quotes.advisor_id', $userId);
        }
    }

    /**
     * Prepare data for export with additional columns
     *
     * @param  Builder  $query  Query builder instance
     * @param  string  $baseTable  Base table for the query
     * @param  bool  $isEndorsementList  Whether this is for the endorsement list
     * @param  array  $selectColumns  Base select columns
     * @return Collection Collection of export data
     */
    private function prepareExportData(Builder $query, string $baseTable, bool $isEndorsementList, array $selectColumns): Collection
    {
        $excelExportColumns = [];

        // Add customer details if not already joined
        if (! request()->has('insured_name')) {
            $query->leftJoin('customer', 'personal_quotes.customer_id', 'customer.id');
            $excelExportColumns = array_merge($excelExportColumns, [
                'customer.first_name as customer_first_name',
                'customer.last_name as customer_last_name',
            ]);
        }

        // Add department details if not already joined
        if (! request()->has('department')) {
            $query->leftJoin('users', 'personal_quotes.advisor_id', 'users.id');
        }

        // Add payment details if not already joined and not filtering by payment fields
        if (! (request()->has('date_type') && in_array(request()->date_type, $this->paymentsDateFilters))
            && ! request()->has('payment_status')
            && ! request()->has('insurer_tax_invoice_number')
            && ! request()->has('insurer_commission_tax_invoice_number')
        ) {
            if ($isEndorsementList) {
                $query->leftJoin('payments', 'send_update_logs.id', 'payments.send_update_log_id');
            } else {
                $query->leftJoin('payments', 'personal_quotes.code', 'payments.code');
            }
        }

        // Add insurance provider details
        if ($isEndorsementList) {
            $query->leftJoin('insurance_provider', 'send_update_logs.insurance_provider_id', 'insurance_provider.id');
        } else {
            $query->leftJoin('insurance_provider', 'payments.insurance_provider_id', 'insurance_provider.id');
        }

        // Add payment and insurance provider columns
        $excelExportColumns = array_merge($excelExportColumns, [
            'payments.total_price',
            'insurance_provider.text as insurance_provider',
        ]);

        $query->select(array_merge($selectColumns, $excelExportColumns));

        return $query->get();
    }

    /**
     * Check if the authenticated user has a managerial role
     *
     * @return bool Whether the user has a managerial role
     */
    private function isManagerialRole(): bool
    {
        $lobs = [
            QuoteTypes::CAR->value,
            QuoteTypes::HOME->value,
            QuoteTypes::HEALTH->value,
            QuoteTypes::LIFE->value,
            QuoteTypes::BUSINESS->value,
            QuoteTypes::BIKE->value,
            QuoteTypes::YACHT->value,
            QuoteTypes::TRAVEL->value,
            QuoteTypes::PET->value,
            QuoteTypes::CYCLE->value,
            QuoteTypes::JETSKI->value,
            QuoteTypes::SAVINGS->value,
        ];

        // Get manager roles based on quote types
        $managerRoles = collect($lobs)
            ->map(fn ($lob) => strtoupper($lob).'_MANAGER')
            ->toArray();

        // Check if user has any of these roles
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $user->hasAnyRole($managerRoles);
    }

    /**
     * Check if the authenticated user has an advisor role
     *
     * @return bool Whether the user has an advisor role
     */
    private function isAdvisorRole(): bool
    {
        $advisorRoles = [
            RolesEnum::CarAdvisor,
            RolesEnum::HomeAdvisor,
            RolesEnum::HealthAdvisor,
            RolesEnum::LifeAdvisor,
            RolesEnum::BusinessAdvisor,
            RolesEnum::BikeAdvisor,
            RolesEnum::YachtAdvisor,
            RolesEnum::TravelAdvisor,
            RolesEnum::PetAdvisor,
            RolesEnum::CycleAdvisor,
            RolesEnum::JetskiAdvisor,
            RolesEnum::SavingsAdvisor,
        ];

        // Check if user has any of these roles
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $user->hasAnyRole($advisorRoles);

    }

    /**
     * Get filtered company cases columns
     *
     * @param  \Illuminate\Http\Request  $request  Request instance
     * @param  array  $selectColumns  Base select columns
     * @return array Filtered select columns
     */
    private function getFilteredCompanyCases($request, $selectColumns): array
    {
        if ($request->has('company_name')) {
            $selectColumns[] = 'insured.company_name';
        } else {
            $selectColumns[] = DB::raw('"N/A" as company_name');
        }

        return $selectColumns;
    }

    /**
     * Apply search filters to the query
     *
     * @param  Builder  $query  Query builder instance
     * @param  \Illuminate\Http\Request  $request  Request instance
     * @param  bool  $isSendUpdateFilter  Whether this is for send update filter
     */
    private function searchQuoteQueryFilters($query, $request, $isSendUpdateFilter = false): void
    {
        try {
            // Search by code (use exact match for better performance)
            if ($request->has('code')) {
                $query->where('personal_quotes.code', $request->code);
            }

            // Search by insured name - use FULLTEXT index if available
            if ($request->has('insured_name') && ! isset($request->code)) {
                $this->applyInsuredNameSearch($query, $request);
            }

            // Search by member name
            if ($request->has('member_first_name') || $request->has('member_last_name')) {
                $this->applyMemberNameSearch($query, $request);
            }

            // Search by company name
            if ($request->has('company_name')) {
                $query->join('insured', 'personal_quotes.insured_id', 'insured.id');
                // Use FULLTEXT search
                $query->whereRaw('MATCH(insured.company_name) AGAINST(? IN BOOLEAN MODE)', [$this->optimizeSearchTerm($request->company_name)]);

            }

            // Search by policy number
            if ($request->has('policy_number') && ! isset($request->code)) {
                // Use exact match for better performance
                $query->where('personal_quotes.policy_number', $request->policy_number);
            }

            // Search by mobile number (exact match)
            if ($request->has('mobile_no') && ! isset($request->code)) {
                $query->where('personal_quotes.mobile_no', $request->mobile_no);
            }

            // Search by email (exact match for better performance)
            if ($request->has('email') && ! isset($request->code)) {
                $query->where('personal_quotes.email', $request->email);
            }

            // Search by send update code
            if ($request->has('su_code')) {
                if (! $isSendUpdateFilter) {
                    $query->join('send_update_logs', 'personal_quotes.id', 'send_update_logs.personal_quote_id');
                }
                $query->where('send_update_logs.code', $request->su_code);
            }

            // Filter by date range
            if ($request->has('date_type') && $request->has('date_range')) {
                $this->applyDateRangeFilter($query, $request, $isSendUpdateFilter);
            }

            // Filter by quote status
            if ($request->has('quote_status') && ! isset($request->code)) {
                $query->where('personal_quotes.quote_status_id', $request->quote_status);
            }

            // Filter by payment status
            if ($request->has('payment_status') && ! isset($request->code)) {
                $this->applyPaymentStatusFilter($query, $request, $isSendUpdateFilter);
            }

            // Filter by line of business
            if ($request->has('line_of_business') && ! isset($request->code)) {
                $query->whereIn('personal_quotes.quote_type_id', (array) $request->line_of_business);
            }

            // Filter by business insurance type
            if ($request->has('business_insurance_type') && ! isset($request->code)) {
                $query->whereIn('personal_quotes.business_type_of_insurance_id', $request->business_insurance_type);
            }

            // Filter by current insurance provider
            if ($request->has('currently_insured_with') && ! isset($request->code)) {
                $query->whereIn('personal_quotes.insurance_provider_id', $request->currently_insured_with);
            }

            // Filter by department
            if ($request->has('department') && ! isset($request->code)) {
                $query->join('users', 'personal_quotes.advisor_id', 'users.id');
                $query->whereIn('users.department_id', $request->department);
            }

            // Filter by advisors
            if ($request->has('advisors') && ! isset($request->code)) {
                $query->whereIn('personal_quotes.advisor_id', $request->advisors);
            }

            // Filter by support users
            if ($request->has('support_users') && ! isset($request->code)) {
                $query->whereIn('personal_quotes.support_user_id', $request->support_users);
            }

            // Filter by tax invoice numbers
            if (($request->has('insurer_tax_invoice_number') || $request->has('insurer_commission_tax_invoice_number'))
                && ! isset($request->code) && ! $request->has('payment_status')
                && ! ($request->has('date_type') && in_array($request->date_type, $this->paymentsDateFilters))
            ) {
                $this->applyTaxInvoiceFilters($query, $request, $isSendUpdateFilter);
            }

            // Filter by update status
            if ($request->has('update_status') && ! isset($request->su_code)) {
                $formattedUpdateStatuses = array_map(function ($string) {
                    return strtoupper(str_replace(' ', '_', $string));
                }, $request->update_status);
                $query->whereIn('send_update_logs.status', $formattedUpdateStatuses);
            }

            // Filter by send update type
            if ($request->has('send_update_type') && ! isset($request->su_code)) {
                $query->whereIn('send_update_logs.category_id', $request->send_update_type);
            }
        } catch (\Exception $e) {
            LoggerService::error(self::CLASS_NAME.' fn:'.__FUNCTION__.' Error applying search filters: '.$e->getMessage(), extra: [
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Apply insured name search with better performance
     *
     * @param  Builder  $query  Query builder instance
     * @param  \Illuminate\Http\Request  $request  Request instance
     */
    private function applyInsuredNameSearch($query, $request): void
    {
        $query->join('customer', 'personal_quotes.customer_id', 'customer.id');
        // Use FULLTEXT search
        $query->whereRaw('MATCH(customer.insured_first_name, customer.insured_last_name) AGAINST(? IN BOOLEAN MODE)', [$this->optimizeSearchTerm($request->insured_name)]);
    }

    /**
     * Apply member name search
     *
     * @param  Builder  $query  Query builder instance
     * @param  \Illuminate\Http\Request  $request  Request instance
     */
    private function applyMemberNameSearch($query, $request): void
    {
        // Determine the appropriate quote type table
        $resolveQuoteTypeObject = QuoteTypes::getQuoteTypeIdToClass($request->line_of_business);
        $isPersonalQuote = $resolveQuoteTypeObject == PersonalQuote::class;

        // Join the customer members table with correct condition using index hint
        $query->join('customer_members', function ($join) use ($isPersonalQuote) {
            if ($isPersonalQuote) {
                $join->on('personal_quotes.id', 'customer_members.quote_id')
                    ->where('customer_members.quote_type', 'personal_quote');
            } else {
                $quoteTypes = [
                    QuoteTypeId::Car => 'car_quote_request',
                    QuoteTypeId::Home => 'home_quote_request',
                    QuoteTypeId::Health => 'health_quote_request',
                    QuoteTypeId::Life => 'life_quote_request',
                    QuoteTypeId::Business => 'business_quote_request',
                    QuoteTypeId::Travel => 'travel_quote_request',
                ];

                $quoteType = $quoteTypes[request()->line_of_business] ?? null;

                if ($quoteType) {
                    $join->on($quoteType.'.id', 'customer_members.quote_id');
                }
            }
        });

        // Apply member name filters - merged condition
        if ($request->has('member_first_name') || $request->has('member_last_name')) {
            $name = [];

            if ($request->has('member_first_name')) {
                $name[] = $request->member_first_name;
            }

            if ($request->has('member_last_name')) {
                $name[] = $request->member_last_name;
            }
            $fullName = implode(' ', $name);

            $query->whereRaw('MATCH(customer_members.first_name, customer_members.last_name) AGAINST(? IN BOOLEAN MODE)', [$this->optimizeSearchTerm($fullName)]);
        }
    }

    /**
     * Apply date range filter
     *
     * @param  Builder  $query  Query builder instance
     * @param  \Illuminate\Http\Request  $request  Request instance
     * @param  bool  $isSendUpdateFilter  Whether this is for send update filter
     */
    private function applyDateRangeFilter($query, $request, $isSendUpdateFilter): void
    {
        $baseTableDateFilters = ['created_at', 'policy_booking_date', 'policy_start_date', 'policy_expiry_date', 'transaction_approved_at'];
        $startDate = date('Y-m-d 00:00:00', strtotime($request->date_range[0]));
        $endDate = date('Y-m-d 23:59:59', strtotime($request->date_range[1]));

        if (in_array($request->date_type, $this->paymentsDateFilters)) {
            // Join payments table if needed
            if ($isSendUpdateFilter) {
                $query->join('payments', 'send_update_logs.id', 'payments.send_update_log_id');
            } else {
                $query->join('payments', 'personal_quotes.code', 'payments.code');
            }

            if ($request->date_type == 'payment_date') {
                $query->join('payment_status_log', 'payments.paymentable_id', 'payment_status_log.quote_request_id');
                $query->where('payment_status_log.current_payment_status_id', PaymentStatusEnum::PAID);
                $query->whereBetween('payment_status_log.created_at', [$startDate, $endDate]);
            } else {
                $query->whereBetween('payments.'.$request->date_type, [$startDate, $endDate]);
            }
        } elseif (in_array($request->date_type, $baseTableDateFilters)) {
            if ($isSendUpdateFilter && $request->date_type == 'created_at') {
                $query->whereBetween('send_update_logs.'.$request->date_type, [$startDate, $endDate]);
            } else {
                $query->whereBetween('personal_quotes.'.$request->date_type, [$startDate, $endDate]);
            }
        }
    }

    /**
     * Apply payment status filter
     *
     * @param  Builder  $query  Query builder instance
     * @param  \Illuminate\Http\Request  $request  Request instance
     * @param  bool  $isSendUpdateFilter  Whether this is for send update filter
     */
    private function applyPaymentStatusFilter($query, $request, $isSendUpdateFilter): void
    {
        if ($request->has('date_type') && ! in_array($request->date_type, $this->paymentsDateFilters)) {
            if ($isSendUpdateFilter) {
                $query->join('payments', 'send_update_logs.id', 'payments.send_update_log_id');
            } else {
                $query->join('payments', 'personal_quotes.code', 'payments.code');
            }
        }

        $hasPaymentsJoin = collect($query->joins ?? [])->pluck('table')->contains('payments');

        // Join payments table if needed and not already joined
        if (! $hasPaymentsJoin && $request->payment_status) {
            if ($isSendUpdateFilter) {
                $query->join('payments', 'send_update_logs.id', 'payments.send_update_log_id');
            } else {
                $query->join('payments', 'personal_quotes.code', 'payments.code');
            }
        }
        $query->whereIn('payments.payment_status_id', $request->payment_status);
    }

    /**
     * Apply tax invoice filters
     *
     * @param  Builder  $query  Query builder instance
     * @param  \Illuminate\Http\Request  $request  Request instance
     * @param  bool  $isSendUpdateFilter  Whether this is for send update filter
     */
    private function applyTaxInvoiceFilters($query, $request, $isSendUpdateFilter): void
    {
        if (! $request->has('su_code') && ! $isSendUpdateFilter) {
            $query->join('payments', 'personal_quotes.code', 'payments.code');

            if ($request->has('insurer_tax_invoice_number')) {
                $query->where('payments.insurer_tax_number', $request->insurer_tax_invoice_number);
            }

            if ($request->has('insurer_commission_tax_invoice_number')) {
                $query->where('payments.insurer_commmission_invoice_number', $request->insurer_commission_tax_invoice_number);
            }
        } else {
            $query->leftJoin('payments', 'send_update_logs.id', 'payments.send_update_log_id');

            if ($request->has('insurer_tax_invoice_number')) {
                $query->where('send_update_logs.insurer_tax_invoice_number', $request->insurer_tax_invoice_number);
            }

            if ($request->has('insurer_commission_tax_invoice_number')) {
                $query->where('send_update_logs.insurer_commission_invoice_number', $request->insurer_commission_tax_invoice_number);
            }
        }
    }

    /**
     * Optimize a search term for FULLTEXT Boolean mode
     */
    private function optimizeSearchTerm(string $term): string
    {
        // Remove common problematic characters
        $term = preg_replace('/[\'"\\\]/', ' ', $term);

        // Split into words
        $words = preg_split('/[\s,.\-_\/]+/', $term, -1, PREG_SPLIT_NO_EMPTY);

        // Handle different term types
        $optimized = [];
        foreach ($words as $word) {
            // Skip stop words and very short terms
            if (strlen($word) < 2) {
                continue;
            }

            // Check word type
            if (preg_match('/^[a-zA-Z]+$/', $word)) {
                // Pure alphabetic - treat as a single word
                $optimized[] = '+'.$word;
            } elseif (preg_match('/^[a-zA-Z0-9]+$/', $word)) {
                // Alphanumeric - use word prefix matching
                $optimized[] = '+'.$word.'*';

                // If word has both letters and numbers, also add an exact match
                if (preg_match('/[a-zA-Z]/', $word) && preg_match('/[0-9]/', $word)) {
                    $optimized[] = '+"'.$word.'"';
                }
            } else {
                // For special character containing words, add exact and partial matches
                $optimized[] = '"'.$word.'"';
                $optimized[] = $word.'*';

                return implode(' +', $optimized);
            }
        }

        return implode(' ', $optimized);

    }

}
