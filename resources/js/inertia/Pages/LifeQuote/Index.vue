<script setup>
defineProps({
  quotes: Object,
  quoteStatuses: Array,
  advisors: Array,
});

const page = usePage();

const hasRole = role => useHasRole(role);
const rolesEnum = page.props.rolesEnum;

const rules = {
  isRequired: v => !!v || 'This field is required',
  created_at_end: v => {
    if (filters.created_at_start && !v) {
      return 'This field is required';
    }
    return true;
  },
  created_at_start: v => {
    if (filters.created_at_end && !v) {
      return 'This field is required';
    }
    return true;
  },
};

const role = [rolesEnum.Admin, rolesEnum.LeadPool, rolesEnum.LifeManager];
const roleLeadPool = [rolesEnum.LeadPool];
const hasAnyRole = role => useHasAnyRole(role);

const isManualAllocationAllowed = ref(false);
const isLeadPool = ref(false);

isManualAllocationAllowed.value = hasAnyRole(role);
isLeadPool.value = hasAnyRole(roleLeadPool);
const notification = useNotifications('toast');

const filters = reactive({
  code: '',
  first_name: '',
  last_name: '',
  email: '',
  mobile_no: '',
  created_at_start: '',
  created_at_end: '',
  quote_status_id: [],
  advisor_id: [],
  is_ecommerce: '',
  payment_status_id: '',
  previous_quote_policy_number_text: '',
  page: 1,
  payment_due_date:"",
  booking_date: "",
  payment_due_date:"",
  booking_date: ""
});

const loader = reactive({
  table: false,
  export: false,
});

const tableHeader = reactive([
  { text: 'Ref-ID', value: 'code', is_active: true },
  { text: 'FIRST NAME', value: 'first_name', is_active: true },
  { text: 'LAST NAME', value: 'last_name', is_active: true },
  { text: 'LEAD STATUS', value: 'quote_status', is_active: true },
  { text: 'ADVISOR', value: 'advisor', is_active: true },
  { text: 'POLICY NUMBER', value: 'policy_number', is_active: true },
  {
    text: 'CREATED DATE',
    value: 'created_at',
    is_active: true,
  },
  { text: 'LAST MODIFIED DATE', value: 'updated_at', is_active: true },
  { text: 'NATIONALITY', value: 'nationality', is_active: true },
  { text: 'TRANSAPP CODE', value: 'transapp_code', is_active: true },
  { text: 'SOURCE', value: 'source', is_active: true },
  { text: 'LOST REASON', value: 'lost_reason', is_active: true },
  { text: 'PRICE', value: 'premium', is_active: true },
  {
    text: 'Previous Policy Number',
    value: 'previous_quote_policy_number',
    is_active: true,
  },
]);

