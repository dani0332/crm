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

    public function nationality()
    {
        return $this->belongsTo(Nationality::class);
    }

    public function insuredKyc()
    {
        return $this->hasOne(InsuredKyc::class, 'insured_id', 'id');
    }

    public function scopeEmiratesIdNumber($query, $idNumber)
    {
        return $query->where(function ($q) use ($idNumber) {
            $q->where('id_number', formatEmiratesIdNumber($idNumber))
                ->orWhere('id_number', str_replace('-', '', $idNumber));
        });
    }

    public function entity()
    {
        return $this->hasOne(Entity::class, 'id', 'entity_id');
    }
}
