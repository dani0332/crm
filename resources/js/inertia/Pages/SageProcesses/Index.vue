<script setup>
import { ref, computed, reactive, onMounted } from 'vue';
import { router, usePage, Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { SEND_UPDATE_LOG_MODEL_TYPE } from '@/inertia/Composables/useSageProcessRefId';
import SageFailedProcessEpRefIdCell from '@/inertia/Components/SageProcesses/SageFailedProcessEpRefIdCell.vue';
import SageFailedProcessRefIdCell from '@/inertia/Components/SageProcesses/SageFailedProcessRefIdCell.vue';

const { copy, copied } = useClipboard();

const props = defineProps({
  failedProcesses: Object,
  filters: Object,
  error: String,
  dropdowns: Object,
});

const page = usePage();
const notification = useToast();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

const loader = reactive({
  table: false,
  export: false,
});

// Table headers
const tableHeader = computed(() => [
  { text: 'Sage Log ID', value: 'id', width: 40, sortable: true },
  { text: 'REF ID', value: 'ref_id', width: 150, sortable: true },
  { text: 'SU Ref ID', value: 'su_ref_id', width: 150, sortable: true },
  { text: 'EP Ref ID', value: 'ep_ref_id', width: 150, sortable: true },
  {
    text: 'Lead Create Date',
    value: 'lead_create_date',
    width: 160,
    sortable: true,
  },
  { text: 'Policy Number', value: 'policy_number', width: 150, sortable: true },
  {
    text: 'Price Vat Applicable',
    value: 'price_vat_applicable',
    width: 150,
    sortable: true,
  },
  {
    text: 'Price Vat Not Applicable',
    value: 'price_vat_not_applicable',
    width: 150,
    sortable: true,
  },
  { text: 'Price Vat', value: 'price_vat', width: 150, sortable: true },
  { text: 'Discount', value: 'discount_value', width: 150, sortable: true },
  { text: 'Total Price', value: 'total_price', width: 150, sortable: true },
  {
    text: 'Commission (VAT applicable)',
    value: 'commission_vat_applicable',
    width: 150,
    sortable: true,
  },
  {
    text: 'Commission (VAT not applicable)',
    value: 'commission_vat_not_applicable',
    width: 150,
    sortable: true,
  },
  {
    text: 'Commission Vat',
    value: 'commission_vat',
    width: 150,
    sortable: true,
  },
  {
    text: 'Total Commission',
    value: 'total_commission',
    width: 150,
    sortable: true,
  },
  { text: 'Payment Date', value: 'payment_date', width: 150, sortable: true },
  {
    text: 'Payment Status',
    value: 'payment_status',
    width: 150,
    sortable: true,
  },
  { text: 'Provider', value: 'provider', width: 180, sortable: true },
  {
    text: 'Invoice Description',
    value: 'invoice_description',
    width: 180,
    sortable: true,
  },
  {
    text: 'Insurer Tax Invoice No.',
    value: 'insurer_tax_invoice',
    width: 180,
    sortable: true,
  },
  {
    text: 'Insurer Commission Tax Invoice No.',
    value: 'insurer_commission_tax_invoice',
    width: 180,
    sortable: true,
  },
  { text: 'Lead Status', value: 'lead_status', width: 180, sortable: true },
  {
    text: 'Sage Receipt ID',
    value: 'sage_receipt_id',
    width: 180,
    sortable: true,
  },
  {
    text: 'Sage API Status',
    value: 'sage_api_status',
    width: 60,
    sortable: true,
  },
  { text: 'Failed Sage API', value: 'failed_api', width: 120, sortable: false },
  {
    text: 'Failed API Error',
    value: 'failed_error',
    width: 330,
    sortable: false,
  },
  {
    text: 'Error displayed in IMCRM',
    value: 'imcrm_error',
    width: 330,
    sortable: false,
  },
  { text: 'Updated At', value: 'updated_at', width: 160, sortable: true },
]);

function formatLocalYmd(date) {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, '0');
  const d = String(date.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
}

function serializeFilterDate(value) {
  if (value === null || value === undefined || value === '') {
    return value;
  }
  if (value instanceof Date && !Number.isNaN(value.getTime())) {
    return formatLocalYmd(value);
  }
  return value;
}

const today = new Date();
const defaultDateFrom = formatLocalYmd(
  new Date(today.getFullYear(), today.getMonth(), 1),
);
const defaultDateTo = formatLocalYmd(
  new Date(today.getFullYear(), today.getMonth() + 1, 0),
);

// Available filters
const availableFilters = reactive({
  lead_status_filter:
    props.filters?.lead_status_filter || 'Policy Booking Failed',
  date_filter_type: props.filters?.date_filter_type || ['Lead Created Date'],
  date_from: props.filters?.date_from || defaultDateFrom,
  date_to: props.filters?.date_to || defaultDateTo,
  insurance_provider_id: props.filters?.insurance_provider_id || [],
  quote_type_id: props.filters?.quote_type_id || [],
  option: props.filters?.option || '',
  page: props.failedProcesses?.current_page || 1,
});

const copyToClipboard = item => {
  copy(item);
  if (copied)
    notification.success({
      title: 'Copied to clipboard!',
      position: 'top',
    });
};

// Table data
const tableData = computed(() => {
  return props.failedProcesses?.data || [];
});

const lineOfBusinessOptions = computed(() => {
  return (
    props.dropdowns?.quoteTypes?.map(qt => ({
      value: qt.id,
      label: qt.text,
    })) || []
  );
});

const insuranceProvidersOptions = computed(() => {
  return (
    props.dropdowns?.insuranceProviders?.map(ip => ({
      value: ip.id,
      label: ip.text,
    })) || []
  );
});

const optionsOptions = computed(() => {
  return (
    props.dropdowns?.options?.map(opt => ({
      value: opt.id,
      label: opt.text,
    })) || []
  );
});

const leadStatusFilterOptions = computed(() => {
  return (
    props.dropdowns?.leadStatusOptions?.map(opt => ({
      value: opt.id,
      label: opt.text,
    })) || []
  );
});

const dateFilterTypeOptions = [
  { value: 'Lead Created Date', label: 'Lead Created Date' },
  { value: 'Sage API Failure Date', label: 'Sage API Failure Date' },
];

// Filters count
const filtersCount = ref(0);

// Truncate text
const truncate = (text, length = 50) => {
  if (!text) return 'N/A';
  return text.length > length ? text.substring(0, length) + '...' : text;
};

// Calculate filters count and show errors on mount
onMounted(() => {
  filtersCount.value = Object.keys(availableFilters).length;

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
  filtersCount.value = Object.keys(availableFilters).length;
  availableFilters.page = 1;

  router.visit(route('sage-failed-processes.index'), {
    method: 'get',
    data: {
      ...availableFilters,
      date_from: serializeFilterDate(availableFilters.date_from),
      date_to: serializeFilterDate(availableFilters.date_to),
    },
    preserveState: true,
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onFinish: () => (loader.table = false),
  });
}

// Clear filters
function clearFilters() {
  availableFilters.lead_status_filter = 'Policy Booking Failed';
  availableFilters.date_filter_type = ['Lead Created Date'];
  availableFilters.date_from = null;
  availableFilters.date_to = null;
  availableFilters.insurance_provider_id = [];
  availableFilters.quote_type_id = [];
  availableFilters.option = '';
  filtersCount.value = 0;
  router.visit(route('sage-failed-processes.index'));
}

// Export to Excel
async function exportExcel() {
  try {
    loader.export = true;

    const exportURL = route('sage-failed-processes.export');

    // Make axios request with blob response type
    const response = await axios.get(exportURL, {
      params: {
        ...availableFilters,
        date_from: serializeFilterDate(availableFilters.date_from),
        date_to: serializeFilterDate(availableFilters.date_to),
      },
      responseType: 'blob',
    });

    // Create a blob from the response
    const blob = new Blob([response.data], { type: 'text/csv' });

    // Create download link
    const link = document.createElement('a');
    link.href = window.URL.createObjectURL(blob);

    // Generate filename with timestamp
    const filename = `sage-failed-processes-${new Date().toISOString().slice(0, 19).replace(/:/g, '-')}.csv`;
    link.download = filename;

    // Trigger download
    document.body.appendChild(link);
    link.click();

    // Cleanup
    document.body.removeChild(link);
    window.URL.revokeObjectURL(link.href);

    notification.success({
      title: 'Export completed successfully!',
      position: 'top',
    });
  } catch (error) {
    notification.error({
      title:
        error.response?.data?.message || 'Export failed. Please try again.',
      position: 'top',
    });
  } finally {
    loader.export = false;
  }
}
</script>

<template>
  <div>
    <Head>
      <title>Failed Sage Processes</title>
    </Head>
    <div class="flex justify-between items-center">
      <x-form @submit="onSubmit" :auto-focus="false" class="w-full mt-4 py-4">
        <div class="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
          <x-select
            v-model="availableFilters.lead_status_filter"
            label="Lead Status & Sage Failure"
            placeholder="Select Lead Status & Sage Failure"
            :options="leadStatusFilterOptions"
            filterable
            filterPlaceholder="Filter Lead Status...."
            clearable
          />

          <x-select
            v-model="availableFilters.date_filter_type"
            label="Filter Date By"
            placeholder="Select Date Filter Type"
            :options="dateFilterTypeOptions"
            filterable
            filterPlaceholder="Filter Date Type...."
            clearable
            multiple
          />

          <DatePicker
            v-model="availableFilters.date_from"
            name="date_from"
            label="Start Date"
          />

          <DatePicker
            v-model="availableFilters.date_to"
            name="date_to"
            label="End Date"
          />

          <x-select
            v-model="availableFilters.insurance_provider_id"
            label="Insurance Provider"
            placeholder="Select Provider"
            :options="insuranceProvidersOptions"
            filterable
            filterPlaceholder="Filter Provider...."
            clearable
            multiple
          />

          <x-select
            v-model="availableFilters.quote_type_id"
            label="Line of Business"
            placeholder="Select Line of Business"
            :options="lineOfBusinessOptions"
            filterable
            filterPlaceholder="Filter Line of Business...."
            clearable
            multiple
          />

          <x-select
            v-model="availableFilters.option"
            label="Option"
            placeholder="Select Option"
            :options="optionsOptions"
            filterable
            filterPlaceholder="Filter Option...."
            clearable
          />
        </div>

        <div class="flex justify-between gap-3 mb-4 mt-1">
          <div>
            <x-button
              v-if="can(permissionsEnum.SAGE_PROCESS_ISSUE_MANAGEMENT)"
              size="sm"
              color="emerald"
              class="justify-self-start mr-3"
              @click.prevent="exportExcel"
              :loading="loader.export"
            >
              Export
            </x-button>
          </div>
          <div class="flex gap-3 justify-self-end">
            <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
            <x-button size="sm" color="primary" @click.prevent="clearFilters">
              Reset
            </x-button>
          </div>
        </div>
      </x-form>
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
      <!-- REF ID Column - Main Lead -->
      <template #item-ref_id="item">
        <SageFailedProcessRefIdCell :item="item" />
      </template>

      <!-- SU Ref ID Column - Send Update -->
      <template #item-su_ref_id="item">
        <Link
          v-if="
            item.model_type === SEND_UPDATE_LOG_MODEL_TYPE && item.model?.uuid
          "
          :href="route('send-update.show', item.model.uuid)"
          class="text-primary-500 hover:underline"
        >
          <span>{{ item.model?.code }}</span>
        </Link>
        <span v-else>N/A</span>
      </template>

      <template #item-ep_ref_id="item">
        <SageFailedProcessEpRefIdCell :item="item" />
      </template>

      <!-- Lead Create Date Column -->
      <template #item-lead_create_date="item">
        <span>{{ item.model?.created_at || 'N/A' }}</span>
      </template>

      <!-- Policy Number Column -->
      <template #item-policy_number="item">
        <span>{{
          item.model?.policy_number ||
          item.model?.personal_quote?.policy_number ||
          'N/A'
        }}</span>
      </template>

      <!-- Price Vat Applicable -->
      <template #item-price_vat_applicable="item">
        <span>{{
          item.model?.payments?.[0]?.price_vat != 0
            ? item.model?.payments?.[0]?.price_vat_applicable
              ? item.model?.price_vat_applicable
              : item.model?.price_vat_applicable
            : 'N/A'
        }}</span>
      </template>

      <!-- Price Vat Not Applicable -->
      <template #item-price_vat_not_applicable="item">
        <span>{{
          item.model?.payments?.[0]?.price_vat == 0
            ? item.model?.payments?.[0]?.price_vat_applicable
              ? item.model?.price_vat_not_applicable
              : item.model?.price_vat_not_applicable
            : 'N/A'
        }}</span>
      </template>

      <!-- Price Vat -->
      <template #item-price_vat="item">
        <span>{{
          item.model?.payments?.[0]?.price_vat ||
          item.model?.total_vat_amount ||
          'N/A'
        }}</span>
      </template>

      <!-- Discount -->
      <template #item-discount_value="item">
        <span>{{
          item.model?.payments?.[0]?.discount_value ||
          item.model?.discount ||
          'N/A'
        }}</span>
      </template>

      <!-- Total Price -->
      <template #item-total_price="item">
        <span>{{
          item.model?.payments?.[0]?.total_price ||
          item.model?.price_with_vat ||
          'N/A'
        }}</span>
      </template>

      <!-- Commission Vat Applicable -->
      <template #item-commission_vat_applicable="item">
        <span>{{
          item.model?.payments?.[0]?.commission_vat_applicable ||
          item.model?.commission_vat_applicable ||
          'N/A'
        }}</span>
      </template>
      <!-- Commission Vat Applicable -->
      <template #item-commission_vat_not_applicable="item">
        <span>{{
          item.model?.payments?.[0]?.commission_vat_not_applicable ||
          item.model?.commission_vat_not_applicable ||
          'N/A'
        }}</span>
      </template>

      <!-- Commission Vat -->
      <template #item-commission_vat="item">
        <span>{{
          item.model?.payments?.[0]?.commission_vat ||
          item.model?.vat_on_commission ||
          'N/A'
        }}</span>
      </template>

      <!-- Total Commission -->
      <template #item-total_commission="item">
        <span>{{
          item.model?.payments?.[0]?.commission ||
          item.model?.total_commission ||
          'N/A'
        }}</span>
      </template>

      <!-- Payment Date -->
      <template #item-payment_date="item">
        <span>{{ item.model?.payments?.[0]?.captured_at ?? 'N/A' }}</span>
      </template>

      <!-- Payment Status -->
      <template #item-payment_status="item">
        <span>{{
          item.model?.payments?.[0]?.payment_status?.text || 'N/A'
        }}</span>
      </template>

      <!-- Provider -->
      <template #item-provider="item">
        <span>{{ item.insurance_provider?.text || 'N/A' }}</span>
      </template>

      <!-- Invoice Description -->
      <template #item-invoice_description="item">
        <span>{{
          item.model?.payments?.[0]?.invoice_description || 'N/A'
        }}</span>
      </template>

      <!-- Insurer Tax Invoice -->
      <template #item-insurer_tax_invoice="item">
        <span>{{
          item.model?.payments?.[0]?.insurer_tax_number || 'N/A'
        }}</span>
      </template>

      <!-- Insurer Commission Tax Invoice -->
      <template #item-insurer_commission_tax_invoice="item">
        <span>{{
          item.model?.payments?.[0]?.insurer_commmission_invoice_number || 'N/A'
        }}</span>
      </template>

      <!-- Lead Status -->
      <template #item-lead_status="item">
        <span>{{
          item.model?.quote_status?.text || item.model?.status || 'N/A'
        }}</span>
      </template>

      <!-- Sage Receipt ID -->
      <template #item-sage_receipt_id="item">
        <span class="whitespace-pre-line">{{
          item.model?.payments?.[0]?.collected_sage_receipt_ids
            ?.split(',')
            .map(s => s.trim())
            .filter(Boolean)
            .join(',\n') || 'N/A'
        }}</span>
      </template>

      <!-- Sage Status Column -->
      <template #item-sage_api_status="item">
        <span>{{
          item.sage_api_status ? item.sage_api_status.toUpperCase() : 'N/A'
        }}</span>
      </template>

      <!-- Failed Sage API Endpoint -->
      <template #item-failed_api="item">
        <div v-if="item.failed_api">
          {{ truncate(item.failed_api, 50) }}
          <x-icon
            @click.prevent="copyToClipboard(item.failed_api)"
            icon="copy"
            class="text-primary cursor-pointer"
            size="md"
          />
        </div>
        <div v-else>
          <span>N/A</span>
        </div>
      </template>

      <!-- Failed API Error -->
      <template #item-failed_error="item">
        <div v-if="item.failed_error">
          {{ truncate(item.failed_error, 80) }}
          <x-icon
            @click.prevent="copyToClipboard(item.failed_error)"
            icon="copy"
            class="text-primary cursor-pointer"
            size="md"
          />
        </div>
        <div v-else>
          <span>N/A</span>
        </div>
      </template>

      <!-- Error displayed in IMCRM Column -->
      <template #item-imcrm_error="item">
        <div v-if="item.imcrm_error">
          {{ truncate(item.imcrm_error, 80) }}
          <x-icon
            @click.prevent="copyToClipboard(item.imcrm_error)"
            icon="copy"
            class="text-primary cursor-pointer"
            size="md"
          />
        </div>
        <div v-else>
          <span>N/A</span>
        </div>
      </template>

      <!-- Updated At Column -->
      <template #item-updated_at="{ updated_at }">
        {{ updated_at || 'N/A' }}
      </template>
    </DataTable>

    <!-- Pagination -->
    <Pagination
      :links="{
        next: failedProcesses.next_page_url,
        prev: failedProcesses.prev_page_url,
        current: failedProcesses.current_page,
        from: failedProcesses.from,
        to: failedProcesses.to,
      }"
    />
  </div>
</template>

<style scoped>
/* Add any custom styles here */
</style>
