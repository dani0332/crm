<script setup>
const page = usePage();
const toast = useToast();
const comp_for_car_revival = 'carrevival';
const comp_for_car = 'car';

const loader = reactive({
  table: false,
  export: false,
});

const props = defineProps({
  dynamic_route: {
    type: String,
    default: '',
    required: true,
  },
});

const batchOptions = computed(() => {
  return page.props.leadStatuses.batches.map(batch => ({
    value: batch.id,
    label: batch.name,
  }));
});

const paymentStatusOptions = computed(() => {
  return page.props.leadStatuses.payment_statuses.map(payment_status => ({
    value: payment_status.id,
    label: payment_status.text,
  }));
});

const leadStatusOptions = computed(() => {
  return page.props.leadStatuses.lead_statuses.map(lead_status => ({
    value: lead_status.id,
    label: lead_status.text,
  }));
});

const tierOptions = computed(() => {
  return page.props.leadStatuses.tiers.map(tier => ({
    value: tier.id,
    label: tier.name,
  }));
});

const vehicleTypeOptions = computed(() => {
  return page.props.leadStatuses.vehicle_types.map(vehicle_type => ({
    value: vehicle_type.id,
    label: vehicle_type.text,
  }));
});

const typeOfInsuranceOptions = computed(() => {
  return page.props.leadStatuses.types_of_insurance.map(type_of_insurance => ({
    value: type_of_insurance.id,
    label: type_of_insurance.text,
  }));
});

const currentlyInsuredWith = computed(() => {
  return page.props.leadStatuses.currently_insured_with_options.map(
    currently_insured_with => ({
      value: currently_insured_with.id,
      label: currently_insured_with.text,
    }),
  );
});

const advisorOptions = computed(() => {
  return page.props.leadStatuses.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.name,
  }));
});

const filters = reactive({
  code: '',
  quote_batch_id: [],
  first_name: '',
  last_name: '',
  email: '',
  mobile_no: '',
  created_at_start: '',
  created_at_end: '',
  advisor_date_start: '',
  advisor_date_end: '',
  payment_status_id: [],
  is_ecommerce: '',
  quote_status_id: [],
  tier_id: [],
  vehicle_type_id: '',
  car_type_insurance_id: '',
  currently_insured_with: '',
  renewal_batch: '',
  policy_expiry_date_start: '',
  policy_expiry_date_end: '',
  policy_number: '',
  advisor_id: [],
  page: 1,
});

