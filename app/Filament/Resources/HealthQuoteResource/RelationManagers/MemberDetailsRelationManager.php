<?php

namespace App\Filament\Resources\HealthQuoteResource\RelationManagers;

use App\Enums\GenericRequestEnum;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;

class MemberDetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'members';
    protected static ?string $title = 'Member Details';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('health_quote_request_id'),
                Forms\Components\Select::make('gender')
                    ->options([
                        'M' => GenericRequestEnum::MALE_SINGLE,
                        'FS' => GenericRequestEnum::FEMALE_SINGLE,
                        'FM' => GenericRequestEnum::FEMALE_MARRIED,
                    ]),
                Forms\Components\DatePicker::make('dob'),
                Forms\Components\Select::make('nationality_id')
                    ->searchable()
                    ->options(function () {
                        return \App\Models\Nationality::all()->pluck('text', 'id');
                    }),
                Forms\Components\Select::make('emirate_of_your_visa_id')
                    ->options(function () {
                        return \App\Models\Emirate::all()->pluck('text', 'id');
                    }),
                Forms\Components\Select::make('member_category_id')
                    ->label('Relationship')
                    ->options(function () {
                        return \App\Models\MemberCategory::all()->pluck('text', 'id');
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->getStateUsing(static function ($rowLoop): string {
                    return (string) 'Member '.$rowLoop->iteration;
                }),
                Tables\Columns\TextColumn::make('gender')
                    ->enum([
                        'M' => GenericRequestEnum::MALE_SINGLE,
                        'FS' => GenericRequestEnum::FEMALE_SINGLE,
                        'FM' => GenericRequestEnum::FEMALE_MARRIED,
                    ]),
                Tables\Columns\TextColumn::make('dob')->date(),
                Tables\Columns\TextColumn::make('nationality.text')->label('Nationality'),
                Tables\Columns\TextColumn::make('emirate.text')->label('Emirate of Visa'),
                Tables\Columns\TextColumn::make('memberCategory.text')->label('Relationship'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Add Member'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [false];
    }
}
