<?php

namespace App\Models;

use App\Traits\SpatieActivityLog;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Customer extends Model implements AuditableContract
{
    use Auditable, HasFactory, SpatieActivityLog;

    protected $table = 'customer';
    protected $guarded = ['ref_id'];
    protected $appends = ['pcp_tag_formatted'];
    public $ref_id;
    protected $casts = [
        'pcp_tag' => 'boolean',
    ];
    /**
     * customer detail relation
     *
     * @return HasOne
     */
    public function getAuditables()
    {
        return [
            'auditable_type' => self::class,
        ];
    }

    public function transformAudit(array $data): array
    {
        if (isset($this->ref_id)) {
            $data['new_values']['ref_id'] = $this->ref_id;
        }

        return $data;
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
     * @return HasMany
     */
    public function additionalContacts()
    {
        return $this->hasMany(CustomerAdditionalContact::class, 'customer_id', 'id');
    }

    public function insureds()
    {
        return $this->hasManyThrough(
            Insured::class,
            CustomerInsured::class,
            'customer_id', // customer_insured.customer_id
            'id', // insured.id
            'id', // customer.id (customer_insured.customer_id)
            'insured_id' // customer_insured.insured_id
        )->where('customer_insured.is_active', true);
    }

    public function pcpTagFormatted(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->attributes['pcp_tag'] === true || $this->attributes['pcp_tag'] === 1 ? 'Yes' : ($this->attributes['pcp_tag'] === false || $this->attributes['pcp_tag'] === 0 ? 'Ex - PC' : 'No');
            }
        );
    }

    public static function formattedPcpTagCase($tableAlias = 'c'): string
    {
        return '
            CASE
                WHEN '.$tableAlias.".pcp_tag = 1 THEN 'Yes'
                WHEN ".$tableAlias.".pcp_tag = 0 THEN 'Ex-PC'
                ELSE 'No'
            END
        ";
    }

    public function personalQuote(): HasMany
    {
        return $this->hasMany(PersonalQuote::class, 'customer_id', 'id');
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(CustomerBankAccount::class, 'customer_id', 'id');
    }
}
