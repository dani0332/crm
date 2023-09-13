<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RuleDetail extends Model
{
    use HasFactory;

    /**
     * attributes those are mass assignable
     *
     * @var array
     */
    protected $fillable = [
        'car_make_id',
        'car_model_id',
        'lead_source_id',
    ];

    /**
     * Util functions
     */
    public static function getFillables()
    {
        return (new static())->fillable;
    }
}
