<?php

namespace App\Models\Audit;

use App\Enums\AssignmentTypeEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteTypes;
use App\Models\BaseMongoModel;
use App\Models\PersonalQuote;
use App\Models\User;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class AllocationAudit extends BaseMongoModel
{
    protected $collection = 'allocation_audits';
    protected $fillable = [
        'quote_type_id',
        'uuid',
        'assignment_type',
        'advisor_id',
        'action_by_id',
        'action_by_details',
        'advisor_details',
    ];
    protected $appends = [
        'action_by_name',
        'advisor_name',
        'assignment_type_text',
        'action_by_email',
        'advisor_email',
    ];

    public static function log(Model $model, ?QuoteTypes $quoteType = null, ?User $user = null): bool
    {
        $record = $model->newQuery()->with('advisor:id,name,email')->find($model->getKey());

        LoggerService::startQuoteLogging($record, LoggerFeatureEnum::ALLOCATION_AUDIT);

        $user = $user ?: (object) [
            'id' => -1,
            'name' => 'System',
            'email' => 'system',
        ];

        $actionByDetails = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];

        $advisorDetails = [
            'id' => $record->advisor->id,
            'name' => $record->advisor->name,
            'email' => $record->advisor->email,
        ];

        $data = [
            'quote_type_id' => (int) $quoteType?->id(),
            'uuid' => $record->uuid,
            'assignment_type' => (int) $record->assignment_type,
            'advisor_id' => (int) $advisorDetails['id'],
            'action_by_id' => (int) $actionByDetails['id'],
            'action_by_details' => $actionByDetails,
            'advisor_details' => $advisorDetails,
        ];

        if ($record instanceof PersonalQuote) {
            $quoteType = $record->quoteType;
            if (! checkPersonalQuotes($quoteType?->code)) {
                LoggerService::info('Allocation Audit Skipping because Parent Quote is not migrated to Personal Quote yet.');

                return false;
            }

            $data['quote_type_id'] = (int) $quoteType?->id;
        }

        LoggerService::debug('AllocationAudit - Record', [
            'data' => $data,
            'record' => $record,
        ]);

        static::create($data);

        return true;
    }

    public function assignmentTypeText(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => AssignmentTypeEnum::getAssignmentTypeText($this->assignment_type)
        );
    }

    public function actionByName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->action_by_details['name'] ?? 'N/A'
        );
    }

    public function actionByEmail(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->action_by_details['email'] ?? 'N/A'
        );
    }

    public function advisorName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->advisor_details['name'] ?? 'N/A'
        );
    }

    public function advisorEmail(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->advisor_details['email'] ?? 'N/A'
        );
    }

    public function scopeByQuoteType($query)
    {
        $query->when(
            request()->filled('quote_type'),
            function ($query) {
                $quoteType = QuoteTypes::tryFrom(request('quote_type'));

                $query->where('quote_type_id', (int) $quoteType?->id());
            },
            fn ($q) => $q->whereNull('quote_type_id'),
        );
    }

    public function scopeByUuid($query)
    {
        $query->when(
            request()->filled('uuid'),
            fn ($q) => $q->where('uuid', request('uuid')),
            fn ($q) => $q->whereNull('uuid'),
        );
    }
}
