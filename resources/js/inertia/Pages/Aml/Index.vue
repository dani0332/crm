<script setup>
import { ref } from 'vue';
import LeadAssignment from '../PersonalQuote/Partials/LeadAssignment';

defineProps({
  aml: Object,
  quoteTypes: Array,
});

const page = usePage();
const loader = reactive({
  table: false,
  export: false,
});

let availableFilters = {
  quoteType: null,
  searchType: '',
  searchField: '',
  matchFound: '',
  amlCreatedStartDate: '',
  amlCreatedEndDate: '',
  page: 1,
};

const filters = reactive(availableFilters);

function onSubmit(isValid) {
  if (isValid) {
    filters.page = 1;

    Object.keys(filters).forEach(
      key =>
        (filters[key] === '' || filters[key].length === 0) &&
        delete filters[key],
    );

    router.visit('/kyc/aml/', {
      method: 'get',
      data: filters,
      preserveState: true,
      preserveScroll: true,
      onBefore: () => (loader.table = true),
      onSuccess: () => (loader.table = false),
    });
  } else {
    console.log('Invalid');
  }
}

function setQueryStringFilters() {
  let queryString = window.location.search;
  let urlParams = new URLSearchParams(queryString);

  for (const [key] of Object.entries(availableFilters)) {
    if (urlParams.has(key)) {
      filters[key] = urlParams.get(key);
    }
  }
}

onMounted(() => {
  setQueryStringFilters();
});
const tableHeader = [
  { text: 'AML Id', value: 'id' },
  { text: 'Quote Type', value: 'quote_type_text' },
  { text: 'CDB Id', value: 'cdb_id' },
  { text: 'Input', value: 'input' },
  { text: 'Screenshot', value: 'screenshot' },
  { text: 'Created At', value: 'created_at' },
  { text: 'Updated At', value: 'updated_at' },
];
</script>

<template>
  <div>
    <Head title="AMl" />
    <!--   filters     -->
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
        <x-select
          v-model="filters.quoteType"
          label="Quote Type"
          placeholder=""
          :options="
            quoteTypes.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
        />
        <x-select
          v-model="filters.searchType"
          label="Search By"
          placeholder=""
          :options="[
            { value: 'cdbId', label: 'CDB ID' },
            { value: 'customerEmail', label: 'Customer Email' },
            { value: 'id', label: 'AML ID' },
          ]"
          class="w-full"
        />
        <x-input
          v-model="filters.searchField"
          type="Search Value"
          name="code"
          label="Search Value"
          class="w-full"
          placeholder="Search Value"
        />

        <x-select
          v-model="filters.matchFound"
          label="Match found"
          placeholder=""
          :options="[
            { value: 0, label: 'False' },
            { value: 1, label: 'True' },
          ]"
          class="w-full"
        />
        <DatePicker
          v-model="filters.amlCreatedStartDate"
          name="created_at_end"
          label="Created Date Start"
          class="w-full"
        />
        <DatePicker
          v-model="filters.amlCreatedEndDate"
          name="created_at_end"
          label="Created Date End"
          class="w-full"
        />
      </div>
      <div class="flex justify-end gap-3 mb-4">
        <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
        <x-button size="sm" color="primary" @click.prevent="onReset">
          Reset
        </x-button>
      </div>
    </x-form>

    <DataTable
      v-model:items-selected="quotesSelected"
      table-class-name="tablefixed"
      :headers="tableHeader"
      :loading="loader.table"
      :items="aml.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
      fixed-checkbox
    >
      <template #item-id="{ code, id }">
        <Link :href="`/kyc/aml/${id}`" class="text-primary-500 hover:underline">
          {{ id }}
        </Link>
      </template>

      <template #item-screenshot="{ screenshot }">
        <img :src="screenshot" alt="IMCRM" class="w-6" />
      </template>
    </DataTable>
  </div>
</template>
