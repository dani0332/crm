<?php

namespace App\Traits;

use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypeShortCode;
use App\Models\BikeQuote;
use App\Models\BikeQuoteRequestDetail;
use App\Models\BusinessQuote;
use App\Models\BusinessQuoteRequestDetail;
use App\Models\CarQuote;
use App\Models\CarQuoteRequestDetail;
use App\Models\CycleQuote;
use App\Models\HealthQuote;
use App\Models\HealthQuoteRequestDetail;
use App\Models\HomeQuote;
use App\Models\HomeQuoteRequestDetail;
use App\Models\JetskiQuote;
use App\Models\LifeQuote;
use App\Models\LifeQuoteRequestDetail;
use App\Models\PersonalQuote;
use App\Models\PersonalQuoteDetail;
use App\Models\PetQuote;
use App\Models\PetQuoteRequestDetail;
use App\Models\QuoteSync;
use App\Models\TravelQuote;
use App\Models\TravelQuoteRequestDetail;
use App\Models\YachtQuote;
use App\Models\YachtQuoteRequestDetail;
use App\Repositories\PersonalQuoteRepository;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Enums\QuoteSyncStatus;

trait PersonalQuoteSyncTrait
{
    public function syncQuote($quote, $updatedFields)
    {
        $quoteTypeId = $this->getQuoteTypeId($quote::class);
        $uuid = $quote->uuid;

        // add entry to quote sync table if missing entries in personal quotes and quote sync table
        $personalQuote = PersonalQuoteRepository::where('uuid', $uuid)->count();
        $entries = QuoteSync::where('is_synced', false)
            ->where('quote_uuid', $uuid)
            ->where('quote_type_id', $quoteTypeId)
            ->count();
        if ($personalQuote == 0 && $entries == 0) {
            Log::warning("Quote not synced from quote_sync table, uuid: {$uuid}");
            $quoteTypeId = $this->getQuoteTypeId($quote::class);
            $sourceQuote = $this->getQuoteRecord($quoteTypeId, $uuid);

            if ($sourceQuote) {
                $this->addQuoteSyncEntry($uuid, $quoteTypeId, $sourceQuote->getAttributes());

                $sourceQuoteDetails = $this->getQuoteDetailRecord($quoteTypeId, $sourceQuote->id);
                if ($sourceQuoteDetails) {
                    $this->addQuoteSyncEntry($uuid, $quoteTypeId, $sourceQuoteDetails->getAttributes());
                }
            }
        }

        $this->addQuoteSyncEntry($uuid, $quoteTypeId, $updatedFields);
    }

    private function addQuoteSyncEntry($uuid, $quoteTypeId, $updatedFields)
    {
        unset($updatedFields['created_at'], $updatedFields['updated_at']);
        QuoteSync::create([
            'is_synced' => 0,
            'quote_uuid' => $uuid,
            'quote_type_id' => $quoteTypeId,
            'updated_fields' => json_encode($updatedFields),
        ]);
    }

    public function syncTable($quote, $updatedFields, $quoteTable)
    {
        foreach ($updatedFields as $column => $value) {
            if ($column === 'id' || $column === 'currently_insured_with') {
                continue;
            }
            if (Schema::hasColumn($quoteTable, $column)) {
                $columnType = DB::getSchemaBuilder()->getColumnType($quoteTable, $column);
                $value = $this->formatColumnValue($columnType, $value);
                if ($value !== null || $value !== '' || $value !== 'NULL') {
                    $quote->$column = $value;
                }
            }
        }
    }

    private function formatColumnValue($columnType, $value)
    {
        if ($value && in_array($columnType, ['date', 'datetime'])) {
            return Carbon::parse($value)->toDateTimeString();
        }

        return $value;
    }

