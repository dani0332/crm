<script setup>
import { ref, computed, reactive, onMounted } from 'vue';
import { router, usePage, Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
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
  { text: 'Sage Pro. ID', value: 'id', width: 40, sortable: true },
  { text: 'Quote Code', value: 'quote_code', width: 150, sortable: true },
  { text: 'Policy Number', value: 'policy_number', width: 150, sortable: true },
  {
    text: 'Price Vat Applicable',
    value: 'price_vat_applicable',
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
  { text: 'Sage Proc. Status', value: 'status', width: 60, sortable: true },
  { text: 'Failed Sage API', value: 'failed_api', width: 120, sortable: false },
  {
    text: 'Failed API Error',
    value: 'failed_error',
    width: 330,
    sortable: false,
  },
  { text: 'Updated At', value: 'updated_at', width: 160, sortable: true },
]);

// Available filters
const availableFilters = reactive({
  insurance_provider_id: props.filters?.insurance_provider_id || [],
  quote_type_id: props.filters?.quote_type_id || [],
  date_from: props.filters?.date_from || '',
  date_to: props.filters?.date_to || '',
  page: props.failedProcesses?.current_page || 1,
});

const copyToClipboard = item => {
  console.log(item);
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

// Filters count
const filtersCount = ref(0);

// Format date
const dateFormat = dateString =>
  dateString
    ? useDateFormat(useConvertDate(dateString), 'DD-MM-YYYY HH:mm:ss').value
    : '';

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
  availableFilters.date_from = null;
  availableFilters.date_to = null;
  filtersCount.value = 0;
  router.visit(route('sage-failed-processes.index'));
}

function getDetailPageRoute(item) {
  const sageRequest = JSON.parse(item.request);
  console.log(sageRequest.sagePayload.quoteTypeId);
  let quoteTypeId = sageRequest.sagePayload.quoteTypeId;
  if (item.model?.status) {
    return route('send-update.show', item.model?.uuid);
  } else {
    return useGetShowPageRoute(
      item.model?.uuid,
      quoteTypeId,
      item.model?.business_type_of_insurance_id,
    );
  }
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
    console.error('Export error:', error);
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
    <Head :title="'Failed Sage Processes'" />
    <div class="flex justify-between items-center">
      <x-form @submit="onSubmit" :auto-focus="false" class="w-full mt-4 py-4">
        <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
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

          <DatePicker
            v-model="availableFilters.date_from"
            name="created_at_start"
            label="Created Date Start"
          />

          <DatePicker
            v-model="availableFilters.date_to"
            name="created_at_end"
            label="Created Date End"
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
      <!-- Quote Code Column -->
      <template #item-quote_code="item">
        <Link
          :href="getDetailPageRoute(item)"
          class="text-primary-500 hover:underline"
        >
          <span>{{ item.model?.code }}</span>
        </Link>
      </template>

      <!-- Policy Number Column -->
      <template #item-policy_number="item">
        <span>{{ item.model?.policy_number || 'N/A' }}</span>
      </template>

      <!-- Price Vat Applicable -->
      <template #item-price_vat_applicable="item">
        <span>{{
          item.model?.payments?.[0]?.price_vat_applicable || 'N/A'
        }}</span>
      </template>

      <!-- Price Vat -->
      <template #item-price_vat="item">
        <span>{{ item.model?.payments?.[0]?.price_vat || 'N/A' }}</span>
      </template>

      <!-- Discount -->
      <template #item-discount_value="item">
        <span>{{ item.model?.payments?.[0]?.discount_value || 'N/A' }}</span>
      </template>

      <!-- Total Price -->
      <template #item-total_price="item">
        <span>{{ item.model?.payments?.[0]?.total_price || 'N/A' }}</span>
      </template>

      <!-- Commission Vat Applicable -->
      <template #item-commission_vat_applicable="item">
        <span>{{
          item.model?.payments?.[0]?.commission_vat_applicable ||
          item.model?.payments?.[0]?.commission_vat_not_applicable ||
          'N/A'
        }}</span>
      </template>

      <!-- Commission Vat -->
      <template #item-commission_vat="item">
        <span>{{ item.model?.payments?.[0]?.commission_vat || 'N/A' }}</span>
      </template>

      <!-- Total Commission -->
      <template #item-total_commission="item">
        <span>{{ item.model?.payments?.[0]?.commission || 'N/A' }}</span>
      </template>

      <!-- Payment Date -->
      <template #item-payment_date="item">
        <span>{{ dateFormat(item.model?.payments?.[0]?.captured_at) }}</span>
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
        <span>{{ item.collected_sage_receipt_ids || 'N/A' }}</span>
      </template>

      <!-- Sage Status Column -->
      <template #item-status="{ status }">
        <span>{{ status ? status.toUpperCase() : 'N/A' }}</span>
      </template>

      <!-- Failed Sage API Endpoint -->
      <template #item-failed_api="item">
        <div v-if="item.model?.sage_api_logs?.[0]?.sage_end_point">
          {{ truncate(item.model?.sage_api_logs[0]?.sage_end_point, 50) }}
          <x-icon
            @click.prevent="
              copyToClipboard(item.model?.sage_api_logs[0]?.sage_end_point)
            "
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
        <div v-if="item.model?.sage_api_logs?.[0]?.response">
          {{ truncate(item.model?.sage_api_logs[0]?.response, 80) }}
          <x-icon
            @click.prevent="
              copyToClipboard(item.model?.sage_api_logs[0]?.response)
            "
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
        {{ dateFormat(updated_at) }}
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
