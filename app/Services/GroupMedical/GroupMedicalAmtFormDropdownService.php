<?php

declare(strict_types=1);

namespace App\Services\GroupMedical;

use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\GroupMedicalCategory;
use App\Models\HealthNetwork;
use App\Models\HealthPlanType;
use App\Models\HealthThirdPartyAdministrator;
use App\Models\QuoteType;
use App\Repositories\InsuranceProviderRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

final class GroupMedicalAmtFormDropdownService
{
    /**
     * @return array<string, Collection<int, object>|array<int, object>>
     */
    public function formDropdownProps(): array
    {
        return [
            'companyActivityTypes' => $this->companyActivityTypes(),
            'healthPlanTypes' => $this->groupHealthPlanTypes(),
            'insuranceProviders' => $this->groupMedicalInsuranceProviders(),
            'groupMedicalCategories' => $this->groupMedicalCategories(),
            'healthThirdPartyAdministrators' => $this->healthThirdPartyAdministrators(),
        ];
    }

    /**
     * @return Collection<int, object>
     */
    public function companyActivityTypes(): Collection
    {
        return QuoteType::find(QuoteTypes::getId(QuoteTypes::GROUP_MEDICAL))
            ->businessActivities()
            ->active()
            ->select('business_activities.id', 'business_activities.name as text')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function groupHealthPlanTypes(): Collection
    {
        $query = HealthPlanType::query()->orderBy('text');

        if (Schema::hasColumn('health_plan_type', 'emirates_id')) {
            $query->select(['id', 'text', 'emirates_id']);
        } elseif (Schema::hasColumn('health_plan_type', 'emirate_id')) {
            $query->select(['id', 'text', 'emirate_id']);
        } else {
            $query->select(['id', 'text']);
        }

        if (Schema::hasColumn('health_plan_type', 'is_active')) {
            $query->where('is_active', 1);
        }

        if (Schema::hasColumn('health_plan_type', 'type')) {
            $query->whereRaw('LOWER(type) = ?', ['group']);
        }

        return $query->get()->map(function ($plan) {
            if (! isset($plan->emirates_id) && isset($plan->emirate_id)) {
                $plan->emirates_id = $plan->emirate_id;
            }

            return $plan;
        });
    }

    /**
     * @return Collection<int, object>
     */
    public function groupMedicalInsuranceProviders(): Collection
    {
        return InsuranceProviderRepository::byQuoteTypeMapping(QuoteTypeId::GroupMedical);
    }

    /**
     * @return Collection<int, object>
     */
    public function groupMedicalCategories(): Collection
    {
        return GroupMedicalCategory::query()
            ->active()
            ->select('id', 'text')
            ->ordered()
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function healthThirdPartyAdministrators(): Collection
    {

        return HealthThirdPartyAdministrator::query()
            ->active()
            ->select('id', 'text')
            ->orderBy('text')
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function groupMedicalNetworks(?int $tpaId = null): Collection
    {
        $query = HealthNetwork::active()
            ->join('health_network_quote_type as hqt', 'hqt.health_network_id', 'health_networks.id')
            ->select('id', 'level as text')
            ->where('hqt.quote_type_id', QuoteTypes::getId(QuoteTypes::GROUP_MEDICAL))
            ->orderBy('health_networks.sort_order');

        if ($tpaId !== null) {
            $query->where('health_third_party_administrator_id', $tpaId);
        }

        return $query->get();
    }

    /**
     * Resolve group_medical_quote_category DB rows to display labels for the Show page.
     *
     * @param  \Illuminate\Database\Eloquent\Collection|Collection  $quoteCategories
     * @return array<int, array<string, mixed>>
     */
    public function enrichCategoryIntakeForDisplay(Collection $quoteCategories): array
    {
        if ($quoteCategories->isEmpty()) {
            return [];
        }

        $categories = $this->groupMedicalCategories()->keyBy('id');
        $providers = $this->groupMedicalInsuranceProviders()->keyBy('id');
        $tpas = $this->healthThirdPartyAdministrators()->keyBy('id');
        $networks = $this->groupMedicalNetworks()->keyBy('id');

        $rows = [];

        foreach ($quoteCategories as $index => $row) {
            $row = is_array($row) ? $row : $row->toArray();

            $categoryId = $row['group_medical_category_id'] ?? null;
            $providerId = $row['insurance_provider_id'] ?? null;
            $tpaId = $row['health_third_party_administrator_id'] ?? null;
            $networkId = $row['health_network_id'] ?? null;
            $renewalDate = $row['renewal_date'] ?? null;

            $rows[] = [
                'serial' => $index + 1,
                'category_label' => $categoryId
                    ? ($categories->get((int) $categoryId)?->text ?? '—')
                    : '—',
                'existing_insurance_provider' => $providerId
                    ? ($providers->get((int) $providerId)?->text ?? 'N/A')
                    : 'N/A',
                'existing_tpa' => $tpaId
                    ? ($tpas->get((int) $tpaId)?->text ?? 'N/A')
                    : 'N/A',
                'existing_network' => $networkId
                    ? ($networks->get((int) $networkId)?->text ?? 'N/A')
                    : 'N/A',
                'existing_policy_renewal_date' => is_string($renewalDate) && $renewalDate !== ''
                    ? Carbon::parse($renewalDate)->format('d-m-Y')
                    : 'N/A',
                'number_of_people' => isset($row['number_of_people'])
                    ? (string) $row['number_of_people']
                    : '—',
            ];
        }

        return $rows;
    }

    /**
     * Resolve gm_category_intake JSON rows (ECOM format) to display labels for the Show page.
     * Handles keys from both the ECOM journey and the AMT camelCase format.
     *
     * @param  array<int, array<string, mixed>>  $categoryIntake
     * @return array<int, array<string, mixed>>
     */
    public function enrichCategoryIntakeFromJson(array $categoryIntake): array
    {
        if (empty($categoryIntake)) {
            return [];
        }

        $categories = $this->groupMedicalCategories()->keyBy('id');
        $providers = $this->groupMedicalInsuranceProviders()->keyBy('id');
        $tpas = $this->healthThirdPartyAdministrators()->keyBy('id');
        $networks = $this->groupMedicalNetworks()->keyBy('id');

        $rows = [];

        foreach ($categoryIntake as $index => $row) {
            $categoryId = $row['member_category_id'] ?? $row['groupMedicalCategoryId'] ?? null;
            $providerId = $row['existing_insurance_provider_id'] ?? $row['insuranceProviderId'] ?? null;
            $tpaId = $row['existing_tpa_id'] ?? $row['healthTpaId'] ?? null;
            $networkId = $row['existing_network_id'] ?? $row['healthNetworkId'] ?? $row['groupMedicalNetworkId'] ?? null;
            $renewalDate = $row['existing_policy_renewal_date'] ?? $row['renewalDate'] ?? null;
            $numberOfPeople = $row['number_of_people'] ?? $row['numberOfPeople'] ?? null;

            $rows[] = [
                'serial' => $index + 1,
                'category_label' => $categoryId
                    ? ($categories->get((int) $categoryId)?->text ?? '—')
                    : '—',
                'existing_insurance_provider' => $providerId
                    ? ($providers->get((int) $providerId)?->text ?? 'N/A')
                    : 'N/A',
                'existing_tpa' => $tpaId
                    ? ($tpas->get((int) $tpaId)?->text ?? 'N/A')
                    : 'N/A',
                'existing_network' => $networkId
                    ? ($networks->get((int) $networkId)?->text ?? 'N/A')
                    : 'N/A',
                'existing_policy_renewal_date' => is_string($renewalDate) && $renewalDate !== ''
                    ? Carbon::parse($renewalDate)->format('d-m-Y')
                    : 'N/A',
                'number_of_people' => $numberOfPeople !== null ? (string) $numberOfPeople : '—',
            ];
        }

        return $rows;
    }
}
