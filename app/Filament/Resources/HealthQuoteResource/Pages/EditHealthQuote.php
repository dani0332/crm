<?php

namespace App\Filament\Resources\HealthQuoteResource\Pages;

use App\Filament\Resources\HealthQuoteResource;
use App\Models\HealthQuoteRequestDetail;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditHealthQuote extends EditRecord
{
    protected static string $resource = HealthQuoteResource::class;

    // protected function mutateFormDataBeforeFill(array $data): array
    // {
    //     $hqrd = HealthQuoteRequestDetail::where('health_quote_request_id', $this->record->id)->first();
    //     $data['notes'] = $hqrd->notes;

    //     return $data;
    // }

    // protected function handleRecordUpdate(Model $record, array $data): Model
    // {
    //     $healthQuoteRequestDetail = HealthQuoteRequestDetail::where('health_quote_request_id', $this->record->id)->first();
    //     $healthQuoteRequestDetail->update(['notes' => $data['notes']]);

    //     unset($data['notes']);
    //     $record->update($data);

    //     return $record;
    // }

    protected function getActions(): array
    {
        return [
            Actions\ViewAction::make()->label('View')->icon('heroicon-o-eye'),
        ];
    }
}
