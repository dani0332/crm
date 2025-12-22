<?php

namespace App\Models;

use App\Traits\IdNumberFormatting;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class InsuredKyc extends Model implements AuditableContract
{
    use Auditable;
    use IdNumberFormatting;

    protected $table = 'insured_kyc';
    protected $guarded = [];

    public function getAuditables()
    {
        return [
            'auditable_type' => self::class,
        ];
    }
}
