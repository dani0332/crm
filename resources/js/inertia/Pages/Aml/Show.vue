<script setup>
defineProps({
  aml: Object,
  amlResults: Array,
});

const loader = reactive({
  table: false,
});
const tableHeader = [
  { text: 'ID', value: 'id' },
  { text: 'FIRST NAME', value: 'firstName' },
  { text: 'LAST NAME', value: 'lastName' },
  { text: 'Alias', value: 'alias' },
  { text: 'Gender', value: 'gender' },
  { text: 'DOB', value: 'dob' },
  { text: 'YOB Match', value: 'created_at' },
  { text: 'POB', value: 'pob' },
  { text: 'Nationality', value: 'nationality' },
  { text: 'Nationality Match', value: '' },
  { text: 'Source', value: 'source' },
  { text: 'Created At', value: 'createdAt' },
];
</script>

<template>
  <div>
    <Head title="AML" />

    <div class="flex justify-between items-center flex-wrap gap-2 mb-5">
      <h2 class="text-xl font-semibold">AML</h2>
      <div class="flex gap-2">
        <Link href="/kyc/aml" preserve-scroll>
          <x-button size="sm" color="primary" tag="div">AMl </x-button>
        </Link>
      </div>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">AML Id</dt>
            <dd>{{ aml.id }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Quote Type</dt>
            <dd>{{ aml.quote_type_text }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Quote Request ID</dt>
            <Link
              :href="`${aml.quote_type_id}/details/${aml.quote_request_id}`"
              class="text-primary-500 hover:underline"
            >
              {{ aml.quote_request_id }}
            </Link>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Input</dt>
            <dd>{{ aml.input }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Created At</dt>
            <dd>{{ aml.created_at }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Updated At</dt>
            <dd>{{ aml.updated_at }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Results Found</dt>
            <dd>{{ aml.results_found }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Search Type</dt>
            <dd>{{ aml.search_type }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Screenshot</dt>
            <dd>
              <img :src="aml.screenshot" alt="IMCRM" class="w-auto" />
            </dd>
          </div>
        </dl>
      </div>
      <div class="mt-6">
        <h3 class="font-semibold text-primary-800">Results</h3>
        <x-divider class="mb-4 mt-1" />
      </div>

      <DataTable
        v-model:items-selected="quotesSelected"
        table-class-name="tablefixed"
        :headers="tableHeader"
        :loading="loader.table"
        :items="amlResults || []"
        border-cell
        hide-rows-per-page
        hide-footer
        fixed-checkbox
      >
      </DataTable>
    </div>
  </div>
</template>
