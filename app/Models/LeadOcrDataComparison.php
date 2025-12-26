<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadOcrDataComparison extends Model
{
    protected $table = 'lead_ocr_data_comparison';
    protected $fillable = [
        'quoteable_id',
        'quoteable_type',
        'uuid',
        'lead_data',
        'ocr_data',
        'ocr_responses',
        'compairson_data',
        'comparison_score',
        'timestamp'
    ];
}
