<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface CsvExportableInterface
{
    /**
     * Get the data collection for CSV export
     */
    public function collection(array $requestParams = []): Collection;

    /**
     * Get the CSV column headings
     */
    public function headings(): array;

    /**
     * Map a single record to CSV row data
     */
    public function map($record): array;

    /**
     * Get the query builder instance for chunked processing (optional)
     * If not implemented, will fall back to collection() method
     * Can return either Eloquent\Builder or Query\Builder
     */
    public function getQuery(array $requestParams = []): Builder|\Illuminate\Database\Query\Builder|null;

    /**
     * Get export metadata
     */
    public function getExportMetadata(array $requestParams = []): array;
}
