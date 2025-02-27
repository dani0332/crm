<?php

namespace App\Traits;

use App\Enums\AssignmentTypeEnum;
use App\Enums\EnvEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteSegmentEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\SendUpdateLogStatusEnum;
use App\Models\Payment;
use App\Models\QuoteTag;
use App\Models\SendUpdateLog;
use App\Traits\QuoteTraits\QuoteAllocatable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

trait QuoteModelTrait
{
    use Filterable, QuoteAllocatable;

    /**
     * @return mixed|void
     */
    public function scopeWithFakeLeadCriteria($query, $totalLeadsCount = false)
    {
        if ((! empty(request()->quote_status_id) && request()->quote_status_id != QuoteStatusEnum::Fake)) {

            return;
        }

        if ($totalLeadsCount) {
            return $query->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate]);
        }

        if (! request()->hasAny(['code', 'mobile_no', 'email', 'first_name', 'last_name', 'previous_quote_policy_number', 'renewal_batch', 'previous_quote_policy_number_text'])) {

            return $query->where('quote_status_id', '<>', QuoteStatusEnum::Fake);
        }
    }

    /**
     * @return string
     */
    public function getCreatedAtAttribute($table)
    {
        return $this->asDateTime($table)->timezone(config('app.timezone'))->format(Config::get('constants.datetime_format'));
    }

    /**
     * @return string
     */
    public function getUpdatedAtAttribute($table)
    {
        return $this->asDateTime($table)->timezone(config('app.timezone'))->format(Config::get('constants.datetime_format'));
    }

    public function isPaymentAuthorized()
    {
        return $this->payments->count() > 0 && $this->payments->every(fn (Payment $payment) => $payment->isPaymentAuthorized());
    }

    public function isPaid()
    {
        return $this->payments->count() > 0 && $this->payments->every(fn (Payment $payment) => $payment->isPaymentAuthorized() || $payment->isPaid());
    }

    public function scopeAs($q, string $as)
    {
        $q->from("{$q->getModel()->getTable()} as {$as}");
    }

    public static function applySegmentFilter($query, $segmentFilter, $alias, $quoteTypeId)
    {
        $user = auth()->user();
        if ($user->can(PermissionsEnum::SEGMENT_FILTER) && $segmentFilter) {
            $query->when($segmentFilter === QuoteSegmentEnum::SIC->value, function ($query) use ($alias, $quoteTypeId) {
                $query->whereIn("{$alias}.uuid", function ($query) use ($quoteTypeId) {
                    $query->distinct()
                        ->select('quote_uuid')
                        ->from('quote_tags')
                        ->where('quote_tags.name', QuoteSegmentEnum::SIC->tag())
                        ->where('quote_tags.quote_type_id', $quoteTypeId);
                })->whereNotIn("{$alias}.source", [
                    LeadSourceEnum::REVIVAL,
                    LeadSourceEnum::REVIVAL_REPLIED,
                    LeadSourceEnum::REVIVAL_PAID,
                ])->where("{$alias}.source", 'like', '%'.(config('constants.APP_ENV') == EnvEnum::PRODUCTION ? LeadSourceEnum::INSURANCE_MARKET : LeadSourceEnum::ALFRED_AE).'%');
            })->when($segmentFilter === QuoteSegmentEnum::NON_SIC->value, function ($query) use ($alias, $quoteTypeId) {
                $query->whereNotIn("{$alias}.uuid", function ($query) use ($quoteTypeId) {
                    $query->distinct()
                        ->select('quote_uuid')
                        ->from('quote_tags')
                        ->where('quote_tags.name', QuoteSegmentEnum::SIC->tag())
                        ->where('quote_tags.quote_type_id', $quoteTypeId);
                })->where("{$alias}.source", 'like', '%'.(config('constants.APP_ENV') == EnvEnum::PRODUCTION ? LeadSourceEnum::INSURANCE_MARKET : LeadSourceEnum::ALFRED_AE).'%');
            })->when($segmentFilter === QuoteSegmentEnum::SIC_REVIVAL->value, function ($query) use ($alias) {
                $query->whereIn("{$alias}.source", [
                    LeadSourceEnum::REVIVAL,
                    LeadSourceEnum::REVIVAL_REPLIED,
                    LeadSourceEnum::REVIVAL_PAID,
                ]);
            });
        }
    }

    public function isCPDEndorsment($sendUpdateId)
    {
        $sendUpdateLog = SendUpdateLog::where('id', $sendUpdateId)->with('category')->first();

        return ['isCPDEndorsment' => $sendUpdateLog->category?->code == SendUpdateLogStatusEnum::CPD, 'sendUpdateUUID' => $sendUpdateLog->uuid];
    }

    public function scopeIsSICLead($q, QuoteTypes $quoteType, bool $not = false)
    {
        $subQuery = function ($query) use ($quoteType) {
            $query->distinct()
                ->select('quote_uuid')
                ->from('quote_tags')
                ->where('quote_tags.name', QuoteSegmentEnum::SIC->tag())
                ->where('quote_tags.quote_type_id', $quoteType->id());
        };

        if ($not) {
            $q->whereNotIn("{$q->getModel()->getTable()}.uuid", $subQuery);
        } else {
            $q->whereIn("{$q->getModel()->getTable()}.uuid", $subQuery)->whereNotIn("{$q->getModel()->getTable()}.source", [
                LeadSourceEnum::REVIVAL,
                LeadSourceEnum::REVIVAL_REPLIED,
                LeadSourceEnum::REVIVAL_PAID,
            ]);
        }
    }

    public function scopeIsNonSICLead($q, QuoteTypes $quoteType)
    {
        $q->isSICLead($quoteType, true);
    }

    public function isSIC(QuoteTypes $quoteType): bool
    {
        return QuoteTag::where('quote_uuid', $this->uuid)->where('quote_tags.name', QuoteSegmentEnum::SIC->tag())->where('quote_tags.quote_type_id', $quoteType->id())->exists();
    }

    public function isNonSIC(QuoteTypes $quoteType): bool
    {
        return ! $this->isSIC($quoteType);
    }

    public function isStale()
    {
        return ! empty($this->stale_at);
    }

    public function isBuyLeadApplicable(bool $isSIC = false): bool
    {
        if ($isSIC) {
            return (! $this->isStale() && ! $this->isPaid()) &&
            (request('isRequestedForAnAdvisor', false) ||
            $this->sic_advisor_requested == 1 ||
            $this->assignment_type == AssignmentTypeEnum::BOUGHT_LEAD ||
            $this->assignment_type == AssignmentTypeEnum::REASSIGNED_AS_BOUGHT_LEAD);
        }

        // If lead is not stale and not paid, or previously lead is bought lead or reassigned as bought lead

        return (! $this->isStale() && ! $this->isPaid()) || in_array(
            $this->assignment_type,
            [AssignmentTypeEnum::BOUGHT_LEAD, AssignmentTypeEnum::REASSIGNED_AS_BOUGHT_LEAD]
        );
    }

    public function getForeignKey()
    {
        return Str::snake(Str::singular($this->getTable())).'_id';
    }

    public static function applyRequestTableJoins($query, $request): void
    {
        $applicableFilters = ['member_first_name', 'member_last_name', 'company_name'];
        $quoteTypes = [
            QuoteTypeId::Car => 'car_quote_request',
            QuoteTypeId::Home => 'home_quote_request',
            QuoteTypeId::Health => 'health_quote_request',
            QuoteTypeId::Life => 'life_quote_request',
            QuoteTypeId::Business => 'business_quote_request',
            QuoteTypeId::Travel => 'travel_quote_request',
        ];

        if ($request->hasAny($applicableFilters) && $request->has('line_of_business') && isset($quoteTypes[$request->line_of_business])) {
            $query->join($quoteTypes[$request->line_of_business], function ($join) use ($quoteTypes, $request) {
                $join->where('personal_quotes.quote_type_id', '=', $request->line_of_business);
                $join->on('personal_quotes.code', '=', $quoteTypes[$request->line_of_business].'.code');
            });
        }
    }
}
