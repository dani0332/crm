<?php

declare(strict_types=1);

namespace App\Services\AML;

use App\Enums\DatabaseColumnsString;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Models\AML;
use App\Models\PersonalQuote;
use App\Repositories\QuoteTypeRepository;
use App\Services\AMLService;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class AMLQueryService
{
    public function getAMLQuotes(object $request): \Illuminate\Contracts\Pagination\Paginator|array
    {
        if (! $request->ajax() || ! isset($request->quoteType) || empty($request->quoteType)) {
            return [];
        }

        $quoteTypes = QuoteTypeRepository::allowedQuoteForAml();
        $quoteType = $quoteTypes->where('code', $request->quoteType)->first();

        if (! $quoteType) {
            return [];
        }

        $quoteRequestTable = $this->determineQuoteRequestTable($quoteType, $request);
        $dataAml = $this->buildBaseQuery($quoteRequestTable, $request, $quoteType->id);
        $dataAml = $this->applyFilters($dataAml, $quoteRequestTable, $request, $quoteType->id);
        $dataAml = $dataAml->orderBy($quoteRequestTable.'.created_at', 'desc');

        return $dataAml->simplePaginate(10)->withQueryString();
    }

    private function determineQuoteRequestTable(QuoteTypes $quoteType, object $request): string
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
                $createdDate = $request->searchType === 'id'
                    ? AML::where($searchType, $request->searchField)->firstOrFail()->created_at
                    : PersonalQuote::where($searchType, $request->searchField)->firstOrFail()->created_at;
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

        // Special handling for Pet quote request
        if ($quoteRequestTable === strtolower(quoteTypeCode::Pet).'_quote_request') {
            return $dataAml->select(
                $quoteRequestTable.'.*',
                $quoteRequestTable.'.personal_quote_id as id',
                DB::raw('"'.$request->quoteType.' Insurance" as quote_type_text'),
                DB::raw('"'.$quoteTypeId.'" as quote_type_id'),
                $quoteRequestTable.'.code as cdb_id'
            );
        }

        return $dataAml->select(
            $quoteRequestTable.'.*',
            DB::raw('"'.$request->quoteType.' Insurance" as quote_type_text'),
            DB::raw('"'.$quoteTypeId.'" as quote_type_id'),
            $quoteRequestTable.'.code as cdb_id'
        );
    }

    private function applyFilters(Builder $dataAml, string $quoteRequestTable, object $request): Builder
    {
        // Filter by quote type for personal_quotes table
        if ($quoteRequestTable === 'personal_quotes') {
            $quoteTypes = QuoteTypeRepository::allowedQuoteForAml();
            $quoteTypeId = $quoteTypes->where('code', $request->quoteType)->first()?->id;
            $dataAml->where($quoteRequestTable.'.quote_type_id', $quoteTypeId);
        }

        // Apply search filters
        if (isset($request->searchType) && ! empty($request->searchType) &&
            isset($request->searchField) && ! empty($request->searchField)
        ) {
            if ($request->searchType === 'cdbId') {
                $dataAml->where($quoteRequestTable.'.code', $request->searchField);
            }

            if ($request->searchType === 'customerEmail') {
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
}
