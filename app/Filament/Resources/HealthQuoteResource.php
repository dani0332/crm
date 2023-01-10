<?php

namespace App\Filament\Resources;

use App\Enums\GenericRequestEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Filament\Resources\HealthQuoteResource\Pages;
use App\Filament\Resources\HealthQuoteResource\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\HealthQuoteResource\RelationManagers\MemberDetailsRelationManager;
use App\Models\Activities;
use App\Models\HealthQuote;
use App\Models\QuoteStatus;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Tabs;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Layout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Z3d0X\FilamentSimplePermissions\Concerns\HasResourcePermissions;

class HealthQuoteResource extends Resource
{
    use HasResourcePermissions;

    protected static array $permissions = [
        'viewAny' => PermissionsEnum::HealthQuotesList,
        // 'view' => 'access-users',
        // 'create' => 'create-users',
        // 'update' => 'update-users',
        // 'delete' => ['update-users', 'delete-stuff'],
        'deleteAny' => false,
    ];
    protected static ?string $model = HealthQuote::class;
    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static ?string $navigationGroup = 'Personal Quotes';
    protected static ?string $recordRouteKeyName = 'uuid';
    protected static ?string $recordTitleAttribute = 'code';
    protected static bool $isGloballySearchable = false;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('health_quote_request.quote_status_id', '!=', QuoteStatusEnum::Fake)
            ->orderBy('health_quote_request.created_at', 'DESC');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Lead')
                    ->tabs([
                        Tabs\Tab::make('Lead Details')
                            ->schema([
                                Fieldset::make('Lead Details')
                                    ->schema([
                                        Forms\Components\Placeholder::make('code')->label('CDB ID')->content(function (Model $record) {
                                            return $record?->code;
                                        }),
                                        Forms\Components\Placeholder::make('created_at')->label('Created At')->content(function (Model $record) {
                                            return $record?->created_at->format('M d, Y H:i:s');
                                        }),
                                        Forms\Components\Placeholder::make('health_team_type')->label('Sub Team')->content(function (Model $record) {
                                            return $record?->health_team_type;
                                        }),
                                        Forms\Components\Placeholder::make('advisor_id')->label('Advisor')->content(function (Model $record) {
                                            return $record?->advisor?->name;
                                        }),
                                        Forms\Components\Placeholder::make('source')->content(function (Model $record) {
                                            return $record?->source;
                                        }),
                                        Forms\Components\Placeholder::make('updated_at')->label('Last Modified Date')->content(function (Model $record) {
                                            return $record?->updated_at->format('M d, Y H:i:s');
                                        }),
                                        Forms\Components\Placeholder::make('parent_duplicate_quote_id')->label('Parent CDB ID')->content(function (Model $record) {
                                            return $record?->parent_duplicate_quote_id;
                                        }),
                                        Forms\Components\Placeholder::make('renewal_batch')->label('Renewal Batch')->content(function (Model $record) {
                                            return $record?->renewal_batch;
                                        }),
                                        Forms\Components\Placeholder::make('is_ecommerce')->label('Is Ecommerce')->content(function (Model $record) {
                                            return $record?->is_ecommerce ? 'Yes' : 'No';
                                        }),
                                        Forms\Components\Placeholder::make('is_renewal')->label('Is Renewal')->content(function (Model $record) {
                                            return $record?->is_renewal ? 'Yes' : 'No';
                                        }),
                                    ])
                                    ->visibleOn('view'),
                                Fieldset::make('Customer Profile')
                                    ->schema([
                                        Forms\Components\TextInput::make('first_name')
                                            ->maxLength(255)
                                            ->required(),
                                        Forms\Components\TextInput::make('last_name')
                                            ->maxLength(255)
                                            ->required(),
                                        Forms\Components\TextInput::make('email')
                                            ->email()
                                            ->maxLength(150)
                                            ->required(),
                                        Forms\Components\TextInput::make('mobile_no')
                                            ->maxLength(20)
                                            ->required(),
                                        Forms\Components\Select::make('gender')
                                            ->options([
                                                GenericRequestEnum::MALE_SINGLE,
                                                GenericRequestEnum::FEMALE_SINGLE,
                                                GenericRequestEnum::FEMALE_MARRIED,
                                            ]),
                                        Forms\Components\Select::make('marital_status_id')
                                            ->label('Marital Status')
                                            ->options(function () {
                                                return DB::table('marital_status')->where('is_active', true)->pluck('text', 'id');
                                            })
                                            ->required(),
                                        Forms\Components\Select::make('nationality_id')
                                            ->label('Nationality')
                                            ->options(function () {
                                                return DB::table('nationality')->where('is_active', true)->pluck('text', 'id');
                                            })
                                            ->searchable()
                                            ->required(),
                                        Forms\Components\DatePicker::make('dob')->label('Date of Birth')->required(),
                                        Forms\Components\Select::make('emirate_of_your_visa_id')
                                            ->label('Emirate of Visa')
                                            ->options(function () {
                                                return DB::table('emirates')->where('is_active', true)->pluck('text', 'id');
                                            })
                                            ->required(),
                                        Forms\Components\Select::make('member_category_id')
                                            ->label('Member Category')
                                            ->options(function () {
                                                return DB::table('member_category')->where('is_active', true)->pluck('text', 'id');
                                            }),
                                        Forms\Components\Select::make('salary_band_id')
                                            ->label('Salary Band')
                                            ->options(function () {
                                                return DB::table('salary_band')->where('is_active', true)->pluck('text', 'id');
                                            }),
                                    ]),
                                Fieldset::make('Quote Details')
                                    ->schema([
                                        Forms\Components\Select::make('cover_for_id')->label('Who are you looking to cover')
                                            ->options(function () {
                                                return DB::table('health_cover_for')->where('is_active', true)->pluck('text', 'id');
                                            }),
                                        Forms\Components\Select::make('currently_insured_with_id')
                                            ->label('Currently Insured With')
                                            ->searchable()
                                            ->options(function () {
                                                return DB::table('insurance_provider')->where('is_active', true)->pluck('text', 'id');
                                            }),

                                        Forms\Components\Select::make('health_plan_type_id')
                                            ->label('Type of Plan')
                                            ->options(function () {
                                                return DB::table('health_plan_type')->where('is_active', true)->pluck('text', 'id');
                                            }),

                                        Forms\Components\TextInput::make('details')
                                            ->maxLength(1000),
                                    ]),
                                Fieldset::make("Last Year's Policy Details")
                                    ->schema([
                                        Forms\Components\TextInput::make('previous_quote_policy_number')
                                            ->maxLength(100),
                                        Forms\Components\DatePicker::make('previous_policy_expiry_date'),
                                        Forms\Components\TextInput::make('previous_quote_policy_premium'),
                                    ])
                                    ->visibleOn('view'),
                                Fieldset::make('Policy Details')
                                    ->schema([
                                        Forms\Components\TextInput::make('policy_number')
                                            ->maxLength(100),
                                        Forms\Components\DatePicker::make('policy_start_date'),
                                        Forms\Components\DatePicker::make('renewal_expiry_date')->label('Policy End Date'),
                                    ]),
                                Fieldset::make('Request Details')
                                    ->schema([
                                        Forms\Components\Placeholder::make('transapp_code')->label('Transapp Code')->content(function (Model $record) {
                                            return $record->transapp_code;
                                        }),
                                        Forms\Components\Placeholder::make('next_followup_date')->label('Next Followup Date')->content(function (Model $record) {
                                            return $record->next_followup_date?->format('M d, Y H:i:s');
                                        }),
                                    ])
                                    ->visibleOn('view'),

                            ]),
                        Tabs\Tab::make('E-COM Details')
                            ->schema([
                                Forms\Components\Placeholder::make('Plan Name'),
                                Forms\Components\Placeholder::make('Provider Name'),
                                Forms\Components\Placeholder::make('Payment Status'),
                                Forms\Components\Placeholder::make('Paid At'),
                                Forms\Components\Placeholder::make('Network'),
                            ])->columns(2)->hiddenOn(['create', 'edit']),
                        Tabs\Tab::make('Lead History')
                            ->schema(function (?Model $record) {
                                return static::getActivitiesSchema($record->health_quote_request_id);
                            })->hiddenOn(['create', 'edit']),
                    ]),

