<?php

namespace App\Filament\Resources;

use App\Enums\GenericRequestEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\RolesEnum;
use App\Filament\Resources\HealthQuoteResource\Pages;
use App\Filament\Resources\HealthQuoteResource\RelationManagers\AvaialblePlansRelationManager;
use App\Filament\Resources\HealthQuoteResource\RelationManagers\MemberDetailsRelationManager;
use App\Models\HealthQuote;
use App\Models\QuoteStatus;
use App\Models\User;
use App\Services\HealthQuoteService;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Resources\Form;
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

class HealthQuoteResource extends Resource
{
    protected static ?string $model = HealthQuote::class;
    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static ?string $navigationGroup = 'Personal Quotes';
    protected static ?string $recordRouteKeyName = 'uuid';
    protected static ?string $recordTitleAttribute = 'code';
    protected static bool $isGloballySearchable = false;

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
                                        Forms\Components\TextInput::make('code')->label('CDB ID'),
                                        Forms\Components\DateTimePicker::make('created_at')->label('Created Date'),
                                        Forms\Components\Select::make('health_team_type')
                                            ->label('Sub Team')
                                            ->options([
                                                'All' => 'All',
                                                'RM-NB' => 'RM-NB',
                                                'RM-Speed' => 'RM-Speed',
                                                'EBP' => 'EBP',
                                                'Wow-Call' => 'Wow-Call',
                                                'No-Type' => 'No-Type',
                                            ]),
                                        Forms\Components\Select::make('advisor_id')
                                            ->label('Advisor')
                                            ->searchable()
                                            ->options(function () {
                                                return  DB::table('users as u')->select('u.id', 'u.name')
                                                    ->join('model_has_roles as mhr', 'mhr.model_id', '=', 'u.id')
                                                    ->join('roles as r', 'mhr.role_id', '=', 'r.id')->get()->pluck('name', 'id');
                                            }),
                                        Forms\Components\TextInput::make('source'),
                                        Forms\Components\DateTimePicker::make('updated_at')->label('Last Modified Date'),
                                        Forms\Components\TextInput::make('parent_duplicate_quote_id')->label('Parent CDB ID'),
                                        Forms\Components\TextInput::make('renewal_batch')->label('Renewal Batch'),
                                        Forms\Components\Toggle::make('is_ecommerce')
                                            ->label('Is Ecommerce'),
                                        Forms\Components\Toggle::make('is_ebp_renewal')
                                            ->label('Is EBP Renewal'),
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
                                        Forms\Components\DatePicker::make('policy_end_date'),
                                    ]),
                                Fieldset::make('Request Details')
                                    ->relationship('healthQuoteRequestDetail')
                                    ->schema([
                                        Forms\Components\TextInput::make('transapp_code'),
                                        Forms\Components\DatePicker::make('next_followup_date')->label('Next Followup Date'),
                                    ])
                                    ->visibleOn('view'),

                            ]),
                        Tabs\Tab::make('Lead Status')
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
                                Forms\Components\Textarea::make('notes'),
                            ])->hiddenOn('create'),
                        Tabs\Tab::make('E-COM Details')
                            ->schema(
                                []
                                // function (?Model $record) {
                                //     return static::getEcomDetailsSchema($record);
                                // }
                            )->hiddenOn(['create', 'edit']),
                        // Tabs\Tab::make('Available Plans')
                        //     ->schema(
                        //         []
                        //         // function (?Model $record) {
                        //         //     return static::getPlansSchema($record->uuid ?? null);
                        //         // }
                        //     )->hiddenOn(['create', 'edit']),
                        Tabs\Tab::make('Lead History')
                            ->schema([
                                // ...
                            ])->hiddenOn(['create', 'edit']),
                    ]),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('CDB ID')->color('primary'),
                Tables\Columns\TextColumn::make('first_name')->label('First Name'),
                Tables\Columns\TextColumn::make('last_name')->label('Last Name'),
                Tables\Columns\TextColumn::make('quoteStatus.text')->label('Lead Status'),
                Tables\Columns\TextColumn::make('advisor.name')->label('Advisor'),
                Tables\Columns\TextColumn::make('created_at')->label('Created Date')->dateTime(),
                Tables\Columns\TextColumn::make('updated_at')->label('Last Modified Date')->dateTime(),
                Tables\Columns\TextColumn::make('health_team_type')->label('Sub Team'),
                Tables\Columns\TextColumn::make('premium'),
                Tables\Columns\TextColumn::make('policy_number'),
                Tables\Columns\TextColumn::make('source'),
                Tables\Columns\TextColumn::make('salaryBand.text')->label('Salary Band'),
                Tables\Columns\TextColumn::make('memberCategory.text')->label('Member Category'),
                Tables\Columns\TextColumn::make('currentProvider.text')->label('Currently Insured With'),
                Tables\Columns\IconColumn::make('is_ecommerce')->boolean(),
            ])
            ->defaultSort('created_at', 'desc')
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
                    Tables\Actions\ViewAction::make(),
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
            MemberDetailsRelationManager::class,
            AvaialblePlansRelationManager::class,
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

    public static function getEcomDetailsSchema($record): array
    {
        $fieldsArray = [];
        $ecom = app(HealthQuoteService::class)->getEcomDetails($record);

        // dd($ecom);

        $fieldsArray = [
            Forms\Components\Placeholder::make('Provider Name')->content($ecom['providerName']),
            Forms\Components\Placeholder::make('Network', 'network'),
            Forms\Components\Placeholder::make('Payment Status', 'paymentStatus'),
            Forms\Components\Placeholder::make('Paid At', 'paidAt'),
            Forms\Components\Placeholder::make('Plan Name', 'planName'),
        ];

        return $fieldsArray;
    }

    public static function getPlansSchema($uuid): array
    {
        $listQuotePlans = '';
        $quotePlans = app(HealthQuoteService::class)->getQuotePlansNew($uuid);
        if (isset($quotePlans->message) && $quotePlans->message != '') {
            $listQuotePlans = $quotePlans->message;
        } else {
            if (gettype($quotePlans) != 'string') {
                $listQuotePlans = $quotePlans->quote->plans;
            } else {
                $listQuotePlans = $quotePlans;
            }
        }

        $fieldsArray = [];

        if (gettype($listQuotePlans) != 'string') {
            foreach ($listQuotePlans as $key => $quotePlan) {
                $fieldsArray = array_merge([
                    Section::make(ucwords($quotePlan->providerName))
                        ->schema([
                            Forms\Components\Placeholder::make('Plan Name')->content(ucwords($quotePlan->name)),
                            Forms\Components\Placeholder::make('Actual Premium With Basmah')->content($quotePlan->actualPremium + $quotePlan->basmah),
                            Forms\Components\Placeholder::make('Premium With Vat And Basmah')->content($quotePlan->actualPremium + $quotePlan->vat + $quotePlan->basmah),
                        ])
                        ->columns(4)
                        ->compact(),

                ], $fieldsArray);
            }
        }

        return $fieldsArray;
    }
}
