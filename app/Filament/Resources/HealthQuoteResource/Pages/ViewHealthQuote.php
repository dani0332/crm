<?php

namespace App\Filament\Resources\HealthQuoteResource\Pages;

use App\Filament\Resources\HealthQuoteResource;
use App\Models\HealthQuoteRequestDetail;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewHealthQuote extends ViewRecord
{
    protected static string $resource = HealthQuoteResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $hqrd = HealthQuoteRequestDetail::where('health_quote_request_id', $this->record->id)->first();
        $data['notes'] = $hqrd->notes;
        $data['transapp_code'] = $hqrd->transapp_code;

        return $data;
    }

    protected function getActions(): array
    {
        return [
            Actions\ReplicateAction::make()
                ->label('Duplicate Lead')
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
