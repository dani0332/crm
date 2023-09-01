<script setup>
defineProps({
  quotes: Object,
  advisors: Array,
  dropdownSource: Object
});

const page = usePage();
const notification = useToast();
const params = useUrlSearchParams('history');

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

const tableHeader = [
  { text: 'REF-ID', value: 'code' },
  { text: 'BATCH', value: 'quote_batch_id_text' },
  { text: 'FIRST NAME', value: 'first_name' },
  { text: 'LAST NAME', value: 'last_name' },
  { text: 'DATE OF BIRTH', value: 'dob' },
  { text: 'LEAD SOURCE', value: 'source' },
  { text: 'NATIONALITY', value: 'nationality_id_text' },
  { text: 'UAE LICENCE HELD FOR', value: 'uae_license_held_for_id_text' },
  { text: 'CAR MAKE', value: 'car_make_id_text' },
  { text: 'CAR MODEL', value: 'car_model_id_text' },
  { text: 'CAR MODEL YEAR', value: 'year_of_manufacture' },
  { text: 'FIRST REGISTRATION DATE', value: 'year_of_first_registration' },
  { text: 'CAR VALUE', value: 'car_value' },
  { text: 'CAR VALUE (AT ENQUIRY)', value: 'car_value_tier' },
  { text: 'VEHICLE TYPE', value: 'vehicle_type_id_text' },
  { text: 'TYPE OF CAR INSURANCE', value: 'current_insurance_status' },
  { text: 'CURRENTLY INSURED WITH', value: 'currently_insured_with_text' },
  { text: 'CLAIM HISTORY', value: 'claim_history_id_text' },
  { text: 'CREATED DATE', value: 'created_at' },
  { text: 'ADVISOR ASSIGNED DATE', value: 'advisor_assigned_date' },
  { text: 'LEAD COST', value: 'cost_per_lead' },
  { text: 'LEAD STATUS', value: 'quote_status_id_text' },
  { text: 'PAYMENT STATUS', value: 'payment_status_id_text' },
  { text: 'ECOMMERCE', value: 'is_ecommerce' },
  { text: 'TIER NAME', value: 'tier_id_text' },
  { text: 'VISIT COUNT', value: 'visit_count' },
  { text: 'FOLLOW UP DATE', value: 'next_followup_date' },
  { text: 'LAST MODIFIED DATE', value: 'updated_at' },
  { text: 'UPDATED BY', value: 'updated_by' },
  { text: 'ADDITIONAL NOTES', value: 'additional_notes' },
  { text: 'ADVISOR', value: 'advisor_id_text' },
  { text: 'POLICY NUMBER', value: 'policy_number' },
  { text: 'RENEWAL EXPIRY DATE', value: 'renewal_expiry_date' },
  { text: 'IS GCC STANDARD', value: 'is_gcc_standard' },
  { text: 'IS VEHICLE MODIFIED', value: 'is_modified' },
  { text: 'PRICE', value: 'premium' },
  { text: 'LOST REASON', value: 'lost_reason' },
  { text: 'QUOTE LINK', value: 'quote_link' },  
];

const ecommerceOptions = [
    { value: '', label: 'Please select is ecommerce' },
    { value: 'Yes', label: 'Yes' },
    { value: 'No', label: 'No' },
]

const advisorOptions = computed(() => {
    return page.props.advisors.map(advisor => ({
        value: advisor.id,
        label: advisor.name,
    }));
});

const leadStatuses = computed(() => {
    return page.props.dropdownSource.quote_status_id.map(status => ({
        value: status.id,
        label: status.text,
    }));
});

const leadTiers = computed(() => {
    return page.props.dropdownSource.tier_id.map(tier => ({
        value: tier.id,
        label: tier.name,
    }));
});

const vehicleTypes = computed(() => {
    return page.props.dropdownSource.vehicle_type_id.map(type => ({
        value: type.id,
        label: type.text,
    }));
});

const carTypeInsurances = computed(() => {
    return page.props.dropdownSource.car_type_insurance_id.map(carTypeInsurance => ({
        value: carTypeInsurance.id,
        label: carTypeInsurance.text,
    }));
});

const providers = computed(() => {
    return page.props.dropdownSource.car_plan_provider_id.map(provider => ({
        value: provider.id,
        label: provider.text,
    }));
});

