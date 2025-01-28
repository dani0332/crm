<script setup>
const props = defineProps({
  quotes: Object,
  formOptions: Array,
});

const page = usePage();
const notification = useToast();
const params = useUrlSearchParams('history');
const hasRole = role => useHasRole(role);
const hasAnyRole = role => useHasAnyRole(role);
const rolesEnum = page.props.rolesEnum;

const { isRequired } = useRules();
const tableHeader = [
  { text: 'Ref-ID', value: 'code' },
  { text: 'FIRST NAME', value: 'first_name' },
  { text: 'LAST NAME', value: 'last_name' },
  { text: 'LEAD STATUS', value: 'quote_status.text' },
  { text: 'ADVISOR', value: 'advisor.name' },
  { text: 'ASSIGNMENT TYPE', value: 'assignment_type_text' },
  { text: 'CREATED DATE', value: 'created_at' },
  { text: 'LAST MODIFIED DATE', value: 'updated_at' },
  { text: 'HEALTH TEAM TYPE', value: 'health_team_type' },
  {
    text: 'LOST REASON',
    value: 'health_quote_request_detail.lost_reason.text',
  },
  { text: 'STARTING FROM', value: 'price_starting_from' },
  { text: 'PRICE', value: 'premium' },
  { text: 'POLICY NUMBER', value: 'policy_number' },
  { text: 'SOURCE', value: 'source' },
  { text: 'LEAD TYPE', value: 'health_lead_type.text' },
  { text: 'SALARY BAND', value: 'salary_band.text' },
  { text: 'MEMBER CATEGORY', value: 'member_category.text' },
  { text: 'CURRENTLY INSURED WITH', value: 'current_provider.text' },
  { text: 'IS ECOMMERCE', value: 'is_ecommerce' },
  { text: 'Previous Policy Number', value: 'previous_quote_policy_number' },
  { text: 'Renewal Batch', value: 'renewal_batch' },
];

const assignForm = useForm({
  assign_team: null,
  assigned_to_id_new: null,
  assignment_type: '1',
  modelType: 'Health',
  selectTmLeadId: '',
  isManagerOrDeputy: 1,
  isLeadPool: null,
  isManualAllocationAllowed: 1,
});

const subTeamsOptions = [
  { value: 'Best', label: 'Best' },
  { value: 'Good', label: 'Good' },
  { value: 'Entry-Level', label: 'Entry-Level' },
  { value: 'Wow-Call', label: 'Wow-Call' },
  { value: 'No-Type', label: 'No-Type' },
];
const fixedValue = numberString => {
  const number = parseFloat(numberString);
  if (isNaN(number)) {
    return 'Invalid number';
  } else if (number === Math.floor(number)) {
    return number.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  } else {
    return parseFloat(number.toFixed(2)).toLocaleString(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  }
};

const subTeamOptions = [
  { value: '', label: 'All' },
  { value: 'RM-NB', label: 'RM-NB' },
  { value: 'RM-Speed', label: 'RM-Speed' },
  { value: 'EBP', label: 'EBP' },
  { value: 'Wow-Call', label: 'Wow-Call' },
  { value: 'No-Type', label: 'No-Type' },
];

const advisorOptions = computed(() => {
  return props.formOptions.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.name,
  }));
});
const modifiedAdvisorOptions = ref([]);

const quotesSelected = ref([]);

const readOnlyMode = reactive({
  isDisable: true,
});

modifiedAdvisorOptions.value = advisorOptions.value;

modifiedAdvisorOptions.value.push({
  value: 'unassigned',
  label: 'Unassigned',
});

const assignmentTypeOptions = [
  { value: '', label: 'Please select is assignment type' },
  { value: 1, label: 'System Assigned' },
  { value: 2, label: 'System ReAssigned' },
  { value: 3, label: 'Manual Assigned' },
  { value: 4, label: 'Manual ReAssigned' },
];
const leadStatusOptions = computed(() => {
  return props.formOptions.leadStatuses.map(status => ({
    value: status.id,
    label: status.text,
  }));
});

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
  sub_team: '',
  quote_status: [],
  advisors: [],
  is_ecommerce: '',
  is_renewal: '',
  page: 1,
  previous_quote_policy_number: '',
  renewal_batch: '',
  assigned_to_date_start: '',
  assigned_to_date_end: '',
});

