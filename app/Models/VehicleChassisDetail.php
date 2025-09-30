<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleChassisDetail extends Model
{
    protected $table = 'vehicle_depreciation';
    protected $fillable = [
        'uuid',
        'quote_type_id',
        'chassis_number',
        'vehicle_make_model',
        'vehicle_trim',
        'transmissions',
        'cylinder',
        'seating_capacity',
        'number_of_doors',
        'cubic_capacity',
        'hp',
        'engine_type',
        'fuel_type',
        'drive_type',
    ];

    public function quoteType(): BelongsTo
    {
        return $this->belongsTo(QuoteType::class, 'quote_type_id');
    }
}