    private function processExistingQuote($quote, $entry)
    {
        if ($entry->quote_type_id) {

            info('Entry for quote: ' . $entry->quote_uuid . ' found in personal quotes table');
            $newValues = json_decode($entry->updated_fields, true);
            $this->syncTable($quote, $newValues, 'personal_quotes');
            $quote->quote_type_id = $entry->quote_type_id;
            $quote->save();
            QuoteSync::where('id', $entry->id)->update(['is_synced' => true, 'status' => QuoteSyncStatus::COMPLETED, 'synced_at' => now()]);
            info('Entry for quote: ' . $entry->quote_uuid . ' updated in quote sync table');

        } else {

            info('Entry for quote: ' . $entry->quote_uuid . ' found in personal quotes table but missing required fields');
            $sourceQuote = $this->getQuoteRecord($entry->quote_type_id, $entry->quote_uuid);
            if ($sourceQuote) {
                $this->syncTable($quote, $sourceQuote->getAttributes(), 'personal_quotes');
                $quote->quote_type_id = $entry->quote_type_id;
                $quote->save();
                $this->syncTable($quote, json_decode($entry->updated_fields, true), 'personal_quotes');
                $quote->quote_type_id = $entry->quote_type_id;
                $quote->save();
                QuoteSync::where('id', $entry->id)->update(['is_synced' => true, 'status' => QuoteSyncStatus::COMPLETED, 'synced_at' => now()]);
            }
        }

        $this->upsertPersonalQuoteDetail($quote, json_decode($entry->updated_fields, true));
    }

    private function processQuoteNotFound($entry)
    {
        info('Entry for quote: '.$entry->quote_uuid.' not found in personal quotes table');
        $sourceQuote = $this->getQuoteRecord($entry->quote_type_id, $entry->quote_uuid);

        $personalQuote = null;
        if ($sourceQuote) {
            $newValues = json_decode($entry->updated_fields, true);
            $personalQuote = $this->createPersonalQuoteFromSource($sourceQuote, $newValues, $entry->quote_uuid, $entry->quote_type_id);
            $this->upsertPersonalQuoteDetail($personalQuote, $newValues);
            QuoteSync::where('id', $entry->id)->update(['is_synced' => true, 'status' => QuoteSyncStatus::COMPLETED, 'synced_at' => now()]);
            info('Entry for quote: ' . $personalQuote->id . ' saved in personal quotes table');
        }

        return $personalQuote;
    }

    private function getQuoteRecord($quote_type_id, $quote_uuid)
    {
        $modelClassName = $this->getQuoteType($quote_type_id);
        $sourceQuote = $modelClassName::where('uuid', $quote_uuid)->first();

        return $sourceQuote;
    }

    private function getQuoteDetailRecord($quoteTypeId, $sourceId)
    {
        if (in_array($quoteTypeId, [QuoteTypeId::Cycle, QuoteTypeId::Jetski])) {
            return null;
        }

        $modelDetail = $this->getQuoteTypeDetail($quoteTypeId);
        $modelDetailClassName = $modelDetail[0];
        $modelColumnName = $modelDetail[1];
        $sourceQuoteDetails = $modelDetailClassName::where($modelColumnName, $sourceId)->first();

        return $sourceQuoteDetails;
    }

    /**
     * Create a new personal quote from source quote
     *
     * @param  $sourceQuote  - Existing object of car/heath/travel/... quote
     * @param  $entry  - Entry from quote_sync table
     */
    private function createPersonalQuoteFromSource($sourceQuote, $newValues, $quoteUuid, $quoteTypeId)
    {
        $personalQuote = new PersonalQuote();
        $sourceAttributes = $sourceQuote->getAttributes();
        $this->syncTable($personalQuote, $sourceAttributes, 'personal_quotes');
        $personalQuote->quote_type_id = $quoteTypeId;

        // update missing required fields
        $this->updateMissingFields($personalQuote, 'personal_quotes', $quoteTypeId);
        $this->syncTable($personalQuote, $newValues, 'personal_quotes');
        $personalQuote->save();

        return $personalQuote;
    }

    public function getQuoteTypeId($modelClassName)
    {
        $quoteTypeModels = [
            CarQuote::class => 1,
            HomeQuote::class => 2,
            HealthQuote::class => 3,
            LifeQuote::class => 4,
            BusinessQuote::class => 5,
            BikeQuote::class => 6,
            YachtQuote::class => 7,
            TravelQuote::class => 8,
            PetQuote::class => 9,
            CycleQuote::class => 10,
            JetskiQuote::class => 11,
        ];

        // Retrieve the source quote based on quote type and UUID
        $quoteTypeId = $quoteTypeModels[$modelClassName];

        return $quoteTypeId;
    }

