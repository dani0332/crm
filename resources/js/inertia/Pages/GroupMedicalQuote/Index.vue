<script setup>
defineProps({
  model: String,
  leadStatuses: Array,
  advisors: Array,
  isManagerORDeputy: Boolean,
  quotes: Object,
  isManualAllocationAllowed: Boolean,
});

const canExport = ref(false);
const page = usePage();
const notification = useNotifications('toast');
const { isRequired } = useRules();

const created_at_rule = v => {
  if (filters.created_at_end) {
    return isRequired(v);
  }
  return true;
};

const created_at_end_rule = v => {
  if (filters.created_at_start) {
    return isRequired(v);
  }
  return true;
};

const quotesSelected = ref([]);

const loader = reactive({
  table: false,
  export: false,
});

const filters = reactive({
  code: '',
  first_name: '',
  last_name: '',
  email: '',
  mobile_no: '',
  created_at_start: '',
  created_at_end: '',
  leadStatus: '',
  advisor_id: '',
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
const tableHeader = [
  { text: 'Ref-ID', value: 'code' },
  { text: 'FIRST NAME', value: 'first_name' },
  { text: 'LAST NAME', value: 'last_name' },
  { text: 'LEAD STATUS', value: 'leadStatus' },
  { text: 'ADVISOR', value: 'advisor_id_text' },
  { text: 'PRICE', value: 'premium' },
  { text: 'Company Name', value: 'company_name' },
  { text: 'POLICY NUMBER', value: 'policy_number' },
  { text: 'LOST REASON', value: 'lost_reason' },
  { text: 'SOURCE', value: 'source' },
  { text: 'CREATED AT', value: 'created_at' },
  { text: 'Updated AT', value: 'updated_at' },
];

function resetFilters() {
  for (const key in filters) {
    filters[key] = '';
  }
  router.visit('/medical/amt', {
    method: 'get',
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

function filterQuotes(isValid) {
  if (!isValid) {
    return;
  }
  for (const key in filters) {
    if (filters[key] === '') {
      delete filters[key];
    }
  }
  if (filters.created_at_start) {
    filters.created_at_start = filters.created_at_start.split('T')[0];
  }
  if (filters.created_at_end) {
    filters.created_at_end = filters.created_at_end.split('T')[0];
  }
  router.visit('/medical/amt', {
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

const assignForm = useForm({
  assigned_to_id_new: null,
  modelType: 'business',
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
      .post('/quotes/business/manualLeadAssign', {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
          displayNotification();
        },
      });
  }
}

function displayNotification() {
  const session = usePage().props.flash;
  for (const key in session) {
    notification[key]({
      title: session[key],
      position: 'top',
      timeout: 0,
    });
  }
}

function setQueryFilters() {
  let urlParams = new URLSearchParams(window.location.search);
  for (const [key, value] of urlParams) {
    if (key.includes('[')) {
      let index = key.replace('[]', '');
      filters[index] = urlParams.getAll(key).map(item => parseInt(item));
    } else {
      filters[key] = value.match(/^\d+$/) ? parseInt(value) : value;
    }
  }
}

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

const onDataExport = () => {
  const data = useObjToUrl(filters);
  const url = route('data-extraction', 'amt');
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

onMounted(() => {
  setQueryFilters();
});
</script>

<template>
  <div>
    <Head title="AMT List" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Lead List</h2>
      <div class="space-x-3">
        <Link href="/medical/amt/cards">
          <x-button size="sm" color="#1d83bc" tag="div"> Cards View </x-button>
        </Link>

        <Link href="/medical/amt/create">
          <x-button size="sm" color="#ff5e00" tag="div"> Create Lead </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="filterQuotes" :auto-focus="false">
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
          name="created_at_start"
          label="Created Date Start"
          :rules="[created_at_rule]"
        />
        <DatePicker
          v-model="filters.created_at_end"
          name="created_at_end"
          label="Created Date End"
          :rules="[created_at_end_rule]"
        />

        <x-select
          v-model="filters.leadStatus"
          label="Lead Status"
          name="leadStatus"
          placeholder="Search by Lead Status"
          :options="leadStatusOptions"
        />

        <x-select
          v-model="filters.advisor_id"
          label="Advisor"
          placeholder="Search by Advisor"
          :options="advisorOptions"
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
          v-if="isManualAllocationAllowed == true"
        >
          <x-form @submit="onAssignLead" :auto-focus="false">
            <div class="w-full flex flex-col md:flex-row gap-4">
              <x-select
                v-model="assignForm.assigned_to_id_new"
                label="Assign Advisor"
                :options="advisorOptions"
                placeholder="Select Advisor"
                class="flex-1 w-auto"
                :rules="[isRequired]"
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
        <a
          :href="`/medical/amt/${uuid}`"
          target="_blank"
          class="text-primary-500 hover:underline"
        >
          {{ code }}
        </a>
      </template>

      <template #item-source="{ source }">
        <a
          :href="source && source.includes('http') ? source : '#'"
          :target="source && source.includes('http') ? '_blank' : '_self'"
          class="text-primary-500 hover:underline"
        >
          {{ source }}
        </a>
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
