<script setup>
const props = defineProps({
  quotes: Object,
  formOptions: Object,
  isManualAllocationAllowed: Boolean,
});

const page = usePage();
/** Sync filters from URL query (same approach as LifeRevivalQuote/Index.vue). */
let params = useUrlSearchParams('history');

const hasAnyRole = role => useHasAnyRole(role);
const hasRole = role => useHasRole(role);
const rolesEnum = page.props.rolesEnum;

const tableHeader = [
  { text: 'Ref-ID', value: 'code' },
  { text: 'FIRST NAME', value: 'first_name' },
  { text: 'LAST NAME', value: 'last_name' },
  { text: 'EMAIL', value: 'email' },
  { text: 'MOBILE', value: 'mobile_no' },
  { text: 'SOURCE', value: 'source' },
  { text: 'LEAD STATUS', value: 'quote_status.text' },
  { text: 'ADVISOR', value: 'advisor.name' },
  { text: 'CREATED DATE', value: 'created_at' },
  { text: 'ADVISOR ASSIGNED DATE', value: 'advisor_assigned_date' },
  { text: 'POLICY NUMBER', value: 'policy_number' },
  { text: 'PREMIUM', value: 'premium' },
];

const loader = reactive({
  table: false,
});

const quotesSelected = ref([]);

const readOnlyMode = reactive({
  isDisable: true,
});

const filters = reactive({
  code: '',
  first_name: '',
  last_name: '',
  email: '',
  mobile_no: '',
  created_at_start: '',
  created_at_end: '',
  assigned_to_date: '',
  insurer_aml_status: [],
  quote_status: [],
  advisors: [],
  lead_source: [],
  previous_quote_policy_number: '',
  policy_expiry_date_start: '',
  policy_expiry_date_end: '',
  is_renewal: '',
  renewal_batch: '',
  payment_due_date: '',
  booking_date: '',
  authorize_date: '',
  captured_date: '',
  private_client: '',
  page: 1,
});

function setQueryFilters() {
  const integerArrayFields = ['quote_status', 'insurer_aml_status'];
  const integerSingleFields = ['page'];

  const arrayParams = {};
  const singleParams = {};

  for (const [key, value] of Object.entries(params)) {
    const arrayMatch = key.match(/^(.+)\[(\d+)\]$/);

    if (arrayMatch) {
      const [, fieldName, index] = arrayMatch;
      if (!arrayParams[fieldName]) {
        arrayParams[fieldName] = [];
      }
      arrayParams[fieldName][parseInt(index, 10)] = value;
    } else if (key.includes('[]')) {
      const fieldName = key.substring(0, key.length - 2);
      arrayParams[fieldName] = Array.isArray(value) ? value : [value];
    } else {
      singleParams[key] = value;
    }
  }

  for (const [fieldName, values] of Object.entries(arrayParams)) {
    const cleanValues = values.filter(v => v !== undefined);

    if (fieldName === 'advisors') {
      filters[fieldName] = cleanValues
        .map(v =>
          String(v) === 'unassigned' ? 'unassigned' : parseInt(String(v), 10),
        )
        .filter(v => v === 'unassigned' || !Number.isNaN(v));
    } else if (integerArrayFields.includes(fieldName)) {
      filters[fieldName] = cleanValues
        .map(v => parseInt(String(v), 10))
        .filter(v => !Number.isNaN(v));
    } else {
      filters[fieldName] = cleanValues;
    }
  }

  for (const [key, value] of Object.entries(singleParams)) {
    if (
      integerSingleFields.includes(key) &&
      String(value).trim() !== '' &&
      !Number.isNaN(parseInt(String(value), 10))
    ) {
      filters[key] = parseInt(String(value), 10);
    } else {
      filters[key] = value;
    }
  }
}

onMounted(() => {
  setQueryFilters();
  readOnlyMode.isDisable = !can(permissionsEnum.All_QUOTES_VIEWONLY_ACCESS);
});

const advisorOptions = computed(() => {
  return (props.formOptions.advisors || []).map(advisor => ({
    value: advisor.id,
    label: advisor.name,
  }));
});

const modifiedAdvisorOptions = ref([]);

modifiedAdvisorOptions.value = [...advisorOptions.value];
modifiedAdvisorOptions.value.push({
  value: 'unassigned',
  label: 'Unassigned',
});

const leadStatusOptions = computed(() => {
  return (props.formOptions.leadStatuses || []).map(status => ({
    value: status.id,
    label: status.text,
  }));
});

