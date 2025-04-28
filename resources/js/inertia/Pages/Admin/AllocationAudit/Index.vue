<script setup>
const props = defineProps({
  audits: Object,
  quoteTypes: Object,
  assignmentTypes: Object,
});

const formatDate = date =>
  date ? useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value : '-';

const { isRequired } = useRules();
const params = useUrlSearchParams('history');
const serverOptions = ref({
  page: 1,
  rowsPerPage: 15,
  sortBy: 'created_at',
  sortType: 'desc',
});

const loader = reactive({
  cards: false,
});

const filters = reactive({
  quote_type: '',
  uuid: '',
});

const tableHeader = [
  {
    text: 'Quote Type',
    value: 'quote_type_id',
    sortable: true,
  },
  {
    text: 'Assignment Type',
    value: 'assignment_type',
    sortable: true,
  },
  {
    text: 'Advisor ID',
    value: 'advisor_id',
    sortable: true,
  },
  {
    text: 'Action By ID',
    value: 'action_by_id',
    sortable: true,
  },
  {
    text: 'Created At',
    value: 'created_at',
    sortable: true,
  },
  {
    text: 'Actions',
    value: 'actions',
    align: 'right',
  },
];

function resetFilters() {
  for (const key in filters) {
    filters[key] = '';
  }

  router.visit(route('admin.allocation-audit.index'), {
    method: 'get',
    data: { page: 1 },
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loader.cards = false;
    },
    onBefore: () => {
      loader.cards = true;
    },
  });
}

function search(isValid) {
  if (!isValid) {
    return;
  }

  serverOptions.value.page = 1;

  for (const key in filters) {
    if (filters[key] === '') {
      delete filters[key];
    }
  }

  router.visit(route('admin.allocation-audit.index'), {
    method: 'get',
    data: {
      ...filters,
      ...serverOptions.value,
    },
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loader.cards = false;
    },
    onBefore: () => {
      loader.cards = true;
    },
  });
}

function setQueryFilters() {
  for (const [key] of Object.entries(params)) {
    if (key.includes('[]')) {
      filters[key.substring(0, key.length - 2)] = params[key];
    } else {
      filters[key] = params[key];
    }
  }
}

onMounted(() => {
  setQueryFilters();
});

watch(
  serverOptions,
  value => {
    search(true);
  },
  { deep: true },
);
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <Head title="Allocation Audit" />

      <!-- Header Section -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Allocation Audit</h1>
        <p class="mt-2 text-sm text-gray-600">
          Track and monitor allocation changes across the system
        </p>
      </div>

      <!-- Search Card -->
      <div
        class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8 transition-all duration-200 hover:shadow-md"
      >
        <h2 class="text-xl font-semibold text-gray-900 mb-6 flex items-center">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-6 w-6 mr-2 text-primary-600"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
            />
          </svg>
          Search Filters
        </h2>

        <x-form @submit="search" :auto-focus="false">
          <div class="grid sm:grid-cols-2 gap-6">
            <x-field label="Quote Type" :rules="[isRequired]">
              <x-select
                v-model="filters.quote_type"
                placeholder="Select Quote Type"
                :options="props.quoteTypes"
                class="w-full rounded-lg"
              />
            </x-field>
            <x-field
              :label="(filters.quote_type || '') + ' UUID'"
              :rules="[isRequired]"
            >
              <x-input
                v-model="filters.uuid"
                type="search"
                class="w-full rounded-lg"
                :placeholder="'Type ' + (filters.quote_type || '') + ' UUID'"
              />
            </x-field>
          </div>
          <div class="flex justify-end gap-4 mt-8">
            <x-button
              size="sm"
              color="primary"
              @click.prevent="resetFilters"
              class="px-6 py-2.5 rounded-lg transition-all duration-200 hover:shadow-md"
            >
              Reset
            </x-button>
            <x-button
              size="sm"
              color="#ff5e00"
              type="submit"
              class="px-6 py-2.5 rounded-lg transition-all duration-200 hover:shadow-md"
            >
              Search
            </x-button>
          </div>
        </x-form>
      </div>

      <!-- Empty State -->
      <div
        class="text-center"
        v-if="!loader.cards && !props.audits?.data?.length"
      >
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12">
          <div class="flex flex-col items-center">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-16 w-16 text-gray-400 mb-4"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
            <div class="text-gray-500 text-lg font-medium">
              No Records Available
            </div>
            <p class="text-gray-400 mt-2">Try adjusting your search criteria</p>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <x-loader
        v-if="loader.cards"
        label="Loading"
        status="active"
        class="flex justify-center"
      />

      <!-- Results Table -->
      <div
        v-if="!loader.cards && props.audits?.data?.length > 0"
        class="space-y-6"
      >
        <div
          class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden"
        >
          <DataTable
            table-class-name="tablefixed"
            :headers="tableHeader"
            :items="props.audits.data"
            :loading="loader.cards"
            border-cell
            hide-rows-per-page
            hide-footer
            class="rounded-lg"
          >
            <template #item-created_at="{ created_at }">
              <span class="text-sm text-gray-600">{{
                formatDate(created_at)
              }}</span>
            </template>
            <template #item-actions>
              <div class="flex justify-end">
                <x-button
                  size="xs"
                  color="primary"
                  class="hover:bg-primary-600 transition-all duration-200 rounded-lg px-4 py-2"
                >
                  View Details
                </x-button>
              </div>
            </template>
          </DataTable>
        </div>

        <!-- Pagination -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
          <Pagination
            :links="{
              next: props.audits.next_page_url,
              prev: props.audits.prev_page_url,
              current: props.audits.current_page,
              from: props.audits.from,
              to: props.audits.to,
            }"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.tablefixed {
  @apply w-full;
}

.tablefixed :deep(th) {
  @apply bg-gray-50 text-gray-700 font-medium text-sm py-4 px-6;
}

.tablefixed :deep(td) {
  @apply py-4 px-6 text-sm text-gray-600 border-t border-gray-100;
}

.tablefixed :deep(tr:hover) {
  @apply bg-gray-50;
}
</style>
