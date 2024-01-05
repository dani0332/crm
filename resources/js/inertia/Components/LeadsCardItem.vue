<script setup>
import {
  useSortable,
  moveArrayElement,
} from '@vueuse/integrations/useSortable';
import { nextTick } from 'vue';
import { daysSinceStale } from '../Composables/utilities';

const page = usePage();

const props = defineProps({
  leads: {
    type: Array,
    require: true,
  },
  id: Number,
  title: String,
  quote_type_id: Number,
});

const quoteStatusEnum = inject('quoteStatusEnum');

const leads = ref(props.leads);
const canDrag = computed(() => {
  return props.id == quoteStatusEnum?.Lost ||
    props.id == quoteStatusEnum?.TransactionApproved ||
    props.id == quoteStatusEnum?.PolicyIssued
    ? false
    : true;
});

useSortable(`#${props.title}`, props.leads, {
  group: {
    name: 'shared',
    put: true,
    pull: canDrag.value,
  },
  animation: 500,
  onAdd: function (e) {
    let quote_status_id = e.to.getAttribute('quote_status_id');
    console.log(quote_status_id);
    // setTimeout(() => {
    //   const ids = orderedList.value.map(item => item.id);
    //   axios
    //     .post(route('reward-sliders.update-order'), {
    //       ids,
    //     })
    //     .then(({ data }) => {
    //       router.get(
    //         route('reward-sliders.index'),
    //         {},
    //         { preserveScroll: true },
    //       );
    //       toast.success({
    //         title: data.data,
    //         position: 'top',
    //       });
    //     });
    // }, 1000);
  },
  onRemove: function (e) {
    // quote_type_id is missing
    let { id, quote_type_id, quote_status_id } = leads.value[e.oldIndex];
  },
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
  <!--  -->
  <div
    :id="title"
    :quote_status_id="id"
    class="shared"
    :class="{ 'h-screen': props.leads.length == 0 }"
  >
    <a
      v-for="{
        id,
        uuid,
        first_name,
        last_name,
        premium,
        updated_at,
        company_name,
        leadName,
        health_cover_for,
        stale_at,
      } in leads"
      :key="id"
      :href="`/quotes/health/${uuid}`"
      target="_blank"
      title="View Lead"
      class="block p-3 mt-2 border border-gray-300 space-y-2 hover:transition hover:border-primary-500 rounded"
      :class="[
        daysSinceStale(stale_at) === false ? 'bg-white' : 'bg-error-200',
        { 'cursor-not-allowed': !canDrag },
      ]"
    >
      <div class="flex items-center space-x-1 overflow-hidden">
        <span class="font-semibold text-sm"
          >{{ first_name }} {{ last_name }}
        </span>
        <stale-leads-badge :date="stale_at"></stale-leads-badge>
      </div>

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
        <p class="text-xs">{{ updated_at }}</p>
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
