<?php

namespace App\Filament\Resources\HealthAvailablePlanResource\Pages;

use App\Filament\Resources\HealthAvailablePlanResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageHealthAvailablePlans extends ManageRecords
{
    protected static string $resource = HealthAvailablePlanResource::class;
    protected static ?string $recordTitleAttribute = 'id';

    protected function getActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
