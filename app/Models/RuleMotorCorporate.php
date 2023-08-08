<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RuleMotorCorporate extends Model
{
    use HasFactory;

    /**
     * attributes those are mass assignable
     *
     * @var array
     */
    protected $fillable = [
        'rule_id',
        'car_make_id',
        'car_model_id',
        'user_id',
        'created_at',
        'updated_at'
    ];


    /**
     * RELATIONS
     */


    /**
     * get the parent rule function
     *
     * @return BelongsTo
     */
    public function rule():BelongsTo
    {
        return $this->belongsTo(
            Rule::class,
            'rule_id',
            'id',
            'rule'
        );
    }
}

