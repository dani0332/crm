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
            Actions\ReplicateAction::make()
                ->label('Duplicate')
                ->icon('heroicon-o-duplicate')
                ->color('warning')
                ->modalHeading('Duplicate Health Quote')
                ->modalSubheading('Are you sure you want to duplicate this health quote?')
                ->modalButton('Duplicate')
                ->afterReplicaSaved(fn ($replica) => redirect(HealthQuoteResource::getUrl('edit', ['record' => $replica]))),
            Actions\EditAction::make()->label('Edit')->icon('heroicon-o-pencil-alt'),
        ];
    }
}
