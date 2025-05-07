<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Insured extends Model implements AuditableContract
{
    use Auditable;

    protected $table = 'insured';
    protected $guarded = [];
    protected $appends = ['pcp_tag_formatted'];

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

    public function pcpTagFormatted(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->attributes['pcp_tag'] === true || $this->attributes['pcp_tag'] === 1 ? 'Yes' : ($this->attributes['pcp_tag'] === false || $this->attributes['pcp_tag'] === 0 ? 'Ex - PC' : 'No');
            }
        );
    }

    public static function formattedPcpTagCase(): string
    {
        return "
            CASE 
                WHEN insured.pcp_tag = 1 THEN 'Yes'
                WHEN insured.pcp_tag = 0 THEN 'Ex-PC'
                ELSE 'No'
            END
        ";
    }
}
