<?php

namespace App\Traits;

use App\Enums\AMLStatusCode;
use App\Enums\AssignmentTypeEnum;
use App\Enums\CustomerTypeEnum;
use App\Enums\EnvEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteSegmentEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\QuoteTypeShortCode;
use App\Enums\SendUpdateLogStatusEnum;
use App\Models\CarQuotePlanDetail;
use App\Models\Payment;
use App\Models\QuoteTag;
use App\Models\SendUpdateLog;
use App\Services\BuyLeads\BuyLeadService;
use App\Traits\QuoteTraits\QuoteAllocatable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

trait QuoteModelTrait
{
    use Filterable, Logable, Optionable, QuoteAllocatable, QuotePaymentable;

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
        if ($user && $user->can(PermissionsEnum::SEGMENT_FILTER) && $segmentFilter) {
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
                // Exclude leads with SIC tag
                $query->whereNotIn("{$alias}.uuid", function ($query) use ($quoteTypeId) {
                    $query->distinct()
                        ->select('quote_uuid')
                        ->from('quote_tags')
                        ->where('quote_tags.name', QuoteSegmentEnum::SIC->tag())
                        ->where('quote_tags.quote_type_id', $quoteTypeId);
                })
                // Also exclude leads with AIG tag
                    ->whereNotIn("{$alias}.uuid", function ($query) use ($quoteTypeId) {
                        $query->distinct()
                            ->select('quote_uuid')
                            ->from('quote_tags')
                            ->where('quote_tags.name', QuoteSegmentEnum::AIG->tag())
                            ->where('quote_tags.quote_type_id', $quoteTypeId);
                    })
                    ->where("{$alias}.source", 'like', '%'.(config('constants.APP_ENV') == EnvEnum::PRODUCTION ? LeadSourceEnum::INSURANCE_MARKET : LeadSourceEnum::ALFRED_AE).'%');
            })->when($segmentFilter === QuoteSegmentEnum::SIC_REVIVAL->value, function ($query) use ($alias) {
                $query->whereIn("{$alias}.source", [
                    LeadSourceEnum::REVIVAL,
                    LeadSourceEnum::REVIVAL_REPLIED,
                    LeadSourceEnum::REVIVAL_PAID,
                ]);
            })->when($segmentFilter === QuoteSegmentEnum::AIG->value, function ($query) use ($alias, $quoteTypeId) {
                $query->whereIn("{$alias}.uuid", function ($query) use ($quoteTypeId) {
                    $query->distinct()
                        ->select('quote_uuid')
                        ->from('quote_tags')
                        ->where('quote_tags.name', QuoteSegmentEnum::AIG->tag())
                        ->where('quote_tags.quote_type_id', $quoteTypeId);
                });
            })->when($segmentFilter === QuoteSegmentEnum::FIC->value, function ($query) use ($alias, $quoteTypeId) {
                $query->whereIn("{$alias}.uuid", function ($query) use ($quoteTypeId) {

                    $query->distinct()
                        ->select('quote_uuid')
                        ->from('quote_tags')
                        ->where('quote_tags.name', QuoteSegmentEnum::FIC->tag())
                        ->where('quote_tags.quote_type_id', $quoteTypeId);
                });
            })
                ->when($segmentFilter === QuoteSegmentEnum::NON_FIC->value, function ($query) use ($alias, $quoteTypeId) {
                    $query->whereNotIn("{$alias}.uuid", function ($query) use ($quoteTypeId) {
                        $query->distinct()
                            ->select('quote_uuid')
                            ->from('quote_tags')
                            ->where('quote_tags.name', QuoteSegmentEnum::FIC->tag())
                            ->where('quote_tags.quote_type_id', $quoteTypeId);
                    });
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

    public function scopeIsSIC($q, QuoteTypes $quoteType)
    {
        $subQuery = function ($query) use ($quoteType) {
            $query->distinct()
                ->select('quote_uuid')
                ->from('quote_tags')
                ->where('quote_tags.name', QuoteSegmentEnum::SIC->tag())
                ->where('quote_tags.quote_type_id', $quoteType->id());
        };

        $q->whereIn("{$q->getModel()->getTable()}.uuid", $subQuery);
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

    public function isCatABuyLeadApplicable(QuoteTypes $quoteType): bool
    {
        return ! $this->isStale() && $this->source == LeadSourceEnum::REVIVAL && in_array($this->nationality_id, BuyLeadService::getNationalitiesIds($quoteType));
    }

    public function getForeignKey()
    {
        return Str::snake(Str::singular($this->getTable())).'_id';
    }

    public static function applyRequestTableJoins($query, $request): void
    {
        $applicableFilters = ['member_first_name', 'member_last_name'/* , 'company_name' */];
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

    public function assignmentTypeText(): Attribute
    {
        return Attribute::make(
            get: function () {
                return AssignmentTypeEnum::getAssignmentTypeText($this->assignment_type);
            }
        );
    }

    public function dobFormatted(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->dob ? Carbon::parse($this->dob)->format('d-m-Y') : null;
            }
        );
    }

    public function previousPolicyExpiryDateFormatted(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->previous_policy_expiry_date ? Carbon::parse($this->previous_policy_expiry_date)->format('d-m-Y') : null;
            }
        );
    }

    public function insurerAmlStatusText(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match ($this->insurer_aml_status) {
                    AMLStatusCode::InsurerAMLScreeningPending => AMLStatusCode::getName(AMLStatusCode::InsurerAMLScreeningPending),
                    AMLStatusCode::InsurerAMLScreeningCleared => AMLStatusCode::getName(AMLStatusCode::InsurerAMLScreeningCleared),
                    AMLStatusCode::InsurerAMLScreeningFailed => AMLStatusCode::getName(AMLStatusCode::InsurerAMLScreeningFailed),
                    default => AMLStatusCode::InsurerAMLScreeningNA,
                };
            }
        );
    }

    public function isPaymentLinkRequested(): bool
    {
        return $this->quote_status_id == QuoteStatusEnum::PaymentLinkRequestedByCustomer;
    }

    public function isPUA(): bool
    {
        if (empty($this->plan_id)) {
            return false;
        }

        return CarQuotePlanDetail::where('quote_uuid', $this->uuid)
            ->whereNotNull('pua_premium')
            ->where('plan_id', $this->plan_id)
            ->exists();
    }

    public function payment()
    {
        return $this->morphOne(Payment::class, 'paymentable')->mainLeadPayment();
    }

    public function customerType(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Extract the prefix from the quote code (before the first dash)
                $codePrefix = explode('-', $this->code)[0] ?? '';

                // Get the latest insured record and return its customer_type
                // If code prefix is BUS, default to Entity, otherwise default to Individual
                $defaultType = ($codePrefix === QuoteTypeShortCode::BUS)
                    ? CustomerTypeEnum::Entity
                    : CustomerTypeEnum::Individual;

                return $this->latestInsured?->customer_type ?? $defaultType;
            }
        );
    }

    public function pcQualifiedFormatted(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->pc_qualified === true || $this->pc_qualified === 1 ? 'Yes' : 'No';
            }
        );
    }

    public static function formattedPcQualifiedCase(string $tableAlias = ''): string
    {
        $column = $tableAlias ? $tableAlias.'.pc_qualified' : 'pc_qualified';

        return "
            CASE
                WHEN {$column} = 1 THEN 'Yes'
                ELSE 'No'
            END
        ";
    }

    public function hasOneOfPaidStatus(): bool
    {
        return $this->payments && $this->payments->count() > 0 &&
               $this->payments->contains(function (Payment $payment) {
                   return $payment->hasOneOfPaidStatus();
               });
    }

    public function getSegments($lead, $quoteTypeId)
    {
        $requestData = request()->all();
        $segmentFilter = $requestData['segment_filter'] ?? null;

        $segment = QuoteSegmentEnum::tryFrom($segmentFilter);

        // Handle specific segment filter case
        if ($segmentFilter && $segmentFilter !== strtolower(QuoteSegmentEnum::ALL->label())) {
            return $segment->label();
        }

        // Strategy 1: Use preloaded relationship if available
        // Strategy 2: Fallback to original database query
        $tagNames = $this->getTagNames($lead, $quoteTypeId);

        $leadSource = $lead->source;

        $isProduction = config('constants.APP_ENV') == EnvEnum::PRODUCTION;
        $marketSource = $isProduction ? LeadSourceEnum::INSURANCE_MARKET : LeadSourceEnum::ALFRED_AE;

        // This section handles both "ALL" filter and export case (no filter)
        // Priority: AIG check first
        if (in_array(strtolower(QuoteSegmentEnum::AIG->tag()), $tagNames)) {
            return QuoteSegmentEnum::AIG->label();
        }

        $matchedSegments = [];

        // Check SIC
        if (
            in_array(strtolower(QuoteSegmentEnum::SIC->tag()), $tagNames) &&
            ! in_array($leadSource, [
                LeadSourceEnum::REVIVAL,
                LeadSourceEnum::REVIVAL_REPLIED,
                LeadSourceEnum::REVIVAL_PAID,
            ]) &&
            str_contains($leadSource, $marketSource)
        ) {
            $matchedSegments[] = QuoteSegmentEnum::SIC->label();
        }

        // Check NON-SIC - ensure it's neither SIC nor AIG and has the right source
        if (
            ! in_array(strtolower(QuoteSegmentEnum::SIC->tag()), $tagNames) &&
            ! in_array(strtolower(QuoteSegmentEnum::AIG->tag()), $tagNames) &&
            str_contains($leadSource, $marketSource)
        ) {
            $matchedSegments[] = QuoteSegmentEnum::NON_SIC->label();
        }

        // Check SIC-REVIVAL
        if (in_array($leadSource, [
            LeadSourceEnum::REVIVAL,
            LeadSourceEnum::REVIVAL_REPLIED,
            LeadSourceEnum::REVIVAL_PAID,
        ])) {
            $matchedSegments[] = QuoteSegmentEnum::SIC_REVIVAL->label();
        }

        return implode(', ', $matchedSegments);
    }

    /**
     * Get tag names using optimized relationship or fallback to database query
     */
    private function getTagNames($lead, $quoteTypeId): array
    {
        // Strategy 1: Use preloaded relationship if available (optimized)
        if (method_exists($lead, 'quoteTags') && $lead->relationLoaded('quoteTags')) {
            return collect($lead->quoteTags ?? [])
                ->pluck('name')
                ->map(fn ($name) => strtolower($name))
                ->toArray();

        }

        // Strategy 2: Fallback to original database query (backward compatible)
        return QuoteTag::where('quote_uuid', $lead->uuid)
            ->where('quote_type_id', $quoteTypeId)
            ->pluck('name')
            ->map(fn ($name) => strtolower($name))
            ->toArray();
    }

    public function isSuppressIntroEmail(): bool
    {
        $excludedQuoteStatuses = [
            QuoteStatusEnum::TransactionApproved,
            QuoteStatusEnum::PolicyBooked,
            QuoteStatusEnum::PolicyIssued,
            QuoteStatusEnum::PolicySentToCustomer,
            QuoteStatusEnum::POLICY_BOOKING_QUEUED,
            QuoteStatusEnum::POLICY_BOOKING_FAILED,
            QuoteStatusEnum::CancellationPending,
            QuoteStatusEnum::PolicyCancelled,
            QuoteStatusEnum::PolicyCancelledReissued,
        ];

        return in_array($this->quote_status_id, $excludedQuoteStatuses);
    }

    public function getCrmQuoteLink(): string
    {
        $baseUrl = config('app.url', env('APP_URL'));
        $quoteId = $this->uuid ?? $this->id ?? '';

        // Generate appropriate link based on quote type
        return match ($this->quote_type_id) {
            QuoteTypeId::Car => "{$baseUrl}/quotes/car/{$quoteId}",           // Car quote type
            QuoteTypeId::Bike => "{$baseUrl}/personal-quotes/bike/{$quoteId}", // Bike quote type
            QuoteTypeId::Cyber => "{$baseUrl}/personal-quotes/cyber/{$quoteId}", // Cyber quote type
            QuoteTypeId::Device => "{$baseUrl}/personal-quotes/smartphone/{$quoteId}", // Device quote type
            default => 'N/A'
        };
    }
}
