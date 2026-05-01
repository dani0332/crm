<script setup>
import { setQueryStringFilters } from '../../Composables/utilities.js';

const page = usePage();

defineProps({
  embeddedProduct: Object,
  ep_enums: Object,
  sync_statuses: Object,
  sage_statuses: Array,
  payment_statuses: Array,
  policy_statuses: Array,
  reportLobFilterOptions: {
    type: Array,
    default: () => [],
  },
});

const dateFormat = date =>
  date ? useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value : '-';
const params = useUrlSearchParams('history');
const serverOptions = ref({
  page: 1,
  sortBy: 'id',
  sortType: 'desc',
});
const notification = useToast();

const loader = reactive({
  table: false,
  export: false,
});

const filters = reactive({
  ref_id: '',
  certificate_number: '',
  name: '',
  date_of_purchase: '',
  chassis_number: '',
  sync_status: 'all',
  months: '',
  ep_payment_status: [],
  ep_api_status: [],
  ep_sage_status: [],
  tax_invoice_no: '',
  tax_invoice_buyer_no: '',
  lob: [],
});

const getLink = (quote_uuid, quote_type_id, ref_id) =>
  buildCdbidLink(quote_uuid, quote_type_id, ref_id);

const tableHeader = [
  { text: 'EP Ref-ID', value: 'ref_id' },
  { text: 'Line of Business', value: 'lob' },
  { text: 'Advisor Name', value: 'advisor_name' },
  { text: 'Date of Issuance', value: 'payment_date', sortable: true },
  { text: 'Plan Commencement Date', value: 'plan_start_date' },
  { text: 'Plan End Date', value: 'plan_end_date' },
  { text: 'Full Name', value: 'name' },
  { text: 'EMIRATES ID NUMBER', value: 'emirates_id_number' },
  { text: 'DOB', value: 'dob' },
  { text: 'AGE', value: 'age' },
  { text: 'PASSPORT', value: 'passport_number' },
  { text: 'Sync Status', value: 'sync_status', sortable: true },
  { text: 'NATIONALITY', value: 'nationality' },
  { text: 'Vehicle', value: 'vehicle' },
  { text: 'Contribution Amount', value: 'contribution_amount', sortable: true },
  { text: 'Policy Issue Status', value: 'status' },

  { text: 'EP Payment Status', value: 'ep_payment_status' },
  { text: 'EP API Status', value: 'ep_api_status' },
  { text: 'EP Sage Status', value: 'ep_sage_status' },

  { text: 'Certificate Number', value: 'certificate_number' },
  { text: 'Tax Invoice Number', value: 'tax_invoice_no' },
  { text: 'Tax Invoice Raised by Buyer Number', value: 'tax_invoice_buyer_no' },
  { text: 'Model Year', value: 'model_year' },
  { text: 'Make', value: 'make' },
  { text: 'Model', value: 'model' },
  { text: 'Chassis Number', value: 'chassis_number' },
  { text: 'Excess Amount', value: 'excess_amount' },
];

function resetFilters() {
  for (const key in filters) {
    if (Array.isArray(filters[key])) {
      filters[key] = [];
    } else if (key === 'sync_status') {
      filters[key] = 'all';
    } else {
      filters[key] = '';
    }
  }
  router.visit(
    route(
      'embedded-products.reports.certificates',
      page.props.embeddedProduct.detail.id,
    ),
    {
      method: 'get',
      data: { page: 1 },
      preserveState: true,
      preserveScroll: true,
      onFinish: () => {
        loader.table = false;
      },
      onBefore: () => {
        loader.table = true;
      },
    },
  );
}

function filterTransactions(isValid) {
  if (!isValid) {
    return;
  }

  serverOptions.value.page = 1;

  for (const key in filters) {
    if (filters[key] === '') {
      delete filters[key];
    }
    if (Array.isArray(filters[key]) && filters[key].length === 0) {
      delete filters[key];
    }
  }

  router.visit(
    route(
      'embedded-products.reports.certificates',
      page.props.embeddedProduct.detail.id,
    ),
    {
      method: 'get',
      data: {
        ...filters,
        ...serverOptions.value,
      },
      preserveState: true,
      preserveScroll: true,
      onFinish: () => {
        loader.table = false;
      },
      onBefore: () => {
        loader.table = true;
      },
    },
  );
}

function exportReport() {
  const filteredData = Object.fromEntries(
    Object.entries(filters).filter(
      ([, value]) =>
        value !== null &&
        value !== '' &&
        !(Array.isArray(value) && value.length === 0),
    ),
  );
  const data = useObjToUrl(filteredData);
  const url = route(
    'embedded-products.reports.certificates.export',
    page.props.embeddedProduct.detail.id,
  );
  window.open(url + '?' + new URLSearchParams(data).toString());
}

