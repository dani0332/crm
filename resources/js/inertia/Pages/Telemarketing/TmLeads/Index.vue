<script setup>
import LeadAssignment from '../../PersonalQuote/Partials/LeadAssignment.vue';

const props = defineProps({
  tmLeadStatuses: Array,
  handlers: Array,
  tmLeadTypes: Array,
  tmInsuranceTypes: Array,
  isCurrentUserIsAdvisor: String,
  queryTmLeads: Object,
});

const page = usePage();
const { isRequired } = useRules();

const loader = reactive({ table: false });
const itemsSelected = ref([]);

const hasRole = role => useHasRole(role);
const rolesEnum = page.props.rolesEnum;

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const objToUrl = obj => useObjToUrl(obj);

const handlersOptions = ref([
  ...[
    { name: 'All', id: ' ' },
    { name: 'Unassigned', id: 'Unassigned' },
    { name: 'MyLeads', id: 'MyLeads' },
  ],
  ...props.handlers,
]);

const filters = reactive({
  searchType: 'cdbID',
  searchField: '',
  assigned_to_id: ' ',
  tm_lead_types_id: '',
  tm_lead_statuses_id: '',
  tmLeadsStartDate: '',
  tmLeadsEndDate: '',
  page: 1,
});

const showdates = computed(() => {
  return filters.searchType == 'created_at' ||
    filters.searchType == 'updated_at' ||
    filters.searchType == 'enquiry_date' ||
    filters.searchType == 'allocation_date' ||
    filters.searchType == 'next_followup_date'
    ? true
    : false;
});

const canAssignLead = computed(() => {
  return props.isCurrentUserIsAdvisor == '1' ? true : false;
});

const tableHeader = ref([
  { text: 'TM ID', value: 'cdb_id' },
  { text: 'CUSTOMER NAME', value: 'customer_name' },
  { text: 'INSURANCE TYPE', value: 'tm_insurance_types_text' },
  { text: 'LEAD STATUS', value: 'tm_lead_status_text' },
  { text: 'NOTES', value: 'notes' },
  { text: 'ENQUIER DATE', value: 'enquiry_date' },
  { text: 'ALLOCATION DATE', value: 'allocation_date' },
  { text: 'NEXT FOLLOW-UP DATE', value: 'next_followup_date' },
  { text: 'ADVISOR', value: 'handlers_name' },
  { text: 'CREATED AT', value: 'tm_created_at' },
  { text: 'UPDATED AT', value: 'tm_updated_at' },
]);

