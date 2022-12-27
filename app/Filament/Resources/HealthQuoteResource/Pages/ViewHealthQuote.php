<?php

namespace App\Filament\Resources\HealthQuoteResource\Pages;

use App\Filament\Resources\HealthQuoteResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewHealthQuote extends ViewRecord
{
    protected static string $resource = HealthQuoteResource::class;

    protected function getActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