const syncingRecords = ref([]);

function reSync(code) {
  if (confirm('Are you sure ?')) {
    syncingRecords.value = [...syncingRecords.value, code];
    axios
      .post(route('embedded-products.courier.re-sync', code), {})
      .then(res => {
        if (syncingRecords.value.length <= 1 && res?.data?.ok) {
          router.get(
            route(
              'embedded-products.reports.certificates',
              page.props.embeddedProduct.detail.id,
            ),
            {
              replace: true,
              preserveScroll: true,
              preserveState: true,
            },
          );
        }
        if (res?.data?.ok) {
          notification.success({
            title: res?.data?.message,
            position: 'top',
          });
        } else {
          notification.warning({
            title: res?.data?.message,
            position: 'top',
          });
        }
      })
      .catch(err => {
        notification.error({
          title: err?.message,
          position: 'top',
        });
      })
      .finally(() => {
        syncingRecords.value = syncingRecords.value.filter(
          record => record !== code,
        );
      });
  }
}

onMounted(() => {
  setQueryStringFilters(
    {
      ...params,
      ...serverOptions.value,
    },
    filters,
  );
});

watch(
  serverOptions,
  value => {
    filterTransactions(true);
  },
  { deep: true },
);

const filteredHeaders = computed(() => {
  return tableHeader.filter(header => {
    if (header.value === 'lob') {
      return (page.props.reportLobFilterOptions?.length ?? 0) > 0;
    }

    if (header.value === 'sync_status') {
      return (
        page.props.embeddedProduct.detail.short_code ===
        page.props.ep_enums.COURIER
      );
    }

    // Show Tax Invoice columns only for MDX, RDX, and ECB
    if (
      header.value === 'tax_invoice_no' ||
      header.value === 'tax_invoice_buyer_no'
    ) {
      return [
        page.props.ep_enums.ECB,
        page.props.ep_enums.RDX,
        page.props.ep_enums.MDX,
      ].includes(page.props.embeddedProduct.detail.short_code);
    }

    if (
      page.props.embeddedProduct.detail.short_code === page.props.ep_enums.ECB
    ) {
      let excludeHeaders = [
        'advisor_name',
        'dob',
        'age',
        'passport_number',
        'nationality',
        'vehicle',
      ];
      return !excludeHeaders.includes(header.value);
    } else if (
      ![
        page.props.ep_enums.ECB,
        page.props.ep_enums.RDX,
        page.props.ep_enums.MDX,
      ].includes(page.props.embeddedProduct.detail.short_code)
    ) {
      /** exclude these columns (from filtering out) if its -- NOT -- from ECB,RDX,MDX */
      const excludeHeaders = ['ep_api_status', 'ep_sage_status'];

      if (
        page.props.embeddedProduct.detail.short_code !==
        page.props.ep_enums.COURIER
      ) {
        /** only exclude this column (from filtering out) for ECB,RDX,MDX AND COURIER */
        excludeHeaders.push('ep_payment_status');
      }

      return !excludeHeaders.includes(header.value);
    } else {
      let excludeHeaders = [
        'model_year',
        'make',
        'model',
        'chassis_number',
        'excess_amount',
      ];
      return !excludeHeaders.includes(header.value);
    }

    return true;
  });
});

const showCertificateNumberFilter = computed(() => {
  const reports = [
    page.props.ep_enums.ECB,
    page.props.ep_enums.RDX,
    page.props.ep_enums.MDX,
  ];

  if (reports.includes(page.props.embeddedProduct.detail.short_code)) {
    return true;
  }
  return false;
});

const showEpPaymentStatusFilter = (function () {
  const reports = [
    page.props.ep_enums.ECB,
    page.props.ep_enums.RDX,
    page.props.ep_enums.MDX,
    page.props.ep_enums.COURIER,
  ];

  return reports.includes(page.props.embeddedProduct.detail.short_code);
})();

const showEpApiStatusFilter = (function () {
  const reports = [
    page.props.ep_enums.ECB,
    page.props.ep_enums.RDX,
    page.props.ep_enums.MDX,
  ];

  return reports.includes(page.props.embeddedProduct.detail.short_code);
})();

const showEpSageStatusFilter = (function () {
  const reports = [
    page.props.ep_enums.ECB,
    page.props.ep_enums.RDX,
    page.props.ep_enums.MDX,
  ];

  return reports.includes(page.props.embeddedProduct.detail.short_code);
})();
</script>

