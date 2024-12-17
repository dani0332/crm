<script setup>
const props = defineProps({
  customers: Object,
  userId: Number,
  quoteTypes: Object,
});

let availableFilters = {
  search_type: '',
  search_value: '',
  page: 1,
};

const filters = reactive(availableFilters);
const loader = reactive({
  table: false,
  export: false,
});

const getDetailPageRoute = (
  uuid,
  quote_type_id,
  business_type_of_insurance_id,
) => useGetShowPageRoute(uuid, quote_type_id, business_type_of_insurance_id);

const getQuoteType = quoteTypeKey => {
  return props.quoteTypes[quoteTypeKey];
};

const tableHeader = [
  { text: 'CUSTOMER ID', value: 'uuid' },
  { text: 'REF-ID', value: 'code' },
  { text: 'NAME', value: 'first_name' },
  { text: 'INSURED NAME', value: 'insured_first_name' },
  { text: 'CREATED AT', value: 'created_at' },
  { text: 'UPDATED AT', value: 'updated_at' },
  { text: 'POLICY NUMBER', value: 'policy_number' },
  { text: 'POLICY START DATE', value: 'policy_start_date' },
  { text: 'POLICY END DATE', value: 'policy_expiry_date' },
  { text: 'TYPE OF POLICY', value: 'quote_type_id' },
  { text: 'ADVISOR', value: 'advisor' },
  { text: 'RECEIVE MARKETING', value: 'receive_marketing_updates' },
];

function onSubmit(isValid) {
  if (isValid) {
    filters.page = 1;
    Object.keys(filters).forEach(
      key =>
        (filters[key] === '' || filters[key].length === 0) &&
        delete filters[key],
    );

    router.visit('/customer', {
      method: 'get',
      data: filters,
      preserveState: true,
      preserveScroll: true,
      onBefore: () => (loader.table = true),
      onSuccess: () => (loader.table = false),
    });
  } else {
    console.log('Invalid');
  }
}

function onReset() {
  router.visit('/customer', {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}
</script>

<template>
  <div>
    <Head title="Customer" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Customer List</h2>
    </div>
    <x-divider class="my-4" />

    <!-- Filters -->
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
        <ComboBox
          v-model="filters.search_type"
          label="Search By"
          placeholder="Search By"
          :options="[
            { value: 'email', label: 'Email Address' },
            { value: 'first_name', label: 'Customer Name' },
            { value: 'entity_name', label: 'Entity Name' },
            { value: 'insured_first_name', label: 'Insured Name' },
            { value: 'mobile_no', label: 'Mobile Number' },
            { value: 'uuid', label: 'Customer ID' },
          ]"
          :single="true"
        />
        <x-input
          v-model="filters.search_value"
          type="search"
          name="search_value"
          label="Search Value"
          placeholder="Search Value"
          class="w-full"
        />
      </div>
      <div class="flex justify-end gap-2 mb-4 mt-1">
        <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
        <x-button size="sm" color="primary" @click.prevent="onReset">
          Reset
        </x-button>
      </div>
    </x-form>
    <DataTable
      table-class-name="tablefixed"
      :headers="tableHeader"
      :loading="loader.table"
      :items="customers.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #item-uuid="{ customer }">
        <Link
          :href="`/customer/${customer.uuid}`"
          class="text-primary-500 hover:underline"
        >
          {{ customer.id }}
        </Link>
      </template>

      <template
        #item-code="{
          code,
          uuid,
          advisor_id,
          quote_type_id,
          business_type_of_insurance_id,
        }"
      >
        <Link
          v-if="userId === advisor_id"
          :href="
            getDetailPageRoute(
              uuid,
              quote_type_id,
              business_type_of_insurance_id,
            )
          "
          target="_blank"
          class="text-primary-500 hover:underline"
        >
          {{ code }}
        </Link>
        <span v-else>{{ code }}</span>
      </template>

      <template #item-first_name="{ customer }">
        {{ customer?.first_name }}
      </template>

      <template #item-quote_type_id="{ quote_type_id }">
        {{ getQuoteType(quote_type_id) }}
      </template>

      <template #item-insured_first_name="{ customer }">
        {{ customer?.insured_first_name }}
      </template>

      <template #item-created_at="{ customer }">
        {{ customer?.created_at }}
      </template>

      <template #item-updated_at="{ customer }">
        {{ customer?.updated_at }}
      </template>

      <template #item-advisor="{ advisor }">
        {{ advisor?.name }}
      </template>

      <template #item-receive_marketing_updates="item">
        <div class="text-center">
          <x-tag
            size="sm"
            :color="
              item.customer.receive_marketing_updates === 1
                ? 'success'
                : 'error'
            "
          >
            {{ item.customer.receive_marketing_updates === 1 ? 'Yes' : 'No' }}
          </x-tag>
        </div>
      </template>
    </DataTable>

    <Pagination
      :links="{
        next: customers.next_page_url,
        prev: customers.prev_page_url,
        current: customers.current_page,
        from: customers.from,
        to: customers.to,
      }"
    />
  </div>
</template>
