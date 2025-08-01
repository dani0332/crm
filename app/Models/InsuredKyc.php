<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class InsuredKyc extends Model implements AuditableContract
{
    use Auditable;

    protected $table = 'insured_kyc';
    protected $guarded = [];

    public function getAuditables()
    {
        return [
            'auditable_type' => self::class,
        ];
    }
}
