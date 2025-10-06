<script setup>
import { ref, computed, reactive, onMounted } from 'vue';
import { router, usePage, Head, Link } from '@inertiajs/vue3';

const props = defineProps({
  failedProcesses: Object,
  insuranceProviders: Array,
  quoteTypes: Array,
  users: Array,
  filters: Object,
  error: String,
});

const page = usePage();
const notification = useToast();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const filterModal = ref(false);
const cleanObj = obj => useCleanObj(obj);

const loader = reactive({
  table: false,
  export: false,
});

// Available filters
const availableFilters = reactive({
  insurance_provider_id: props.filters?.insurance_provider_id || [],
  quote_type_id: props.filters?.quote_type_id || [],
  date_from: props.filters?.date_from || '',
  date_to: props.filters?.date_to || '',
  quote_code: props.filters?.quote_code || '',
  user_id: props.filters?.user_id || [],
});

// Server options for pagination
const serverOptions = ref({
  page: props.failedProcesses?.current_page || 1,
  per_page: props.failedProcesses?.per_page || 15,
  sortBy: 'updated_at',
  sortType: 'desc',
});

// Table headers
const tableHeader = computed(() => [
  { text: 'Sage Process ID', value: 'id', width: 120, sortable: true },
  { text: 'Quote Code', value: 'quote_code', width: 150, sortable: true },
  { text: 'Quote Type', value: 'quote_type_name', width: 120, sortable: true },
  { text: 'Quote Status', value: 'quote_status', width: 180, sortable: true },
  { text: 'Insurance Provider', value: 'insurance_provider_name', width: 180, sortable: true },
  { text: 'User', value: 'user_name', width: 150, sortable: true },
  { text: 'Sage Request Type', value: 'sage_request_type', width: 180, sortable: true },
  { text: 'Sage Status', value: 'sage_api_status', width: 120, sortable: true },
  { text: 'Process Message', value: 'message', width: 250, sortable: false },
  { text: 'Updated At', value: 'updated_at', width: 160, sortable: true },
]);

// Table data
const tableData = computed(() => {
  return props.failedProcesses?.data || [];
});

// Pagination info
const paginationInfo = computed(() => ({
  from: props.failedProcesses?.from || 0,
  to: props.failedProcesses?.to || 0,
  total: props.failedProcesses?.total || 0,
  current_page: props.failedProcesses?.current_page || 1,
  last_page: props.failedProcesses?.last_page || 1,
}));

// Filters count
const filtersCount = ref(0);

// Format date
const dateFormat = dateString =>
  dateString
    ? useDateFormat(useConvertDate(dateString), 'DD-MM-YYYY HH:mm:ss').value
    : '';

// Get detail page route
const getDetailPageRoute = (uuid, quote_type_id, business_type_of_insurance_id) => 
  useGetShowPageRoute(uuid, quote_type_id, business_type_of_insurance_id);

// Truncate text
const truncate = (text, length = 50) => {
  if (!text) return 'N/A';
  return text.length > length ? text.substring(0, length) + '...' : text;
};

// Calculate filters count and show errors on mount
onMounted(() => {
  const filtersCleaned = cleanObj(availableFilters);
  filtersCount.value = Object.keys(filtersCleaned).length;
  
  // Show error notification if any
  if (props.error) {
    notification.error({
      title: props.error,
      position: 'top',
    });
  }
});

// Submit filter
function onSubmit() {
  const filtersCleaned = cleanObj(availableFilters);
  filtersCount.value = Object.keys(filtersCleaned).length;
  serverOptions.value.page = 1;
  filterModal.value = false;

  router.visit(route('sage-failed-processes.index'), {
    method: 'get',
    data: {
      ...filtersCleaned,
      ...serverOptions.value,
    },
    preserveState: true,
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onFinish: () => (loader.table = false),
  });
}

// Clear filters
function clearFilters() {
  availableFilters.insurance_provider_id = [];
  availableFilters.quote_type_id = [];
  availableFilters.date_from = '';
  availableFilters.date_to = '';
  availableFilters.quote_code = '';
  availableFilters.user_id = [];
  filtersCount.value = 0;
}

// Export to Excel
function exportExcel() {
  const url = new URL(window.location.href);
  const exportFilters = url.search;
  const exportURL = '/sage-processes/failed/export' + exportFilters;

  window.open(exportURL, '_blank');
}

// Update server options
function updateServerOptions(newOptions) {
  serverOptions.value = { ...serverOptions.value, ...newOptions };
  
  router.visit(route('sage-failed-processes.index'), {
    method: 'get',
    data: {
      ...cleanObj(availableFilters),
      ...serverOptions.value,
    },
    preserveState: true,
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onFinish: () => (loader.table = false),
  });
}

// Handle pagination change
function onPageChange(page) {
  updateServerOptions({ page });
}

