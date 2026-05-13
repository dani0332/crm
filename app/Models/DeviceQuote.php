<?php

namespace App\Models;

use App\Enums\PolicyIssuanceEnum;
use App\Traits\Filterable;
use App\Traits\FilterCriteria;
use App\Traits\QuoteModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class DeviceQuote extends Model implements AuditableContract
{
    use Auditable, Filterable, FilterCriteria, HasFactory, QuoteModelTrait;

    protected $table = 'device_quote_request';
    protected $guarded = [
        // empty because there is no risky fields
    ];
    protected $appends = [
        'insurer_api_status',
        'api_issuance_status',
    ];

    public function getApiIssuanceStatusAttribute(): ?string
    {
        return $this->api_issuance_status_id ? PolicyIssuanceEnum::getAPIIssuanceStatuses($this->api_issuance_status_id) : null;
    }

    public function getInsurerApiStatusAttribute(): ?string
    {
        return $this->insurer_api_status_id ? PolicyIssuanceEnum::getInsurerAPIStatuses($this->insurer_api_status_id) : null;
    }

    public function personalQuote(): BelongsTo
    {
        return $this->belongsTo(PersonalQuote::class, 'personal_quote_id');
    }

    public function deviceMake(): BelongsTo
    {
        return $this->belongsTo(DeviceMake::class, 'make_id');
    }
    public function deviceModel(): BelongsTo
    {
        return $this->belongsTo(DeviceModel::class, 'model_id');
    }
    public function getAuditables()
    {
        return [
            'auditable_type' => PersonalQuote::class,
            'relations' => [
                ['auditable_type' => PersonalQuoteDetail::class, 'key' => 'personal_quote_id'],
                ['auditable_type' => self::class, 'key' => 'personal_quote_id'],
            ],
        ];
    }

}
