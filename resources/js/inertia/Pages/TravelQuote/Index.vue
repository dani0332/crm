<script setup>
import { reactive, computed, onMounted, ref } from 'vue';
import { Head, router, usePage, Link } from '@inertiajs/vue3';
import Pagination from '@/inertia/Components/Pagination.vue';
import ExportExcel from '@/inertia/Components/ExportExcel.vue';
import ComboBox from '@/inertia/Components/ComboBox.vue';

defineProps({
  quotes: Object,
  dropdownSource: Object,
});

const selectedItems = ref([]);

const page = usePage();

const filters = reactive({
  code: '',
  first_name: '',
  last_name: '',
  email: '',
  mobile_no: '',
  created_at_start: '',
  created_at_end: '',
  quote_status: [],
  advisors: [],
  is_ecommerce: '',
  payment_status: '',
  page: 1,
});

const loader = reactive({
  table: false,
  export: false,
});

const tableHeader = [
  { text: 'CDB ID', value: 'code' },
  { text: 'FIRST NAME', value: 'first_name' },
  { text: 'LAST NAME', value: 'last_name' },
  { text: 'LEAD STATUS', value: 'quote_status_id_text' },
  { text: 'ADVISOR', value: 'advisor_id_text' },
  { text: 'CREATED DATE', value: 'created_at' },
  { text: 'LAST MODIFIED DATE', value: 'updated_at' },
  { text: 'TRANSAPP CODE', value: 'transapp_code' },
  { text: 'LOST REASON', value: 'lost_reason' },
  { text: 'SOURCE', value: 'source' },
  { text: 'PREMIUM', value: 'premium' },
  { text: 'POLICY NUMBER', value: 'policy_number' },
  { text: 'DESTINATION', value: 'destination' },
  { text: 'CURRENTLY LOCATED IN', value: '' },
  { text: 'EXPIRY DATE', value: 'expiry_date' },
  { text: 'IS ECOMMERCE', value: 'is_ecommerce' },
  { text: 'PAYMENT STATUS', value: 'payment_status_id_text' },
];

const paymentStatusOptions = computed(() => {
  return page.props.dropdownSource.payment_status.map(item => {
    return {
      value: item.id,
      label: item.text,
    };
  });
});

const advisorsOptions = computed(() => {
  return page.props.dropdownSource.advisors.map(item => {
    return {
      value: item.id,
      label: item.name,
    };
  });
});

const leadsStatusOptions = computed(() => {
  return page.props.dropdownSource.leads.map(item => {
    return {
      value: item.id,
      label: item.text,
    };
  });
});

function filterQuotes() {
  filters.page = 1;

  for (const key in filters) {
    if (filters[key] === '') {
      delete filters[key];
    }
  }

  router.visit('/quotes/travel', {
    method: 'get',
    data: filters,
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loader.table = false;
    },
    onBefore: () => {
      loader.table = true;
    },
  });
}

function resetFilters() {
  for (const key in filters) {
    filters[key] = '';
  }
  filterQuotes();
}

function setQueryFilters() {
  let query = router.page.url.split('?')[1];
  if (query) {
    query = query.split('&');
    query.forEach(item => {
      const [key, value] = item.split('=');
      filters[key] = value;
    });
  }
}

onMounted(() => {
  setQueryFilters();
});
</script>

<template>
  <div>
    <Head title="Travel List" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Lead List</h2>
      <div class="space-x-3">
        <Link href="/quotes/health-cards">
          <x-button size="sm" color="#1d83bc" tag="div"> Cards View </x-button>
        </Link>

        <Link href="/quotes/travel/create">
          <x-button size="sm" color="#ff5e00" tag="div"> Create Lead </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="filterQuotes" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
        <x-input
          v-model="filters.code"
          type="search"
          name="code"
          label="CDB ID"
          class="w-full"
          placeholder="Search by CDB ID"
        />
        <x-input
          v-model="filters.first_name"
          type="search"
          name="first_name"
          label="First Name"
          class="w-full"
          placeholder="Search by First Name"
        />
        <x-input
          v-model="filters.last_name"
          type="search"
          name="last_name"
          label="Last Name"
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
        <x-input
          v-model="filters.created_at_start"
          type="date"
          name="created_at_start"
          label="Created Date"
          class="w-full"
        />
        <x-input
          v-model="filters.created_at_end"
          type="date"
          name="created_at_end"
          label="Created Date End"
          class="w-full"
        />

        <ComboBox
          v-model="filters.quote_status"
          label="Lead Status"
          name="quote_status"
          placeholder="Search by Lead Status"
          :options="leadsStatusOptions"
        />
        <ComboBox
          v-model="filters.advisors"
          label="Advisor"
          placeholder="Search by Advisor"
          :options="advisorsOptions"
        />
        <x-select
          v-model="filters.is_ecommerce"
          label="Ecommerce"
          placeholder="Search by Ecommerce"
          :options="[
            { value: 'Yes', label: 'Yes' },
            { value: 'No', label: 'No' },
          ]"
          class="w-full"
        />
        <x-select
          name="payment_status_id"
          v-model="filters.payment_status"
          label="PAYMENT STATUS"
          placeholder="Search by Payment Status"
          :options="paymentStatusOptions"
          class="w-full"
        />
      </div>
      <div class="flex justify-end gap-3 mb-4">
        <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
        <x-button size="sm" color="primary" @click.prevent="resetFilters">
          Reset
        </x-button>
      </div>
    </x-form>

    <DataTable
      v-model:items-selected="selectedItems"
      table-class-name="tablefixed"
      :loading="loader.table"
      :headers="tableHeader"
      :items="quotes.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
      fixed-checkbox
    >
      <template #item-code="{ code, uuid }">
        <Link
          :href="`/quotes/travel/${uuid}`"
          class="text-primary-500 hover:underline"
        >
          {{ code }}
        </Link>
      </template>
      <template #item-is_ecommerce="{ is_ecommerce }">
        <div class="text-center">
          <x-tag size="sm" :color="is_ecommerce ? 'success' : 'error'">
            {{ is_ecommerce ? 'Yes' : 'No' }}
          </x-tag>
        </div>
      </template>
    </DataTable>

    <Pagination
      :links="{
        next: quotes.next_page_url,
        prev: quotes.prev_page_url,
        current: quotes.current_page,
        from: quotes.from,
        to: quotes.to,
      }"
    />
  </div>
</template>
