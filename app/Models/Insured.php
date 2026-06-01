<?php

namespace App\Models;

use App\Traits\IdNumberFormatting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Insured extends Model implements AuditableContract
{
    use Auditable, HasFactory;
    use IdNumberFormatting;

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

    /**
     * Normalize Emirates ID before creating a new model instance
     */
    public static function create(array $attributes = [])
    {
        return static::query()->create(static::normalizeEmiratesId($attributes));
    }

    /**
     * Create or update a record matching the attributes, and fill it with values.
     * Automatically normalizes Emirates ID if applicable.
     */
    public static function updateOrCreate(array $attributes, array $values = [])
    {
        return static::query()->updateOrCreate(
            static::normalizeEmiratesId($attributes),
            static::normalizeEmiratesId($values)
        );
    }
}
