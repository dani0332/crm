<script setup>

defineProps({
  quotes: Object,
  dropdownSource: Object,
  session: Object,
});

const page = usePage();

const notification = useNotifications('toast');
const { isRequired } = useRules();

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
  quote_status_id: '',
  advisor_id: '',
  insurance_type: '',
  page: 1,
});

const leadStatusOptions = computed(() => {
  return page.props.dropdownSource.quote_status_id.map(status => ({
    value: status.id,
    label: status.text,
  }));
});

const advisorOptions = computed(() => {
  return page.props.dropdownSource.advisor_id.map(advisor => ({
    value: advisor.id,
    label: advisor.name,
  }));
});

const insuranceTypeOptions = computed(() => {
  return page.props.dropdownSource.business_type_of_insurance_id.map(
    advisor => ({
      value: advisor.id,
      label: advisor.text,
    }),
  );
});

const tableHeader = [
  { text: 'CDB ID', value: 'code' },
  { text: 'FIRST NAME', value: 'first_name' },
  { text: 'LAST NAME', value: 'last_name' },
  { text: 'LEAD STATUS', value: 'quote_status_id_text' },
  { text: 'ADVISOR', value: 'advisor_id_text' },
  { text: 'PREMIUM', value: 'premium' },
  { text: 'Company Name', value: 'company_name' },
  { text: 'POLICY NUMBER', value: 'policy_number' },
  { text: 'LOST REASON', value: 'lost_reason' },
  {
    text: 'BUSINESS INSURANCE TYPE',
    value: 'business_type_of_insurance_id_text',
  },
  { text: 'NUMBER OF EMPLOYEES', value: 'number_of_employees' },
  { text: 'SOURCE', value: 'source' },
  { text: 'CREATED DATE', value: 'created_at' },
  { text: 'Updated Date', value: 'updated_at' },
];

function resetFilters() {
  for (const key in filters) {
    filters[key] = '';
  }
  filterQuotes(true);
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
  router.visit('/quotes/business', {
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
        onSuccess: res => {
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

onMounted(() => {
  setQueryFilters();
});
</script>

<template>
  <div>
    <Head title="Business Quote List" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Lead List</h2>
      <div class="space-x-3">
        <Link href="/quotes/business/cards">
          <x-button size="sm" color="#1d83bc" tag="div"> Cards View </x-button>
        </Link>
        <Link href="/quotes/business/create">
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

        <x-select
          v-model="filters.quote_status_id"
          label="Lead Status"
          name="quote_status_id"
          placeholder="Search by Lead Status"
          :options="leadStatusOptions"
        />

        <x-select
          v-model="filters.insurance_type"
          label="BUSINESS INSURANCE TYPE"
          placeholder="INSURANCE TYPE"
          :options="insuranceTypeOptions"
        />

        <x-select
          v-model="filters.advisor_id"
          label="Advisor"
          placeholder="Search by Advisor"
          :options="advisorOptions"
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
      <div v-if="quotesSelected.length > 0" class="mb-4">
        <div class="px-4 py-6 rounded shadow mb-4 bg-primary-50/50">
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

        <ExportExcel
          :data="quotesSelected"
          :columns="tableHeader"
          :filename="'Business-List'"
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
          :href="`/quotes/business/${uuid}`"
          class="text-primary-500 hover:underline"
        >
          {{ code }}
        </Link>
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
