<script setup>
import LeadAssignment from '../PersonalQuote/Partials/LeadAssignment';

const notification = useToast();

defineProps({
  quotes: Object,
  quoteStatuses: Array,
  advisors: Array,
  products: Array,
});
const { isRequired } = useRules();
const quoteType = 'car';

const advisorOptions = computed(() => {
  return page.props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.name,
  }));
});

const dateFormat = date => {
  return date ? useDateFormat(date, 'DD-MM-YYYY').value : '-';
};

const page = usePage();
const loader = reactive({
  table: false,
  export: false,
});

let availableFilters = {
  code: '',
  first_name: '',
  last_name: '',
  email: '',
  previous_quote_policy_number: '',
  source: 'Renewal_upload',
  product: '',
  expiry_date: '',
  page: 1,
  policy_expiry_date_start: '',
  policy_expiry_date_end: '',
  mobile_no: '',
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

    router.visit('/renewals/search', {
      method: 'get',
      data: filters,
      preserveState: true,
      preserveScroll: true,
      onBefore: () => (loader.table = true),
      onSuccess: () => {
        setExportStrings();
        loader.table = false;
      },
    });
  } else {
    notification.error({
      title: 'Error while fetching quotes. Please try again',
      position: 'top',
    });
  }
}

function onReset() {
  router.visit('/renewals/search', {
    method: 'get',
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
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

function setExportStrings() {
  let queryParams = window.location.search;
  let exportLink = document.getElementById('export_link');
  if (exportLink != null) {
    exportLink.href = '/renewals/search/export' + queryParams;
  }
}

function getProductName(id) {
  let businessName = '';
  page.props.products.map(item => {
    if (item.id == id) {
      businessName = item.text;
    }
  });

  return businessName;
}
onMounted(() => {
  setQueryStringFilters();
  setExportStrings();
});
const source_type_list = [
  { text: 'All', value: '' },
  { text: 'Renewal', value: 'Renewal_upload' },
];
const tableHeader = [
  { text: 'Ref ID', value: 'code' },
  { text: 'PRODUCT', value: 'advisor' },
  { text: 'CURRENTLY INSURED WITH', value: 'currently_insured_with' },
  { text: 'POLICY START DATE', value: 'policy_start_date' },
  { text: 'POLICY EXPIRY DATE', value: 'policy_expiry_date' },
  { text: 'GROSS PREMIUM', value: 'premium' },
];
const tableHeader2 = [
  { text: 'Ref ID', value: 'code' },
  { text: 'PRODUCT', value: 'advisor' },
  { text: 'CURRENTLY INSURED WITH', value: 'currently_insured_with' },
  { text: 'POLICY START DATE', value: 'policy_start_date' },
  { text: 'POLICY EXPIRY DATE', value: 'policy_expiry_date' },
  { text: 'GROSS PREMIUM', value: 'premium' },
];

const businessHeaders = [
  { text: 'Ref ID', value: 'code' },
  { text: 'PRODUCT', value: 'advisor' },
  { text: 'SUB TYPE', value: 'subtype' },
  { text: 'CURRENTLY INSURED WITH', value: 'currently_insured_with' },
  { text: 'POLICY START DATE', value: 'policy_start_date' },
  { text: 'POLICY EXPIRY DATE', value: 'policy_expiry_date' },
  { text: 'GROSS PREMIUM', value: 'premium' },
];

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
</script>

<template>
  <div>
    <Head title="Search" />

    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Search</h2>
    </div>
    <x-divider class="my-4" />

    <!--   filters     -->
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
        <DatePicker
          v-model="filters.policy_expiry_date_start"
          name="policy_expiry_date_start"
          label="Policy Expiry Date Start"
        />
        <DatePicker
          v-model="filters.policy_expiry_date_end"
          name="policy_expiry_date_end"
          label="Renewal Expiry End Date"
        />

        <x-select
          v-model="filters.product"
          label="Products"
          name="product"
          :options="
            products.map(item => ({
              value: item.id.toString(),
              label: item.text,
            }))
          "
          placeholder="Select Product"
          required
          class="w-full"
          :rules="[isRequired]"
        />
        <x-input
          v-model="filters.code"
          type="search"
          name="code"
          label="Ref ID"
          class="w-full"
          placeholder="Search by Ref ID"
        />
      </div>
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
        <x-input
          v-model="filters.email"
          type="search"
          name="email"
          label="Email"
          class="w-full"
          placeholder="Search by Email"
        />
        <x-input
          v-model="filters.mobile_no"
          type="search"
          name="mobile_no"
          label="Phone Number"
          class="w-full"
          placeholder="Search by Phone Number"
        />
        <x-input
          v-model="filters.previous_quote_policy_number"
          type="search"
          name="previous_quote_policy_number"
          label="Policy Number"
          class="w-full"
          placeholder="Search by Policy Number"
        />
      </div>
      <div class="flex justify-end gap-3 mb-4">
        <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
        <x-button size="sm" color="primary" @click.prevent="onReset">
          Reset
        </x-button>
      </div>
    </x-form>
    <div
      class="flex justify-end gap-3 mb-4 mt-4"
      v-if="can(permissionsEnum.EXPORT_NO_CONTACTINFO)"
    >
      <a
        id="export_link"
        target="_blank"
        class="border appearance-none rounded-md shadow-sm py-2 text-sm px-4 cursor-pointer"
        href=""
        size="sm"
        color="emerald"
      >
        Export
      </a>
    </div>

    <DataTable
      table-class-name="tablefixed"
      :loading="loader.table"
      :headers="
        filters.product == 1
          ? tableHeader2
          : filters.product == 5
            ? businessHeaders
            : tableHeader
      "
      :items="quotes.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #item-code="{ code, uuid }">
        {{ code }}
      </template>

      <template #item-advisor="item">
        {{ getProductName(filters.product) }}
      </template>
      <template #item-subtype="item">
        {{
          item.business_type_of_insurance
            ? item.business_type_of_insurance.code
            : 'N/A'
        }}
      </template>
      subtype
      <template #item-insurance_provider="{ insurance_provider }">
        {{ insurance_provider?.text }}
      </template>

      <template #item-car_type_insurance_id="{ car_type_insurance_id }">
        {{ car_type_insurance_id?.text }}
      </template>

      <template #item-nationality="{ nationality }">
        {{ nationality?.text }}
      </template>
      <template #item-currently_insured_with="{ currently_insured_with }">
        {{
          currently_insured_with?.text
            ? currently_insured_with.text
            : currently_insured_with
        }}
      </template>
    </DataTable>

    <Pagination
      v-if="quotes.total > 0"
      :links="{
        next: quotes.next_page_url,
        prev: quotes.prev_page_url,
        current: quotes.current_page,
        from: quotes.from,
        to: quotes.to,
        total: quotes.total,
      }"
    />
  </div>
</template>
