<script setup>
const props = defineProps({
  quotes: Object,
  formOptions: Object,
});

const page = usePage();
const hasAnyRole = role => useHasAnyRole(role);
const hasRole = role => useHasRole(role);
const rolesEnum = page.props.rolesEnum;


const tableHeader = [
  { text: 'Ref-ID', value: 'code' },
  { text: 'FIRST NAME', value: 'first_name' },
  { text: 'LAST NAME', value: 'last_name' },
  { text: 'EMAIL', value: 'email' },
  { text: 'MOBILE', value: 'mobile_no' },
  { text: 'LEAD STATUS', value: 'quote_status.text' },
  { text: 'ADVISOR', value: 'advisor.name' },
  { text: 'INSURER AML', value: 'insurer_aml_status_text' },
  { text: 'CREATED DATE', value: 'created_at' },
  { text: 'LAST MODIFIED', value: 'updated_at' },
  { text: 'POLICY NUMBER', value: 'policy_number' },
  { text: 'PREV POLICY #', value: 'previous_quote_policy_number' },
  { text: 'POLICY EXPIRY', value: 'policy_expiry_date' },
  { text: 'RENEWAL BATCH', value: 'renewal_batch' },
  { text: 'PLAN TYPE', value: 'life_quote_purpose' },
  { text: 'TENURE', value: 'life_quote_tenure' },
  { text: 'SUM ASSURED', value: 'sum_insured_value' },
  { text: 'PREMIUM', value: 'premium' },
  { text: 'PAYMENT STATUS', value: 'payment_status.text' },
  { text: 'SOURCE', value: 'source' },
  { text: 'PRIVATE CLIENT', value: 'pc_qualified_formatted' },
];

const loader = reactive({
  table: false,
});

const filters = reactive({
  code: '',
  first_name: '',
  last_name: '',
  email: '',
  mobile_no: '',
  created_at_start: '',
  created_at_end: '',
  assigned_to_date_start: '',
  assigned_to_date_end: '',
  quote_status: [],
  advisors: [],
  insurer_aml_status: [],
  policy_number: '',
  policy_expiry_date_start: '',
  policy_expiry_date_end: '',
  is_renewal: '',
  renewal_batch: '',
  purpose_of_insurance_id: [],
  tenure_of_insurance_id: [],
  private_client: '',
  payment_due_date: '',
  booking_date: '',
  authorize_date: '',
  captured_date: '',
  sum_insured_currency_id: null,
  sum_insured_range: '',
  updated_at_start: '',
  updated_at_end: '',
  page: 1,
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

const purposeOptions = computed(() => {
  return (props.formOptions.purposeOfInsurance || []).map(row => ({
    value: row.id,
    label: row.text,
  }));
});

const tenureOptions = computed(() => {
  return (props.formOptions.insuranceTenures || []).map(row => ({
    value: row.id,
    label: row.text,
  }));
});

const currencyOptions = computed(() => {
  return (props.formOptions.currencies || []).map(row => ({
    value: row.id,
    label: row.text,
  }));
});

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
    router.visit(route('life-revival-quotes-list'), {
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
  router.visit(route('life-revival-quotes-list'), {
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
    <Head title="Life Revival List" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Life Revival List</h2>
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
          v-model="filters.assigned_to_date_start"
          name="assigned_to_date_start"
          label="Advisor Assigned Date Start"
        />
        <DatePicker
          v-model="filters.assigned_to_date_end"
          name="assigned_to_date_end"
          label="Advisor Assigned Date End"
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
              @select-all="
                filters.advisors = advisorOptions.map(a => a.value)
              "
              @clear="filters.advisors = []"
            />
          </template>
        </x-select>
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
        <x-input
          v-model="filters.policy_number"
          type="search"
          name="policy_number"
          label="Policy number"
          class="w-full"
          placeholder="Search by policy number"
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
        <DatePicker
          v-model="filters.updated_at_start"
          name="updated_at_start"
          label="Last Modified Start Date"
        />
        <DatePicker
          v-model="filters.updated_at_end"
          name="updated_at_end"
          label="Last Modified End Date"
        />
        <x-select
          v-model="filters.purpose_of_insurance_id"
          label="Plan Type"
          placeholder="Plan type"
          :options="purposeOptions"
          filterable
          multiple
          truncate
          class="w-full"
        >
          <template #content-footer>
            <ui-select-actions
              @select-all="
                filters.purpose_of_insurance_id = purposeOptions.map(
                  o => o.value,
                )
              "
              @clear="filters.purpose_of_insurance_id = []"
            />
          </template>
        </x-select>
        <x-select
          v-model="filters.tenure_of_insurance_id"
          label="Tenure of Cover"
          placeholder="Tenure"
          :options="tenureOptions"
          filterable
          multiple
          truncate
          class="w-full"
        >
          <template #content-footer>
            <ui-select-actions
              @select-all="
                filters.tenure_of_insurance_id = tenureOptions.map(
                  o => o.value,
                )
              "
              @clear="filters.tenure_of_insurance_id = []"
            />
          </template>
        </x-select>
        <div class="grid sm:grid-cols-2 md:grid-cols-2 gap-1">
          <x-select
            v-if="!hasRole(rolesEnum.LifeAdvisor)"
            v-model="filters.sum_insured_currency_id"
            placeholder="Currency"
            label="Sum Assured"
            :options="currencyOptions"
            class="w-full"
          />
          <x-select
            v-if="!hasRole(rolesEnum.LifeAdvisor)"
            v-model="filters.sum_insured_range"
            placeholder="Value Range"
            label="&nbsp;"
            :options="[
              { value: 'lt500k', label: 'Less than 500k' },
              { value: '500k-1m', label: '500k to less than 1M' },
              { value: 'gte1m', label: 'Greater than or equal to 1M' },
            ]"
              class="border-l-0 rounded-tl-none rounded-bl-none"
          />
        </div>
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
        <div />
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
      :loading="loader.table"
      :headers="tableHeader"
      :items="quotes.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #item-code="{ code, uuid }">
        <Link
          :href="route('life-revival-quotes-show', uuid)"
          class="text-primary-500 hover:underline"
        >
          {{ code }}
        </Link>
      </template>
      <template #item-premium="item">
        <p v-if="item.premium != null">{{ fixedValue(item.premium) }}</p>
      </template>
      <template #item-life_quote_purpose="item">
        <span>{{ item.life_quote?.purpose_of_insurance?.text ?? '' }}</span>
      </template>
      <template #item-life_quote_tenure="item">
        <span>{{ item.life_quote?.insurance_tenure?.text ?? '' }}</span>
      </template>
      <template #item-sum_insured_value="item">
        <p v-if="item.life_quote?.sum_insured_value != null">
          {{ fixedValue(item.life_quote.sum_insured_value) }}
        </p>
      </template>
    </DataTable>
  </div>
</template>
