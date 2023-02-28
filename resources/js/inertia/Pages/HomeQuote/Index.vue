<script setup>
import { reactive, computed, onMounted, ref } from 'vue';
import { Head, router, usePage, Link } from '@inertiajs/vue3';

defineProps({
  quotes: Object,
  leadStatuses: Array,
  advisors: Array,
});

const page = usePage();
const loader = reactive({
  table: false,
  export: false,
});

const quotesSelected = ref([]),
  assignAdvisor = ref(null),
  assignmentType = ref(null),
  isDisabled = ref(false);

const tableHeader = [
  { text: 'CDB ID', value: 'code' },
  { text: 'FIRST NAME', value: 'first_name' },
  { text: 'LAST NAME', value: 'last_name' },
  { text: 'LEAD STATUS', value: 'quote_status_id_text' },
  { text: 'ADVISOR', value: 'advisor_id_text' },
  { text: 'CREATED DATE', value: 'created_at' },
  { text: 'LAST MODIFIED DATE', value: 'updated_at' },
  { text: 'TRANSAPP CODE', value: 'transapp_code' },
  { text: 'SOURCE', value: 'source' },
  { text: 'LOST REASON', value: 'lost_reason' },
  { text: 'PREMIUM', value: 'premium' },
  { text: 'POLICY NUMBER', value: 'policy_number' },
];

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
  is_renewal: '',
  page: 1,
});

const leadStatusOptions = computed(() => {
  return page.props.leadStatuses.map(status => ({
    value: status.id,
    label: status.text,
  }));
});

const advisorOptions = computed(() => {
  return page.props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.name,
  }));
});

const onAdvisorAssign = () => {
  if (!assignAdvisor.value || !assignmentType.value) {
    notification.error({
      title: !assignAdvisor.value
        ? 'Please select advior'
        : 'Please select assignment type',
      position: 'top',
    });
    return;
  }
  router.post(
    `/quotes/home/manualLeadAssign`,
    {
      modelType: 'Home',
      assigned_to_id_new: assignAdvisor.value,
    },
    {
      preserveScroll: true,
      onBefore: () => {
        isDisabled.value = true;
      },
      onSuccess: () => {
        notification.success({
          title: 'Lead(s) Assigned',
          position: 'top',
        });
      },
      onFinish: () => {
        isDisabled.value = false;
      },
    },
  );
};

function onSubmit(isValid) {
  if (isValid) {
    filters.page = 1;
    Object.keys(filters).forEach(
      key =>
        (filters[key] === '' || filters[key].length === 0) &&
        delete filters[key],
    );
    router.visit('/quotes/home', {
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

function onReset() {
  router.visit('/quotes/home', {
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

  if (urlParams.has('code')) {
    filters.code = urlParams.get('code');
  }
  if (urlParams.has('first_name')) {
    filters.first_name = urlParams.get('first_name');
  }
  if (urlParams.has('last_name')) {
    filters.last_name = urlParams.get('last_name');
  }
  if (urlParams.has('email')) {
    filters.email = urlParams.get('email');
  }
  if (urlParams.has('mobile_no')) {
    filters.mobile_no = urlParams.get('mobile_no');
  }
  if (urlParams.has('created_at_start')) {
    filters.created_at_start = urlParams.get('created_at_start');
  }
  if (urlParams.has('created_at_end')) {
    filters.created_at_end = urlParams.get('created_at_end');
  }
  if (urlParams.has('quote_status[]')) {
    filters.quote_status = urlParams
      .getAll('quote_status[]')
      .map(status => parseInt(status));
  }
  if (urlParams.has('advisors[]')) {
    filters.advisors = urlParams
      .getAll('advisors[]')
      .map(status => parseInt(status));
  }
  if (urlParams.has('is_renewal')) {
    filters.is_renewal = urlParams.get('is_renewal');
  }
}

onMounted(() => {
  setQueryStringFilters();
});
</script>

<template>
  <div>
    <Head title="Home List" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Home List</h2>
      <div class="space-x-3">
        <Link href="/quotes/home-cards">
          <x-button size="sm" color="#1d83bc" tag="div"> Cards View </x-button>
        </Link>

        <Link href="/quotes/home/create">
          <x-button size="sm" color="#ff5e00" tag="div"> Create Lead </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
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
        <DatePicker
          v-model="filters.created_at_start"
          name="created_at_start"
          label="Created Date Start"
        />
        <DatePicker
          v-model="filters.created_at_end"
          name="created_at_end"
          label="Created Date End"
        />
        <ComboBox
          v-model="filters.quote_status"
          label="Lead Status"
          name="quote_status"
          placeholder="Search by Lead Status"
          :options="leadStatusOptions"
        />
        <ComboBox
          v-model="filters.advisors"
          label="Advisor"
          placeholder="Search by Advisor"
          :options="advisorOptions"
        />
        <x-select
          v-model="filters.is_renewal"
          label="Is Renewal"
          placeholder="Search by Renewal"
          :options="[
            { value: '', label: 'All' },
            { value: 'Yes', label: 'Yes' },
            { value: 'No', label: 'No' },
          ]"
          class="w-full"
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
      v-if="quotesSelected.length > 0"
      class="p-4 rounded shadow mb-6 bg-white"
    >
      <h3 class="font-semibold text-primary-800">Assign Leads</h3>
      <x-divider class="mb-4 mt-1" />
      <div class="flex flex-wrap md:flex-nowrap gap-4 w-full items-end">
        <x-select
          v-model="assignAdvisor"
          label="Assign Advisor"
          :options="advisorOptions"
          placeholder="Select Advisor"
          class="w-auto flex-1"
        />
        <x-select
          v-model="assignmentType"
          label="Assignment Type"
          :options="advisorOptions"
          placeholder="Select"
          class="w-auto flex-1"
        />
        <div>
          <x-button
            color="orange"
            size="sm"
            @click.prevent="onAdvisorAssign"
            :loading="isDisabled"
          >
            Assign
          </x-button>
        </div>
      </div>
    </div>

    <Transition name="fade">
      <div v-if="quotesSelected.length > 0" class="mb-4">
        <ExportExcel
          :data="quotesSelected"
          :columns="tableHeader"
          :filename="'Home-List'"
          :sheetname="'Leads'"
        >
          <x-button size="sm" color="emerald">
            Export -
            <span class="lining-nums">
              Selected: {{ quotesSelected.length }}
            </span>
          </x-button>
        </ExportExcel>
      </div>
    </Transition>
    <DataTable
      v-model:items-selected="quotesSelected"
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
          :href="`/quotes/home/${uuid}`"
          class="text-primary-500 hover:underline"
        >
          {{ code }}
        </Link>
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