const batchOptions = computed(() => {
    return page.props.dropdownSource.quote_batch_id.map(batch => ({
        value: batch.id,
        label: batch.name,
    }));
});

const paymentStatusOptions = computed(() => {
    return page.props.dropdownSource.payment_status_id.map(status => ({
        value: status.id,
        label: status.text,
    }));
});

const filters = reactive({
    code: '', 
    first_name: '', 
    last_name: '', 
    email: '', 
    mobile_no: '', 
    quote_status_id: [], 
    created_at: '', 
    currently_insured_with: '', 
    renewal_expiry_date: '', 
    is_ecommerce: '', 
    payment_status_id: '', 
    renewal_batch: '', 
    previous_quote_policy_number: '', 
    car_type_insurance_id: '', 
    vehicle_type_id: '', 
    advisor_assigned_date: '', 
    tier_id: [], 
    quote_batch_id: [], 
    advisor_id: [], 
    advisor_assigned_date_end: '', 
    renewal_expiry_date_end: '', 
    created_at_end: '', 
    page: 1,
});

const loader = reactive({
    table: false,
    export: false,
});

const assignForm = useForm({
    assign_team: null,
    assigned_to_id_new: null,
    assignment_type: '',
    modelType: 'Car',
    selectTmLeadId: '',
    isManagerOrDeputy: 1,
    isLeadPool: null,
    isManualAllocationAllowed: 1,
});

const quotesSelected = ref([]);
const canExport = ref(false);

watch(
  () => filters,
  () => {
    if (filters.created_at && filters.created_at_end) {
      canExport.value = true;
    } else {
      canExport.value = false;
    }
  },
  { deep: true, immediate: true },
);

const rules = {
  isRequired: v => !!v || 'Please select this option',
};

function onSubmit(isValid) {
  if (isValid) {
    filters.page = 1;
    Object.keys(filters).forEach(
      key =>
        (filters[key] === '' || filters[key].length === 0) &&
        delete filters[key],
    );
    router.visit(route('car.index'), {
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

function onAssignLead(isValid) {
  if (isValid) {
    const selected = quotesSelected.value.map(e => e.id);
    const url =
      assignForm.assign_team === 'Wow-Call'
        ? '/quotes/wcuAssign'
        : '/quotes/car/manualLeadAssign';
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
            title: 'Car Leads Assigned',
            position: 'top',
          });
        },
      });
  }
}

const fixedValue = numberString => {
  const number = parseFloat(numberString);
  if (isNaN(number)) {
    return "Invalid number";
  } else if (number === Math.floor(number)) {
    return number.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");;
  } else {
    return parseFloat(number.toFixed(2)).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }
};

function onReset() {
    router.visit(route('car.index'), {
        method: 'get',
        data: { page: 1 },
        preserveScroll: true,
        onBefore: () => (loader.table = true),
        onSuccess: () => (loader.table = false),
    });
}

const objToUrl = obj => {
    Object.keys(obj).forEach(
        key => (obj[key] === '' || obj[key].length === 0) && delete obj[key],
    );
    return Object.keys(obj)
        .map(key => {
            if (Array.isArray(obj[key])) {
                return obj[key].map(value => `${key}[]=${value}`).join('&');
            }
            return `${key}=${obj[key]}`;
        })
        .join('&');
};

