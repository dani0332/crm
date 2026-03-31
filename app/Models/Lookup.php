<?php

namespace App\Models;

use App\Enums\LookupsEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Lookup extends Model
{
    use HasFactory;

    /**
     * Scope to filter active records
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Scope to filter by key
     */
    public function scopeByKey($query, string $key)
    {
        return $query->where('key', $key);
    }

    /**
     * Scope to filter by code
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    /**
     * Scope to order by sort order
     */
    public function scopeOrderBySortOrder($query)
    {
        return $query->orderBy('sort_order');
    }

    public function scopeWithChildTree($query, $quoteTypeId, $removeOptions = [])
    {
        return $query->with(['childs' => function ($query) use ($quoteTypeId, $removeOptions) {
            $query->select('id', 'text as title', 'description', 'code as slug', 'parent_id', 'quote_type_id')
                // remove child records that are in the removeOptions array
                ->when(! empty($removeOptions), function ($query) use ($removeOptions) {
                    $query->whereNotIn('code', $removeOptions);
                })
                // Include grandchild records and filter them by quoteTypeId
                ->with(['childs' => function ($query) use ($quoteTypeId) {
                    $query->where('quote_type_id', $quoteTypeId)
                        ->select('id', 'text as title', 'description', 'code as slug', 'parent_id', 'quote_type_id');
                }]);
        }])
            // only parents visible those children are included.
            ->whereHas('childs', function ($query) use ($removeOptions) {
                $query->when(! empty($removeOptions), function ($query) use ($removeOptions) {
                    $query->whereNotIn('code', $removeOptions);
                });
            })
            ->select('id', 'text as title', 'description', 'code as slug', 'parent_id', 'quote_type_id');
    }

    public function scopeSendUpdateOptions($query, $quoteTypeId, $parentId, $businessInsuranceTypeId = null)
    {
        return $query->where('quote_type_id', $quoteTypeId)
            ->where('parent_id', $parentId)
            ->when($businessInsuranceTypeId, function ($query) use ($businessInsuranceTypeId) {
                return $query->where('business_insurance_type_id', $businessInsuranceTypeId);
            })
            ->when(is_null($businessInsuranceTypeId), function ($query) {
                return $query->whereNull('business_insurance_type_id');
            })
            ->select('id', 'text as title', 'description', 'code as slug', 'parent_id', 'quote_type_id');
    }

    public function childs()
    {
        return $this->hasMany(Lookup::class, 'parent_id', 'id');
    }

    public function parent()
    {
        return $this->belongsTo(Lookup::class, 'parent_id', 'id');
    }

    public static function getCompanyTypes()
    {
        return Cache::remember('lookup_company_types', now()->addHour(), function () {
            return self::where('key', LookupsEnum::COMPANY_TYPE->value)->get();
        });
    }

    public static function getMemberRelations()
    {
        return Cache::remember('lookup_member_relations', now()->addHour(), function () {
            return self::where('key', LookupsEnum::MEMBER_RELATION->value)->get();
        });
    }

    public static function getUBORelations()
    {
        return Cache::remember('lookup_ubo_relations', now()->addHour(), function () {
            return self::where('key', LookupsEnum::UBO_RELATION->value)->get();
        });
    }
}
