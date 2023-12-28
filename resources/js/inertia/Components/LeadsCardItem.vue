<script setup>
import { useSortable } from '@vueuse/integrations/useSortable';

const page = usePage();
const props = defineProps({
  leads: {
    type: Array,
    require: true,
  },
  id: String,
});

useSortable(`#${props.id}`, props.leads, {
  group: { name: 'shared', put: true },
  animation: 500,
});

const hasAnyRole = role => useHasAnyRole(role);
const rolesEnum = page.props.rolesEnum;

const isAllowed = computed(() => {
  return (
    hasAnyRole([
      rolesEnum.Advisor,
      rolesEnum.OperationAssistant,
      rolesEnum.UnitManager,
      rolesEnum.UnitHead,
    ]) ?? false
  );
});

const dateFormat = date => {
  return useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value;
};

// const lostReasonsOptions = computed(() => {
//   return page.props.lostReasons.map(reason => ({
//     value: reason.id,
//     label: reason.text,
//   }));
// });

const showModal = ref(false);
</script>
<template>
  <!-- :class="{ 'h-screen': props.leads.length == 0 }" -->
  <div :id="props.id" class="shared">
    <a
      v-for="(
        {
          id,
          uuid,
          first_name,
          last_name,
          premium,
          updated_at,
          company_name,
          leadName,
          health_cover_for,
          is_stale
        },
        index
      ) in leads"
      :key="id"
      :href="`/quotes/health/${uuid}`"
      target="_blank"
      title="View Lead"
      class="block p-3 mt-2 border border-gray-300 space-y-2 hover:transition hover:border-primary-500 rounded"
      :class="is_stale ? 'bg-error-200' : 'bg-white'"
    >
      <div class="font-semibold text-sm">{{ first_name }} {{ last_name }}</div>
      <div class="flex items-center gap-2">
        <x-tooltip>
          <x-icon icon="person" size="sm" class="text-primary-400" />
          <template #tooltip>
            <span class="x-sm">
              This indicates the specific type of insurance coverage.</span
            >
          </template>
        </x-tooltip>
        <p class="text-xs">{{ health_cover_for?.text }}</p>
      </div>

      <div v-if="company_name" class="flex items-center gap-2">
        <x-icon icon="company" size="sm" class="text-primary-400" />
        <p class="text-xs">{{ company_name }}</p>
      </div>

      <div class="flex items-center gap-2">
        <x-tooltip>
          <x-icon icon="money" size="sm" class="text-primary-400" />
          <template #tooltip>
            <span v-if="leadName == 'Health'" class="x-sm">
              The complete amount due including VAT and before any potential
              discounts. Remember, VAT is exempt for Life Insurance
              policies.</span
            >
            <span v-else class="x-sm">
              The complete amount due including VAT and before any potential
              discounts. Remember, VAT is exempt for Life Insurance
              policies.</span
            >
          </template>
        </x-tooltip>
        <p class="text-xs">{{ Number(premium).toLocaleString() }}</p>
      </div>

      <div class="flex items-center gap-2">
        <x-tooltip>
          <x-icon icon="calendar" size="sm" class="text-primary-400" />
          <template #tooltip>
            <span
              >The 'Last Modified Date' displays the most recent date and time
              when the lead was last worked on.</span
            >
          </template>
        </x-tooltip>
        <p class="text-xs">{{ dateFormat(updated_at) }}</p>
      </div>
    </a>
  </div>
  <!-- <app-modal v-model="showModal"></app-modal> -->
  <x-modal v-model="showModal" show-close backdrop>
    <template #header>
      <span>Lead Lost Reason </span>
    </template>

    <x-form>
      <x-field label="Lost Reason">
        <x-select
          :options="[]"
          placeholder="Lost Reason is required"
          class="w-full"
        />
      </x-field>
    </x-form>

    <template #actions>
      <div class="text-right space-x-4">
        <x-button>Continue</x-button>
        <x-button color="orange">Go Back</x-button>
      </div>
    </template>
  </x-modal>
</template>
