<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OCRResponseData extends Model
{
    protected $table = 'ocr_response_data';
    protected $fillable = [
        'quoteable_id',
        'quoteable_type',
        'ocr_response',
        'ocr_data',
    ];
}
