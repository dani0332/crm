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

const rules = {
  isRequired: v => !!v || 'This field is required',
  created_at_end: v => {
    if (filters.created_at && !v) {
      return 'This field is required';
    }
    return true;
  },
  created_at: v => {
    if (filters.created_at_end && !v) {
      return 'This field is required';
    }
    return true;
  },
};

const selectedItems = ref([]);

const page = usePage();

const filters = reactive({
  code: '',
  first_name: '',
  last_name: '',
  email: '',
  mobile_no: '',
  created_at: '',
  created_at_end: '',
  quote_status_id: [],
  advisor_id: [],
  is_ecommerce: '',
  payment_status_id: '',
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
  { text: 'NATIONALITY', value: '' },
  { text: 'TRANSAPP CODE', value: 'transapp_code' },
  { text: 'SOURCE', value: 'source' },
  { text: 'LOST REASON', value: 'lost_reason' },
  { text: 'PREMIUM', value: 'premium' },
];

const advisorsOptions = computed(() => {
  return page.props.dropdownSource.advisor_id.map(item => {
    return {
      value: item.id,
      label: item.name,
    };
  });
});

const leadsStatusOptions = computed(() => {
  return page.props.dropdownSource.quote_status_id.map(item => {
    return {
      value: item.id,
      label: item.text,
    };
  });
});

function filterQuotes(isValid) {
  if (!isValid) {
    return;
  }
  for (const key in filters) {
    if (filters[key] === '') {
      delete filters[key];
    }
  }

  router.visit('/quotes/life', {
    method: 'get',
    data: {
      ...filters,
      created_at: filters.created_at
        ? filters.created_at.split('-').reverse().join('-')
        : '',
      created_at_end: filters.created_at_end
        ? filters.created_at_end.split('-').reverse().join('-')
        : '',
    },
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loader.table = false;
    },
    onBefore: () => {
      filters.page = 1;
      loader.table = true;
    },
  });
}

function resetFilters() {
  for (const key in filters) {
    filters[key] = '';
  }
  filterQuotes(true);
}

function setQueryFilters() {
  let query = router.page.url.split('?')[1];
  if (query) {
    query = query.split('&');
    query.forEach(item => {
      const [key, value] = item.split('=');

      if (key === 'created_at' || key === 'created_at_end') {
        filters[key] = value.split('-').reverse().join('-');
      } else if (key === 'quote_status_id[]' || key === 'advisor_id[]') {
        let id = key.slice(0, -2);
        if (filters[id]) {
          filters[id].push(parseInt(value));
        }
      } else {
        filters[key] = value;
      }
    });
  }
}

onMounted(() => {
  setQueryFilters();
});
</script>

<template>
  <div>
    <Head title="Life List" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Lead List</h2>
      <div class="space-x-3">
        <Link href="/quotes/life/create">
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
          :rules="[rules.created_at]"
          v-model="filters.created_at"
          type="date"
          name="created_at"
          label="Created Date"
          class="w-full"
        />
        <x-input
          :rules="[rules.created_at_end]"
          v-model="filters.created_at_end"
          type="date"
          name="created_at_end"
          label="Created Date End"
          class="w-full"
        />

        <ComboBox
          v-model="filters.quote_status_id"
          label="Lead Status"
          name="quote_status_id"
          placeholder="Search by Lead Status"
          :options="leadsStatusOptions"
        />
        <ComboBox
          v-model="filters.advisor_id"
          label="Advisor"
          placeholder="Search by Advisor"
          :options="advisorsOptions"
        />

        <x-select
          v-model="filters.is_renewal"
          label="Renewal"

          placeholder="Renewal"
          :options="[
            { value: 'Yes', label: 'Yes' },
            { value: 'No', label: 'No' },
            { value: '', label: 'All'}
          ]"
        />
      </div>
      <div class="flex justify-end gap-3 mb-4">
        <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
        <x-button size="sm" color="primary" @click.prevent="resetFilters">
          Reset
        </x-button>
      </div>
    </x-form>

    <Transition name="fade">
      <div v-if="selectedItems.length > 0" class="mb-4">
        <ExportExcel
          :data="selectedItems"
          :columns="tableHeader"
          :filename="'Health-List'"
          :sheetname="'Leads'"
        >
          <x-button size="sm" color="emerald">
            Export -
            <span class="lining-nums">
              Selected: {{ selectedItems.length }}
            </span>
          </x-button>
        </ExportExcel>
      </div>
    </Transition>

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
          :href="`/quotes/life/${uuid}`"
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
