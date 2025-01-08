<?php

namespace App\Models\ProcessTracker;

use App\Enums\ProcessTracker\ProcessTrackerModelEnum;
use App\Enums\ProcessTracker\ProcessTrackerTypeEnum;
use App\Enums\QuoteTypes;
use App\Models\BaseMongoModel;

class Tracker extends BaseMongoModel
{
    protected $fillable = [
        'quote_uuid',
        'quote_type',
        'process_model',
    ];
    protected $casts = [
        'quote_type' => QuoteTypes::class,
        'process_model' => ProcessTrackerModelEnum::class,
    ];

    public function processes()
    {
        return $this->hasMany(TrackerProcess::class);
    }

    public function startProcess(ProcessTrackerTypeEnum $processType)
    {
        return $this->processes()->firstOrCreate([
            'type' => $processType,
        ]);
    }

    // helper methods
    public static function initQuoteModel(QuoteTypes $quoteType, string $uuid): self
    {
        return self::firstOrCreate([
            'quote_type' => $quoteType,
            'quote_uuid' => $uuid,
            'process_model' => ProcessTrackerModelEnum::QUOTE,
        ]);
    }
}
