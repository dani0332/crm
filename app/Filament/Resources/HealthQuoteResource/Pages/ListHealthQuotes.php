<?php

namespace App\Filament\Resources\HealthQuoteResource\Pages;

use App\Enums\QuoteStatusEnum;
use App\Filament\Resources\HealthQuoteResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;

class ListHealthQuotes extends ListRecords
{
    protected static string $resource = HealthQuoteResource::class;

    // simple pagination
    // protected function paginateTableQuery(Builder $query): Paginator
    // {
    //     return $query->simplePaginate($this->getTableRecordsPerPage() == -1 ? $query->count() : $this->getTableRecordsPerPage());
    // }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();
        $query = $query
            ->leftJoin('health_quote_request_detail', 'health_quote_request_detail.health_quote_request_id', '=', 'health_quote_request.id')
            ->whereNotNull('health_quote_request_detail.id')
            ->where('health_quote_request.quote_status_id', '!=', QuoteStatusEnum::Fake)
            ->orderBy('health_quote_request.created_at', 'DESC');

        return $query;
    }

    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [false];
    }

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Create Lead'),
        ];
    }
}
