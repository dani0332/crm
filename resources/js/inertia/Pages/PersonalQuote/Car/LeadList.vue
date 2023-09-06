<script setup>
defineProps({
  quotes: Object,
  leadStatuses: Array,
  advisors: Array,
});

const page = usePage();
const notification = useToast();
const params = useUrlSearchParams('history');

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
    quote_status_id: '',
    created_at: '',
    currently_insured_with: '', 
    renewal_expiry_date: '', 
    payment_status_id: '', 
    renewal_batch: '', 
    previous_quote_policy_number: '', 
    car_type_insurance_id: '', 
    vehicle_type_id: '', 
    advisor_assigned_date: '', 
    tier_id: '', 
    quote_batch_id: '', 
    advisor_id: '', 
    advisor_assigned_date_end: '', 
    renewal_expiry_date_end: '', 
    created_at_end: '', 
});

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

</script>

<template>
    <div>
        <Head title="Lead List" />
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold">Lead List</h2>
            <div class="space-x-3">
                <!-- <Link :href="route('health.cards')">
                    <x-button size="sm" color="#1d83bc" tag="div"> Cards View </x-button>
                </Link> -->

                <Link :href="route('health.create')">
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
                    v-model="filters.advisors"
                    label="Advisor"
                    placeholder="Search by Advisor"
                    :options="advisorOptions"
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
            </div>
            <div class="flex justify-between gap-3 mb-4 mt-1">
                <div v-if="can(permissionsEnum.DATA_EXTRACTION)">
                <x-button
                    v-if="canExport"
                    size="sm"
                    color="emerald"
                    :href="`/quotes/health-export?${objToUrl(filters)}`"
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
    </div>
</template>