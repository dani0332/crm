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
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Service class for handling AML query operations
 * Manages data retrieval, filtering, and pagination for AML listings
 */
class AMLQueryService
{
    public function getAMLQuotes(object $request): \Illuminate\Contracts\Pagination\Paginator|array
    {
        if (! $request->ajax()) {
            return [];
        }

        if (! isset($request->quoteType) || empty($request->quoteType)) {
            return [];
        }

        $quoteTypes = QuoteTypeRepository::allowedQuoteForAml();
        $quoteTypeId = $quoteTypes->where('code', $request->quoteType)->first()?->id;

        if (! $quoteTypeId) {
            return [];
        }

        $quoteRequestTable = $this->determineQuoteRequestTable($quoteTypeId, $request);
        $dataAml = $this->buildBaseQuery($quoteRequestTable, $request, $quoteTypeId);
        $dataAml = $this->applyFilters($dataAml, $quoteRequestTable, $request);
        $dataAml = $dataAml->orderBy($quoteRequestTable.'.created_at', 'desc');

        return $dataAml->simplePaginate(10)->withQueryString();
    }

    private function determineQuoteRequestTable(int $quoteTypeId, object $request): string
    {
        $quoteRequestTable = strtolower($request->quoteType).'_quote_request';

        // Check if quote type uses personal_quotes table
        $personalQuoteTypes = [
            QuoteTypes::BIKE->id(),
            QuoteTypes::YACHT->id(),
            QuoteTypes::PET->id(),
            QuoteTypes::CYCLE->id(),
            QuoteTypes::JETSKI->id(),
            QuoteTypes::LIFE->id(),
            QuoteTypes::SAVINGS->id(),
            QuoteTypes::HOME->id(),
        ];

        if (! in_array($quoteTypeId, $personalQuoteTypes)) {
            return $quoteRequestTable;
        }

        // Check if data has been migrated based on date
        if (isset($request->amlCreatedStartDate) && ! empty($request->amlCreatedStartDate)) {
            return AMLService::isDataMigrated($quoteTypeId, '', $request->amlCreatedStartDate)
                ? 'personal_quotes'
                : $quoteRequestTable;
        }

        // Check migration status based on search criteria
        if (isset($request->searchType) && in_array($request->searchType, ['cdbId', 'customerEmail'])) {
            $createdDate = $this->getCreatedDateForSearch($request);
            if ($createdDate) {
                return AMLService::isDataMigrated($quoteTypeId, '', $createdDate)
                    ? 'personal_quotes'
                    : $quoteRequestTable;
            }
        }

        return $quoteRequestTable;
    }

    private function getCreatedDateForSearch(object $request): ?string
    {
        $searchType = match ($request->searchType) {
            'cdbId' => DatabaseColumnsString::CODE,
            'customerEmail' => DatabaseColumnsString::EMAIL,
            default => null,
        };

        if (! $searchType) {
            return null;
        }

        try {
            if ($request->searchType === 'id') {
                return AML::where($searchType, $request->searchField)->firstOrFail()->created_at;
            }

            return PersonalQuote::where($searchType, $request->searchField)->firstOrFail()->created_at;
        } catch (\Exception $e) {
            return null;
        }
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
