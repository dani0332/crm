<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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