const advisorOptions = computed(() => {
  return page.props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.roles[0].name
      ? advisor.name + ' - ' + advisor.roles[0]?.name
      : advisor.name,
  }));
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
  router.visit(route('life-quotes-list'), {
    method: 'get',
    data: {
      ...filters,
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
  router.visit(route('life-quotes-list'), {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}

function setQueryFilters() {
  let query = router.page.url.split('?')[1];
  if (query) {
    query = query.split('&');
    query.forEach(item => {
      const [key, value] = item.split('=');

      if (key === 'quote_status_id[]' || key === 'advisor_id[]') {
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
const quotesSelected = ref([]);

const assignForm = useForm({
  assigned_to_id_new: null,
  modelType: 'life',
  selectTmLeadId: '',
});

function onAssignLead(isValid) {
  if (isValid) {
    const selected = quotesSelected.value.map(e => e.id);
    assignForm
      .transform(data => ({
        ...data,
        selectTmLeadId: `${selected}`,
      }))
      .post('/quotes/life/manualLeadAssign', {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
          let title =
            quotesSelected.value.length > 1
              ? 'Life Leads Assigned'
              : 'Life Lead Assigned';
          quotesSelected.value = [];
          notification.success({
            title: title,
            position: 'top',
          });
        },
      });
  }
}

const canExport = ref(false);
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

const onExport = () => {
  const data = { ...filters };
  delete data.page;
  Object.keys(data).forEach(
    key => (data[key] === '' || data[key].length === 0) && delete data[key],
  );
  const url = route('data-extraction', 'life');
  window.open(url + '?' + new URLSearchParams(data).toString());
};

watch(
  () => filters,
  () => {
    if (
      filters.created_at_start &&
      filters.created_at_end &&
      can(permissionsEnum.DATA_EXTRACTION)
    ) {
      canExport.value = true;
    } else {
      canExport.value = false;
    }
  },
  { deep: true, immediate: true },
);

onMounted(() => {
  setQueryFilters();
});
</script>

<template>
  <div>
    <Head title="Life List" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Lead List</h2>
      <div class="space-x-3 flex">
        <Link href="/quotes/life/cards">
          <x-button size="sm" color="#1d83bc" tag="div"> Cards View </x-button>
        </Link>
        <Link :href="route('life-quotes-create')">
          <x-button size="sm" color="#ff5e00" tag="div"> Create Lead </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="filterQuotes" :auto-focus="false">
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
            :options="
              quoteStatuses.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
          />
        </x-field>
        <x-field label="Advisor">
          <ComboBox
            v-if="!hasRole(rolesEnum.TravelAdvisor)"
            v-model="filters.advisor_id"
            placeholder="Search by Advisor"
            :options="advisorOptions"
          />
        </x-field>
        <x-field label="Policy Number">
          <x-input
            v-model="filters.previous_quote_policy_number_text"
            type="text"
            name="previous_quote_policy_number"
            class="w-full"
            placeholder="Policy Number"
          />
        </x-field>
        <x-select
          v-model="filters.is_renewal"
          placeholder="Renewal"
          label="Renewal"
          :options="[
            { value: 'Yes', label: 'Yes' },
            { value: 'No', label: 'No' },
            { value: '', label: 'All' },
          ]"
        />
        <DatePicker
          v-model="filters.payment_due_date"
          label="Payment Due Date"
          class="w-full"
          range
          multi-calendars
          multi-calendars-solo
        />
        <DatePicker
          v-model="filters.booking_date"
          label="Booking Date"
          class="w-full"
          range
          multi-calendars
          multi-calendars-solo
        />
      </div>

      <div class="flex justify-between gap-3 mb-4 mt-1">
        <div v-if="can(permissionsEnum.DATA_EXTRACTION)">
          <x-button
            v-if="canExport"
            size="sm"
            color="emerald"
            class="justify-self-start"
            @click.prevent="onExport"
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
        <div class="flex gap-3 justify-self-end">
          <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
          <x-button size="sm" color="primary" @click.prevent="resetFilters">
            Reset
          </x-button>
        </div>
      </div>
    </x-form>

    <Transition name="fade">
      <div v-if="quotesSelected.length > 0" class="mb-4">
        <div
          class="px-4 py-6 rounded shadow mb-4 bg-primary-50/50"
          v-if="isManualAllocationAllowed"
        >
          <x-form @submit="onAssignLead" :auto-focus="false">
            <div class="w-full flex flex-col md:flex-row gap-4">
              <x-select
                v-model="assignForm.assigned_to_id_new"
                label="Assign Advisor"
                :options="advisorOptions"
                placeholder="Select Advisor"
                class="flex-1 w-auto"
                :rules="[rules.isRequired]"
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
          :href="route('life-quotes-show', uuid)"
          class="text-primary-500 hover:underline"
        >
          <span>{{ code }}</span>
        </Link>
      </template>
      <template #item-advisor="{ advisor }">
        {{ advisor?.name }}
      </template>
      <template #item-quote_status="{ quote_status }">
        {{ quote_status?.code }}
      </template>
      <template #item-nationality="{ nationality }">
        {{ nationality?.code }}
      </template>
      <template #item-lost_reason="{ life_quote_request_detail }">
        {{ life_quote_request_detail?.lost_reason?.text }}
      </template>

      <!-- <template #item-is_ecommerce="{ is_ecommerce }">
        <div class="text-center">
          <x-tag size="sm" :color="is_ecommerce ? 'success' : 'error'">
            {{ is_ecommerce ? 'Yes' : 'No' }}
          </x-tag>
        </div>
      </template> -->
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
