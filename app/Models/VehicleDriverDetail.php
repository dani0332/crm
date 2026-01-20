<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class VehicleDriverDetail extends Model
{
    use HasFactory;

    protected $table = 'vehicle_driver_details';
    protected $fillable = [
        'rta_transaction_type',
        'vehicle_plate_code',
        'vehicle_plate_number',
        'traffic_code_number',
        'vehicle_engine_number',
        'rta_plate_category',
        'vehicle_color',
        'vehicle_plate_color',
        'bank_loan',
        'bank_name',
        'first_registration_date',
        'annual_mileage_estimate',
        'is_insured_and_driver_same',
        'driver_first_name',
        'driver_last_name',
        'driver_dob',
        'driver_gender',
        'driver_license_number',
        'driver_license_issue_place',
        'driver_license_issue_date',
        'driver_license_expiry_date',
        'driver_uae_driving_experience',
        'driver_home_country_license_issuance',
        'driver_home_country_driving_experience',
        'nationality_id',
        'driver_eid_number',
    ];
    protected $casts = [
        'driver_dob' => 'date',
        'driver_license_issue_date' => 'date',
        'driver_license_expiry_date' => 'date',
        'uae_driving_experience' => 'integer',
        'nationality_id' => 'integer',
    ];

    public function driverLicenseIssueDate(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Carbon::parse($value)->format('Y-m-d') : null,
        );
    }

    public function driverLicenseExpiryDate(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Carbon::parse($value)->format('Y-m-d') : null,
        );
    }

    public function driverDob(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Carbon::parse($value)->format('Y-m-d') : null,
        );
    }

    public function nationality(): BelongsTo
    {
        return $this->belongsTo(Nationality::class);
    }

    public function quoteable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForQuotable($query, $quotableType, $quotableId)
    {
        return $query->where('quoteable_type', $quotableType)
            ->where('quoteable_id', $quotableId);
    }

    protected function driverEidNumber(): Attribute
    {
        return Attribute::make(
            get: function (?string $value): ?string {
                if (! $value) {
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
                if (! $value) {
                    return $value;
                }

                return str_replace('-', '', $value);
            },
        );
    }

    /**
     * Normalize Driver Emirates ID in the given attributes array
     * Removes hyphens from driver_eid_number
     */
    protected static function normalizeDriverEidNumber(array $attributes): array
    {
        if (
            isset($attributes['driver_eid_number'])
            && is_string($attributes['driver_eid_number'])
        ) {
            $attributes['driver_eid_number'] = str_replace('-', '', $attributes['driver_eid_number']);
        }

        return $attributes;
    }

    /**
     * Normalize Driver Emirates ID before creating a new model instance
     */
    public static function create(array $attributes = [])
    {
        return static::query()->create(static::normalizeDriverEidNumber($attributes));
    }

    /**
     * Create or update a record matching the attributes, and fill it with values.
     * Automatically normalizes Driver Emirates ID if applicable.
     */
    public static function updateOrCreate(array $attributes, array $values = [])
    {
        return static::query()->updateOrCreate(
            static::normalizeDriverEidNumber($attributes),
            static::normalizeDriverEidNumber($values)
        );
    }
}
