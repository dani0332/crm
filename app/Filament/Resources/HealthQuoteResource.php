<?php

namespace App\Filament\Resources;

use App\Enums\QuoteStatusEnum;
use App\Filament\Resources\HealthQuoteResource\Pages;
use App\Models\HealthQuote;
use App\Models\QuoteStatus;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Layout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class HealthQuoteResource extends Resource
{
    protected static ?string $model = HealthQuote::class;
    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static ?string $navigationGroup = 'Personal Quotes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('marital_status_id'),
                Forms\Components\TextInput::make('cover_for_id'),
                Forms\Components\TextInput::make('emirate_of_your_visa_id'),
                Forms\Components\TextInput::make('customer_id'),
                Forms\Components\TextInput::make('nationality_id'),
                Forms\Components\TextInput::make('payment_status_id'),
                Forms\Components\TextInput::make('quote_status_id'),
                Forms\Components\TextInput::make('advisor_id'),
                Forms\Components\TextInput::make('pa_id'),
                Forms\Components\TextInput::make('wcu_id'),
                Forms\Components\TextInput::make('lead_type_id'),
                Forms\Components\TextInput::make('salary_band_id'),
                Forms\Components\TextInput::make('member_category_id'),
                Forms\Components\TextInput::make('plan_id'),
                Forms\Components\TextInput::make('primary_member_id'),
                Forms\Components\TextInput::make('currently_insured_with_id'),
                Forms\Components\TextInput::make('previous_advisor_id'),
                Forms\Components\TextInput::make('sponsor_category_id'),
                Forms\Components\TextInput::make('health_plan_type_id'),
                Forms\Components\TextInput::make('preference')
                    ->maxLength(1000),
                Forms\Components\TextInput::make('details')
                    ->maxLength(1000),
                Forms\Components\Toggle::make('has_dental'),
                Forms\Components\Toggle::make('has_worldwide_cover'),
                Forms\Components\Toggle::make('has_home'),
                Forms\Components\TextInput::make('first_name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('last_name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->maxLength(150),
                Forms\Components\TextInput::make('mobile_no')
                    ->maxLength(20),
                Forms\Components\TextInput::make('gender')
                    ->maxLength(8),
                Forms\Components\TextInput::make('lang')
                    ->maxLength(2),
                Forms\Components\TextInput::make('source')
                    ->maxLength(2000),
                Forms\Components\DateTimePicker::make('dob'),
                Forms\Components\Toggle::make('is_synced'),
                Forms\Components\TextInput::make('device')
                    ->maxLength(500),
                Forms\Components\TextInput::make('reference_url')
                    ->maxLength(2000),
                Forms\Components\TextInput::make('additional_notes')
                    ->maxLength(1000),
                Forms\Components\TextInput::make('reviver_name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('promo_code')
                    ->maxLength(20),
                Forms\Components\TextInput::make('code')
                    ->maxLength(30),
                Forms\Components\TextInput::make('uuid')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('policy_number')
                    ->maxLength(100),
                Forms\Components\TextInput::make('previous_quote_id'),
                Forms\Components\TextInput::make('health_team_type')
                    ->maxLength(200),
                Forms\Components\TextInput::make('premium'),
                Forms\Components\Toggle::make('is_ebp_renewal')
                    ->required(),
                Forms\Components\TextInput::make('parent_duplicate_quote_id')
                    ->maxLength(30),
                Forms\Components\TextInput::make('renewal_batch')
                    ->maxLength(50),
                Forms\Components\DateTimePicker::make('renewal_expiry_date'),
                Forms\Components\TextInput::make('previous_quote_policy_number')
                    ->maxLength(100),
                Forms\Components\TextInput::make('other_email_addresses')
                    ->email()
                    ->maxLength(355),
                Forms\Components\TextInput::make('renewal_import_code')
                    ->maxLength(20),
                Forms\Components\DatePicker::make('previous_policy_expiry_date'),
                Forms\Components\TextInput::make('previous_quote_policy_premium'),
                Forms\Components\Toggle::make('is_ecommerce'),
                Forms\Components\DateTimePicker::make('quote_updated_at'),
                Forms\Components\TextInput::make('order_reference')
                    ->maxLength(255),
                Forms\Components\TextInput::make('payment_reference')
                    ->maxLength(255),
                Forms\Components\TextInput::make('payment_hash_code')
                    ->maxLength(255),
                Forms\Components\TextInput::make('payment_gateway')
                    ->maxLength(15),
                Forms\Components\DateTimePicker::make('payment_status_date'),
                Forms\Components\DateTimePicker::make('quote_status_date'),
                Forms\Components\DateTimePicker::make('policy_start_date'),
                Forms\Components\DateTimePicker::make('policy_issuance_date'),
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
                            Forms\Components\TextInput::make('code')->label('CDB ID'),
                        ])
                        ->query(function (Builder $query, array $data): Builder {
                            return $query
                                ->when(
                                    $data['code'],
                                    fn (Builder $query, $value): Builder => $query->where('code', $value),
                                );
                        }),
                    Filter::make('created_start')
                        ->form([
                            Forms\Components\DatePicker::make('created_start'),
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
                            Forms\Components\DatePicker::make('created_end'),
                        ])
                        ->query(function (Builder $query, array $data): Builder {
                            return $query
                                ->when(
                                    $data['created_end'],
                                    fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                                );
                        }),
                    SelectFilter::make('lead_status')
                        ->placeholder('All')
                        ->multiple()
                        ->options(
                            function () {
                                // could be more discerning here, and select a distinct list of aircraft id's
                                // that actually appear in the Daily Logs, so we aren't presenting filter options
                                // which don't exist in the table, but in my case we know they are all used
                                return QuoteStatus::
                                whereNotIn('id', [
                                    QuoteStatusEnum::AMLScreeningCleared, QuoteStatusEnum::Draft, QuoteStatusEnum::Cancelled, QuoteStatusEnum::AMLScreeningFailed,
                                    QuoteStatusEnum::TransactionDeclined, QuoteStatusEnum::PolicyInvoiced, QuoteStatusEnum::Issued, QuoteStatusEnum::PriceTooHigh, QuoteStatusEnum::PolicyPurchasedBeforeFirstCall, QuoteStatusEnum::NotContactablePe, QuoteStatusEnum::FollowupCall, QuoteStatusEnum::Interested, QuoteStatusEnum::NoAnswer, QuoteStatusEnum::NotInterested, QuoteStatusEnum::NotEligibleForInsurance, QuoteStatusEnum::AfiaRenewal, QuoteStatusEnum::NotLookingForMotorInsurance, QuoteStatusEnum::NonGccSpec,
                                ])
                                ->where('is_active', true)
                                ->orderBy('sort_order', 'asc')
                                ->pluck('text', 'id')->toArray();
                            }
                        )
                        ->query(function (Builder $query, array $data) {
                            if (! empty($data['value'])) {
                                $query->whereIn('quote_status_id', $data['value']);
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHealthQuotes::route('/'),
            'create' => Pages\CreateHealthQuote::route('/create'),
            'view' => Pages\ViewHealthQuote::route('/{record}'),
            'edit' => Pages\EditHealthQuote::route('/{record}/edit'),
        ];
    }
}
