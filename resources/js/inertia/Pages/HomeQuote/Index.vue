<script setup>
import { reactive, computed, onMounted, ref } from 'vue';
import { Head, router, usePage, Link, useForm } from '@inertiajs/vue3';
import { useNotifications } from '@indielayer/ui';
import { useHasRole } from '../../Composables/can';

defineProps({
  quotes: Object,
  leadStatuses: Array,
  advisors: Array,
  isManualAllocationAllowed: Boolean,
});

const page = usePage();
const notification = useNotifications('toast');

const loader = reactive({
  table: false,
  export: false,
});

const canExport = ref(false);
const quotesSelected = ref([]),
  assignAdvisor = ref(null),
  assignmentType = ref(null),
  isDisabled = ref(false);

const tableHeader = [
  { text: 'Ref-ID', value: 'code' },
  { text: 'FIRST NAME', value: 'first_name' },
  { text: 'LAST NAME', value: 'last_name' },
  { text: 'LEAD STATUS', value: 'quote_status_id_text' },
  { text: 'ADVISOR', value: 'advisor_id_text' },
  { text: 'CREATED DATE', value: 'created_at' },
  { text: 'LAST MODIFIED DATE', value: 'updated_at' },
  { text: 'SOURCE', value: 'source' },
  { text: 'LOST REASON', value: 'lost_reason' },
  { text: 'PRICE', value: 'premium' },
  { text: 'POLICY NUMBER', value: 'policy_number' },
  { text: 'Previous Policy Number', value: 'previous_quote_policy_number' },
  { text: 'Renewal Batch', value: 'renewal_batch' },
];

const filters = reactive({
  code: '',
  first_name: '',
  last_name: '',
  email: '',
  mobile_no: '',
  created_at_start: '',
  created_at_end: '',
  quote_status_id: [],
  advisors: [],
  is_renewal: '',
  page: 1,
  previous_quote_policy_number: '',
  renewal_batch: '',
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
const onDataExport = () => {
  const data = useObjToUrl(filters);
  const url = route('data-extraction', 'home');
  window.open(url + '?' + new URLSearchParams(data).toString());
};
function onSubmit(isValid) {
  if (isValid) {
    filters.page = 1;
    Object.keys(filters).forEach(
      key =>
        (filters[key] === '' || filters[key].length === 0) &&
        delete filters[key],
    );
    router.visit(route('home.index'), {
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
  router.visit(route('home.index'), {
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
  if (urlParams.has('quote_status_id[]')) {
    filters.quote_status_id = urlParams
      .getAll('quote_status_id[]')
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

const rules = {
  isRequired: v => !!v || 'Please select this option',
};

const assignForm = useForm({
  assigned_to_id_new: null,
  modelType: 'Home',
  selectTmLeadId: '',
  isManualAllocationAllowed: page.props.isManualAllocationAllowed,
});

function onAssignLead(isValid) {
  if (isValid) {
    const selected = quotesSelected.value.map(e => e.id);
    assignForm
      .transform(data => ({
        ...data,
        selectTmLeadId: `${selected}`,
      }))
      .post(route('manualLeadAssign', { quoteType: 'home' }), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
          quotesSelected.value = [];
        },
      });
  }
}

const hasRole = role => useHasRole(role);
const rolesEnum = page.props.rolesEnum;
onMounted(() => {
  setQueryStringFilters();
});

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

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
</script>

<template>
  <div>
    <Head title="Home List" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Home List</h2>
      <div class="space-x-3">
        <Link :href="route('home-cardView')">
          <x-button size="sm" color="#1d83bc" tag="div"> Cards View </x-button>
        </Link>

        <Link :href="route('home.create')">
          <x-button size="sm" color="#ff5e00" tag="div"> Create Lead </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
        <div>
          <x-tooltip position="bottom">
            <label
              class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
            >
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
        <x-field label="First Name">
          <x-input
            v-model="filters.first_name"
            type="search"
            name="first_name"
            class="w-full"
            placeholder="Search by First Name"
          />
        </x-field>
        <x-field label="Last Name">
          <x-input
            v-model="filters.last_name"
            type="search"
            name="last_name"
            class="w-full"
            placeholder="Search by Last Name"
          />
        </x-field>
        <x-field label="Email">
          <x-input
            v-model="filters.email"
            type="search"
            name="email"
            class="w-full"
            placeholder="Search by Email"
          />
        </x-field>
        <x-field label="Mobile Number">
          <x-input
            v-model="filters.mobile_no"
            type="search"
            name="mobile_no"
            class="w-full"
            placeholder="Search by Mobile Number"
          />
        </x-field>
        <x-field label="Created Date Start">
          <DatePicker
            v-model="filters.created_at_start"
            name="created_at_start"
          />
        </x-field>
        <x-field label="Created Date End">
          <DatePicker v-model="filters.created_at_end" name="created_at_end" />
        </x-field>
        <x-field label="Lead Status">
          <ComboBox
            v-model="filters.quote_status_id"
            name="quote_status_id"
            placeholder="Search by Lead Status"
            :options="leadStatusOptions"
          />
        </x-field>
        <x-field label="Advisor">
          <ComboBox
            v-if="!hasRole(rolesEnum.Advisor)"
            v-model="filters.advisors"
            placeholder="Search by Advisor"
            :options="advisorOptions"
          />
        </x-field>
        <x-field label="Is Renewal">
          <x-select
            v-model="filters.is_renewal"
            placeholder="Search by Renewal"
            :options="[
              { value: '', label: 'All' },
              { value: 'Yes', label: 'Yes' },
              { value: 'No', label: 'No' },
            ]"
            class="w-full"
          />
        </x-field>
        <x-input
          v-model="filters.previous_quote_policy_number"
          type="text"
          name="previous_quote_policy_number"
          label="Previous Policy Number"
          class="w-full"
          placeholder="Search by Previous Policy Number"
        />
        <x-input
          v-model="filters.renewal_batch"
          type="text"
          name="renewal_batch"
          label="Renewal Batch"
          class="w-full"
          placeholder="Search by Renewal Batch"
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

    <section v-if="quotesSelected.length > 0" class="mb-4">
      <div
        class="px-4 py-6 rounded shadow mb-4 bg-primary-50/50"
        v-if="isManualAllocationAllowed == true"
      >
        <h3 class="font-semibold text-primary-800">Assign Leads</h3>
        <x-divider class="mb-4 mt-1" />
        <x-form @submit="onAssignLead" :auto-focus="false">
          <div class="w-full flex flex-col md:flex-row gap-4">
            <x-select
              v-model="assignForm.assigned_to_id_new"
              :options="advisorOptions"
              placeholder="Select Advisor"
              class="flex-1 w-full"
              :rules="[rules.isRequired]"
              label="Assign Advisor"
            />
            <div class="mb-3 md:pt-6">
              <x-button
                color="orange"
                size="sm"
                type="submit"
                :loading="assignForm.processing"
              >
                Assign
              </x-button>
            </div>
          </div>
        </x-form>
      </div>
    </section>
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
          :href="route('home.show', uuid)"
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
