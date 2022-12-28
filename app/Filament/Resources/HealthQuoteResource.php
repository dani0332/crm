<?php

namespace App\Filament\Resources;

use App\Enums\GenericRequestEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\RolesEnum;
use App\Filament\Resources\HealthQuoteResource\Pages;
use App\Models\HealthQuote;
use App\Models\QuoteStatus;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Layout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class HealthQuoteResource extends Resource
{
    protected static ?string $model = HealthQuote::class;
    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static ?string $navigationGroup = 'Personal Quotes';
    protected static ?string $recordRouteKeyName = 'uuid';
    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('Lead Details')
                    ->schema([
                        Forms\Components\TextInput::make('code')->label('CDB ID'),
                        Forms\Components\DateTimePicker::make('created_at')->label('Created Date'),
                        Forms\Components\Select::make('health_team_type')
                            ->label('Sub Team')
                            ->options([
                                'All',
                                'RM-NB',
                                'RM-Speed',
                                'EBP',
                                'Wow-Call',
                                'No-Type',
                            ]),
                        Forms\Components\Select::make('advisor_id')
                            ->label('Advisor')
                            ->options(function () {
                                return  DB::table('users as u')->select('u.id', DB::raw("CONCAT(u.name,' - ',r.name) AS name"))
                                    ->join('model_has_roles as mhr', 'mhr.model_id', '=', 'u.id')
                                    ->join('roles as r', 'mhr.role_id', '=', 'r.id')
                                    ->whereIn('r.name', ['RM_ADVISOR', 'EBP_ADVISOR', 'HEALTH_WCU_ADVISOR'])->get()->pluck('name', 'id');
                            }),
                        Forms\Components\TextInput::make('source'),
                        Forms\Components\DateTimePicker::make('updated_at')->label('Last Modified Date'),
                        Forms\Components\TextInput::make('parent_duplicate_quote_id')->label('Parent CDB ID'),
                        Forms\Components\TextInput::make('renewal_batch')->label('Renewal Batch'),
                        Forms\Components\Toggle::make('is_ecommerce')
                            ->label('Is Ecommerce'),
                        Forms\Components\Toggle::make('is_ebp_renewal')
                            ->label('Is EBP Renewal'),
                        Forms\Components\TextInput::make('device'),
                    ])
                    ->hiddenOn('create'),
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
                        Forms\Components\TextInput::make('currently_insured_with_id')->label('Currently Insured With'),
                        Forms\Components\Select::make('health_plan_type')
                            ->label('Type of Plan')
                            ->options(function () {
                                return DB::table('health_plan_type')->where('is_active', true)->pluck('text', 'id');
                            }),
                        Forms\Components\DatePicker::make('next_followup_date')->label('Next Followup Date'),
                        Forms\Components\Textarea::make('details')
                            ->maxLength(1000)
                            ->rows(2),
                    ])
                    ->hiddenOn('create'),
                Fieldset::make("Last Year's Policy Details")
                    ->schema([
                        Forms\Components\TextInput::make('previous_quote_policy_number')
                            ->maxLength(100),
                        Forms\Components\DatePicker::make('previous_policy_expiry_date'),
                        Forms\Components\TextInput::make('previous_quote_policy_premium'),
                    ])
                    ->hiddenOn('create'),
                Fieldset::make('Policy Details')
                    ->schema([
                        Forms\Components\TextInput::make('policy_number')
                            ->maxLength(100),
                        Forms\Components\DatePicker::make('policy_start_date'),
                        Forms\Components\DatePicker::make('policy_end_date'),
                        Forms\Components\Placeholder::make('transapp_code'),
                    ])
                    ->hiddenOn('create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('CDB ID'),
                Tables\Columns\TextColumn::make('first_name')->label('First Name'),
                Tables\Columns\TextColumn::make('last_name')->label('Last Name'),
                Tables\Columns\TextColumn::make('quoteStatus.text')->label('Lead Status'),
                Tables\Columns\TextColumn::make('advisor.name')->label('Advisor'),
                Tables\Columns\TextColumn::make('created_at')->label('Created Date')->dateTime(),
                Tables\Columns\TextColumn::make('updated_at')->label('Last Modified Date')->dateTime(),
                Tables\Columns\TextColumn::make('health_plan_type_id'),
                Tables\Columns\TextColumn::make('premium'),
                Tables\Columns\TextColumn::make('policy_number'),
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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                ExportBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
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
