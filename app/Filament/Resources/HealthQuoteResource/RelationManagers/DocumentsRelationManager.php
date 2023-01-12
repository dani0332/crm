<?php

namespace App\Filament\Resources\HealthQuoteResource\RelationManagers;

use App\Enums\QuoteTypeId;
use App\Models\DocumentType;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Support\HtmlString;

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
                        Tabs::make('documents')->tabs(static::getFieldsSchema()),
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
                    ->modalWidth('6xl')
                    ->modalButton('Save'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getFieldsSchema(): array
    {
        $fieldsArray = [];

        $docTypes = DocumentType::where([
            'is_active' => 1,
            'quote_type_id' => QuoteTypeId::Health,
        ])
            // ->orderBy('sort_order')
            ->orderBy('id', 'desc')
            ->get();

        $quoteFields = $docTypes->filter(function ($docType) {
            return $docType['category'] == 'QUOTE';
        });
        $quoteSchema = [];

        $memberFields = $docTypes->filter(function ($docType) {
            return $docType['category'] == 'MEMBER';
        });
        $memberSchema = [];

        if ($quoteFields) {
            foreach ($quoteFields as $docType) {
                $quoteSchema = array_merge([
                    Section::make($docType['text'])
                        ->description(new HtmlString('<p class="text-sm">Accepted file types: '.$docType['accepted_files'].'</p><p class="text-sm">Max files : '.$docType['max_files'].'</p>'))
                        ->aside()
                        ->compact()
                        ->schema([
                            Forms\Components\FileUpload::make('quote_field_'.$docType['code'])
                                ->label('')
                                ->multiple()
                                ->acceptedFileTypes(['image/*', 'application/*'])
                                ->maxSize($docType['max_size'] * 1000)
                                ->required($docType['is_required'])
                                ->maxFiles($docType['max_files'])
                                ->visibility('private')
                                ->preserveFilenames(),
                        ])->columns(1),

                ], $quoteSchema);
            }
        }

        if ($memberFields) {
            foreach ($memberFields as $docType) {
                $memberSchema = array_merge([
                    Section::make($docType['text'])
                    ->description(new HtmlString('<p class="text-sm">Accepted file types: '.$docType['accepted_files'].'</p><p class="text-sm">Max files : '.$docType['max_files'].'</p>'))
                        ->aside()
                        ->compact()
                        ->schema([
                            Forms\Components\FileUpload::make('member_field_'.$docType['code'])
                                ->label('')
                                ->multiple()
                                ->acceptedFileTypes(['image/*', 'application/*'])
                                ->maxSize($docType['max_size'] * 1000)
                                ->required($docType['is_required'])
                                ->maxFiles($docType['max_files']),
                        ])->columns(1),

                ], $memberSchema);
            }
        }

        $fieldsArray = array_merge([
            Tabs\Tab::make('Document')->schema($quoteSchema),
            Tabs\Tab::make('Member 1')->schema($memberSchema),
        ], $fieldsArray);

        return $fieldsArray;
    }

    protected function isTablePaginationEnabled(): bool
    {
        return false;
    }
}
