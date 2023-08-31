<script setup>
defineProps({
  policies: Array,
});

const { isRequired } = useRules();

const page = usePage();
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
let availableFilters = {
  policy_number: '',
  email: '',
  mobile_no: '',
  page: 1,
};
const filters = reactive(availableFilters);
const tableHeader = [
  { text: 'Policy Number', value: 'policy_no' },
  { text: 'Customer name', value: 'customer.name' },
  { text: 'Currently insured with', value: 'policy.insurer' },
  { text: 'Product', value: '' },
  { text: 'Policy expiry date', value: 'policy.end_date' },
];
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
          placeholder="Search by Last Name"
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

    <DataTable
      table-class-name="tablefixed"
      :headers="tableHeader"
      :items="policies.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
      fixed-checkbox
    >
      <template #item-policy_no="{ policy_no }">
        <Link
          :href="`/legacy-policy/${policy_no}`"
          class="text-primary-500 hover:underline"
        >
          {{ policy_no }}
        </Link>
      </template>
    </DataTable>

    <Pagination
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
