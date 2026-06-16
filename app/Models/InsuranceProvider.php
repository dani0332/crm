<?php

namespace App\Models;

use App\Traits\UsesTestConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class InsuranceProvider extends BaseModel implements AuditableContract
{
    use Auditable, HasFactory, UsesTestConnection;

    protected $connection = 'mysql';
    protected $table = 'insurance_provider';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'aml_lookups_enabled' => 'boolean',
        ];
    }

    public $access = [

        'write' => ['advisor', 'oe'],
        'update' => ['advisor', 'oe'],
        'delete' => ['advisor', 'oe'],
        'access' => [
            'pa' => ['code', 'text'],
            'advisor' => ['code', 'text'],
            'oe' => ['code', 'text'],
            'admin' => ['code', 'text'],
            'invoicing' => ['code', 'text'],
        ],
        'list' => [
            'pa' => ['id', 'code', 'text', 'insurance_company_id'],
            'advisor' => ['id', 'code', 'text', 'insurance_company_id'],
            'oe' => ['id', 'code', 'text', 'insurance_company_id'],
            'admin' => ['id', 'code', 'text', 'insurance_company_id'],
            'invoicing' => ['code', 'text', 'insurance_company_id'],
        ],
    ];

    public function relations()
    {
        return [];
    }

    public function scopeWithActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function getCreatedAtAttribute($table)
    {
        $date_time_format = config('constants.datetime_format');

        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }

    public function getUpdatedAtAttribute($table)
    {
        $date_time_format = config('constants.datetime_format');

        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }

    public function quoteTypes()
    {
        return $this->belongsToMany(QuoteType::class, 'insurance_provider_quote_type');
    }

    public function isProvider($code)
    {
        return $this->code === $code;
    }

    /**
     * Transitions where this provider is the source (lead insurer).
     *
     * @return HasMany
     */
    public function transitionsAsSource()
    {
        return $this->hasMany(InsuranceProviderTransition::class, 'source_insurance_provider_id');
    }

    /**
     * Allowed target providers this source can transition to.
     *
     * @return BelongsToMany
     */
    public function allowedTransitionTargets()
    {
        return $this->belongsToMany(
            InsuranceProvider::class,
            'renewal_insurance_provider_transitions',
            'source_insurance_provider_id',
            'target_insurance_provider_id'
        );
    }

    public function genericDocuments()
    {
        return $this->morphMany(GenericDocument::class, 'documentable');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(InsuranceProviderContact::class, 'insurance_provider_id');
    }
}
