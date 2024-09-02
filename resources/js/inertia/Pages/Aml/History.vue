<script setup>
defineProps({
  url: String,
  sanctionListDownloads: Object,
});

const notification = useNotifications('toast');
const page = usePage();
const loader = reactive({
  table: false,
});

const serverItemsLength = ref(0);

const tableHeader = [
  { text: 'AML Id', value: 'id' },
  { text: 'FILE NAME', value: 'file_name' },
  { text: 'SOURCE', value: 'source', sortable: true },
  { text: 'TOTAL RECORDS', value: 'total_records' },
  { text: 'IS PROCESSED', value: 'is_processed' },
  { text: 'CREATED AT', value: 'created_at' },
  { text: 'UPDATED AT', value: 'updated_at', sortable: true },
];

const serverOptions = ref({
  sortBy: 'created_at',
  sortType: 'desc',
});

const filtersOpen = ref(true);

const filters = reactive({
  file_name: '',
  is_processed: '',
});

const filterData = () => {
  loader.table = true;
  const url = page.url;
  const params = {
    ...serverOptions.value,
    ...filters,
  };

  router.visit(url, {
    method: 'get',
    data: params,
    preserveState: true,
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onFinish: () => (loader.table = false),
  });
};

const onReset = () => {
  router.visit('/kyc/aml/download/history', {
    method: 'get',
    preserveState: true,
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onFinish: () => (loader.table = false),
  });
};

watch(
  () => serverOptions.value,
  () => {
    filterData();
  },
  { deep: true },
);

onMounted(() => {});
</script>

<template>
  <div>
    <Head title="AML Download History" />

    <div class="flex justify-between items-center flex-wrap gap-2 mb-5">
      <h2 class="text-xl font-semibold">AML Download History</h2>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="flex flex-row">
        <div class="grid grid-cols-6 gap-4">
          <div class="col-start-1 col-end-3">
            <button
              type="button"
              class="inline-flex justify-center w-full rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600"
              @click="filtersOpen = !filtersOpen"
            >
              Filters

              <svg
                class="-mr-1 ml-2 h-5 w-5"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"
                ></path>
              </svg>
            </button>
          </div>
        </div>
      </div>
      <x-form v-if="filtersOpen" @submit="filterData">
        <div class="grid grid-cols-4 gap-4 mt-4">
          <div>
            <x-input
              class="w-full"
              label="File Name"
              v-model="filters.file_name"
            />
          </div>
          <div>
            <ComboBox
              label="Is Processed"
              placeholder="Search by Lead Status"
              v-model="filters.is_processed"
              class="w-full"
              :single="true"
              :options="[
                { label: 'All', value: '' },
                { label: 'Yes', value: '1' },
                { label: 'No', value: '0' },
              ]"
            />
          </div>
        </div>
        <div class="flex justify-end gap-3 mb-4">
          <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
          <x-button size="sm" color="primary" @click.prevent="onReset">
            Reset
          </x-button>
        </div>
      </x-form>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-white">
      <DataTable
        table-class-name="tablefixed"
        v-model:server-options="serverOptions"
        :headers="tableHeader"
        :loading="loader.table"
        :items="sanctionListDownloads.data || []"
        :server-items-length="serverItemsLength"
        border-cell
        hide-rows-per-page
        hide-footer
      >
        <template #item-file_name="{ file_name }">
          <a
            :href="url + '/' + file_name"
            title="Download File"
            target="_blank"
            download
            v-if="file_name"
          >
            <span class="text-blue-500 hover:text-blue-700 cursor-pointer">
              {{ file_name }}
            </span>
          </a>
          <span v-else> NULL </span>
        </template>

        <template #item-total_records="{ total_records }">
          <span>
            {{ total_records.toLocaleString() }}
          </span>
        </template>

        <template #item-is_processed="{ is_processed }">
          <span v-if="is_processed" class="inline-block h-5 w-5 text-green-500">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="inline-block h-5 w-5 text-green-500"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
              ></path>
            </svg>
          </span>
          <span v-else class="inline-block h-5 w-5 text-red-500">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="inline-block h-5 w-5 text-red-500"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"
              ></path>
            </svg>
          </span>
        </template>
      </DataTable>
      <Pagination
        :links="{
          next: sanctionListDownloads.next_page_url,
          prev: sanctionListDownloads.prev_page_url,
          current: sanctionListDownloads.current_page,
          from: sanctionListDownloads.from,
          to: sanctionListDownloads.to,
        }"
      />
    </div>
  </div>
</template>
