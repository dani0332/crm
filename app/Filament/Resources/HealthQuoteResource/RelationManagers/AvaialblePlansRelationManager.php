<?php

namespace App\Filament\Resources\HealthQuoteResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Webbingbrasil\FilamentCopyActions\Tables\Actions\CopyAction;

class AvaialblePlansRelationManager extends RelationManager
{
    protected static string $relationship = 'avaialblePlans';
    protected static ?string $recordTitleAttribute = 'health_quote_request_id';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('health_quote_request_id'),
            ]);
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        return $query;
    }

    public static function getPlansSchema(): array
    {
        return [
            Tables\Columns\TextColumn::make('providerName')->label('Provider Name')
                ->getStateUsing(function (Model $record): string {
                    // dump($record);

                    return $record->plan_payload['plans'][0]['providerName'];
                }),
                Tables\Columns\TextColumn::make('plan_name')->label('Plan Name')
                ->getStateUsing(function (Model $record): string {
                    return $record->plan_payload['plans'][0]['name'];
                }),
                Tables\Columns\TextColumn::make('actualPremium')->label('Actual Premium with BASMAH')
                ->getStateUsing(function (Model $record): float {
                    return $record->plan_payload['plans'][0]['actualPremium'] + $record->plan_payload['plans'][0]['basmah'];
                }),
                Tables\Columns\TextColumn::make('actualPremiumPlusVat')->label('Actual Premium with BASMAH')
                ->getStateUsing(function (Model $record): float {
                    return $record->plan_payload['plans'][0]['actualPremium'] + $record->plan_payload['plans'][0]['vat'] + $record->plan_payload['plans'][0]['basmah'];
                }),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::getPlansSchema())
            ->filters([
                //
            ])
            ->headerActions([
                CopyAction::make()->copyable(config('constants.AFIA_WEBSITE_DOMAIN'))->label('Copy Link')->icon('heroicon-o-link'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                CopyAction::make()->copyable(config('constants.AFIA_WEBSITE_DOMAIN')),
            ])
            ->bulkActions([
                // Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
