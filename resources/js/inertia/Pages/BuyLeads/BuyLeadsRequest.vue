<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
  lobs: Array,
  requests: Array,
});

const notification = useToast();
const { isRequired } = useRules();

const maximumLeads = ref(0);
const maximumValue = ref(1);
const formatted = date => useDateFormat(date, 'YYYY-MM-DD HH:mm:ss').value;

const calculateMaximumCost = computed(() => {
  return requestForm.count * maximumValue.value;
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
  { text: 'Total Cost', value: 'value_cost_per_lead' },
  { text: 'Requested Date', value: 'created_at' },
]);

const table = ref({
  data: [],
  loading: false,
});

const onSubmit = isValid => {
  if (isValid) {
    table.value.loading = true;
    requestForm.submit('post', route('buy-leads.request.submit'), {
      onError: errors => {
        Object.keys(errors).forEach(function (key) {
          notification.error({
            title: errors[key],
            position: 'top',
          });
        });
      },
      onSuccess: response => {
        notification.success({
          title: 'Buy Lead Configuration updated successfully',
          position: 'top',
        });
        table.value.loading = false;
      },
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
      let { maxCapacity, value } = response.data;
      maximumLeads.value = maxCapacity;
      maximumValue.value = value;
      table.value.loading = false;
    })
    .catch(error => {
      notification.error({
        title: 'failed to fetch maximum leads',
        position: 'top',
      });
      table.value.loading = false;
    });
};
</script>
<template>
  <Head title="Buy Lead Configuration" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Buy Leads Configuration</h2>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-3 gap-4">
      <x-field label="Line Of Business" required>
        <x-select
          placeholder="Select Line Of Business"
          :options="lobs"
          filterable
          v-model="requestForm.quote_type"
          :rules="[isRequired]"
          @update:modelValue="requestForm.count = null"
        ></x-select>
      </x-field>
      <x-tooltip placement="top-left">
        <x-field label="Buy Leads" required>
          <x-input
            :disabled="requestForm.quote_type == null || maximumLeads == 0"
            v-model="requestForm.count"
            type="number"
            :max="maximumLeads"
            :min="0"
            @keypress="
              $event => {
                // Prevent input if already 1 digit
                if ($event.target.value.length >= 1) {
                  $event.preventDefault();
                  return;
                }

                // Get the key pressed
                const key = String.fromCharCode($event.keyCode);
                const value = parseInt(key);

                // Check if value is within range
                if (value < 0 || value > maximumLeads) {
                  $event.preventDefault();
                }
              }
            "
            :rules="[isRequired]"
            :loading="table.loading"
          />
        </x-field>
        <template #tooltip>
          <div>
            You may request up to {{ maximumLeads }} leads per day for the
            selected Line Of Business.
          </div>
        </template>
      </x-tooltip>
      <x-field label="The total cost for the leads is:">
        <x-input disabled v-model="calculateMaximumCost" />
      </x-field>
    </div>
    <div>
      <p class="text-red-500 font-bold">Note:</p>
      <ul class="list-disc px-5">
        <li>
          Please check on the submit button to initiate you buy leads request
        </li>
        <li>
          The total cost for the requested leads will be displayed once the "Buy
          Leads" dropdown is selected
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
        color="primary"
        type="submit"
        :loading="table.loading"
        :disabled="maximumLeads == 0"
      >
        Submit
      </x-button>
    </div>
  </x-form>
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
