<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FilterTypes;
use App\Traits\FilterCriteria;
use Config;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * Class ClaimRequestDetail
 *
 * Represents detailed information for a claim request
 *
 * @property int $id
 * @property int $claim_request_id
 * @property string $claim_uuid
 * @property string|null $car_make
 * @property string|null $car_model
 * @property int|null $service_type_id
 * @property string|null $request_reference_number
 * @property string|null $user_ip
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ClaimRequestDetail extends Model implements AuditableContract
{
    use Auditable, FilterCriteria, HasFactory;

    protected $table = 'claim_request_details';
    protected $fillable = [
        'claim_request_id',
        'claim_uuid',
        'car_make',
        'car_model',
        'model_year',
        'plate_number',
        'service_type_id',
        'request_reference_number',
        'user_ip',
    ];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Filterable fields for search functionality
    public $filterables = [
        'claim_request_id' => FilterTypes::EXACT,
        'car_make' => FilterTypes::EXACT,
        'car_model' => FilterTypes::EXACT,
        'model_year' => FilterTypes::EXACT,
        'plate_number' => FilterTypes::EXACT,
        'service_type_id' => FilterTypes::IN,
        'request_reference_number' => FilterTypes::EXACT,
        'created_at' => FilterTypes::DATE_BETWEEN,
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($claimRequestDetail) {
            // Set user IP if available
            if (empty($claimRequestDetail->user_ip) && request()) {
                $claimRequestDetail->user_ip = request()->ip();
            }
        });
    }

    /**
     * Relationships
     */
    public function claimRequest(): BelongsTo
    {
        return $this->belongsTo(ClaimRequest::class, 'claim_request_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(Lookup::class, 'service_type_id');
    }

    /**
     * Scopes
     */
    public function scopeByClaimRequest($query, $claimRequestId)
    {
        return $query->where('claim_request_id', $claimRequestId);
    }

    public function scopeByServiceType($query, $serviceTypeId)
    {
        return $query->where('service_type_id', $serviceTypeId);
    }

    public function scopeByCarMake($query, $carMake)
    {
        return $query->where('car_make', $carMake);
    }

    public function scopeByCarModel($query, $carModel)
    {
        return $query->where('car_model', $carModel);
    }

    /**
     * Accessors
     */
    public function getVehicleInfoAttribute(): string
    {
        $parts = array_filter([$this->car_make, $this->car_model]);

        return implode(' ', $parts);
    }

    public function getHasVehicleInfoAttribute(): bool
    {
        return ! empty($this->car_make) || ! empty($this->car_model);
    }

    /**
     * Business Logic Methods
     */
    public function updateVehicleInfo(string $carMake, string $carModel): void
    {
        $this->car_make = $carMake;
        $this->car_model = $carModel;
        $this->save();
    }

    public function updateServiceType(int $serviceTypeId): void
    {
        $this->service_type_id = $serviceTypeId;
        $this->save();
    }

    public function getDisplayCreatedAtAttribute($value)
    {
        $date_time_format = Config::get('constants.datetime_format');

        return $this->asDateTime($value)->timezone(config('app.timezone'))->format($date_time_format);
    }

    public function getDisplayUpdatedAtAttribute($value)
    {
        $date_time_format = Config::get('constants.datetime_format');

        return $this->asDateTime($value)->timezone(config('app.timezone'))->format($date_time_format);
    }
}
