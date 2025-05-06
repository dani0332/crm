<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Insured extends Model implements AuditableContract
{
    use Auditable;

    protected $table = 'insured';
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'pcp_tag' => 'boolean',
    ];

    public function getAuditables()
    {
        return [
            'pcp_tag',
        ];
    }

    public function nationality()
    {
        return $this->belongsTo(Nationality::class);
    }

    public function getPcpTagAttribute()
    {
        return $this->attributes['pcp_tag'] === true || $this->attributes['pcp_tag'] === 1 ? 'Yes' :
               ($this->attributes['pcp_tag'] === false || $this->attributes['pcp_tag'] === 0 ? 'Ex-PC' : 'No');
    }
}
