<script setup>
defineProps({
  quotes: Object,
  dropdownSource: Object,
  permissions: Object,
  advisors: Object,
});

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

const quotesSelected = ref([]);
const canExport = ref(false);
const page = usePage();
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
  page: 1,
});

const loader = reactive({
  table: false,
  export: false,
});

const tableHeader = [
  { text: 'Ref-ID', value: 'code' },
  { text: 'FIRST NAME', value: 'first_name' },
  { text: 'LAST NAME', value: 'last_name' },
  { text: 'LEAD STATUS', value: 'quote_status_id_text' },
  { text: 'ADVISOR', value: 'advisor_id_text' },
  { text: 'CREATED DATE', value: 'created_at' },
  { text: 'LAST MODIFIED DATE', value: 'updated_at' },
  { text: 'DATE OF BIRTH', value: 'dob' },
  { text: 'TRANSAPP CODE', value: 'transapp_code' },
  { text: 'LOST REASON', value: 'lost_reason' },
  { text: 'SOURCE', value: 'source' },
  { text: 'PRICE', value: 'premium' },
  { text: 'POLICY NUMBER', value: 'policy_number' },
  { text: 'DESTINATION', value: 'destination_id_text' },
  { text: 'CURRENTLY LOCATED IN', value: 'currently_located_in_id_text' },
  { text: 'EXPIRY DATE', value: 'expiry_date' },
  { text: 'IS ECOMMERCE', value: 'is_ecommerce' },
  { text: 'PAYMENT STATUS', value: 'payment_status_id_text' },
];

const paymentStatusOptions = computed(() => {
  return page.props.dropdownSource.payment_status_id.map(item => {
    return {
      value: item.id,
      label: item.text,
    };
  });
});

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

  router.visit('/quotes/travel', {
    method: 'get',
    data: filters,

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
  router.visit('/quotes/travel', {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}

const advisorOptions = computed(() => {
  return page.props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.name,
  }));
});

const assignForm = useForm({
  assigned_to_id_new: null,
  modelType: 'Travel',
  selectTmLeadId: '',
  isManagerOrDeputy: page.props.permissions.isManagerOrDeputy,
  isLeadPool: page.props.permissions.isLeadPool,
  isManualAllocationAllowed: page.props.permissions.isManualAllocationAllowed,
});

function onAssignLead(isValid) {
  if (isValid) {
    const selected = quotesSelected.value.map(e => e.id);
    assignForm
      .transform(data => ({
        ...data,
        selectTmLeadId: `${selected}`,
      }))
      .post('/quotes/travel/manualLeadAssign', {
        preserveScroll: true,
        preserveState: true,
        onSuccess: res => {
          if (res.props.session.message) {
            let title = res.props.session.message;
            notification.success({
              title: title,
              position: 'top',
              timeout: 0,
            });
            return false;
          }

          let title = res.props.session.success;
          quotesSelected.value = [];
          notification.success({
            title: title,
            position: 'top',
          });
        },
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
  const url = route('data-extraction', 'travel');
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
    <Head title="Travel List" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Lead List</h2>
      <div class="space-x-3">
        <Link href="/quotes/travel-cards">
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
        />
        <DatePicker
          v-model="filters.created_at_end"
          name="created_at_end"
          label="Created Date End"
        />
        <ComboBox
          v-model="filters.quote_status_id"
          label="Lead Status"
          name="quote_status_id"
          placeholder="Search by Lead Status"
          :options="leadsStatusOptions"
        />
        <ComboBox
          v-if="!permissions.travelAdvisor"
          v-model="filters.advisor_id"
          label="Advisor"
          placeholder="Search by Advisor"
          :options="advisorsOptions"
        />
        <x-select
          v-model="filters.is_ecommerce"
          label="Ecommerce"
          placeholder="Search by Ecommerce"
          :options="[
            { value: '', label: 'All' },
            { value: 'Yes', label: 'Yes' },
            { value: 'No', label: 'No' },
          ]"
          class="w-full"
        />
        <x-select
          name="payment_status_id"
          v-model="filters.payment_status_id"
          label="Payment Status"
          placeholder="Search by Payment Status"
          :options="paymentStatusOptions"
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
          v-if="permissions.isManualAllocationAllowed == true"
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
        <a
          :href="`/quotes/travel/${uuid}`"
          class="text-primary-500 hover:underline"
        >
          {{ code }}
        </a>
      </template>
      <template #item-dob="{ dob }">
        <div class="text-center">
          {{ dob == '00-00-0000' ? '' : dob }}
        </div>
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
