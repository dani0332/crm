<script setup>
import { getQuoteType } from '../../Composables/utilities.js';

const params = useUrlSearchParams('history');

const props = defineProps({
  leads: [Array, Object],
  userId: Number,
  quoteTypes: Object,
});

let availableFilters = {
  primary_email: '',
  additional_email: '',
  page: 1,
};

const filters = reactive(availableFilters);
const loader = reactive({
  table: false,
  export: false,
});

// const getDetailPageRoute = (
//   uuid,
//   quote_type_id,
//   business_type_of_insurance_id,
// ) => useGetShowPageRoute(uuid, quote_type_id, business_type_of_insurance_id);

const tableHeader = [
  { text: 'REF-ID', value: 'ref_id' },
  { text: 'FIRST NAME', value: 'first_name' },
  { text: 'LAST NAME', value: 'last_name' },
  { text: 'CUSTOMER ID', value: 'customer_id' },
  { text: 'LEAD STATUS', value: 'lead_status' },
  { text: 'SOURCE', value: 'source' },
  { text: 'ADVISOR NAME', value: 'advisor_name' },
  { text: 'CREATED AT', value: 'created_at' },
];

function onSubmit(isValid) {
  if (isValid) {
    filters.page = 1;
    Object.keys(filters).forEach(
      key =>
        (filters[key] === '' || filters[key].length === 0) &&
        delete filters[key],
    );

    router.visit('leads-by-email', {
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
  router.visit('leads-by-email', {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}

function setQueryStringFilters() {
  for (const [key] of Object.entries(params)) {
    if (key.includes('[]')) {
      filters[key.substring(0, key.length - 2)] = params[key];
    } else {
      filters[key] = params[key];
    }
  }
}

onMounted(() => {
  setQueryStringFilters();
});
</script>

<template>
  <div>
    <Head title="Leads by Email" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Leads by Email</h2>
    </div>
    <x-divider class="my-4" />

    <!-- Filters -->
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-4">
        <x-input
          v-model="filters.primary_email"
          type="email"
          name="primary_email"
          label="Primary Email"
          placeholder="Enter primary email address"
          class="w-full"
        />
        <x-input
          v-model="filters.additional_email"
          type="email"
          name="additional_email"
          label="Additional Email"
          placeholder="Enter additional email address"
          class="w-full"
        />
      </div>
      <div class="flex justify-end gap-2 mb-4 mt-1">
        <x-button size="sm" color="#ff5e00" type="submit"> Search </x-button>
        <x-button size="sm" color="primary" @click.prevent="onReset">
          Reset
        </x-button>
      </div>
    </x-form>

    <!-- Results Section -->
    <div class="mt-6">
      <!-- Empty State -->
      <div
        v-if="!leads.data || leads.data.length === 0"
        class="text-center py-5"
      >
        <p class="text-gray-500">
          {{
            filters.primary_email || filters.additional_email
              ? 'No leads found matching your search criteria.'
              : 'Enter an email address to search for leads.'
          }}
        </p>
      </div>

      <DataTable
        table-class-name="tablefixed"
        :headers="tableHeader"
        :loading="loader.table"
        :items="leads.data || []"
        border-cell
        hide-rows-per-page
        hide-footer
      >
        <template #item-ref_id="{ ref_id, uuid, quote_type_id, customer }">
          <Link
            :href="`${getQuoteType(quote_type_id, 'link')}/${getQuoteType(quote_type_id, 'id')}/${uuid}`"
            class="text-primary-500 hover:underline"
          >
            {{ ref_id }}
          </Link>
        </template>

        <template #item-first_name="{ first_name }">
          <span class="font-medium">{{ first_name || '-' }}</span>
        </template>

        <template #item-last_name="{ last_name }">
          <span class="font-medium">{{ last_name || '-' }}</span>
        </template>

        <template #item-customer_id="{ customer_id, customer }">
          <Link
            :href="`/customer/${customer?.uuid}`"
            class="text-primary-500 hover:underline"
          >
            {{ customer_id }}
          </Link>
        </template>

        <template #item-lead_status="{ quote_status }">
          <x-tag size="sm">
            {{ quote_status?.text || 'N/A' }}
          </x-tag>
        </template>

        <template #item-source="{ source }">
          <span class="capitalize">{{ source || '-' }}</span>
        </template>

        <template #item-advisor_name="{ advisor }">
          <span class="font-medium">{{ advisor?.name || '-' }}</span>
        </template>

        <template #item-created_at="{ created_at }">
          <span class="text-sm text-gray-600">
            {{ created_at }}
          </span>
        </template>
      </DataTable>
      <Pagination
        :links="{
          next: leads.next_page_url,
          prev: leads.prev_page_url,
          current: leads.current_page,
          from: leads.from,
          to: leads.to,
        }"
      />
    </div>
  </div>
</template>

<script>
export default {
  methods: {
    formatDate(dateString) {
      if (!dateString) return '-';
      try {
        return new Date(dateString).toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'short',
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit',
        });
      } catch (e) {
        return dateString;
      }
    },
  },
};
</script>