// Handle sorting
function onSort(column, sortType) {
  updateServerOptions({ sortBy: column, sortType });
}
</script>

<template>
  <div>
    <Head :title="'Failed Sage Processes'" />
    <div class="flex justify-between items-center">
      <x-tooltip placement="bottom">
        <h2
          class="font-semibold text-gray-800 text-xl underline decoration-dotted decoration-primary-600"
        >
          Failed Sage Processes
        </h2>
        <template #tooltip>
          Displays failed sage processes along with related quote/send update entries and sage api logs
        </template>
      </x-tooltip>
      <div class="space-x-3">
        <x-tooltip v-if="can(permissionsEnum.VIEW_SAGE_API_LOGS) && tableData.length === 0" placement="bottom">
          <x-button
            disabled
            size="sm"
            color="emerald"
            :loading="loader.export"
          >
            Export to Excel
          </x-button>
          <template #tooltip>
            No data available to export. Apply filters to see results.
          </template>
        </x-tooltip>
        
        <x-button
          v-if="can(permissionsEnum.VIEW_SAGE_API_LOGS) && tableData.length > 0"
          size="sm"
          color="emerald"
          :loading="loader.export"
          @click="exportExcel()"
        >
          Export to Excel
        </x-button>
        
        <x-button
          size="sm"
          color="orange"
          @click.prevent="filterModal = true"
          :loading="loader.table"
        >
          <x-icon
            icon="magnifyingGlass"
            size="sm"
            class="transition transform duration-300"
          />
          Search Filters
          <x-badge v-if="filtersCount > 0" color="danger" size="sm" class="ml-2">
            {{ filtersCount }}
          </x-badge>
        </x-button>
      </div>
    </div>
    <x-divider class="my-4" />

    <!-- Data Table -->
    <DataTable
      table-class-name="table-fixed"
      :headers="tableHeader"
      :loading="loader.table"
      :items="tableData || []"
      border-cell
      hide-rows-per-page
      hide-footer
      fixed-header
      :table-height="600"
    >
      <!-- Quote Code Column -->
      <template #item-quote_code="{ quote_code, quote_uuid, quote_type_id }">
        <Link
          v-if="quote_uuid && quote_type_id"
          :href="getDetailPageRoute(quote_uuid, quote_type_id, null)"
          class="text-primary-600 hover:text-primary-700 font-medium underline"
        >
          {{ quote_code || 'N/A' }}
        </Link>
        <span v-else>{{ quote_code || 'N/A' }}</span>
      </template>

      <!-- Quote Type Column -->
      <template #item-quote_type_name="{ quote_type_name }">
        <x-badge :color="quote_type_name ? 'primary' : 'gray'" size="sm">
          {{ quote_type_name || 'N/A' }}
        </x-badge>
      </template>

      <!-- Quote Status Column -->
      <template #item-quote_status="{ quote_status }">
        <x-badge color="danger" size="sm">
          {{ quote_status || 'N/A' }}
        </x-badge>
      </template>

      <!-- Sage Status Column -->
      <template #item-sage_api_status="{ sage_api_status }">
        <x-badge
          :color="sage_api_status === 'fail' ? 'danger' : 'gray'"
          size="sm"
        >
          {{ sage_api_status ? sage_api_status.toUpperCase() : 'N/A' }}
        </x-badge>
      </template>

      <!-- Process Message Column -->
      <template #item-message="{ message }">
        <x-tooltip v-if="message && message.length > 50" placement="top">
          <span>{{ truncate(message, 50) }}</span>
          <template #tooltip>
            <div class="max-w-md whitespace-pre-wrap">{{ message }}</div>
          </template>
        </x-tooltip>
        <span v-else>{{ message || 'N/A' }}</span>
      </template>

      <!-- Updated At Column -->
      <template #item-updated_at="{ updated_at }">
        {{ dateFormat(updated_at) }}
      </template>
    </DataTable>

    <!-- Pagination -->
    <div v-if="tableData.length > 0" class="mt-4 flex justify-between items-center">
      <div class="text-sm text-gray-600">
        Showing {{ paginationInfo.from }} to {{ paginationInfo.to }} of {{ paginationInfo.total }} entries
      </div>
      <x-pagination
        :current-page="paginationInfo.current_page"
        :total-pages="paginationInfo.last_page"
        @page-change="onPageChange"
      />
    </div>

    <!-- Empty State -->
    <div v-if="tableData.length === 0 && !loader.table" class="text-center py-12">
      <x-icon icon="documentMagnifyingGlass" size="xl" class="text-gray-400 mb-4" />
      <p class="text-gray-600 text-lg">No failed sage processes found</p>
      <p class="text-gray-500 text-sm mt-2">Try adjusting your search filters</p>
    </div>
 
  </div>
</template>

<style scoped>
/* Add any custom styles here */
</style>