function setQueryStringFilters() {
  for (const [key] of Object.entries(params)) {
    if (key.includes('[]')) {
      filters[key.substring(0, key.length - 2)] = params[key];
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
        <Head title="View Car" />
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold">Lead List</h2>
            <div class="space-x-3">
                <!-- <Link :href="route('health.cards')">
                    <x-button size="sm" color="#1d83bc" tag="div"> Cards View </x-button>
                </Link> -->

                <Link :href="route('car.create')">
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
                    label="REF-ID"
                    class="w-full"
                    placeholder="Search by REF-ID"
                />
                <ComboBox
                    v-model="filters.quote_batch_id"
                    label="Batch"
                    name="quote_batch_id"
                    placeholder="Please select batch"
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
                <DatePicker
                    v-model="filters.created_at"
                    name="created_at"
                    label="Created Date Start"
                />
                <DatePicker
                    v-model="filters.created_at_end"
                    name="created_at_end"
                    label="Created Date End"
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
                    v-model="filters.advisor_assigned_date"
                    name="advisor_assigned_date"
                    label="Advisor Assigned Date Start"
                />
                <DatePicker
                    v-model="filters.advisor_assigned_date_end"
                    name="advisor_assigned_date_end"
                    label="Advisor Assigned Date End"
                />
                <x-select
                    v-model="filters.payment_status_id"
                    label="Payment Status"
                    name="payment_status_id"
                    :options="paymentStatusOptions"
                    placeholder="Please select payment status"
                    class="w-full"
                />
                <x-select
                    v-model="filters.is_ecommerce"
                    label="Ecommerce"
                    name="is_ecommerce"
                    :options="ecommerceOptions"
                    placeholder="Please select is ecommerce"
                    class="w-full"
                />
                <ComboBox
                    v-model="filters.quote_status_id"
                    label="Lead Status"
                    name="quote_status_id"
                    :options="leadStatuses"
                />
                <ComboBox
                    v-model="filters.tier_id"
                    label="Tier Name"
                    name="tier_id"
                    placeholder="Please select batch"
                    :options="leadTiers"
                />
                <ComboBox
                    :single="true"
                    v-model="filters.vehicle_type_id"
                    label="Vehicle Type"
                    name="vehicle_type_id"
                    :options="vehicleTypes"
                    placeholder="Please select an option"
                    class="w-full"
                />
                <ComboBox
                    :single="true"
                    v-model="filters.car_type_insurance_id"
                    label="Type of Car Insurance"
                    name="car_type_insurance_id"
                    :options="carTypeInsurances"
                    placeholder="Please select an option"
                    class="w-full"
                />                
                <x-input
                    v-model="filters.renewal_batch"
                    type="number"
                    name="renewal_batch"
                    label="Renewal Batch"
                    class="w-full"
                    placeholder="Search by Renewal Batch"
                />
                <DatePicker
                    v-model="filters.renewal_expiry_date"
                    name="renewal_expiry_date"
                    label="Renewal Expiry Date Start"
                />
                <DatePicker
                    v-model="filters.renewal_expiry_date_end"
                    name="renewal_expiry_date_end"
                    label="Renewal Expiry Date End"
                />
                <ComboBox
                    :single="true"
                    v-model="filters.currently_insured_with"
                    label="Currently Insured with"
                    name="currently_insured_with"
                    :options="providers"
                    placeholder="Please select an option"
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
                <ComboBox
                    v-model="filters.advisor_id"
                    label="Advisor"
                    name="advisor_id"
                    placeholder="Please select Advisor"
                    :options="advisorOptions"
                />
            </div>
            <div class="flex justify-between gap-3 mb-4 mt-1">
                <div v-if="can(permissionsEnum.DATA_EXTRACTION)">
                    <x-button
                        v-if="canExport"
                        size="sm"
                        color="emerald"
                        :href="`/quotes/car-export?${objToUrl(filters)}`"
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
            <div v-if="quotesSelected.length > 0" class="mb-4">
                <div class="px-4 py-6 rounded shadow mb-4 bg-primary-50/50">
                    <div class="row">
                        <h2 class="text-xl font-semibold mb-5">Assign Lead</h2>
                    </div>
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
                        <x-select
                            v-model="assignForm.assignment_type"
                            label="Assignment Type"
                            :options="[
                                { value: 'With-Email', label: 'With Email' },
                                { value: 'Without-Email', label: 'Without Email' },
                            ]"
                            placeholder="Select Subteam"
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
                <Link :href="route('car.show', uuid)" class="text-primary-500 hover:underline">
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
            <template #item-is_gcc_standard="{ is_gcc_standard }">
                <div class="text-center">
                    <x-tag size="sm" :color="is_gcc_standard ? 'success' : 'error'">
                        {{ is_gcc_standard ? 'Yes' : 'No' }}
                    </x-tag>
                </div>
            </template>
            <template #item-is_modified="{ is_modified }">
                <div class="text-center">
                    <x-tag size="sm" :color="is_modified ? 'success' : 'error'">
                        {{ is_modified ? 'Yes' : 'No' }}
                    </x-tag>
                </div>
            </template>
            <template #item-price_starting_from="item">
                <p v-if="item.price_starting_from != null">{{ fixedValue(item.price_starting_from) }}</p>
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