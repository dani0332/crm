<script setup>
defineProps({
  lobs: Array,
  request: Array,
});
const tableHeader = reactive([
  { text: 'Line Of Business', value: 'line_of_business' },
  { text: 'Bought Leads', value: 'bought_leads' },
  { text: 'Total Costs', value: 'total_cost' },
  { text: 'Requested Date', value: 'requested_date' },
]);

const table = ref({
  data: [],
  loading: false,
});

const maximumLeads = 5;
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
          :options="[]"
          filterable
        ></x-select>
      </x-field>
      <x-tooltip placement="top-left">
        <x-field label="Buy Leads" required>
          <x-input type="number" />
        </x-field>
        <template #tooltip>
          <div>
            You may request up to 5 leads per day for the selected Line Of
            Business.
          </div>
        </template>
      </x-tooltip>
      <x-field label="The total cost for the leads is:">
        <x-input type="number" disabled />
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
      <x-button size="md" color="primary" type="submit"> Submit </x-button>
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
  ></DataTable>
</template>
