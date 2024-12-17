<script setup>
defineProps({
  carmake: Object,
  carmodel: Object,
  insuranceProvider: Object,
  vehicledepreciation: Object,
});

const page = usePage();
const permissionsEnum = page.props.permissionsEnum;
const can = permission => useCan(permission);
</script>
<template>
  <Head title="Vehicle Depreciation Detail" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Vehicle Depreciation Detail</h2>
    <div class="space-x-3">
      <Link
        v-if="can(permissionsEnum.VehicleDepreciationDelete)"
        method="delete"
        :href="route('vehicledepreciation.destroy', vehicledepreciation.id)"
      >
        <x-button size="sm" color="rose" tag="div"> Delete </x-button>
      </Link>
      <Link :href="route('vehicledepreciation.index')">
        <x-button size="sm" color="#ff5e00" tag="div">
          Depreciation List
        </x-button>
      </Link>
      <Link
        v-if="can(permissionsEnum.VehicleDepreciationEdit)"
        :href="route('vehicledepreciation.edit', vehicledepreciation.id)"
      >
        <x-button size="sm" color="" tag="div"> Edit </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <div class="p-4 rounded shadow mb-6 bg-white">
    <div class="text-sm">
      <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
        <div class="grid sm:grid-cols-2">
          <div>
            <x-tooltip placement="bottom">
              <label
                class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-700"
              >
                Ref-ID
              </label>
              <template #tooltip> Reference ID </template>
            </x-tooltip>
          </div>
          <div>{{ vehicledepreciation.id }}</div>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Car Make</dt>
          <dd>{{ carmake?.text ?? 'N/A' }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Car Model</dt>
          <dd>{{ carmodel?.text ?? 'N/A' }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Insurance Provider</dt>
          <dd>{{ insuranceProvider?.text ?? 'N/A' }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">First Year</dt>
          <dd>{{ vehicledepreciation.first_year ?? 'N/A' }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Second year</dt>
          <dd>{{ vehicledepreciation.second_year ?? 'N/A' }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Third year</dt>
          <dd>{{ vehicledepreciation.third_year ?? 'N/A' }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Fourth year</dt>
          <dd>{{ vehicledepreciation.fourth_year ?? 'N/A' }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Fifth Year</dt>
          <dd>{{ vehicledepreciation.fifth_year ?? 'N/A' }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Sixth Year</dt>
          <dd>{{ vehicledepreciation.sixth_year ?? 'N/A' }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Seventh year</dt>
          <dd>{{ vehicledepreciation.seventh_year ?? 'N/A' }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Eighth year</dt>
          <dd>{{ vehicledepreciation.eighth_year ?? 'N/A' }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Ninth year</dt>
          <dd>{{ vehicledepreciation.ninth_year ?? 'N/A' }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Tenth year</dt>
          <dd>{{ vehicledepreciation.tenth_year ?? 'N/A' }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Upper limit</dt>
          <dd>{{ vehicledepreciation.upper_limit ?? 'N/A' }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Lower limit</dt>
          <dd>{{ vehicledepreciation.lower_limit ?? 'N/A' }}</dd>
        </div>
      </dl>
    </div>
  </div>
  <AuditLogs
    :type="'App\\Models\\VehicleDepreciation'"
    :quoteType="'VehicleDepreciation'"
    :id="vehicledepreciation.id"
  />
</template>
