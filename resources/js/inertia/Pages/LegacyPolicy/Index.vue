<script setup>
defineProps({
  policies: Array,
  legacyPolicyMapping: Array,
  coveragePolicyMapping: Array,
});

const { isRequired, emptyOrNumericAndNoSpecialChar } = useRules();

const poidForm = useForm({
  poid: '',
});

const page = usePage();
const permissionsEnum = page.props.permissionsEnum;
const can = permission => useCan(permission);
const loader = reactive({
  table: false,
  export: false,
});

function onReset() {
  router.visit('/legacy-policy', {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}

function onSubmit(isValid) {
  if (isValid) {
    filters.page = 1;
    Object.keys(filters).forEach(
      key =>
        (filters[key] === '' || filters[key].length === 0) &&
        delete filters[key],
    );
    router.visit('/legacy-policy', {
      method: 'get',
      data: filters,
      preserveState: true,
      preserveScroll: true,
      onBefore: () => (loader.table = true),
      onFinish: () => (loader.table = false),
    });
  } else {
    console.log('Invalid');
  }
}

function onSubmitMigrate(isValid) {
  if (!isValid) return;
  router.visit(route('migrate-legacy-policy', poidForm.poid), {
    method: 'post',
    preserveState: true,
    preserveScroll: true,
    onFinish: response => {
      loader.poid = false;
    },
    onBefore: () => {
      loader.poid = true;
    },
  });
}
let availableFilters = {
  policy_number: '',
  email: '',
  mobile_no: '',
  page: 1,
  poid: '',
};
const filters = reactive(availableFilters);
const tableHeader = [
  { text: 'ID', value: '_id' },
  { text: 'Policy Number', value: 'policy_no' },
  { text: 'Customer name', value: 'customer.name' },
  { text: 'Currently insured with', value: 'policy.insurer' },
  { text: 'Product', value: 'product_name' },
  { text: 'Policy expiry date', value: 'policy_end_date' },
];

const dateFormat = date => {
  if (date) {
    if (date.$date && date.$date.$numberLong) {
      date = formatDate(date);
    }
    return useDateFormat(date, 'DD-MM-YYYY').value;
  }
  return null;
};

const productName = item => {
  let product = item?.product?.product;
  let coverage = item?.policy?.coverage;
  if (product) {
    let productKey = product.toLowerCase().trim();
    return page.props.legacyPolicyMapping[productKey] ?? '';
  } else if (coverage) {
    let coverageKey = coverage.toLowerCase().trim();
    return (
      page.props.coveragePolicyMapping[coverageKey] ??
      page.props.legacyPolicyMapping[coverageKey] ??
      ''
    );
  }
  return '-';
};
</script>

<template>
  <div>
    <Head title="Legacy Policy" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Legacy Policy List</h2>
    </div>
    <x-divider class="my-4" />

    <!--   filters     -->
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
        <x-input
          v-model="filters.policy_number"
          type="search"
          name="policy_number"
          label="Policy Number"
          class="w-full"
          placeholder="Search by Policy Number"
        />
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
          label="Mobile Number"
          class="w-full"
          :rules="[emptyOrNumericAndNoSpecialChar]"
          placeholder="Search by Mobile Number"
        />
      </div>
      <div class="flex justify-end gap-3 mb-4">
        <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
        <x-button size="sm" color="primary" @click.prevent="onReset">
          Reset
        </x-button>
      </div>
    </x-form>

    <x-form
      v-if="can(permissionsEnum.MIGRATE_INSLY_LEAD)"
      @submit="onSubmitMigrate"
      :auto-focus="false"
    >
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
        <x-input
          v-model="poidForm.poid"
          type="text"
          name="policy_number"
          label="POID"
          class="w-full"
          placeholder="Enter POID"
          :rules="[isRequired]"
        />
        <div class="flex items-center">
          <x-button
            :loading="loader.poid"
            size="sm"
            color="#ff5e00"
            type="submit"
            class="w-full sm:w-auto"
          >
            Migrate
          </x-button>
        </div>
      </div>
    </x-form>

    <DataTable
      table-class-name="tablefixed"
      :headers="tableHeader"
      :items="policies.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #item-_id="item">
        <Link
          :href="`/legacy-policy/${item.id}`"
          :data="{ policy_oid: item.policy_oid }"
          class="text-primary-500 hover:underline"
        >
          {{ item.id }}
        </Link>
      </template>
      <template #item-product_name="item">
        {{ productName(item) }}
      </template>
      <template #item-policy_end_date="item">
        {{ dateFormat(item?.policy?.end_date) ?? '' }}
      </template>
    </DataTable>

    <Pagination
      v-if="policies.data && policies.data.length > 0"
      :links="{
        next: policies.next_page_url,
        prev: policies.prev_page_url,
        current: policies.current_page,
        from: policies.from,
        to: policies.to,
      }"
    />
  </div>
</template>