    public function getQuoteType($quoteTypeId)
    {
        $quoteTypeModels = [
            1 => CarQuote::class,
            2 => HomeQuote::class,
            3 => HealthQuote::class,
            4 => LifeQuote::class,
            5 => BusinessQuote::class,
            6 => BikeQuote::class,
            7 => YachtQuote::class,
            8 => TravelQuote::class,
            9 => PetQuote::class,
            10 => CycleQuote::class,
            11 => JetskiQuote::class,
        ];

        // Retrieve the source quote based on quote type and UUID
        $modelClassName = $quoteTypeModels[$quoteTypeId];

        return $modelClassName;
    }

    public function getQuoteTypeDetail($quoteTypeId)
    {
        $quoteTypeModels = [
            1 => [CarQuoteRequestDetail::class, 'car_quote_request_id'],
            2 => [HomeQuoteRequestDetail::class, 'home_quote_request_id'],
            3 => [HealthQuoteRequestDetail::class, 'health_quote_request_id'],
            4 => [LifeQuoteRequestDetail::class, 'life_quote_request_id'],
            5 => [BusinessQuoteRequestDetail::class, 'business_quote_request_id'],
            6 => [BikeQuoteRequestDetail::class, 'bike_quote_request_id'],
            7 => [YachtQuoteRequestDetail::class, 'yacht_quote_request_id'],
            8 => [TravelQuoteRequestDetail::class, 'travel_quote_request_id'],
            9 => [PetQuoteRequestDetail::class, 'pet_quote_request_id'],
        ];

        // Retrieve the source quote based on quote type and UUID
        $modelClassName = $quoteTypeModels[$quoteTypeId];

        return $modelClassName;
    }

    private function upsertPersonalQuoteDetail($personalQuote, $newValues)
    {
        $personalQuoteDetail = PersonalQuoteDetail::where('personal_quote_id', $personalQuote->id)->first();
        $isNewInsert = false;
        if (! $personalQuoteDetail) {
            $isNewInsert = true;
            $personalQuoteDetail = new PersonalQuoteDetail();
        }

        $this->syncTable($personalQuoteDetail, $newValues, 'personal_quote_details');

        $personalQuoteDetail->personal_quote_id = $personalQuote->id;

        if ($isNewInsert) {
            // update missing required fields
            $this->updateMissingFields($personalQuoteDetail, 'personal_quote_details', $personalQuote->id);
        }

        $personalQuoteDetail->save();

        return $personalQuoteDetail;
    }

    private function updateMissingFields($quote, $table, $identifier)
    {

        $requiredFields = $this->getRequiredColumns($table);
        if (! empty($requiredFields)) {
            $personalQuoteKeys = $quote->getAttributes();
            foreach ($requiredFields as $column => $detail) {
                if (! array_key_exists($column, $personalQuoteKeys) || empty($personalQuoteKeys[$column])) {

                    if ($column === 'code') {
                        $shortCode = QuoteTypeShortCode::getName($quote->quote_type_id);
                        $value = "{$shortCode}-{$quote->uuid}";
                    } else {
                        $type = $detail['type_name'];
                        $value = $this->generateDefaultValue($type);
                    }

                    $quote->$column = $value;
                    Log::warning("Column not found in source quote, table: {$table} - identifier: {$identifier} - column: {$column}, setting default value: {$value}");
                }
            }
        }
    }

    /**
     * Retrieve required columns for a table without defaults and excluding foreign keys
     *
     * @param  $table  - Table name
     */
    private function getRequiredColumns($table)
    {
        $skipColumns = ['id'];
        $columns = Schema::getColumns($table);
        $foreignKeys = Schema::getForeignKeys($table);
        if (! empty($foreignKeys)) {
            $foreignKeys = collect($foreignKeys)->map(function ($foreignKey) {
                return $foreignKey['columns'];
            })->flatten()->all();
        }

        if (! empty($columns)) {
            $columns = collect($columns)->filter(function ($column) use ($skipColumns, $foreignKeys) {
                if (
                    $column['nullable'] === false &&
                    $column['default'] == null &&
                    ! in_array($column['name'], $skipColumns) &&
                    ! in_array($column['name'], $foreignKeys)
                ) {
                    return true;
                }

                return false;
            })->keyBy('name')->all();
        }

        return $columns;
    }

    private function generateDefaultValue($columnType)
    {
        switch ($columnType) {
            case 'int':
            case 'bigint':
            case 'decimal':
            case 'tinyint':
            case 'smallint':
                return 0;
            case 'varchar':
            case 'text':
                return '';
            case 'boolean':
                return false;
            case 'date':
            case 'datetime':
                return Carbon::now();
            default:
                return null;
        }
    }
}
