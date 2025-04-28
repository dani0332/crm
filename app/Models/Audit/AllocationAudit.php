<?php

namespace App\Models\Audit;

use App\Enums\AssignmentTypeEnum;
use App\Enums\QuoteTypes;
use App\Models\BaseMongoModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class AllocationAudit extends BaseMongoModel
{
    protected $collection = 'allocation_audits';
    protected $fillable = [
        'auditable_id',
        'auditable_type',
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
    ];

    public static function log(Model $model, ?QuoteTypes $quoteType = null, ?User $user = null): self
    {
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
            'id' => $model->advisor->id,
            'name' => $model->advisor->name,
            'email' => $model->advisor->email,
        ];

        return static::create([
            'auditable_id' => $model->getKey(),
            'auditable_type' => get_class($model),
            'quote_type_id' => (int) $quoteType?->id() ?? $model->quote_type_id,
            'uuid' => $model->uuid,
            'assignment_type' => (int) $model->assignment_type,
            'advisor_id' => (int) $advisorDetails['id'],
            'action_by_id' => (int) $actionByDetails['id'],
            'action_by_details' => $actionByDetails,
            'advisor_details' => $advisorDetails,
        ]);
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

    public function advisorName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->advisor_details['name'] ?? 'N/A'
        );
    }
}
