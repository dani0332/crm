<?php

namespace App\Filament\Resources\HealthQuoteResource\Pages;

use App\Filament\Resources\HealthQuoteResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHealthQuote extends EditRecord
{
    protected static string $resource = HealthQuoteResource::class;

    protected function getActions(): array
    {
        return [
            Actions\ViewAction::make()->label('View')->icon('heroicon-o-eye'),
        ];
    }
}