const insurerAmlOptions = computed(() => {
  const raw = props.formOptions.insurerAmlStatuses || [];
  return [{ value: '', label: 'All' }, ...raw];
});

const leadSourceOptions = computed(() => {
  return (props.formOptions.leadSources || []).map(row => ({
    value: row.value,
    label: row.label,
  }));
});

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const notification = useToast();
const { isRequired } = useRules();
const canExport = ref(false);

const assignForm = useForm({
  assigned_to_id_new: null,
  modelType: 'Home',
  selectTmLeadId: '',
  isManualAllocationAllowed: props.isManualAllocationAllowed,
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
const exportLoader = ref(false);

watch(
  () => filters,
  () => {
    canExport.value =
      can(permissionsEnum.DATA_EXTRACTION) &&
      Boolean(filters.created_at_start && filters.created_at_end);
  },
  { deep: true, immediate: true },
);

const showExportRequirements = () => {
  if (!can(permissionsEnum.DATA_EXTRACTION)) {
    notification.error({
      title: 'You need data-extraction permission to export.',
      position: 'top',
    });

    return;
  }

  notification.error({
    title: 'Created date start and end are required to export.',
    position: 'top',
  });
};

const onDataExport = (exportType = 'download') => {
  if (filters.created_at_start && filters.created_at_end) {
    let diff, maxLimit, maxPeriod;

    if (exportType === 'email') {
      diff = calculateMonthsDifference(
        filters.created_at_start,
        filters.created_at_end,
      );
      maxLimit = 3;
      maxPeriod = '3 months';
    } else {
      diff = calculateDaysDifference(
        filters.created_at_start,
        filters.created_at_end,
      );
      maxLimit = 31;
      maxPeriod = '31 days';
    }

    if (diff > maxLimit) {
      notification.error({
        message: `Maximum of ${maxPeriod} (created date) are allowed to be exported.`,
        position: 'top',
      });

      return;
    }
  }

  filters.exportType = exportType;

  const data = useObjToUrl(filters);
  const url = route('data-extraction', 'HomeRevival');
  const payload = {
    quote_type_id: getQuoteTypeId(page.props.quoteTypes, 'Home'),
    exportType,
    url: `${url}?${new URLSearchParams(data).toString()}`,
  };

  exportLoader.value = true;
  logAndExportQuotes(payload)
    .then(result => {
      if (result.data.message) {
        notification.success({
          title: result.data.message,
          position: 'top',
        });
      }
      if (result) {
        setTimeout(() => {
          exportLoader.value = false;
        }, 1000);
      }
    })
    .catch(err => {
      notification.error({
        title: err.response?.data?.message
          ? err.response.data.message
          : 'Unable to start an export',
        position: 'top',
      });
      setTimeout(() => {
        exportLoader.value = false;
      }, 1000);
      throw err;
    });
};

function onSubmit(isValid) {
  if (isValid) {
    filters.page = 1;
    const payload = { ...filters };
    Object.keys(payload).forEach(key => {
      const v = payload[key];
      if (v === '' || (Array.isArray(v) && v.length === 0)) {
        delete payload[key];
      }
    });
    router.visit(route('home-revival-quotes-list'), {
      method: 'get',
      data: payload,
      preserveState: true,
      preserveScroll: true,
      onBefore: () => (loader.table = true),
      onFinish: () => (loader.table = false),
    });
  }
}

function onReset() {
  router.visit(route('home-revival-quotes-list'), {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}

const fixedValue = numberString => {
  const number = parseFloat(numberString);
  if (isNaN(number)) {
    return 'Invalid number';
  }
  if (number === Math.floor(number)) {
    return number.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  }
  return parseFloat(number.toFixed(2)).toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};
</script>

<template>
  <div>
    <Head title="Home Revival List" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Home Revival Quotes</h2>
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
        <DatePicker
          v-model="filters.assigned_to_date"
          name="assigned_to_date"
          label="Advisor Assigned Date"
        />
        <x-select
          v-model="filters.insurer_aml_status"
          label="Insurer AML Status"
          name="insurer_aml_status"
          placeholder="Insurer AML"
          :options="insurerAmlOptions.filter(o => o.value !== '')"
          filterable
          multiple
          truncate
          class="w-full"
        />
        <x-select
          v-model="filters.quote_status"
          label="Lead Status"
          name="quote_status"
          placeholder="Search by Lead Status"
          :options="leadStatusOptions"
          filterable
          filterPlaceholder="Filter Lead Status...."
          multiple
          truncate
          class="w-full"
        >
          <template #content-footer>
            <ui-select-actions
              @select-all="
                filters.quote_status = leadStatusOptions.map(s => s.value)
              "
              @clear="filters.quote_status = []"
            />
          </template>
        </x-select>
        <x-select
          v-if="
            !hasAnyRole([
              rolesEnum.RMAdvisor,
              rolesEnum.EBPAdvisor,
              rolesEnum.CarAdvisor,
            ])
          "
          v-model="filters.advisors"
          label="Advisor"
          placeholder="Search by Advisor"
          :options="modifiedAdvisorOptions"
          filterable
          filterPlaceholder="Filter Advisor...."
          multiple
          truncate
          class="w-full"
        >
          <template #content-footer>
            <ui-select-actions
              @select-all="filters.advisors = advisorOptions.map(a => a.value)"
              @clear="filters.advisors = []"
            />
          </template>
        </x-select>
        <x-select
          v-model="filters.lead_source"
          label="Lead Source"
          name="lead_source"
          placeholder="Select Lead Source"
          :options="leadSourceOptions"
          filterable
          multiple
          truncate
          class="w-full"
        >
          <template #content-footer>
            <ui-select-actions
              @select-all="
                filters.lead_source = leadSourceOptions.map(o => o.value)
              "
              @clear="filters.lead_source = []"
            />
          </template>
        </x-select>
        <x-input
          v-model="filters.previous_quote_policy_number"
          type="search"
          name="previous_quote_policy_number"
          label="Policy Number"
          class="w-full"
          placeholder="Search by Policy Number"
        />
        <DatePicker
          v-model="filters.policy_expiry_date_start"
          name="policy_expiry_date_start"
          label="Policy Expiry Start Date"
        />
        <DatePicker
          v-model="filters.policy_expiry_date_end"
          name="policy_expiry_date_end"
          label="Policy Expiry End Date"
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
        <x-input
          v-model="filters.renewal_batch"
          type="text"
          name="renewal_batch"
          label="Renewal Batch"
          class="w-full"
          placeholder="Search by Renewal Batch"
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
        <DatePicker
          v-model="filters.authorize_date"
          label="Payment Authorized Date"
          class="w-full"
          range
          multi-calendars
          multi-calendars-solo
        />
        <DatePicker
          v-model="filters.captured_date"
          label="Payment Captured Date"
          class="w-full"
          range
          multi-calendars
          multi-calendars-solo
        />
        <x-select
          v-model="filters.private_client"
          label="Private Client"
          placeholder="Private client"
          :options="[
            { value: '', label: 'All' },
            { value: 'yes', label: 'Yes' },
            { value: 'no', label: 'No' },
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
            class="justify-self-start mr-3"
            :loading="exportLoader"
            @click.prevent="onDataExport()"
          >
            Export
          </x-button>
          <x-button
            v-if="canExport"
            size="sm"
            color="emerald"
            class="justify-self-start mr-3"
            :loading="exportLoader"
            @click.prevent="onDataExport('email')"
          >
            Export via email
          </x-button>
          <x-tooltip v-else placement="right">
            <div class="inline-flex gap-3">
              <x-button
                tag="div"
                size="sm"
                color="emerald"
                class="opacity-50 cursor-not-allowed"
                @click.prevent="showExportRequirements"
              >
                Export
              </x-button>
              <x-button
                tag="div"
                size="sm"
                color="emerald"
                class="opacity-50 cursor-not-allowed"
                @click.prevent="showExportRequirements"
              >
                Export via email
              </x-button>
            </div>
            <template #tooltip>
              <span class="font-medium">
                data-extraction permission and created date start &amp; end are
                required to export.
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

    <section
      v-if="quotesSelected.length > 0 && !can(permissionsEnum.VIEW_ALL_LEADS)"
      class="mb-4"
    >
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
              :rules="[isRequired]"
              label="Assign Advisor"
              filterable
              v-if="readOnlyMode.isDisable === true"
            />
            <div class="mb-3 md:pt-6">
              <x-button
                color="orange"
                size="sm"
                type="submit"
                :loading="assignForm.processing"
                v-if="readOnlyMode.isDisable === true"
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
    >
      <template #item-code="{ code, uuid }">
        <Link
          :href="route('home-revival-quotes-show', uuid)"
          class="text-primary-500 hover:underline"
        >
          {{ code }}
        </Link>
      </template>
      <template #item-premium="item">
        <p v-if="item.premium != null">{{ fixedValue(item.premium) }}</p>
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
