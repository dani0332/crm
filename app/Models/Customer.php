<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Customer extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $table = 'customer';
    protected $guarded = [];

    /**
     * customer detail relation
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getAuditables()
    {
        return [
            'auditable_type' => self::class,
            'relations' => [
                ['auditable_type' => Insured::class, 'key' => 'customer_insured_customer_id'],
            ],
        ];
    }
    public function detail()
    {
        return $this->hasOne(CustomerDetail::class);
    }

    public function nationality()
    {
        return $this->hasOne(Nationality::class, 'id', 'nationality_id');
    }

    public function MyAlfredUsers()
    {
        return $this->hasOne(MyAlFredUser::class);
    }

    public function carQuotes()
    {
        return $this->hasMany(CarQuote::class, 'customer_id', 'id');
    }

    public function bikeQuotes()
    {
        return $this->hasMany(BikeQuote::class, 'customer_id', 'id');
    }

    public function businessQuotes()
    {
        return $this->hasMany(BusinessQuote::class, 'customer_id', 'id');
    }

    public function travelQuotes()
    {
        return $this->hasMany(TravelQuote::class, 'customer_id', 'id');
    }

    public function lifeQuotes()
    {
        return $this->hasMany(LifeQuote::class, 'customer_id', 'id');
    }

    public function homeQuotes()
    {
        return $this->hasMany(HomeQuote::class, 'customer_id', 'id');
    }

    public function healthQuotes()
    {
        return $this->hasMany(HealthQuote::class, 'customer_id', 'id');
    }

    public function getCreatedAtAttribute($date)
    {
        return $this->asDateTime($date)->format(config('constants.DATETIME_DISPLAY_FORMAT'));
    }

    public function getUpdatedAtAttribute($date)
    {
        return $this->asDateTime($date)->format(config('constants.DATETIME_DISPLAY_FORMAT'));
    }

    public function additionalContactInfo()
    {
        return $this->hasMany(CustomerAdditionalContact::class, 'customer_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(self::class, 'id', 'customer_id');
    }

    public function customerDetail()
    {
        return $this->hasOne(CustomerDetail::class, 'customer_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function additionalContacts()
    {
        return $this->hasMany(CustomerAdditionalContact::class, 'customer_id', 'id');
    }

    public function insured(): HasOneThrough
    {
        return $this->hasOneThrough(
            Insured::class,
            CustomerInsured::class,
            'customer_id', // Foreign key on CustomerInsured table
            'id', // Foreign key on Insured table
            'id', // Local key on Customer table
            'insured_id' // Local key on CustomerInsured table
        );
    }

    /**
     * Get all customer insured records associated with this customer.
     *
     * @todo Review this relationship after customer insured process is updated
     */
    public function customerInsured(): HasMany
    {
        return $this->hasMany(CustomerInsured::class, 'customer_id', 'id');
    }
}
