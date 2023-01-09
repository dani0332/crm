<?php

namespace App\Filament\Resources\HealthQuoteResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';
    protected static ?string $recordTitleAttribute = 'quote_documentable';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Tabs::make('Members')
                            ->tabs([
                                Tabs\Tab::make('Document')
                                    ->schema([
                                        Section::make("Sponsor's Documents - Individual")
                                            ->description('file types info goes here')
                                            ->aside()
                                            ->compact()
                                            ->schema([
                                                Forms\Components\FileUpload::make('sponsor_individual')
                                                    ->label('')
                                                    ->multiple()
                                                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                                                    ->maxFiles(5),
                                            ])->columns(1),
                                        Section::make("Sponsor's Documents - Company")
                                            ->description('file types info goes here')
                                            ->aside()
                                            ->compact()
                                            ->schema([
                                                Forms\Components\FileUpload::make('sponsor_company')
                                                    ->label('')
                                                    ->multiple()
                                                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                                                    ->maxFiles(5),
                                            ])->columns(1),
                                        Section::make('Other Documents')
                                            ->description('file types info goes here')
                                            ->aside()
                                            ->compact()
                                            ->schema([
                                                Forms\Components\FileUpload::make('other')
                                                    ->label('')
                                                    ->multiple()
                                                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                                                    ->maxFiles(5),
                                            ])->columns(1),
                                        Section::make('Confirmation Documents')
                                            ->description('file types info goes here')
                                            ->aside()
                                            ->compact()
                                            ->schema([
                                                Forms\Components\FileUpload::make('confirmation')
                                                    ->label('')
                                                    ->multiple()
                                                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                                                    ->maxFiles(5),
                                            ])->columns(1),
                                        Section::make('E-card')
                                            ->description('file types info goes here')
                                            ->aside()
                                            ->compact()
                                            ->schema([
                                                Forms\Components\FileUpload::make('e_card')
                                                    ->label('')
                                                    ->multiple()
                                                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                                                    ->required()
                                                    ->maxFiles(5),
                                            ])->columns(1),
                                        Section::make('Policy Certificate')
                                            ->description('file types info goes here')
                                            ->aside()
                                            ->compact()
                                            ->schema([
                                                Forms\Components\FileUpload::make('policy_certificate')
                                                    ->label('')
                                                    ->multiple()
                                                    ->required()
                                                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                                                    ->maxFiles(5),
                                            ])->columns(1),
                                        Section::make('Policy Wording/Bond')
                                            ->description('file types info goes here')
                                            ->aside()
                                            ->compact()
                                            ->schema([
                                                Forms\Components\FileUpload::make('policy_wording_bond')
                                                    ->label('')
                                                    ->multiple()
                                                    ->required()
                                                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                                                    ->maxFiles(5),
                                            ])->columns(1),
                                        Section::make('Receipt')
                                            ->description('file types info goes here')
                                            ->aside()
                                            ->compact()
                                            ->schema([
                                                Forms\Components\FileUpload::make('receipt')
                                                    ->label('')
                                                    ->multiple()
                                                    ->required()
                                                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                                                    ->maxFiles(5),
                                            ])->columns(1),
                                        Section::make('Tax Invoice')
                                            ->description('file types info goes here')
                                            ->aside()
                                            ->compact()
                                            ->schema([
                                                Forms\Components\FileUpload::make('tax_invoice')
                                                    ->label('')
                                                    ->multiple()
                                                    ->required()
                                                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                                                    ->maxFiles(5),
                                            ])->columns(1),
                                        Section::make('TIRBB/Credit Note')
                                            ->description('file types info goes here')
                                            ->aside()
                                            ->compact()
                                            ->schema([
                                                Forms\Components\FileUpload::make('tirbb_credit_note')
                                                    ->label('')
                                                    ->multiple()
                                                    ->required()
                                                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                                                    ->maxFiles(5),
                                            ])->columns(1),

                                    ]),
                                Tabs\Tab::make('Member 1')
                                    ->schema([
                                        Section::make("Member's Passport")
                                            ->description('Upload your passport here')
                                            ->aside()
                                            ->compact()
                                            ->schema([
                                                Forms\Components\FileUpload::make('passport')
                                                    ->label('')
                                                    ->multiple()
                                                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                                                    ->maxFiles(5),
                                            ])->columns(1),
                                        Section::make("Member's Visa")
                                            ->description('Upload your passport here')
                                            ->aside()
                                            ->compact()
                                            ->schema([
                                                Forms\Components\FileUpload::make('visa')
                                                    ->label('')
                                                    ->multiple()
                                                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                                                    ->maxFiles(5),
                                            ])->columns(1),
                                        Section::make("Member's Emirates ID")
                                            ->description('Upload your passport here')
                                            ->aside()
                                            ->compact()
                                            ->schema([
                                                Forms\Components\FileUpload::make('emirates')
                                                    ->label('')
                                                    ->multiple()
                                                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                                                    ->maxFiles(5),
                                            ])->columns(1),
                                        Section::make('UAE Birth Certificate (for Newborn)')
                                            ->description('Upload your passport here')
                                            ->aside()
                                            ->compact()
                                            ->schema([
                                                Forms\Components\FileUpload::make('uae_birth_certificate')
                                                    ->label('')
                                                    ->multiple()
                                                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                                                    ->maxFiles(5),
                                            ])->columns(1),
                                        Section::make('Discharge Summary (for Newborn)')
                                            ->description('Upload your passport here')
                                            ->aside()
                                            ->compact()
                                            ->schema([
                                                Forms\Components\FileUpload::make('discharge_summary')
                                                    ->label('')
                                                    ->multiple()
                                                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                                                    ->maxFiles(5),
                                            ])->columns(1),
                                        Section::make('Medical Report (if applicable)')
                                            ->description('Upload your passport here')
                                            ->aside()
                                            ->compact()
                                            ->schema([
                                                Forms\Components\FileUpload::make('medical_report')
                                                    ->label('')
                                                    ->multiple()
                                                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                                                    ->maxFiles(5),
                                            ])->columns(1),
                                    ]),
                            ]),
                    ]),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('documents.doc_name')->label('Document Name'),
                Tables\Columns\TextColumn::make('doc_type')->label('Document Type'),
                Tables\Columns\TextColumn::make('uploaded_by')->label('Uploaded By'),
                Tables\Columns\TextColumn::make('uploaded_at')->label('Uploaded At'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make('Upload Document')
                    ->label('Upload Document')
                    ->modalHeading(function (RelationManager $livewire) {
                        return 'Upload Document for '.$livewire->ownerRecord->code;
                    })
                    ->modalWidth('6xl'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