function onSubmit(isValid) {
  if (isValid) {
    filters.page = 1;
    Object.keys(filters).forEach(
      key =>
        (filters[key] === '' || filters[key].length === 0) &&
        delete filters[key],
    );
    router.visit(route('health-revival-quotes-list'), {
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
  router.visit(route('health-revival-quotes-list'), {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}

function onAssignLead(isValid) {
  if (isValid) {
    const selected = quotesSelected.value.map(e => e.id);
    const url =
      assignForm.assign_team === 'Wow-Call'
        ? '/quotes/wcuAssign'
        : '/quotes/health/manualLeadAssign';
    assignForm
      .transform(data => ({
        ...data,
        selectTmLeadId: `${selected}`,
      }))
      .post(url, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
          quotesSelected.value = [];
          notification.success({
            title: 'Health Leads Assigned',
            position: 'top',
          });
        },
      });
  }
}
onMounted(() => {});
</script>

<template>
  <div>
    <Head title="Health Revival List" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Health Revival List</h2>
      <LeadAssignedWidget
        v-if="hasAnyRole([rolesEnum.RMAdvisor, rolesEnum.EBPAdvisor])"
        :todayAutoCount="todayAutoCount"
        :todayManualCount="todayManualCount"
        :yesterdayAutoCount="yesterdayAutoCount"
        :yesterdayManualCount="yesterdayManualCount"
        :userMaxCap="userMaxCap"
      />
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
        <x-select
          v-model="filters.sub_team"
          label="Sub Team"
          :options="subTeamOptions"
          placeholder="Search by Sub Team"
          class="w-full"
        />

        <ComboBox
          v-model="filters.quote_status"
          label="Lead Status"
          name="quote_status"
          placeholder="Search by Lead Status"
          :options="leadStatusOptions"
        />
        <ComboBox
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
        />
        <x-select
          v-model="filters.is_ecommerce"
          label="Is Ecommerce"
          placeholder="Search by Ecommerce"
          :options="[
            { value: '', label: 'All' },
            { value: 'Yes', label: 'Yes' },
            { value: 'No', label: 'No' },
          ]"
          class="w-full"
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
        <x-select
          v-if="
            !hasAnyRole([
              rolesEnum.RMAdvisor,
              rolesEnum.EBPAdvisor,
              rolesEnum.CarAdvisor,
            ])
          "
          v-model="filters.assignment_type"
          label="Assignment Type"
          name="assignment_type"
          :options="assignmentTypeOptions"
          placeholder="Please select assignment type"
          class="w-full"
        />
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

        <DatePicker
          v-if="!hasAnyRole([rolesEnum.CarAdvisor])"
          v-model="filters.assigned_to_date_start"
          name="assigned_to_date_start"
          label="Advisor Assigned Date Start"
        />
        <DatePicker
          v-if="!hasAnyRole([rolesEnum.CarAdvisor])"
          v-model="filters.assigned_to_date_end"
          name="assigned_to_date_end"
          label="Advisor Assigned Date End"
        />
      </div>
      <div class="flex justify-between gap-3 mb-4 mt-1">
        <div v-if="0">
          <x-button
            v-if="canExport"
            size="sm"
            color="emerald"
            :href="`/health/leads-export?${objToUrl(filters)}`"
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

    <Transition name="fade">
      <div
        v-if="
          quotesSelected.length > 0 &&
          hasRole(rolesEnum.SuperManagerLeadAllocation)
        "
        class="mb-4"
      >
        <div class="px-4 py-6 rounded shadow mb-4 bg-primary-50/50">
          <x-form @submit="onAssignLead" :auto-focus="false">
            <div class="w-full flex flex-col md:flex-row gap-4">
              <x-select
                v-model="assignForm.assign_team"
                label="Assign Subteam"
                :options="subTeamsOptions"
                placeholder="Select Subteam"
                class="flex-1 w-auto"
                :rules="[isRequired]"
                filterable
                v-if="readOnlyMode.isDisable === true"
              />
              <x-select
                v-model="assignForm.assigned_to_id_new"
                label="Assign Advisor"
                :options="advisorOptions"
                placeholder="Select Advisor"
                class="flex-1 w-auto"
                :rules="[isRequired]"
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
          :href="route('health-revival-quotes-show', uuid)"
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
      <template #item-price_starting_from="item">
        <p v-if="item.price_starting_from != null">
          {{ fixedValue(item.price_starting_from) }}
        </p>
      </template>

      <template #item-premium="item">
        <p v-if="item.premium != null">{{ fixedValue(item.premium) }}</p>
      </template>
    </DataTable>
  </div>
</template>
