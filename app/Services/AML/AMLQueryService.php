<?php

declare(strict_types=1);

namespace App\Services\AML;

use App\Enums\CarRegistrationType;
use App\Enums\CustomerTypeEnum;
use App\Enums\DatabaseColumnsString;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Enums\SearchTypeEnum;
use App\Models\AML;
use App\Models\KycLog;
use App\Models\PersonalQuote;
use App\Repositories\CarQuoteRepository;
use App\Repositories\QuoteTypeRepository;
use App\Services\AMLService;
use App\Services\Logger\LoggerService;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class AMLQueryService
{
    public function getAMLQuotes(object $request): Paginator|array
    {
        if (! $request->ajax() || ! isset($request->quoteType) || empty($request->quoteType)) {
            return [];
        }

        $quoteTypes = QuoteTypeRepository::getQuoteTypesByLob();
        $quoteType = $quoteTypes->where('code', $request->quoteType)->first();

        if (! $quoteType) {
            return [];
        }

        $quoteRequestTable = $this->determineQuoteRequestTable($quoteType, $request);
        $dataAml = $this->buildBaseQuery($quoteRequestTable, $request, $quoteType->id);
        $dataAml = $this->applyFilters($dataAml, $quoteRequestTable, $request);
        $dataAml = $dataAml->orderBy($quoteRequestTable.'.created_at', 'desc');

        return $dataAml->simplePaginate(10)->withQueryString();
    }

    private function determineQuoteRequestTable($quoteType, object $request): string
    {
        $quoteRequestTable = strtolower($request->quoteType).'_quote_request';

        if (! checkPersonalQuotes($quoteType->code)) {
            return $quoteRequestTable;
        }

        return $this->resolvePersonalQuoteTable($quoteType->id, $request, $quoteRequestTable);
    }

    private function resolvePersonalQuoteTable(int $quoteTypeId, object $request, string $fallbackTable): string
    {
        // Check if data has been migrated based on date
        if (isset($request->amlCreatedStartDate) && ! empty($request->amlCreatedStartDate)) {
            return $this->getMigratedTableName($quoteTypeId, $request->amlCreatedStartDate, $fallbackTable);
        }

        // Check migration status based on search criteria
        if (isset($request->searchType) && in_array($request->searchType, ['cdbId', 'customerEmail'])) {
            $createdDate = $this->getCreatedDateForSearch($request);
            if ($createdDate) {
                return $this->getMigratedTableName($quoteTypeId, $createdDate, $fallbackTable);
            }
        }

        return $fallbackTable;
    }

    private function getMigratedTableName(int $quoteTypeId, string $date, string $fallbackTable): string
    {
        return AMLService::isDataMigrated($quoteTypeId, '', $date)
            ? 'personal_quotes'
            : $fallbackTable;

    }

    private function getCreatedDateForSearch(object $request): ?string
    {
        $createdDate = null;
        $searchType = match ($request->searchType) {
            'cdbId' => DatabaseColumnsString::CODE,
            'customerEmail' => DatabaseColumnsString::EMAIL,
            default => null,
        };

        if ($searchType) {
            try {
                $createdDate = PersonalQuote::where($searchType, $request->searchField)->firstOrFail()->created_at;
            } catch (\Exception $e) {
                LoggerService::warning('Error getting created date for search', extra: [
                    'search_type' => $request->searchType,
                    'search_field' => $request->searchField,
                    'exception' => $e->getMessage(),
                ]);
                $createdDate = null;
            }
        }

        return $createdDate;
    }

    private function buildBaseQuery(string $quoteRequestTable, object $request, int $quoteTypeId): Builder
    {
        $dataAml = DB::table($quoteRequestTable);
        $baseColumns = $this->getRequiredBaseColumns($quoteRequestTable);
        $computedColumns = [
            DB::raw('"'.$request->quoteType.' Insurance" as quote_type_text'),
            DB::raw('"'.$quoteTypeId.'" as quote_type_id'),
            $quoteRequestTable.'.code as cdb_id',
        ];

        // Special handling for Pet quote request: use personal_quote_id as id
        if ($quoteRequestTable === strtolower(quoteTypeCode::Pet).'_quote_request') {
            return $dataAml->select(array_merge(
                array_map(fn ($col) => $quoteRequestTable.'.'.$col, $baseColumns),
                [$quoteRequestTable.'.personal_quote_id as id'],
                $computedColumns
            ));
        }

        return $dataAml->select(array_merge(
            array_map(fn ($col) => $quoteRequestTable.'.'.$col, $baseColumns),
            $computedColumns
        ));
    }

    /**
     * Required columns from quote request tables for AML list (no select *).
     *
     * @return array<int, string>
     */
    private function getRequiredBaseColumns(string $quoteRequestTable): array
    {
        $common = ['id', 'code', 'created_at', 'updated_at'];

        if ($quoteRequestTable === 'personal_quotes') {
            return [...$common, 'quote_type_id'];
        }

        // Pet: id is added as alias of personal_quote_id in buildBaseQuery
        if ($quoteRequestTable === strtolower(quoteTypeCode::Pet).'_quote_request') {
            return ['code', 'created_at', 'updated_at'];
        }

        return $common;
    }

    private function applyFilters(Builder $dataAml, string $quoteRequestTable, object $request): Builder
    {
        // Filter by quote type for personal_quotes table
        if ($quoteRequestTable === 'personal_quotes') {
            $quoteTypes = QuoteTypeRepository::getQuoteTypesByLob();
            $quoteTypeId = $quoteTypes->where('code', $request->quoteType)->first()?->id;
            $dataAml->where($quoteRequestTable.'.quote_type_id', $quoteTypeId);
        }

        // Apply search filters
        if (isset($request->searchType) && ! empty($request->searchType) &&
            isset($request->searchField) && ! empty($request->searchField)
        ) {
            if ($request->searchType === SearchTypeEnum::CdbId->value) {
                $dataAml->where($quoteRequestTable.'.code', $request->searchField);
            }

            if ($request->searchType === SearchTypeEnum::CustomerEmail->value) {
                $dataAml->where($quoteRequestTable.'.email', $request->searchField);
            }
        }

        // Apply date range filter
        if (isset($request->amlCreatedStartDate) && ! empty($request->amlCreatedStartDate) &&
            isset($request->amlCreatedEndDate) && ! empty($request->amlCreatedEndDate)
        ) {
            $dataAml->whereBetween(
                $quoteRequestTable.'.created_at',
                dateQueryFilter($request->amlCreatedStartDate, $request->amlCreatedEndDate)
            );
        }

        return $dataAml;
    }

    public function getAMLLogs(int $quoteTypeId, int $quoteRequestId): Collection
    {
        return AML::with('quotetype')
            ->where([
                'quote_request_id' => $quoteRequestId,
                'quote_type_id' => $quoteTypeId,
            ])
            ->standardAmlFilters()
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function getAMLScreeningType(int $quoteTypeId, int $quoteRequestId): string
    {
        $customerCode = KycLog::withTrashed()
            ->where([
                'quote_request_id' => $quoteRequestId,
                'quote_type_id' => $quoteTypeId,
            ])
            ->standardAmlFilters()
            ->orderBy('id', 'desc')
            ->value('customer_code');

        $customerCodePrefix = $customerCode !== null ? substr($customerCode, 0, 3) : null;

        if ($customerCodePrefix !== null && $customerCodePrefix !== '') {
            return $customerCodePrefix;
        }

        // Determine customer type based on quote type and business rules
        return $this->determineCustomerTypeFromQuote($quoteTypeId, $quoteRequestId);
    }

    private function determineCustomerTypeFromQuote(int $quoteTypeId, int $quoteRequestId): string
    {
        if ($quoteTypeId == QuoteTypes::BUSINESS->id()) {
            return CustomerTypeEnum::EntityShort;
        }

        if ($quoteTypeId == QuoteTypes::CAR->id()) {
            return $this->getCarQuoteCustomerType($quoteRequestId);
        }

        return CustomerTypeEnum::IndividualShort;
    }

    private function getCarQuoteCustomerType(int $quoteRequestId): string
    {
        $quote = CarQuoteRepository::where('id', $quoteRequestId)
            ->select('registration_type')
            ->first();

        if ($quote?->registration_type === CarRegistrationType::COMPANY) {
            return CustomerTypeEnum::EntityShort;
        }

        return CustomerTypeEnum::IndividualShort;
    }
}
