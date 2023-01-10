<?php

namespace App\Filament\Resources\HealthQuoteResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\Tabs;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Model;
use Webbingbrasil\FilamentCopyActions\Tables\Actions\CopyAction;

class AvailablePlansRelationManager extends RelationManager
{
    protected static string $relationship = 'availablePlans';
    protected static ?string $recordTitleAttribute = 'providerName';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Lead')
                    ->tabs(
                        [
                            Tabs\Tab::make('General Information')
                                ->schema([
                                    Forms\Components\Placeholder::make('Provider Code')->content(function (?Model $record) {
                                        return $record->providerCode;
                                    }),
                                    Forms\Components\Placeholder::make('Provider Name')->content(function (?Model $record) {
                                        return $record->providerName;
                                    }),
                                    Forms\Components\Placeholder::make('Actual Premium')->content(function (?Model $record) {
                                        return $record->actualPremium;
                                    }),
                                    Forms\Components\Placeholder::make('Discount Premium')->content(function (?Model $record) {
                                        return $record->discountPremium;
                                    }),
                                ])->columns(2),
                            Tabs\Tab::make('Members')
                                ->schema([
                                    Forms\Components\ViewField::make('memberPremiumBreakdown')->view('filament.plan_detail'),
                                ]),
                            Tabs\Tab::make('In Patient')
                                ->schema([
                                    Forms\Components\ViewField::make('benefitsInpatient')->view('filament.plan_detail'),
                                ]),
                            Tabs\Tab::make('Out Patient')
                                ->schema([
                                    Forms\Components\ViewField::make('benefitsOutpatient')->view('filament.plan_detail'),
                                ]),
                            Tabs\Tab::make('Co-pay/Co-insurance')
                                ->schema([
                                    Forms\Components\ViewField::make('coInsurance')->view('filament.plan_detail'),
                                ]),
                            Tabs\Tab::make('Region coverage & Network list')
                                ->schema([
                                    Forms\Components\ViewField::make('regionCover')->view('filament.plan_detail'),
                                ]),
                            Tabs\Tab::make('Maternity Cover')
                                ->schema([
                                    Forms\Components\ViewField::make('maternityCover')->view('filament.plan_detail'),
                                ]),
                            Tabs\Tab::make('Exclusions')
                                ->schema([
                                    Forms\Components\ViewField::make('benefitsExclusions')->view('filament.plan_detail'),
                                ]),
                            Tabs\Tab::make('Policy Detail')
                                ->schema([
                                    Forms\Components\ViewField::make('policyDetail')->view('filament.plan_detail'),
                                ]),
                        ]
                    ),

            ])->columns(1);
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
            ->bulkActions([]);
    }

    protected function isTablePaginationEnabled(): bool
    {
        return false;
    }
}
