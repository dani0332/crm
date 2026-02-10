<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class RuleDetail extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $table = 'rule_details';

    public function rule()
    {
        return $this->belongsTo(Rule::class);
    }

    public function leadSource()
    {
        return $this->belongsTo(LeadSource::class, 'lead_source_id');
    }

    /**
     * attributes those are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'car_make_id',
        'car_model_id',
        'lead_source_id',
        'utm_source',
        'utm_campaign',
        'utm_medium',
    ];

    /**
     * Util functions.
     */
    public static function getFillables()
    {
        return (new static)->fillable;
    }
}