<template>
  <div>
    <Head title="Reports" />
    <nav class="mb-4">
      <ol class="flex gap-1">
        <li>
          <Link
            :href="route('embedded-products.index')"
            class="text-sm border-b text-gray-500"
            ><span> Embedded Products </span></Link
          >
        </li>
        <li><span class="text-gray-400">/</span></li>
        <li>
          <Link
            :href="route('embedded-products.reports')"
            class="text-sm border-b text-gray-500"
            ><span> Reports </span></Link
          >
        </li>
        <li><span class="text-gray-400">/</span></li>
        <li>
          <span class="text-sm font-semibold">
            {{ embeddedProduct.detail.product_name }}
          </span>
        </li>
      </ol>
    </nav>
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        {{ embeddedProduct.detail.product_name }}
      </h2>
      <x-button size="sm" color="#ff5e00" @click.prevent="exportReport">
        Export Report XLS
      </x-button>
    </div>

    <x-divider class="my-4" />
    <x-form @submit="filterTransactions" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
        <div>
          <x-tooltip placement="bottom">
            <label
              class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
            >
              Ref-ID
            </label>
            <template #tooltip> Reference ID </template>
          </x-tooltip>
          <x-input
            v-model="filters.ref_id"
            type="search"
            name="ref_id"
            class="w-full"
            placeholder="Search by Ref-ID"
          />
        </div>
        <div v-if="(page.props.reportLobFilterOptions?.length ?? 0) > 0">
          <x-tooltip placement="bottom">
            <label
              class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
            >
              Line of Business
            </label>
            <template #tooltip>
              Line of Business for the underlying policy
            </template>
          </x-tooltip>
          <x-select
            v-model="filters.lob"
            placeholder="Filter by Line of Business"
            :options="page.props.reportLobFilterOptions"
            deselect-all
            filterable
            filterPlaceholder="Filter Line of Business...."
            class="w-full"
            multiple
            truncate
          >
            <template #content-footer>
              <ui-select-actions
                @select-all="
                  filters.lob = page.props.reportLobFilterOptions.map(
                    o => o.value,
                  )
                "
                @clear="filters.lob = []"
              />
            </template>
          </x-select>
        </div>
        <div v-if="showCertificateNumberFilter">
          <x-tooltip placement="bottom">
            <label
              class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
            >
              EP policy number
            </label>
            <template #tooltip> EP policy number </template>
          </x-tooltip>
          <x-input
            v-model="filters.certificate_number"
            type="search"
            name="certificate_number"
            class="w-full"
            placeholder="Search by EP policy number"
          />
        </div>

        <div v-if="embeddedProduct.detail.short_code === ep_enums.ECB">
          <x-tooltip placement="bottom">
            <label
              class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
            >
              Chassis Number
            </label>
          </x-tooltip>
          <x-input
            v-model="filters.chassis_number"
            type="search"
            name="chassis_number"
            class="w-full"
            placeholder="Chassis Number"
          />
        </div>
        <div>
          <x-input
            v-model="filters.name"
            type="search"
            name="last_name"
            class="w-full"
            placeholder="Type here"
            label="Name"
          />
        </div>

        <div>
          <x-tooltip placement="bottom">
            <label
              class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
            >
              Date of issuance
            </label>
            <template #tooltip>
              This is the date on which the EP product was issued and sent to
              the client by the system
            </template>
          </x-tooltip>
          <DatePicker
            v-model="filters.date_of_purchase"
            name="date_of_purchase"
            class="w-full"
            model-type="yyyy-MM-dd"
            range
            max-range="30"
          />
        </div>
        <div>
          <x-tooltip placement="bottom">
            <label
              class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
            >
              Months
            </label>
            <template #tooltip>
              This is the month in which the EP product was issued to the client
            </template>
          </x-tooltip>
          <DatePicker
            v-model="filters.months"
            name="months"
            placeholder="Select month"
            class="w-full"
            month-picker
            model-type="yyyy-MM"
            format="MM-yyyy"
          />
        </div>
        <div v-if="embeddedProduct.detail.short_code === ep_enums.COURIER">
          <x-select
            v-model="filters.sync_status"
            placeholder="Select Sync Status"
            :options="sync_statuses"
            class="w-full"
            filterable
            filterPlaceholder="Filter Sync Status...."
            label="Sync Status"
          />
        </div>

        <div v-if="showEpPaymentStatusFilter">
          <x-select
            v-model="filters.ep_payment_status"
            placeholder="Select EP Payment Status"
            :options="payment_statuses"
            class="w-full"
            multiple
            truncate
            filterable
            filterPlaceholder="Filter EP Payment Status...."
            label="EP Payment Status"
          />
        </div>

        <div v-if="showEpApiStatusFilter">
          <x-select
            v-model="filters.ep_api_status"
            placeholder="Select EP API Status"
            :options="policy_statuses"
            class="w-full"
            multiple
            truncate
            filterable
            filterPlaceholder="Filter EP API Status...."
            label="EP API Status"
          />
        </div>

        <div v-if="showEpSageStatusFilter">
          <x-select
            v-model="filters.ep_sage_status"
            placeholder="Select EP Sage Status"
            :options="sage_statuses"
            class="w-full"
            multiple
            truncate
            filterable
            filterPlaceholder="Filter EP Sage Status...."
            label="EP Sage Status"
          />
        </div>

        <div
          v-if="
            [ep_enums.ECB, ep_enums.RDX, ep_enums.MDX].includes(
              embeddedProduct.detail.short_code,
            )
          "
        >
          <x-tooltip placement="bottom">
            <label
              class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
            >
              Tax Invoice Number
            </label>
            <template #tooltip> Tax Invoice Number </template>
          </x-tooltip>
          <x-input
            v-model="filters.tax_invoice_no"
            type="search"
            name="tax_invoice_no"
            class="w-full"
            placeholder="Search by Tax Invoice Number"
          />
        </div>

        <div
          v-if="
            [ep_enums.ECB, ep_enums.RDX, ep_enums.MDX].includes(
              embeddedProduct.detail.short_code,
            )
          "
        >
          <x-tooltip placement="bottom">
            <label
              class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
            >
              Tax Invoice Raised by Buyer Number
            </label>
            <template #tooltip> Tax Invoice Raised by Buyer Number </template>
          </x-tooltip>
          <x-input
            v-model="filters.tax_invoice_buyer_no"
            type="search"
            name="tax_invoice_buyer_no"
            class="w-full"
            placeholder="Search by Tax Invoice Raised by Buyer Number"
          />
        </div>
      </div>
      <div class="flex flex-row-reverse gap-3">
        <div class="flex justify-self-end gap-3">
          <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
          <x-button size="sm" color="primary" @click.prevent="resetFilters">
            Reset
          </x-button>
        </div>
      </div>
    </x-form>

    <x-divider class="my-4" />

    <DataTable
      v-model:server-options="serverOptions"
      table-class-name=""
      :headers="filteredHeaders"
      :loading="loader.table"
      :items="embeddedProduct.transactions.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #item-id="{ id }">
        <Link
          :href="route('embedded-products.edit', id)"
          class="text-primary-500 hover:underline"
        >
          {{ id }}
        </Link>
      </template>

      <template
        #item-ref_id="item"
        v-if="
          [ep_enums.ECB, ep_enums.MDX, ep_enums.RDX, ep_enums.COURIER].includes(
            embeddedProduct.detail.short_code,
          )
        "
      >
        <SanitizeHtml
          v-if="item.quote_request"
          :html="
            getLink(item.quote_request?.uuid, item.quote_type_id, item.ref_id)
          "
          class="text-primary-500 hover:underline"
          :key="item.ref_id"
        />
      </template>

      <template #item-sync_status="item">
        <x-tooltip>
          <label class="border-b-2 border-dotted border-black uppercase">{{
            item?.sync_status?.status
          }}</label>
          <template #tooltip>
            <span class="custom-tooltip-content">{{
              item?.sync_status?.message
            }}</span>
          </template>
        </x-tooltip>
        <button class="ml-3" title="Sync" @click="reSync(item.ref_id)">
          <x-spinner
            v-if="
              syncingRecords.includes(item.ref_id) &&
              item?.sync_status?.is_syncable
            "
            size="sm"
            class="text-primary"
          />
          <x-icon v-else icon="reset" color="green" size="sm" />
        </button>
      </template>

      <template #item-company_name="{ insurance_provider }">
        {{ insurance_provider?.text }}
      </template>

      <template #item-is_active="{ is_active }">
        <x-icon
          :icon="is_active ? 'true' : 'false'"
          :color="is_active ? 'green' : 'red'"
          size="lg"
        />
      </template>

      <template #item-updated_at="{ updated_at }">
        <div class="text-sm text-center">{{ dateFormat(updated_at) }}</div>
      </template>

      <template #item-actions="{ id }">
        <div class="flex gap-1.5 justify-end">
          <Link :href="route('embedded-products.reports.certificates', id)">
            <x-button color="primary" size="xs" outlined> View </x-button>
          </Link>
        </div>
      </template>
    </DataTable>

    <Pagination
      :links="{
        next: embeddedProduct.transactions.next_page_url,
        prev: embeddedProduct.transactions.prev_page_url,
        current: embeddedProduct.transactions.current_page,
        from: embeddedProduct.transactions.from,
        to: embeddedProduct.transactions.to,
      }"
    />
  </div>
</template>
