<?php

namespace App\Models;

use App\Enums\FilterTypes;
use App\Enums\QuoteTypeId;
use App\Events\QuoteEmailUpdated;
use App\Traits\FilterCriteria;
use App\Traits\QuoteModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class HomeQuote extends Model implements AuditableContract
{
    use Auditable, FilterCriteria, HasFactory, QuoteModelTrait;

    protected $table = 'home_quote_request';
    protected $fillable = [];
    protected $guarded = [];
    public $allowedColumns = ['first_name', 'last_name', 'previous_quote_policy_number', 'code', 'email', 'source', 'policy_expiry_date', 'policy_number', 'policy_start_date', 'uuid', 'mobile_no', 'customer_id', 'advisor_id', 'premium', 'insurance_provider_id', 'insly_migrated', 'quote_status_id'];
    public $filterables = [
        'first_name' => FilterTypes::FREE,
        'last_name' => FilterTypes::FREE,
        'previous_quote_policy_number' => FilterTypes::EXACT,
        'code' => FilterTypes::EXACT,
        'email' => FilterTypes::EXACT,
        'source' => FilterTypes::EXACT,
        'policy_expiry_date' => FilterTypes::DATE_BETWEEN,
        'uuid' => FilterTypes::EXACT,
        'mobile_no' => FilterTypes::EXACT,
        'created_at' => FilterTypes::DATE_BETWEEN,
        'quote_status_id' => FilterTypes::IN,
        'advisor_id' => FilterTypes::IN,
    ];
    protected $dispatchesEvents = [
        'updated' => QuoteEmailUpdated::class,
    ];

    protected static function booted()
    {
        static::updating(function ($model) {
            $skipBookingDateUpdateForNonCPD = true;
            if (isset(request()->sendUpdateId)) {
                $homeQuote = new HomeQuote;
                $endorsmentDetails = $homeQuote->isCPDEndorsment(request()->sendUpdateId);
                if ($endorsmentDetails['isCPDEndorsment']) {
                    info('Book Update - Policy Booking Date update is allowed for CPD Endorsment. Old PBD ('.$model->getOriginal('policy_booking_date').') - New PBD ('.$model->policy_booking_date.'). QuoteType: '.request()->quoteType.' - QuoteUUID: '.request()->quoteUuid.' - SendUpdateUUID: '.$endorsmentDetails['sendUpdateUUID']);
                    $skipBookingDateUpdateForNonCPD = false;
                }
            }

            if ($model->isDirty('policy_booking_date') && $model->getOriginal('policy_booking_date') && $skipBookingDateUpdateForNonCPD) {
                info($model->code.' updating the value of policy_booking_date is skipped. tried to change policy_booking_date from '.$model->getOriginal('policy_booking_date').' to '.$model->policy_booking_date);
                unset($model->policy_booking_date); // lock the policy booking date field
            }
        });
    }
    public function quoteStatus()
    {
        return $this->belongsTo(QuoteStatus::class);
    }

    public function paymentStatus()
    {
        return $this->belongsTo(PaymentStatus::class, 'payment_status_id');
    }

    public function homeQuoteRequestDetail()
    {
        return $this->hasOne(HomeQuoteRequestDetail::class, 'home_quote_request_id', 'id');
    }

    public function insuranceProvider()
    {
        return $this->belongsTo(InsuranceProvider::class, 'insurance_provider_id', 'id')->select(['id', 'text', 'code']);
    }

    public function nationality()
    {
        return $this->hasOne(Nationality::class, 'id', 'nationality_id')->select(['id', 'code', 'text']);
    }

    public function accommodationType()
    {
        return $this->belongsTo(HomeAccomodationType::class, 'ilivein_accommodation_type_id');
    }

    public function possessionType()
    {
        return $this->belongsTo(HomePossessionType::class, 'iam_possesion_type_id');
    }

    public function lookupAccommodationType()
    {
        return $this->belongsTo(Lookup::class, 'accommodation_type_id')->where([
            'key' => 'accommodation-type',
        ]);

    }

    public function lookupPossessionType()
    {
        return $this->belongsTo(Lookup::class, 'possession_type_id')->where([
            'key' => 'possession-type',
        ]);

    }

    public function advisor()
    {
        return $this->belongsTo(User::class, 'advisor_id')->select(['id', 'email', 'name', 'mobile_no', 'landline_no', 'profile_photo_path', 'calendar_link']);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'paymentable');
    }

    public function transactionType()
    {
        return $this->belongsTo(Lookup::class, 'transaction_type_id', 'id');
    }

    public function quoteRequestEntityMapping()
    {
        return $this->hasOne(QuoteRequestEntityMapping::class, 'quote_request_id')
            ->where('quote_type_id', QuoteTypeId::Home);
    }

    public function documents()
    {
        return $this->morphMany(QuoteDocument::class, 'quote_documentable');
    }

    public function activities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Activities::class, 'quote_request_id')
            ->where('quote_type_id', QuoteTypeId::Home);
    }

    public function notes()
    {
        return $this->morphMany(QuoteNote::class, 'quote_noteable');
    }

    public function insuranceProviderDetails()
    {
        return $this->belongsTo(InsuranceProvider::class, 'insurance_provider_id', 'id');
    }

    public function sageApiLogs()
    {
        return $this->morphMany(SageApiLog::class, 'section');
    }

    public function customerMembers()
    {
        return $this->morphMany(CustomerMembers::class, 'quote');
    }

    public function getAuditables()
    {
        return [
            'auditable_type' => PersonalQuote::class,
            'relations' => [
                ['auditable_type' => PersonalQuoteDetail::class, 'key' => 'personal_quote_id'],
                ['auditable_type' => HomeQuote::class, 'key' => 'personal_quote_id'],
            ],
        ];
    }

    public function quoteDetail()
    {
        return $this->hasOne(HomeQuoteRequestDetail::class);
    }

    public function subArea()
    {
        return $this->belongsTo(SubArea::class, 'sub_area_id');
    }

    public function rangeLookup()
    {
        return $this->belongsTo(Lookup::class, 'owner_occupancy_type_id', 'id');
    }

    public function hasContents(): bool
    {
        return ! empty($this->contents_value_id);
    }

    public function contents()
    {
        return $this->belongsTo(RangeLookup::class, 'contents_value_id', 'id');
    }

    public function hasPersonalBelongings(): bool
    {
        return ! empty($this->personal_belongings_value_id);
    }

    public function personalBelongings()
    {
        return $this->belongsTo(RangeLookup::class, 'personal_belongings_value_id', 'id');
    }

    public function hasBuilding(): bool
    {
        return ! empty($this->building_value);
    }

    public function allowedColumns()
    {
        return $this->allowedColumns;
    }

    public function renewalBatchModel()
    {
        return $this->belongsTo(RenewalBatch::class, 'renewal_batch_id');
    }

    public function customerInsured()
    {
        return $this->hasOne(CustomerInsured::class, 'quote_request_id', 'id')
            ->where('quote_type_id', QuoteTypeId::Home);
    }

    public function amlLogs()
    {
        return $this->hasMany(KycLog::class, 'quote_request_id', 'id')
            ->where('quote_type_id', QuoteTypeId::Home)->withTrashed();
    }

    public function subSource()
    {
        return $this->belongsTo(Lookup::class, 'sub_source_id');
    }

    public function subSourceOption()
    {
        return $this->belongsTo(Lookup::class, 'sub_source_options_id');
    }
}
