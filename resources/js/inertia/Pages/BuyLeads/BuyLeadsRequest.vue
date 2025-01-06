<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
  lobs: Array,
  requests: Array,
});

const notification = useToast();
const { isRequired } = useRules();

const maximumLeads = ref(0);
const perLeadCost = ref(1);
const formatted = date => useDateFormat(date, 'YYYY-MM-DD HH:mm:ss').value;
const maxLeadsModal = ref(false);
const isRequestAlreadySubmittedModal = ref(false);
const isRequestAlreadySubmitted = ref(false);

const calculateMaximumCost = computed(() => {
  return requestForm.count * perLeadCost.value;
});

const requestForm = useForm({
  quote_type: '',
  count: '',
  total_cost: '',
  requested_date: '',
});

const tableHeader = reactive([
  { text: 'Line Of Business', value: 'quote_type.code' },
  { text: 'Bought Leads', value: 'requested_count' },
  { text: 'Allocated Leads', value: 'allocated_count' },
  { text: 'Total Cost', value: 'total_cost' },
  { text: 'Requested Date', value: 'created_at' },
]);

const table = ref({
  data: [],
  loading: false,
});

const onSubmit = isValid => {
  if (isValid) {
    table.value.loading = true;
    axios
      .post(route('buy-leads.request.submit'), {
        quote_type: requestForm.quote_type,
        count: requestForm.count,
      })
      .then(response => {
        notification.success({
          title: 'Buy Lead Request submitted successfully',
          position: 'top',
        });
        requestForm.count = null;
        requestForm.quote_type = null;
        router.reload({
          preserveScroll: true,
          preserveState: true,
        });
        table.value.loading = false;
      })
      .catch(errors => {
        table.value.loading = false;
        if (errors.response.status === 422) {
          notification.error({
            title: errors.response.data.message,
            position: 'top',
          });
        } else {
          Object.keys(errors).forEach(function (key) {
            notification.error({
              title: errors[key],
              position: 'top',
            });
          });
        }
      });
  }
};

watch(
  () => requestForm.quote_type,
  value => {
    if (value) {
      fetchMaximumLeads();
    }
  },
);
const fetchMaximumLeads = () => {
  table.value.loading = true;
  axios
    .post(route('buy-leads.rate.fetch'), {
      quote_type: requestForm.quote_type,
    })
    .then(response => {
      let { maxCapacity, cost, isMaxCapReached, requestAlreadySubmitted } =
        response.data;
      isRequestAlreadySubmittedModal.value = requestAlreadySubmitted;
      isRequestAlreadySubmitted.value = requestAlreadySubmitted;
      maximumLeads.value = maxCapacity;
      perLeadCost.value = cost;
      table.value.loading = false;
      if (isMaxCapReached || maxCapacity == 0) {
        maxLeadsModal.value = true;
      }
    })
    .catch(error => {
      notification.error({
        title: 'failed to fetch maximum leads',
        position: 'top',
      });
      table.value.loading = false;
    });
};

const validateMaximumLeads = () => {
  if (requestForm.count > maximumLeads.value) {
    return 'You have exceeded the maximum leads';
  } else {
    return true;
  }
};

const maxLeadsOptions = computed(() => {
  return Array.from({ length: maximumLeads.value }, (_, i) => i + 1).map(i => ({
    label: i,
    value: i,
  }));
});
</script>
<template>
  <Head title="Buy Lead" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Buy Lead</h2>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 gap-3">
      <x-field label="Line Of Business" required>
        <x-select
          placeholder="Select LOB"
          :options="lobs"
          filterable
          v-model="requestForm.quote_type"
          :rules="[isRequired]"
          @update:modelValue="requestForm.count = null"
        ></x-select>
      </x-field>
      <div class="flex items-center gap-4">
        <x-tooltip placement="top-left">
          <x-field label="Buy Leads" required>
            <x-select
              :disabled="
                requestForm.quote_type == null ||
                maximumLeads == 0 ||
                isRequestAlreadySubmitted ||
                table.loading
              "
              v-model="requestForm.count"
              placeholder="Select the number of leads to buy"
              :rules="[isRequired, validateMaximumLeads]"
              :options="maxLeadsOptions"
              :loading="table.loading"
            >
            </x-select>
          </x-field>
          <template #tooltip>
            <div>
              You may request up to {{ maximumLeads }} leads per day for the
              selected Line of Business.
            </div>
          </template>
        </x-tooltip>
        <p
          class="text-vtd-secondary-400"
          v-if="requestForm.quote_type && !table.loading"
        >
          Up to {{ maximumLeads }} Leads Max
        </p>
      </div>
    </div>
    <div class="grid sm:grid-cols-2 gap-3">
      <div class="grid sm:grid-cols-2 items-center">
        <p>The total cost for the requested leads is:</p>
        <x-input disabled v-model="calculateMaximumCost" class="!mb-0">
          <template #suffix>
            <div
              class="absolute inset-y-0 right-2 my-auto mr-2 inline h-5 w-5 shrink-0 select-none text-secondary-400"
            >
              AED
            </div>
          </template>
        </x-input>
      </div>
    </div>
    <div>
      <p class="text-red-500 font-bold">Note:</p>
      <ul class="list-disc px-5">
        <li>
          Please click on the submit button to initiate your buy leads request.
        </li>
        <li>
          The total cost for the requested leads will be displayed once the "buy
          leads" dropdown is selected.
        </li>
        <li>
          There is no guarantee that you will receive the requested leads, as
          the system will assign the leads accordingly once the buy lead request
          is submitted by the advisor.
        </li>
      </ul>
    </div>
    <x-divider class="my-4" />
    <div class="flex justify-end gap-3 mb-4">
      <x-button
        size="md"
        color="orange"
        type="submit"
        :loading="table.loading"
        :disabled="isRequestAlreadySubmitted || maximumLeads == 0"
      >
        Submit
      </x-button>
    </div>
  </x-form>
  <x-modal
    v-model="maxLeadsModal"
    title="Buy Leads"
    show-close
    backdrop
    size="lg"
  >
    <div class="text-red-500">
      Max cap for Buy Lead requests reached. Please submit your next request on
      the following day.
    </div>
  </x-modal>
  <x-modal
    v-model="isRequestAlreadySubmittedModal"
    title="Buy Leads"
    show-close
    backdrop
    size="lg"
  >
    <div class="text-red-500">
      You can initiate a new Buy Lead request once the existing requested leads
      are assigned.
    </div>
  </x-modal>
  <DataTable
    table-class-name="mt-4"
    :loading="table.loader"
    :headers="tableHeader"
    :items="requests.data || []"
    border-cell
    hide-rows-per-page
    hide-footer
  >
    <template #item-created_at="{ created_at }">
      <span>
        {{ created_at ? formatted(created_at) : 'N/A' }}
      </span>
    </template>
  </DataTable>
  <Pagination
    :links="{
      next: props.requests.next_page_url,
      prev: props.requests.prev_page_url,
      current: props.requests.current_page,
      from: props.requests.from,
      to: props.requests.to,
    }"
  />
</template>