const onReset = () => {
  router.visit(route('tmleads-list'), {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
};

function onSubmit(isValid) {
  if (isValid) {
    filters.tmLeadsStartDate = filters.tmLeadsStartDate.split('T')[0];
    filters.tmLeadsEndDate = filters.tmLeadsEndDate.split('T')[0];
    filters.page = 1;

    router.visit(route('tmleads-list'), {
      method: 'get',
      data: useGenerateQueryString(filters),
      preserveState: true,
      preserveScroll: true,
      onBefore: () => (loader.table = true),
      onSuccess: () => (loader.table = false),
    });
  } else {
    console.log('Invalid');
  }
}

const onLeadAssigned = () => {
  itemsSelected.value = [];
};

const canExport = ref(false);
const params = useUrlSearchParams('history');

watch(
  () => filters,
  () => {
    const { tmLeadsStartDate, tmLeadsEndDate } = filters;

    canExport.value =
      tmLeadsStartDate &&
      tmLeadsEndDate &&
      (new Date(tmLeadsEndDate) - new Date(tmLeadsStartDate)) /
        (1000 * 60 * 60 * 24) <=
        30;
  },
  { deep: true, immediate: true },
);

function setQueryStringFilters() {
  for (const [key] of Object.entries(params)) {
    if (key.includes('[]')) {
      filters[key.replace('[]', '')] = params[key];
    } else {
      filters[key] = params[key];
    }
  }
}
onMounted(() => {
  setQueryStringFilters();
});
</script>
<template>
  <div>
    <Head title="TM Leads" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">TM Leads</h2>
      <Link
        v-if="can(permissionsEnum.TeleMarketingCreate)"
        :href="route('tmleads-create')"
      >
        <x-button size="sm" color="#ff5e00"> Create TM Lead </x-button>
      </Link>
    </div>
    <x-divider class="my-4" />
    <!--   filters     -->
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-4">
        <x-field label="Search By">
          <x-select
            v-model="filters.searchType"
            placeholder="Search by"
            :options="[
              { value: 'cdbID', label: 'TM ID' },
              { value: 'emailAddress', label: 'Email Address' },
              { value: 'phoneNumber', label: 'Phone Number' },
              { value: 'created_at', label: 'Created At' },
              { value: 'updated_at', label: 'Updated At' },
              { value: 'next_followup_date', label: 'Next Followup Date' },
              { value: 'enquiry_date', label: 'Enquiry Date' },
              { value: 'allocation_date', label: 'Allocation Date' },
            ]"
            class="w-full"
          />
        </x-field>
        <x-field label="Search Value">
          <x-input
            v-model="filters.searchField"
            placeholder="Search value"
            class="w-full"
          />
        </x-field>
        <x-field label="Lead Status">
          <x-select
            v-model="filters.tm_lead_statuses_id"
            placeholder="Lead status"
            :options="
              tmLeadStatuses.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            class="w-full"
          />
        </x-field>

        <x-field label="Lead Owner" v-if="canAssignLead">
          <x-select
            v-model="filters.assigned_to_id"
            placeholder="Search value"
            :options="
              handlersOptions.map(item => ({
                value: item.id,
                label: item.name,
              }))
            "
            class="w-full"
          />
        </x-field>
        <x-field label="Lead Type">
          <x-select
            v-model="filters.tm_lead_types_id"
            placeholder="Search value"
            :options="
              tmLeadTypes.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            class="w-full"
          />
        </x-field>
        <x-field label="Insurance Type">
          <x-select
            v-model="filters.tm_insurance_types_id"
            placeholder="Search value"
            :options="
              tmInsuranceTypes.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            class="w-full"
          />
        </x-field>
        <x-field label="Start Date" v-if="showdates">
          <DatePicker
            name="created_at_start"
            class="w-full"
            v-model="filters.tmLeadsStartDate"
            :rules="[isRequired]"
          />
        </x-field>
        <x-field label="End Date" v-if="showdates">
          <DatePicker
            name="created_at_end"
            class="w-full"
            v-model="filters.tmLeadsEndDate"
            :rules="[isRequired]"
          />
        </x-field>
      </div>
      <div class="flex justify-between gap-3 mb-4 mt-1">
        <div class="flex justify-between gap-3">
          <div>
            <x-button
              v-if="canExport"
              color="emerald"
              size="sm"
              :href="`/telemarketing/tmleads/export?${objToUrl(filters)}`"
              class="justify-self-start"
            >
              Export
            </x-button>
            <x-tooltip v-else placement="right">
              <x-button tag="div" size="sm" color="emerald"> Export </x-button>
              <template #tooltip>
                <span class="font-medium">
                  Export data requires created dates within the last 30 days.
                </span>
              </template>
            </x-tooltip>
          </div>
        </div>
        <div class="flex gap-3">
          <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
          <x-button size="sm" color="primary" @click.prevent="onReset">
            Reset
          </x-button>
        </div>
      </div>
    </x-form>

    <Transition name="fade">
      <div v-if="itemsSelected.length > 0 && canAssignLead" class="mb-4">
        <LeadAssignment
          :selected="itemsSelected.map(e => e.id)"
          :advisors="
            handlers.map(item => ({
              value: item.id,
              label: item.name,
            }))
          "
          :quoteType="'tmlead'"
          @success="onLeadAssigned"
        />
      </div>
    </Transition>

    <DataTable
      v-model:items-selected="itemsSelected"
      table-class-name="tablefixed"
      :headers="tableHeader"
      :loading="loader.table"
      :items="queryTmLeads.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #item-cdb_id="{ cdb_id }">
        <Link
          class="text-primary-500 hover:underline"
          :href="route('tmleads-show', cdb_id.split('-')[1])"
        >
          {{ cdb_id }}
        </Link>
      </template>
    </DataTable>

    <Pagination
      :links="{
        next: queryTmLeads.next_page_url,
        prev: queryTmLeads.prev_page_url,
        current: queryTmLeads.current_page,
        from: queryTmLeads.from,
        to: queryTmLeads.to,
      }"
    />
  </div>
</template>
