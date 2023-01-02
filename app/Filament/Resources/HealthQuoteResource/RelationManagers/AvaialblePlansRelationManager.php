<?php

namespace App\Filament\Resources\HealthQuoteResource\RelationManagers;

use App\Models\HealthAvailablePlan;
use Filament\Forms;
use Filament\Forms\Components\Tabs;
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
                Tabs::make('Lead')
                    ->tabs(
                        [
                            Tabs\Tab::make('General Information')
                                ->schema([
                                    Forms\Components\TextInput::make('providerCode')->label('Provider Code'),
                                    Forms\Components\TextInput::make('providerName')->label('Provider Name'),
                                    Forms\Components\TextInput::make('actualPremium')->label('Actual Premium'),
                                    Forms\Components\TextInput::make('discountPremium')->label('Discount Premium'),
                                ])->columns(2),
                            Tabs\Tab::make('Members')
                                ->schema([
                                    Forms\Components\TextInput::make('member_id')->label('Member id')->lazy(),
                                ]),
                            Tabs\Tab::make('In Patient')
                                ->schema([
                                    // ...
                                ]),
                            Tabs\Tab::make('Out Patient')
                                ->schema([
                                    // ...
                                ]),
                            Tabs\Tab::make('Co-pay/Co-insurance')
                                ->schema([
                                    // ...
                                ]),
                            Tabs\Tab::make('Region coverage & Network list')
                                ->schema([
                                    // ...
                                ]),
                            Tabs\Tab::make('Maternity Cover')
                                ->schema([
                                    // ...
                                ]),
                            Tabs\Tab::make('Exclusions')
                                ->schema([
                                    // ...
                                ]),
                            Tabs\Tab::make('Policy Detail')
                                ->schema([
                                    // ...
                                ]),
                        ]
                    ),

            ])->columns(1);
    }

    protected function getTableQuery(): Builder
    {
        $query = HealthAvailablePlan::query();

        // dd($query->get());

        return $query;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('providerName')->label('Provider Name'),
                Tables\Columns\TextColumn::make('name')->label('Plan Name'),
                Tables\Columns\TextColumn::make('actualPremium')->label('Actual Premium with BASMAH')
                    ->getStateUsing(function (Model $record): float {
                        return $record->actualPremium + $record->basmah;
                    }),
                Tables\Columns\TextColumn::make('PremiumPlusVat')->label('Premium with VAT and BASMAH')
                    ->getStateUsing(function (Model $record): float {
                        return $record->actualPremium + $record->vat + $record->basmah;
                    }),
            ])
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