function setQueryStringFilters() {
  let queryString = window.location.search;
  let urlParams = new URLSearchParams(queryString);

  if (urlParams.has('code')) {
    filters.code = urlParams.get('code');
  }
  if (urlParams.has('quote_batch_id')) {
    filters.quote_batch_id = urlParams
      .getAll('quote_batch_id')
      .map(batch_code => parseInt(batch_code));
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
  if (urlParams.has('advisor_date_start')) {
    filters.created_at_start = urlParams.get('advisor_date_start');
  }
  if (urlParams.has('advisor_date_end')) {
    filters.created_at_end = urlParams.get('advisor_date_end');
  }
  if (urlParams.has('payment_status_id[]')) {
    filters.payment_status_id = urlParams
      .getAll('payment_status_id[]')
      .map(status => parseInt(status));
  }
  if (urlParams.has('is_ecommerce')) {
    filters.is_ecommerce = urlParams.get('is_ecommerce');
  }
  if (urlParams.has('quote_status_id[]')) {
    filters.quote_status = urlParams
      .getAll('quote_status_id[]')
      .map(status => parseInt(status));
  }
  if (urlParams.has('tier_id[]')) {
    filters.tier_id = urlParams
      .getAll('tier_id[]')
      .map(status => parseInt(status));
  }
  if (urlParams.has('advisor_id[]')) {
    filters.advisor_id = urlParams
      .getAll('advisor_id[]')
      .map(status => parseInt(status));
  }
  if (urlParams.has('vehicle_type_id')) {
    filters.vehicle_type_id = urlParams.get('vehicle_type_id');
  }
  if (urlParams.has('car_type_insurance_id')) {
    filters.car_type_insurance_id = urlParams.get('car_type_insurance_id');
  }
  if (urlParams.has('currently_insured_with')) {
    filters.currently_insured_with = urlParams.get('currently_insured_with');
  }
  if (urlParams.has('renewal_batch')) {
    filters.renewal_batch = urlParams.get('renewal_batch');
  }
  if (urlParams.has('policy_expiry_date_start')) {
    filters.policy_expiry_date_start = urlParams.get(
      'policy_expiry_date_start',
    );
  }
  if (urlParams.has('policy_expiry_date_end')) {
    filters.policy_expiry_date_end = urlParams.get('policy_expiry_date_end');
  }
  if (urlParams.has('policy_number')) {
    filters.policy_number = urlParams.get('policy_number');
  }
}

function onSubmit(isValid) {
  if (isValid) {
    filters.page = 1;
    Object.keys(filters).forEach(
      key =>
        (filters[key] === '' || filters[key].length === 0) &&
        delete filters[key],
    );
    router.visit(`/quotes/${props.dynamic_route}`, {
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
  router.visit(`/quotes/${props.dynamic_route}`, {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}

const hasRole = role => useHasRole(role);
const rolesEnum = page.props.rolesEnum;

onMounted(() => {
  setQueryStringFilters();
});
</script>

<template>
  <div>
    <Head title="Car Revival List" />
    <div class="flex justify-between items-center">
      <h2 v-if="dynamic_route === comp_for_car" class="text-xl font-semibold">
        Car List
      </h2>
      <h2 v-else class="text-xl font-semibold">Car Revival List</h2>

      <div v-if="dynamic_route === comp_for_car" class="space-x-3">
        <Link href="/quotes/home-cards">
          <x-button size="sm" color="#1d83bc" tag="div"> Cards View </x-button>
        </Link>
        <Link href="/quotes/car/create">
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
        <ComboBox
          v-model="filters.quote_batch_id"
          label="Batch"
          name="quote_batch_id"
          placeholder="Search by Batch"
          :options="batchOptions"
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
        <DatePicker
          v-model="filters.advisor_date_start"
          name="advisor_assigned_date"
          label="Advisor Assign Date Start"
        />
        <DatePicker
          v-model="filters.advisor_date_end"
          name="advisor_assigned_date_end"
          label="Advisor Assign Date End"
        />
        <ComboBox
          v-model="filters.payment_status_id"
          label="Payment Status"
          name="payment_status_id"
          placeholder="Search by Payment Status"
          :options="paymentStatusOptions"
        />
        <x-select
          v-model="filters.is_ecommerce"
          name="is_ecommerce"
          label="Ecommerce"
          placeholder="Search by Ecommerce"
          :options="[
            { value: '', label: 'All' },
            { value: 1, label: 'Yes' },
            { value: 0, label: 'No' },
          ]"
          class="w-full"
        />
        <ComboBox
          v-model="filters.quote_status_id"
          label="Lead Status"
          name="quote_status_id"
          placeholder="Search by Lead Status"
          :options="leadStatusOptions"
        />
        <ComboBox
          v-model="filters.tier_id"
          label="Tier Name"
          name="tier_id"
          placeholder="Search by Tier"
          :options="tierOptions"
        />
        <ComboBox
          v-model="filters.vehicle_type_id"
          label="Vehicle Type"
          placeholder="Search by Vehicle Type"
          :options="vehicleTypeOptions"
          :single="true"
        />
        <ComboBox
          v-model="filters.car_type_insurance_id"
          label="Type of Car Insurance"
          placeholder="Search by Type of Car Insurance"
          :options="typeOfInsuranceOptions"
          :single="true"
        />
        <ComboBox
          v-model="filters.currently_insured_with"
          label="Currently Insured With"
          placeholder="Search by Currently Insured With"
          :options="currentlyInsuredWith"
          :single="true"
        />
        <x-input
          v-model="filters.renewal_batch"
          type="search"
          name="renewal_batch"
          label="Renewal Batch #"
          class="w-full"
          placeholder="Search by Renewal Batch #"
        />
        <DatePicker
          v-model="filters.policy_expiry_date_start"
          name="policy_expiry_date"
          label="Policy Expiry Date Start"
        />
        <DatePicker
          v-model="filters.policy_expiry_date_end"
          name="policy_expiry_date_end"
          label="Policy Expiry Date End"
        />
        <x-input
          v-model="filters.policy_number"
          type="search"
          name="previous_quote_policy_number"
          label="Previous Policy Number"
          class="w-full"
          placeholder="Search by Previous Policy Number"
        />
        <ComboBox
          v-if="!hasRole(rolesEnum.Advisor)"
          v-model="filters.advisor_id"
          name="advisor_id"
          label="Advisor"
          placeholder="Search by Advisor"
          :options="advisorOptions"
        />
      </div>
      <div class="flex justify-end gap-3 mb-4">
        <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
        <x-button size="sm" color="primary" @click.prevent="onReset">
          Reset
        </x-button>
      </div>
    </x-form>
  </div>
</template>
