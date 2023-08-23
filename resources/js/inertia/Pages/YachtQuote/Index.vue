<script setup>
import { reactive, computed, onMounted, ref } from 'vue';
import { Head, router, usePage, Link } from '@inertiajs/vue3';
import Pagination from '@/inertia/Components/Pagination.vue';
import ExportExcel from '@/inertia/Components/ExportExcel.vue';
import ComboBox from '@/inertia/Components/ComboBox.vue';
import {useCan} from "../../Composables/can";

defineProps({
  quotes: Object,
  quoteStatuses: Array,
  advisors: Array,
});

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
  mobile_no: '',
  created_at_start: '',
  created_at_end: '',
  renewal_batch: '',
  previous_quote_policy_number: '',
  is_ecommerce: '',
  quote_status_id: '',
  page: 1,
};

const filters = reactive(availableFilters);
const canExport = ref(false);

function onSubmit(isValid) {
  if (isValid) {
    filters.page = 1;

    Object.keys(filters).forEach(
      key =>
        (filters[key] === '' || filters[key].length === 0) &&
        delete filters[key],
    );

    router.visit('/personal-quotes/yacht', {
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
  router.visit('/personal-quotes/yacht', {
    method: 'get',
    data: { page: 1 },
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

onMounted(() => {
  setQueryStringFilters();
});

const tableHeader = [
  { text: 'Ref-ID', value: 'uuid' },
  { text: 'FIRST NAME', value: 'first_name' },
  { text: 'LAST NAME', value: 'last_name' },
  { text: 'LEAD STATUS', value: 'quote_status' },
  { text: 'ADVISOR', value: 'advisor' },
  { text: 'CREATED DATE', value: 'created_at' },
  { text: 'LAST MODIFIED DATE', value: 'updated_at' },
  { text: 'PRICE', value: 'premium' },
  { text: 'POLICY NO', value: 'policy_no' },
  { text: 'SOURCE', value: 'source' },
  { text: 'CURRENTLY INSURED WITH', value: 'currently_insured_with' },
  { text: 'IS ECOMMERCE', value: 'is_ecommerce' },
];

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

const onDataExport = () => {
  const data = useObjToUrl(filters);
  const url = route('data-extraction', 'yacht');
  window.open(url + '?' + new URLSearchParams(data).toString());
};

watch(
  () => filters,
  () => {
    if (filters.created_at_start && filters.created_at_end) {
      canExport.value = true;
    } else {
      canExport.value = false;
    }
  },
  { deep: true, immediate: true },
);

</script>

<template>
  <div>
    <Head title="Yacht Quotes" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Yacht Quotes List</h2>
      <x-button v-if="can(permissionsEnum.YachtQuotesCreate)" size="sm" color="#ff5e00" href="/personal-quotes/yacht/create">
        Create Lead
      </x-button>
    </div>
    <x-divider class="my-4" />

    <!--   filters     -->
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
          <div>
              <x-tooltip position="bottom">
                  <label class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600">
                      Ref-ID
                  </label>
                  <template #tooltip> Reference ID </template>
              </x-tooltip>
              <x-input
                  v-model="filters.code"
                  type="search"
                  name="code"
                  class="w-full"
                  placeholder="Search by Ref-ID"
              />
          </div>
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
        <DatePicker
          v-model="filters.created_at_start"
          type="date"
          name="created_at_start"
          label="Created Date Start"
          class="w-full"
        />
        <DatePicker
          v-model="filters.created_at_end"
          type="date"
          name="created_at_end"
          label="Created Date End"
          class="w-full"
        />

        <x-input
          v-model="filters.renewal_batch"
          type="search"
          name="renewal_batch"
          label="Renewal Batch"
          class="w-full"
          placeholder="Search by Renewal Batch"
        />

        <ComboBox
          v-model="filters.quote_status_id"
          label="Lead Status"
          name="quote_status"
          placeholder="Search by Lead Status"
          :options="
            quoteStatuses.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
        />

        <x-select
          v-model="filters.is_ecommerce"
          label="Is Ecommerce"
          placeholder="Search by Ecommerce"
          :options="[
            { value: '', label: 'All' },
            { value: 1, label: 'Yes' },
            { value: 0, label: 'No' },
          ]"
          class="w-full"
        />

        <x-select
          v-model="filters.previous_quote_policy_number"
          label="Is Renewal"
          placeholder="Search by Renewal"
          :options="[
            { value: '', label: 'All' },
            { value: 0, label: 'Yes' },
            { value: 1, label: 'No' },
          ]"
          class="w-full"
        />
      </div>
      <div class="flex justify-between gap-3 mb-4 mt-1">
          <div v-if="can(permissionsEnum.DATA_EXTRACTION)">
              <x-button
                  v-if="canExport"
                  size="sm"
                  color="emerald"
                  @click.prevent="onDataExport"
                  class="justify-self-start"
              >
                  Export
              </x-button>
              <x-tooltip v-else position="right">
                  <x-button tag="div" size="sm" color="emerald"> Export </x-button>
                  <template #tooltip>
            <span class="font-medium">
              Created dates are required to export data.
            </span>
                  </template>
              </x-tooltip>
          </div>
          <div v-else />
          <div class="flex justify-self-end gap-3">
              <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
              <x-button size="sm" color="primary" @click.prevent="onReset">
                  Reset
              </x-button>
          </div>
      </div>
    </x-form>

    <DataTable
      table-class-name="tablefixed"
      :headers="tableHeader"
      :loading="loader.table"
      :items="quotes.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
      fixed-checkbox
    >
      <template #item-uuid="{ code, uuid }">
        <Link v-if="can(permissionsEnum.YachtQuotesShow)"
          :href="`/personal-quotes/yacht/${uuid}`"
          class="text-primary-500 hover:underline"
        >
          {{ code }}
        </Link>
          <span v-else>{{code}}</span>
      </template>

      <template #item-advisor="{ advisor }">
        {{ advisor?.email }}
      </template>

      <template #item-quote_status="{ quote_status }">
        {{ quote_status?.text }}
      </template>

      <template #item-currently_insured_with="{ currently_insured_with }">
        {{ currently_insured_with?.text }}
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
