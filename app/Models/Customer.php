<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
}
