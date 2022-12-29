<?php

namespace App\Filament\Resources\HealthQuoteResource\Pages;

use App\Filament\Resources\HealthQuoteResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;

class ListHealthQuotes extends ListRecords
{
    protected static string $resource = HealthQuoteResource::class;

    protected function paginateTableQuery(Builder $query): Paginator
    {
        return $query->simplePaginate($this->getTableRecordsPerPage() == -1 ? $query->count() : $this->getTableRecordsPerPage());
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();
        $query->where('quote_status_id', '!=', 9);

        return $query;
    }

    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [false];
    }

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
