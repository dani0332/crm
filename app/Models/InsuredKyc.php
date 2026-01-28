<?php

namespace App\Models;

use App\Traits\IdNumberFormatting;
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
