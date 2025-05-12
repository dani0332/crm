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
    protected $appends = ['pcp_tag_formatted'];

    public function nationality()
    {
        return $this->belongsTo(Nationality::class);
    }
}
