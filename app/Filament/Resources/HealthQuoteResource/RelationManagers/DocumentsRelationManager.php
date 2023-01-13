<?php

namespace App\Filament\Resources\HealthQuoteResource\RelationManagers;

use App\Enums\QuoteTypeId;
use App\Models\DocumentType;
use App\Models\QuoteDocument;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Support\HtmlString;
use Livewire\TemporaryUploadedFile;

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
                Tables\Columns\TextColumn::make('document_type_text')->label('Document Type'),
                Tables\Columns\TextColumn::make('doc_name')
                    ->label('Document Name')
                    ->getStateUsing(fn ($record) => $record->doc_name ? explode('_', $record->doc_name)[1] : null),
                Tables\Columns\TextColumn::make('createdBy.name')->label('Uploaded By'),
                Tables\Columns\TextColumn::make('updated_at')->label('Uploaded At'),
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
                    ->modalButton('Save')
                    ->mutateFormDataUsing(function (RelationManager $livewire, array $data) {
                        $data = array_filter($data);
                        foreach ($data as $key => $value) {
                            if (str_contains($key, 'doc_code')) {
                                $array = explode('doc_code_', $key);
                                $doc_code = array_pop($array);
                                $value = array_pop($value);
                                $filePath = $value ? explode('/', $value)[2] : null;
                                $docUuid = $filePath ? explode('_', $filePath)[0] : null;
                                $updatedDoc = $livewire
                                    ->ownerRecord
                                    ->documents
                                    ->where('quote_documentable_id', $livewire->ownerRecord->id)
                                    ->where('document_type_code', $doc_code)
                                    ->where('doc_uuid', $docUuid)
                                    ->first();
                                if ($updatedDoc) {
                                    if ($value === null) {
                                        // $updatedDoc->delete();
                                    } else {
                                        $updatedDoc->doc_url = $value;
                                        $updatedDoc->save();
                                    }
                                } else {
                                    if ($value === null) {
                                        continue;
                                    } else {
                                        $newDoc = new QuoteDocument();
                                        $newDoc->doc_uuid = $docUuid;
                                        $newDoc->quote_documentable_id = $livewire->ownerRecord->id;
                                        $newDoc->quote_documentable_type = 'App\Models\HealthQuote';
                                        $newDoc->document_type_code = $doc_code;
                                        $newDoc->document_type_text = DocumentType::where('code', $doc_code)->first()->text;
                                        $newDoc->doc_name = $filePath;
                                        $newDoc->doc_url = $value;
                                        $newDoc->doc_mime_type = $value ? explode('.', $value)[1] : null;
                                        $newDoc->created_by_id = auth()->id();
                                        $newDoc->member_detail_id = $livewire->ownerRecord->members[0]->id;
                                        $newDoc->save();
                                    }
                                }
                                unset($data[$key]);
                            }
                        }

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalWidth('6xl')
                    ->mutateRecordDataUsing(function (RelationManager $livewire, array $data): array {
                        $docs = $livewire->ownerRecord->documents;
                        foreach ($docs as $doc) {
                            if (isset($doc->doc_url)) {
                                $data['doc_code_'.$doc->document_type_code] = array_merge(
                                    $data['doc_code_'.$doc->document_type_code] ?? [],
                                    [$doc->doc_url]
                                );
                            }
                        }

                        return $data;
                    })
                    ->mutateFormDataUsing(function (RelationManager $livewire, array $data) {
                        $data = array_filter($data);
                        foreach ($data as $key => $value) {
                            if (str_contains($key, 'doc_code')) {
                                $array = explode('doc_code_', $key);
                                $doc_code = array_pop($array);
                                $value = array_pop($value);
                                $filePath = $value ? explode('/', $value)[2] : null;
                                $docUuid = $filePath ? explode('_', $filePath)[0] : null;
                                $updatedDoc = $livewire
                                    ->ownerRecord
                                    ->documents
                                    ->where('quote_documentable_id', $livewire->ownerRecord->id)
                                    ->where('document_type_code', $doc_code)
                                    ->where('doc_uuid', $docUuid)
                                    ->first();
                                if ($updatedDoc) {
                                    if ($value === null) {
                                        // $updatedDoc->delete();
                                    } else {
                                        $updatedDoc->doc_url = $value;
                                        $updatedDoc->save();
                                    }
                                } else {
                                    if ($value === null) {
                                        continue;
                                    } else {
                                        $newDoc = new QuoteDocument();
                                        $newDoc->doc_uuid = $docUuid;
                                        $newDoc->quote_documentable_id = $livewire->ownerRecord->id;
                                        $newDoc->quote_documentable_type = 'App\Models\HealthQuote';
                                        $newDoc->document_type_code = $doc_code;
                                        $newDoc->document_type_text = DocumentType::where('code', $doc_code)->first()->text;
                                        $newDoc->doc_name = $filePath;
                                        $newDoc->doc_url = $value;
                                        $newDoc->doc_mime_type = $value ? explode('.', $value)[1] : null;
                                        $newDoc->created_by_id = auth()->id();
                                        $newDoc->member_detail_id = $livewire->ownerRecord->members[0]->id;
                                        $newDoc->save();
                                    }
                                }
                                unset($data[$key]);
                            }
                        }

                        return $data;
                    }),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getFieldsSchema(): array
    {
        $fieldsArray = [];

        $docTypes = DocumentType::where([
            'is_active' => 1,
            'quote_type_id' => QuoteTypeId::Health,
        ])
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
                            Forms\Components\FileUpload::make('doc_code_'.$docType['code'])
                                ->label('')
                                ->multiple()
                                ->enableOpen()
                                ->acceptedFileTypes(['image/*', 'application/*'])
                                ->maxSize($docType['max_size'] * 1000)
                                ->maxFiles($docType['max_files'])
                                ->disk('azureIM')
                                ->directory('documents/'.$docType['folder_path'])
                                ->visibility('public')
                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                    return (string) preg_replace('/\s+/', '', uniqid().'_'.$file->getClientOriginalName());
                                }),
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
                            Forms\Components\FileUpload::make('doc_code_'.$docType['code'])
                                ->label('')
                                ->multiple()
                                ->enableOpen()
                                ->acceptedFileTypes(['image/*', 'application/*'])
                                ->maxSize($docType['max_size'] * 1000)
                                ->maxFiles($docType['max_files'])
                                ->disk('azureIM')
                                ->directory('documents/'.$docType['folder_path'])
                                ->visibility('public')
                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                    return (string) preg_replace('/\s+/', '', uniqid().'_'.$file->getClientOriginalName());
                                }),
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
