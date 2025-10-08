<?php

namespace App\Models\Allocation;

use App\Enums\QuoteTypes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class AllocationConfiguration extends Model implements AuditableContract
{
    use Auditable;

    protected $appends = ['lumpsum_brackets', 'regular_brackets', 'value_brackets', 'volume_brackets', 'type1_brackets', 'type2_brackets', 'type3_brackets', 'type4_brackets', 'bracket1'];
    protected $fillable = [
        'quote_type_id',
        'quote_type',
        'config',
        'created_by',
        'updated_by',
    ];
    protected $casts = [
        'quote_type' => QuoteTypes::class,
        'config' => 'array',
    ];

    public function regularBrackets(): Attribute
    {
        return new Attribute(
            get: fn () => $this->config['regular_brackets'] ?? [],
        );
    }

    public function lumpsumBrackets(): Attribute
    {
        return new Attribute(
            get: fn () => $this->config['lumpsum_brackets'] ?? [],
        );
    }

    public function valueBrackets(): Attribute
    {
        return new Attribute(
            get: fn () => $this->config['value_brackets'] ?? [],
        );
    }

    public function volumeBrackets(): Attribute
    {
        return new Attribute(
            get: fn () => $this->config['volume_brackets'] ?? [],
        );
    }

    public function type1Brackets(): Attribute
    {
        return new Attribute(
            get: fn () => $this->config['type1_brackets'] ?? [],
        );
    }

    public function type2Brackets(): Attribute
    {
        return new Attribute(
            get: fn () => $this->config['type2_brackets'] ?? [],
        );
    }

    public function type3Brackets(): Attribute
    {
        return new Attribute(
            get: fn () => $this->config['type3_brackets'] ?? [],
        );
    }

    public function type4Brackets(): Attribute
    {
        return new Attribute(
            get: fn () => $this->config['type4_brackets'] ?? [],
        );
    }

    public function bracket1(): Attribute
    {
        return new Attribute(
            get: fn () => $this->config['bracket1'] ?? ['profiles' => []],
        );
    }
}

