<?php

declare(strict_types=1);

namespace App\Services\GroupMedical;

use App\Enums\QuoteTypeId;
use App\Models\CompanyActivityType;
use App\Models\GroupMedicalCategory;
use App\Models\GroupMedicalNetwork;
use App\Models\HealthPlanType;
use App\Models\HealthThirdPartyAdministrator;
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
        if (! Schema::hasTable('company_activity_type')) {
            return collect();
        }

        return CompanyActivityType::query()
            ->active()
            ->select('id', 'text')
            ->orderBy('text')
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
        $query = GroupMedicalNetwork::query()
            ->active()
            ->select('id', 'text')
            ->orderBy('text');

        if ($tpaId !== null) {
            $query->where('group_medical_third_party_administrator_id', $tpaId);
        }

        return $query->get();
    }

    /**
     * Resolve stored intake row IDs to display labels for the Group Medical show page.
     *
     * @param  array<int, array<string, mixed>>|null  $intake
     * @return array<int, array<string, mixed>>
     */
    public function enrichCategoryIntakeForDisplay(?array $intake): array
    {
        if ($intake === null || $intake === []) {
            return [];
        }

        $categories = $this->groupMedicalCategories()->keyBy('id');
        $providers = $this->groupMedicalInsuranceProviders()->keyBy('id');
        $tpas = $this->healthThirdPartyAdministrators()->keyBy('id');
        $networks = $this->groupMedicalNetworks()->keyBy('id');

        $rows = [];

        foreach ($intake as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $categoryId = $row['member_category_id'] ?? null;
            $providerId = $row['existing_insurance_provider_id'] ?? null;
            $tpaId = $row['existing_tpa_id'] ?? null;
            $networkId = $row['existing_network_id'] ?? null;
            $renewalDate = $row['existing_policy_renewal_date'] ?? null;

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
}
