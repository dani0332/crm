<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    /**
     * RELATIONS
     */

    /**
     * get lead source function
     *
     * @return HasOne
     */
    public function leadSource():HasOne
    {
        return $this->hasOne(
            LeadSource::class,
            'id',
            'lead_source_id',
        );
    }

}
