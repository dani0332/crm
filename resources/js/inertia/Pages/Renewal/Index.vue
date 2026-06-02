<script setup>
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
  previous_policy_expiry_date_start: '',
  previous_policy_expiry_date_end: '',
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

function handleExport() {
  let queryParams = window.location.search;
  window.open('/renewals/search/export' + queryParams, '_blank');
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
});
const source_type_list = [
  { text: 'All', value: '' },
  { text: 'Renewal', value: 'Renewal_upload' },
];
const tableHeader = [
  { text: 'Ref ID', value: 'code' },
  { text: 'Customer ID', value: 'customer_id' },
  { text: 'PRODUCT', value: 'advisor' },
  { text: 'CURRENTLY INSURED WITH', value: 'currently_insured_with' },
  { text: 'POLICY START DATE', value: 'policy_start_date' },
  { text: 'POLICY EXPIRY DATE', value: 'policy_expiry_date' },
  { text: 'GROSS PREMIUM', value: 'premium' },
  { text: 'Lead Level PC Tag', value: 'pc_qualified' },
  { text: 'Nationality', value: 'nationality.text' },
  { text: 'Customer Level PC Tag', value: 'customer_pcp_tag' },
];
const tableHeader2 = [
  { text: 'Ref ID', value: 'code' },
  { text: 'Customer ID', value: 'customer_id' },
  { text: 'PRODUCT', value: 'advisor' },
  { text: 'CURRENTLY INSURED WITH', value: 'currently_insured_with' },
  { text: 'PREVIOUS POLICY NUMBER', value: 'previous_quote_policy_number' },
  { text: 'PREVIOUS POLICY START DATE', value: 'previous_policy_start_date' },
  { text: 'PREVIOUS POLICY EXPIRY DATE', value: 'previous_policy_expiry_date' },
  {
    text: 'Previous Total Price with VAT',
    value: 'previous_quote_policy_premium',
  },
  { text: 'Lead Level PC Tag', value: 'pc_qualified' },
  { text: 'Nationality', value: 'nationality.text' },
  { text: 'Customer Level PC Tag', value: 'customer_pcp_tag' },
];

const businessHeaders = [
  { text: 'Ref ID', value: 'code' },
  { text: 'Customer ID', value: 'customer_id' },
  { text: 'PRODUCT', value: 'advisor' },
  { text: 'SUB TYPE', value: 'subtype' },
  { text: 'CURRENTLY INSURED WITH', value: 'currently_insured_with' },
  { text: 'PREVIOUS POLICY NUMBER', value: 'previous_quote_policy_number' },
  { text: 'POLICY START DATE', value: 'previous_policy_start_date' },
  { text: 'POLICY EXPIRY DATE', value: 'previous_policy_expiry_date' },
  { text: 'GROSS PREMIUM', value: 'previous_quote_policy_premium' },
  { text: 'Lead Level PC Tag', value: 'pc_qualified' },
  { text: 'Nationality', value: 'nationality.text' },
  { text: 'Customer Level PC Tag', value: 'customer_pcp_tag' },
];

const cqfProductIds = [1, 2, 6, 7, 9, 10, 18];

const activeHeaders = computed(() => {
  const product = Number(filters.product);
  if (product === 5) return businessHeaders;
  if (cqfProductIds.includes(product)) return tableHeader2;
  return tableHeader;
});

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
          v-model="filters.previous_policy_expiry_date_start"
          name="previous_policy_expiry_date_start"
          label="Previous Policy Expiry Date Start"
        />
        <DatePicker
          v-model="filters.previous_policy_expiry_date_end"
          name="previous_policy_expiry_date_end"
          label="Previous Policy Expiry End"
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
          label="Previous Policy Number"
          class="w-full"
          placeholder="Search by Policy Number"
        />
      </div>
      <div class="flex justify-between items-center mb-4">
        <div>
          <x-button
            v-if="can(permissionsEnum.EXPORT_NO_CONTACTINFO)"
            id="export_link"
            size="sm"
            color="emerald"
            @click="handleExport"
          >
            Export
          </x-button>
        </div>
        <div class="flex gap-3">
          <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
          <x-button size="sm" color="primary" @click.prevent="onReset">
            Reset
          </x-button>
        </div>
      </div>
    </x-form>

    <DataTable
      table-class-name="tablefixed"
      :loading="loader.table"
      :headers="activeHeaders"
      :items="quotes.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #item-code="{ code }">
        {{ code }}
      </template>

      <template #item-advisor>
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
      <template #item-currently_insured_with="item">
        {{
          (item.currently_insured_with?.text ?? item.currently_insured_with) ??
          item.personal_quote?.currently_insured_with?.text
        }}
      </template>
      <template #item-pc_qualified="{ pc_qualified }">
        {{ pc_qualified == 1 ? 'Yes' : 'No' }}
      </template>
      <template #item-customer_pcp_tag="{ customer }">
        {{ customer?.pcp_tag == 1 ? 'Yes' : 'No' }}
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
