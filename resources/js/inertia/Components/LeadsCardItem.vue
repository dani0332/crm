<script setup>
import { useForm } from '@inertiajs/vue3';
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
  quote_type_id: String,
});

const quoteStatusEnum = inject('quoteStatusEnum');
const { isRequired } = useRules();

const leads = ref(props.leads);
const leadForm = useForm({
  reason: null,
  isReason: false,
});

const reasons = ref([
  { value: 1, label: 'reason one' },
  { value: 2, label: 'reason two' },
  { value: 3, label: 'reason three' },
  { value: 4, label: 'reason four' },
  { value: 4, label: 'reason five' },
]);

const canDrag = computed(() => {
  return props.id == quoteStatusEnum?.Lost ||
    props.id == quoteStatusEnum?.TransactionApproved ||
    props.id == quoteStatusEnum?.PolicyIssued
    ? false
    : true;
});

const updateList = data => {
  axios
    .post(route('update-lead-status-drag-drop'), {
      data,
    })
    .then(({ data }) => {});
};

const isLostReason = computed(() => {
  return leadForm.isReason ? true : false;
});

const canDrop = async (e, callback) => {
  showModal.value = true;

  let response = await new Promise(resolve => {
    const closeHandler = () => {
      showModal.value = false;
      resolve(true);
    };

    callback(closeHandler);
    // setTimeout(() => {
    //   // Simulating the modal close event after 2 seconds (replace with your actual logic)
    //   closeHandler();
    // }, 2000);
  });
  // return false;
};

const moveTask = async () => {
  // Show the confirmation modal
  showModal.value = true;

  // Wait for the confirmation result
  const confirmed = await new Promise(resolve => {
    const confirmationHandler = result => {
      resolve(result);
    };
    // Register the event listener for the confirmation
    context.emit('confirmation-result', confirmationHandler);
  });

  // Hide the modal
  showModal.value = false;

  // Move the task if confirmed, otherwise do nothing
  if (confirmed) {
    // Move the task logic here
    console.log('Task moved!');
  } else {
    console.log('Task not moved!');
  }
};

let sortable = useSortable(`#${props.title}`, props.leads, {
  group: {
    name: 'shared',
    put: true,
    pull: canDrag.value,
  },
  animation: 500,
  onSort: async function (e) {
    let node = e.item;
    let item = leads.value[e.oldIndex];
    let data = item
      ? {
          form: { id: item.id, quote_status_id: item.quote_status_id },
          to: { quote_status_id: e.to.getAttribute('quote_status_id') },
        }
      : null;

    if (data && data.to.quote_status_id == 17) {
      let response = await canDrop(e);
      if (!response) {
        var itemEl = e.item; // dragged HTMLElement
        let originalList = e.from; // previous list
        var newIndex = e.oldIndex;

        var referenceNode = originalList.children[newIndex];

        // Insert the dragged element back to its original position
        originalList.insertBefore(itemEl, referenceNode);
      }
    }
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

const onSubmit = isValid => {
  console.log(sortable);
  // if (!isValid) return false;
};
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
      <span>Kinldy choose a reason for marking as 'Lost' </span>
    </template>

    <x-form @submit="onSubmit" :auto-focus="false">
      <x-field label="Lost Reason" required>
        <x-select
          v-model="leadForm.lostreason"
          :options="reasons"
          placeholder="Lost Reason is required"
          class="w-full"
          :rules="[isRequired]"
        />
      </x-field>
      <div class="text-right space-x-4 mt-4">
        <x-button type="submit">Continue</x-button>
        <x-button
          color="orange"
          @click.prevent="(showModal = false), (leadForm.isLostReason = false)"
          >Go Back</x-button
        >
      </div>
    </x-form>
  </x-modal>
</template>