                Card::make()
                    ->schema([
                        Forms\Components\Select::make('quote_status_id')
                            ->label('Lead Status')
                            ->options(
                                function () {
                                    return QuoteStatus::whereNotIn('id', [
                                        QuoteStatusEnum::AMLScreeningCleared, QuoteStatusEnum::Draft, QuoteStatusEnum::Cancelled, QuoteStatusEnum::AMLScreeningFailed,
                                        QuoteStatusEnum::TransactionDeclined, QuoteStatusEnum::PolicyInvoiced, QuoteStatusEnum::Issued, QuoteStatusEnum::PriceTooHigh, QuoteStatusEnum::PolicyPurchasedBeforeFirstCall, QuoteStatusEnum::NotContactablePe, QuoteStatusEnum::FollowupCall, QuoteStatusEnum::Interested, QuoteStatusEnum::NoAnswer, QuoteStatusEnum::NotInterested, QuoteStatusEnum::NotEligibleForInsurance, QuoteStatusEnum::AfiaRenewal, QuoteStatusEnum::NotLookingForMotorInsurance, QuoteStatusEnum::NonGccSpec,
                                    ])
                                        ->where('is_active', true)
                                        ->orderBy('sort_order', 'asc')
                                        ->pluck('text', 'id')->toArray();
                                }
                            ),
                        Forms\Components\Textarea::make('additional_notes')->label('Notes')->maxLength(1000),
                    ])->hiddenOn('create'),
            ])->columns(1);
    }

    public static function getActivitiesSchema($id): array
    {
        $fieldsArray = [];

        $activities = Activities::where('quote_request_id', $id)->where('quote_type_id', QuoteTypeId::Health)->orderBy('created_at', 'desc')->get();

        if ($activities) {
            foreach ($activities as $activity) {
                $fieldsArray = array_merge([
                    Forms\Components\Grid::make()
                        ->schema([
                            Forms\Components\Group::make()
                                ->schema([
                                    Forms\Components\Placeholder::make('activity_title')->label('Title')->content($activity->title),
                                    Forms\Components\Placeholder::make('activity_cdbid')->label('CDBID')->content($activity->quote_uuid),
                                    Forms\Components\Placeholder::make('activity_client_name')->label('Client Name')->content($activity->client_name),
                                    Forms\Components\Placeholder::make('activity_followup_date')->label('Followup Date')->content($activity->due_date),
                                    Forms\Components\Placeholder::make('activity_created_at')->label('Assigned To')->content($activity->assignee->name),
                                    Forms\Components\Placeholder::make('activity_done')->label('Done')->content($activity->status == 1 ? 'Yes' : 'No'),
                                ])->columns(6),
                        ])->columns(1),
                ], $fieldsArray);
            }
        }

        return $fieldsArray;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('CDB ID')
                    ->color('primary')
                    ->url(fn (HealthQuote $record): string => './health-quotes/'.$record->uuid)
                    ->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('first_name')->label('First Name'),
                Tables\Columns\TextColumn::make('last_name')->label('Last Name'),
                Tables\Columns\TextColumn::make('quoteStatus.text')->label('Lead Status'),
                Tables\Columns\TextColumn::make('advisor.name')->label('Advisor'),
                Tables\Columns\TextColumn::make('wcAdvisor.name')->label('WC Advisor'),
                Tables\Columns\TextColumn::make('created_at')->label('Created Date')->dateTime('d-m-Y H:i:s'),
                Tables\Columns\TextColumn::make('updated_at')->label('Last Modified Date')->dateTime('d-m-Y H:i:s'),
                Tables\Columns\TextColumn::make('health_team_type')->label('Sub Team'),
                Tables\Columns\TextColumn::make('healthQuoteRequestDetail.transapp_code')->label('Transapp Code'),
                Tables\Columns\TextColumn::make('healthQuoteRequestDetail.lostReason.text')->label('Lost Reason'),
                Tables\Columns\TextColumn::make('premium'),
                Tables\Columns\TextColumn::make('policy_number'),
                Tables\Columns\TextColumn::make('source'),
                Tables\Columns\TextColumn::make('salaryBand.text')->label('Salary Band'),
                Tables\Columns\TextColumn::make('memberCategory.text')->label('Member Category'),
                Tables\Columns\TextColumn::make('currentProvider.text')->label('Currently Insured With'),
                Tables\Columns\IconColumn::make('is_ecommerce')->boolean(),
            ])
            ->filters(
                [
                    Filter::make('cdb_id')->label('CDB ID')
                        ->form([
                            Forms\Components\TextInput::make('code')->label('CDB ID')->lazy(),
                        ])
                        ->query(function (Builder $query, array $data): Builder {
                            return $query
                                ->when(
                                    $data['code'],
                                    fn (Builder $query, $value): Builder => $query->where('code', $value),
                                );
                        }),
                    Filter::make('first_name')->label('First Name')
                        ->form([
                            Forms\Components\TextInput::make('first_name')->label('First Name')->lazy(),
                        ])
                        ->query(function (Builder $query, array $data): Builder {
                            return $query
                                ->when(
                                    $data['first_name'],
                                    fn (Builder $query, $value): Builder => $query->where('first_name', $value),
                                );
                        }),
                    Filter::make('last_name')->label('Last Name')
                        ->form([
                            Forms\Components\TextInput::make('last_name')->label('Last Name')->lazy(),
                        ])
                        ->query(function (Builder $query, array $data): Builder {
                            return $query
                                ->when(
                                    $data['last_name'],
                                    fn (Builder $query, $value): Builder => $query->where('last_name', $value),
                                );
                        }),
                    Filter::make('email')->label('Email')
                        ->form([
                            Forms\Components\TextInput::make('email')->label('Email Address')->lazy(),
                        ])
                        ->query(function (Builder $query, array $data): Builder {
                            return $query
                                ->when(
                                    $data['email'],
                                    fn (Builder $query, $value): Builder => $query->where('email', $value),
                                );
                        }),
                    Filter::make('mobile')->label('Mobile Number')
                        ->form([
                            Forms\Components\TextInput::make('number')->tel()->label('Mobile Number')->lazy(),
                        ])
                        ->query(function (Builder $query, array $data): Builder {
                            return $query
                                ->when(
                                    $data['number'],
                                    fn (Builder $query, $value): Builder => $query->where('mobile_no', $value),
                                );
                        }),
                    Filter::make('type')->label('Type')
                        ->form([
                            Forms\Components\Select::make('subTeam')->label('SubTeam')
                                ->options([
                                    '' => 'All',
                                    'RM-NB' => 'RM-NB',
                                    'RM-Speed' => 'RM-Speed',
                                    'EBP' => 'EBP',
                                    'Wow-Call' => 'Wow-Call',
                                    'No-Type' => 'No-Type',
                                ]),
                        ])
                        ->query(function (Builder $query, array $data): Builder {
                            return $query
                                ->when(
                                    $data['subTeam'],
                                    fn (Builder $query, $value): Builder => $query->where('health_team_type', $value),
                                );
                        }),
                    Filter::make('created_start')
                        ->form([
                            Forms\Components\DatePicker::make('created_start')->lazy(),
                        ])
                        ->query(function (Builder $query, array $data): Builder {
                            return $query
                                ->when(
                                    $data['created_start'],
                                    fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                                );
                        }),
                    Filter::make('created_end')
                        ->form([
                            Forms\Components\DatePicker::make('created_end')->lazy(),
                        ])
                        ->query(function (Builder $query, array $data): Builder {
                            return $query
                                ->when(
                                    $data['created_end'],
                                    fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                                );
                        }),
                    SelectFilter::make('lead_status')
                        ->placeholder('Select Lead Status')
                        ->multiple()
                        ->options(
                            function () {
                                return QuoteStatus::whereNotIn('id', [
                                    QuoteStatusEnum::AMLScreeningCleared, QuoteStatusEnum::Draft, QuoteStatusEnum::Cancelled, QuoteStatusEnum::AMLScreeningFailed,
                                    QuoteStatusEnum::TransactionDeclined, QuoteStatusEnum::PolicyInvoiced, QuoteStatusEnum::Issued, QuoteStatusEnum::PriceTooHigh, QuoteStatusEnum::PolicyPurchasedBeforeFirstCall, QuoteStatusEnum::NotContactablePe, QuoteStatusEnum::FollowupCall, QuoteStatusEnum::Interested, QuoteStatusEnum::NoAnswer, QuoteStatusEnum::NotInterested, QuoteStatusEnum::NotEligibleForInsurance, QuoteStatusEnum::AfiaRenewal, QuoteStatusEnum::NotLookingForMotorInsurance, QuoteStatusEnum::NonGccSpec,
                                ])
                                    ->where('is_active', true)
                                    ->orderBy('sort_order', 'asc')
                                    ->pluck('text', 'id')->toArray();
                            }
                        )
                        ->query(function (Builder $query, array $data) {
                            if (! empty($data['values'])) {
                                $query->whereIn('quote_status_id', $data['values']);
                            }
                        }),

                    SelectFilter::make('advisor')
                        ->placeholder('Select Advisor')
                        ->multiple()
                        ->options(
                            function () {
                                return User::query()
                                    ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
                                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                                    ->whereIn('roles.name', [RolesEnum::RMAdvisor, RolesEnum::EBPAdvisor, RolesEnum::HealthRenewalAdvisor, RolesEnum::HealthNewBusinessAdvisor, RolesEnum::HealthWCUAdvisor])
                                    ->select('users.id', DB::raw("CONCAT(users.name,' - ',roles.name) as name"))
                                    ->orderBy('roles.name')
                                    ->get()
                                    ->keyBy('id')
                                    ->map(fn ($users) => $users->name)
                                    ->toArray();
                            }
                        )
                        ->query(function (Builder $query, array $data) {
                            if (! empty($data['values'])) {
                                $query->whereIn('advisor_id', $data['values']);
                            }
                        }),
                    TernaryFilter::make('is_ecommerce')->label('Is Ecommerce')
                        ->placeholder('All')
                        ->queries(
                            true: fn (Builder $query) => $query->where('is_ecommerce', true),
                            false: fn (Builder $query) => $query->where('is_ecommerce', false),
                        ),
                    TernaryFilter::make('is_renewal')->label('Is Renewal')
                        ->placeholder('All')
                        ->queries(
                            true: fn (Builder $query) => $query->whereNotNull('renewal_import_code'),
                            false: fn (Builder $query) => $query->whereNull('renewal_import_code'),
                        ),
                ],
                layout: Layout::AboveContent
            )
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->openUrlInNewTab(),
                    Tables\Actions\EditAction::make()->openUrlInNewTab(),
                ]),
            ])
            ->bulkActions([
                ExportBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationGroup::make('Relations', [
                MemberDetailsRelationManager::class,
                DocumentsRelationManager::class,
            ]),

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHealthQuotes::route('/'),
            'create' => Pages\CreateHealthQuote::route('/create'),
            'view' => Pages\ViewHealthQuote::route('/{record:uuid}'),
            'edit' => Pages\EditHealthQuote::route('/{record:uuid}/edit'),
        ];
    }
}
