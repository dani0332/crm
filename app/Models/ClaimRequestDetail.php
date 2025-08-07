<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FilterTypes;
use App\Traits\FilterCriteria;
use App\Traits\QuoteModelTrait;
use Config;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
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
 * @property string|null $request_referrence_number
 * @property string|null $user_ip
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ClaimRequestDetail extends Model implements AuditableContract
{
    use Auditable, FilterCriteria, HasFactory /* , SoftDeletes */;

    protected $table = 'claim_request_details';
    protected $fillable = [
        'claim_request_id',
        'claim_uuid',
        'car_make',
        'car_model',
        'service_type_id',
        'request_referrence_number',
        'user_ip',
    ];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Filterable fields for search functionality
    public $filterables = [
        'claim_request_id' => FilterTypes::EXACT,
        'claim_uuid' => FilterTypes::EXACT,
        'car_make' => FilterTypes::EXACT,
        'car_model' => FilterTypes::EXACT,
        'service_type_id' => FilterTypes::IN,
        'request_referrence_number' => FilterTypes::EXACT,
        'user_ip' => FilterTypes::EXACT,
        'created_at' => FilterTypes::DATE_BETWEEN,
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($claimRequestDetail) {
            // Generate claim UUID if not provided
            if (empty($claimRequestDetail->claim_uuid)) {
                $claimRequestDetail->claim_uuid = (string) Str::uuid();
            }

            // Generate reference number if not provided
            if (empty($claimRequestDetail->request_referrence_number)) {
                $claimRequestDetail->request_referrence_number = self::generateReferenceNumber();
            }

            // Set user IP if available
            if (empty($claimRequestDetail->user_ip) && request()) {
                $claimRequestDetail->user_ip = request()->ip();
            }
        });
    }

    /**
     * Generate a unique reference number for the claim request detail
     * Format: REF-<10 digit unique number>
     */
    private static function generateReferenceNumber(): string
    {
        $prefix = 'REF-';

        do {
            $randomNumber = str_pad((string) mt_rand(1, 9999999999), 10, '0', STR_PAD_LEFT);
            $referenceNumber = $prefix.$randomNumber;
        } while (self::where('request_referrence_number', $referenceNumber)->exists());

        return $referenceNumber;
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

    public function getCreatedAtAttribute($table)
    {
        $date_time_format = Config::get('constants.datetime_format');

        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }

    public function getUpdatedAtAttribute($table)
    {
        $date_time_format = Config::get('constants.datetime_format');

        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }
}
