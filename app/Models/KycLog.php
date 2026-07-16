<?php

namespace App\Models;

use App\Enums\AMLDecisionStatusEnum;
use App\Enums\AMLScreeningTypeEnum;
use App\Traits\SpatieActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Context;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class KycLog extends BaseModel implements AuditableContract
{
    use Auditable, HasFactory, SpatieActivityLog;

    protected $table = 'kyc_logs';

    /**
     * Only audit fields that reflect user decisions — avoids storing the large `results` JSON on every change.
     *
     * @var array<int, string>
     */
    protected $auditInclude = [
        'decision',
        'notes',
    ];

    /**
     * Scope to exclude RYU decision records (includes NULL records).
     * Use this when you want to filter out RYU but keep records with no decision.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeExcludeRyuDecision($query)
    {
        return $query->where(function ($ryuFilter) {
            $ryuFilter->whereNotIn('decision', [AMLDecisionStatusEnum::RYU]);
            $ryuFilter->orWhereNull('decision');
        });
    }

    /**
     * Scope to exclude RYU decision records (also excludes NULL records).
     * Use this when you want to filter out RYU AND records with no decision.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeExcludeRyuDecisionStrict($query)
    {
        return $query->where('decision', '!=', AMLDecisionStatusEnum::RYU);
    }

    /**
     * Scope to exclude insurer AML screening types (AXA, RSA).
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeExcludeInsurerScreening($query)
    {
        return $query->where(function ($aml) {
            $aml->whereNotIn('screening_type', [AMLScreeningTypeEnum::INSURER_AXA, AMLScreeningTypeEnum::INSURER_RSA]);
            $aml->orWhereNull('screening_type');
        });
    }

    /**
     * Scope to exclude records with screenshot data.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeExcludeScreenshot($query)
    {
        return $query->where(function ($screenshotFilter) {
            $screenshotFilter->whereNull('screenshot');
            $screenshotFilter->orWhere('screenshot', '');
        });
    }

    /**
     * Scope to apply all standard AML screening filters.
     * Combines exclusions for RYU decisions, insurer screening, and screenshots.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeStandardAmlFilters($query)
    {
        return $query->excludeRyuDecision()
            ->excludeInsurerScreening()
            ->excludeScreenshot();
    }

    /**
     * Tag the audit record with the human-readable button label set by AMLService before saving.
     */
    public function generateTags(): array
    {
        $label = Context::get('aml_decision_label');

        if ($label === null) {
            return [];
        }

        return [json_encode(['decision' => $label])];
    }

    public $access = [
        'write' => ['admin'],
        'update' => ['admin'],
        'delete' => ['admin'],
        'access' => [
            'pa' => [],
            'production_approval_manager' => [],
            'advisor' => [],
            'oe' => [],
            'admin' => [],
            'invoicing' => [],
            'payment' => [],
        ],
        'list' => [
            'pa' => ['id', 'results', 'quote_request_id', 'results_found'],
            'production_approval_manager' => ['id', 'results', 'quote_request_id', 'results_found'],
            'advisor' => ['id', 'results', 'quote_request_id', 'results_found'],
            'oe' => ['id', 'results', 'quote_request_id', 'results_found'],
            'admin' => ['id', 'results', 'quote_request_id', 'results_found'],
            'invoicing' => ['id', 'results', 'quote_request_id', 'results_found'],
            'payment' => ['id', 'results', 'quote_request_id', 'results_found'],
        ],
    ];
}
