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

    /**
     * Accessor for id_number: formats as ###-####-#######-# when id_type is emiratesId
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function idNumber(): Attribute
    {
        return Attribute::make(
            get: function (?string $value): ?string {
                if (!$value) {
                    return $value;
                }
                
                // Only format if id_type is emiratesId
                if ($this->attributes['id_type'] !== 'emiratesId') {
                    return $value;
                }
                
                // Remove any existing hyphens
                $clean = str_replace('-', '', $value);
                
                // Format as ###-####-#######-#
                if (strlen($clean) === 15) {
                    return formatEmiratesIdNumber($clean);
                }
                
                return $value;
            },
            set: function (?string $value): ?string {
                if (!$value) {
                    return $value;
                }
                
                // Only remove hyphens before saving if id_type is emiratesId
                if ($this->attributes['id_type'] === 'emiratesId') {
                    return str_replace('-', '', $value);
                }
                
                return $value;
            }
        );
    }

    
}
