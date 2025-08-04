<script setup>
const props = defineProps({
  claims: Object,
  claimDropdownOptions: Object,
  statistics: Object,
  filters: Object,
});

const page = usePage();
const notification = useNotifications('toast');
const permissionsEnum = page.props.permissionsEnum;
const quoteTypeIds = page.props.quoteTypeIds;
const can = permission => useCan(permission);

let availableFilters = {
  code: '',
  first_name: '',
  last_name: '',
  email: '',
  mobile_no: '',
  created_start_date: '',
  created_end_date: '',
  claim_status_id: '',
  claim_sub_status_id: '',
  manager_id: '',
  manager_assigned_date: '',
  quote_type_id: '',
  policy_number: '',
  assigned_status: '',
  source: '',
  incident: '',
  insurance_provider_id: '',
  claim_type_id: '',
  claim_request_type_id: '',
  whatsapp_consent: '',
  page: 1,
};

const filters = reactive({ ...availableFilters, ...props.filters });
const loader = reactive({
  table: false,
  export: false,
});

const tableHeader = [
  { text: 'CODE', value: 'code' },
  { text: 'FIRST NAME', value: 'first_name' },
  { text: 'LAST NAME', value: 'last_name' },
  { text: 'EMAIL', value: 'email' },
  { text: 'MOBILE', value: 'mobile_no' },
  { text: 'QUOTE TYPE', value: 'quote_type' },
  { text: 'CLAIM TYPE', value: 'claim_type' },
  { text: 'POLICY NUMBER', value: 'policy_number' },
  { text: 'INSURANCE PROVIDER', value: 'insurance_provider' },
  { text: 'SOURCE', value: 'source' },
  { text: 'VEHICLE MAKE', value: 'car_make' },
  { text: 'VEHICLE MODEL', value: 'car_model' },
  { text: 'STATUS', value: 'claim_status' },
  { text: 'ASSIGNED TO', value: 'manager' },
  { text: 'CREATED AT', value: 'created_at' }, 
];

const statusOptions = computed(() => {
  if (!props.claimDropdownOptions?.claimStatuses) {
    return [];
  }

  // Convert indexed array to options format
  return Object.entries(props.claimDropdownOptions.claimStatuses).map(
    ([id, text]) => ({
      value: parseInt(id),
      label: text,
    }),
  );
});

const assignedStatusOptions = [
  { value: 'assigned', label: 'Assigned' },
  { value: 'un-assigned', label: 'Un Assigned' },
];

const lineOfBusinessOptions = computed(() => {
  return (
    props.claimDropdownOptions?.lineOfBusiness?.map(qt => ({
      value: qt.id,
      label: qt.text,
    })) || []
  );
});

const claimTypeOptions = computed(() => {
  return (
    props.claimDropdownOptions?.claimTypes?.map(ct => ({
      value: ct.id,
      label: ct.text,
    })) || []
  );
});

const claimSubStatusOptions = computed(() => {
  return (
    props.claimDropdownOptions?.claimSubStatuses
      ?.filter(ct => ct.quote_type_id === filters.line_of_business_id)
      ?.map(ct => ({
        value: ct.id,
        label: ct.text,
      })) || []
  );
});

const managersOptions = computed(() => {
  return (
    props.claimDropdownOptions?.claimsManagers?.map(manager => ({
      value: manager.id,
      label: manager.name,
    })) || []
  );
});
const complaintStatusOptions = computed(() => {
  return (
    props.claimDropdownOptions?.complaintStatuses?.map(status => ({
      value: status.value,
      label: status.text,
    })) || []
  );
});

const vehicleMakeOptions = computed(() => {
  return [];
});

const vehicleModelOptions = computed(() => {
  return [];
});

function onSubmit(isValid) {
  if (isValid) {
    filters.page = 1;
    Object.keys(filters).forEach(
      key =>
        (filters[key] === '' || filters[key]?.length === 0) &&
        delete filters[key],
    );

    router.visit('/claims', {
      method: 'get',
      data: filters,
      preserveState: true,
      preserveScroll: true,
      onBefore: () => (loader.table = true),
      onSuccess: () => (loader.table = false),
    });
  }
}

function onReset() {
  router.visit('/claims', {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}

function assignManager(claimId, managerId) {
  router.post(
    `/claims/${claimId}/assign-manager`,
    {
      manager_id: managerId,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        notification.success({
          title: 'Manager assigned successfully',
          position: 'top',
        });
      },
      onError: errors => {
        notification.error({
          title: 'Error assigning manager',
          position: 'top',
        });
      },
    },
  );
}

function updateStatus(claimId, status) {
  router.post(
    `/claims/${claimId}/update-status`,
    {
      status: status,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        notification.success({
          title: 'Status updated successfully',
          position: 'top',
        });
      },
      onError: errors => {
        notification.error({
          title: 'Error updating status',
          position: 'top',
        });
      },
    },
  );
}

function exportClaims() {
  loader.export = true;
  window.location.href = '/claims/export?' + new URLSearchParams(filters);
  setTimeout(() => {
    loader.export = false;
  }, 3000);
}

// Check if selected line of business is car
const isCarLOB = computed(() => {
  return quoteTypeIds.Car === filters.line_of_business_id;
});

// Watcher to clear vehicle-specific filters when line of business changes away from car/bike
watch(
  () => filters.line_of_business_id,
  (newValue, oldValue) => {
    if (newValue !== oldValue) {
      console.log('Line of business changed to:', newValue);

      // Check if the new selection is car
      const isVehicleType = newValue === quoteTypeIds.Car;

      console.log('Is vehicle type (Car/Bike):', isVehicleType);

      // Clear vehicle-specific filters if not a vehicle type
      if (!isVehicleType) {
        filters.plate_number = '';
        filters.vehicle_make = '';
        filters.vehicle_model = '';
        filters.vehicle_year = '';

        console.log('Cleared vehicle-specific filters');
      }
    }
  },
);
</script>

<template>
  <div>
    <Head title="Claims Management" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Claims Management</h2>
      <div class="flex gap-2">
        <Link v-if="can(permissionsEnum.CLAIM_CREATE)" href="/claims/create">
          <x-button size="sm" color="primary">Add New Claim</x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />

    <!-- Statistics Cards -->
    <!-- <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6" v-if="statistics">
      <div class="bg-white p-4 rounded shadow">
        <div class="text-2xl font-bold text-blue-600">
          {{ statistics.total_claims }}
        </div>
        <div class="text-sm text-gray-600">Total Claims</div>
      </div>
      <div class="bg-white p-4 rounded shadow">
        <div class="text-2xl font-bold text-yellow-600">
          {{ statistics.pending_claims }}
        </div>
        <div class="text-sm text-gray-600">Pending Claims</div>
      </div>
      <div class="bg-white p-4 rounded shadow">
        <div class="text-2xl font-bold text-green-600">
          {{ statistics.completed_claims }}
        </div>
        <div class="text-sm text-gray-600">Completed Claims</div>
      </div>
      <div class="bg-white p-4 rounded shadow">
        <div class="text-2xl font-bold text-red-600">
          {{ statistics.cancelled_claims }}
        </div>
        <div class="text-sm text-gray-600">Cancelled Claims</div>
      </div>
    </div> -->

    <!-- Filters -->
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
        <x-input
          v-model="filters.ref_id"
          type="text"
          name="ref_id"
          label="Ref ID"
          placeholder="Search by Ref ID"
          class="w-full"
        />
        <x-input
          v-model="filters.first_name"
          type="text"
          name="first_name"
          label="First Name"
          placeholder="Search by First Name"
          class="w-full"
        />
        <x-input
          v-model="filters.last_name"
          type="text"
          name="last_name"
          label="Last Name"
          placeholder="Search by Last Name"
          class="w-full"
        />
        <x-input
          v-model="filters.email"
          type="email"
          name="email"
          label="Email Address"
          placeholder="Search by Email"
          class="w-full"
        />
        <x-input
          v-model="filters.phone_number"
          type="text"
          name="phone_number"
          label="Mobile Number"
          placeholder="Search by Mobile Number"
          class="w-full"
        />
        <!-- Date filters -->
        <DatePicker
          v-model="filters.created_date_start"
          name="created_date_start"
          label="Created Date Start "
          placeholder="Select Date From"
          format="yyyy-MM-dd"
        />
        <DatePicker
          v-model="filters.created_date_end"
          name="created_date_end"
          label="Created Date End "
          placeholder="Select Date To"
          format="yyyy-MM-dd"
        />

        <x-select
          v-model="filters.claim_status_id"
          label="Claim Status"
          placeholder="Select Status"
          :options="statusOptions"
          clearable
        />
        <x-select
          v-model="filters.claim_sub_status_id"
          label="Claim Sub Status"
          placeholder="Select Claim Sub Status"
          :options="claimSubStatusOptions"
          filterable
          filterPlaceholder="Filter Claim Sub Status...."
          clearable
        />

        <x-select
          v-model="filters.assigned_claim_manager_id"
          label="Assigned Claims Manager"
          placeholder="Select Manager"
          :options="managersOptions"
          filterable
          filterPlaceholder="Filter Manager...."
          clearable
        />

        <x-select
          v-model="filters.claim_manager_id"
          label="Claims Lead"
          placeholder="Select Manager"
          :options="managersOptions"
          filterable
          filterPlaceholder="Filter Manager...."
          clearable
        />
        <DatePicker
          v-model="filters.claim_manager_assigned_date"
          name="claim_manager_assigned_date"
          label="Claims Manager Assigned Date "
          placeholder="Select Assigned Date"
          format="yyyy-MM-dd"
        />

        <x-select
          v-model="filters.line_of_business_id"
          label="Line of Business"
          placeholder="Select Line of Business"
          :options="lineOfBusinessOptions"
          filterable
          filterPlaceholder="Filter Line of Business...."
          clearable
        />
        <x-input
          v-model="filters.policy_number"
          type="text"
          name="policy_number"
          label="Policy Number"
          placeholder="Search by Policy Number"
          class="w-full"
        />

        <x-select
          v-model="filters.assigned_status"
          label="Search by Assigment"
          placeholder="Select  "
          :options="assignedStatusOptions"
          filterable
          filterPlaceholder="Filter Claim Type...."
          clearable
        />
        <x-select
          v-model="filters.complaint_status"
          label="Complaint Status"
          placeholder="Select  "
          :options="complaintStatusOptions"
          filterable
          filterPlaceholder="Filter Complaint Status...."
          clearable
        />

        <DatePicker
          v-model="filters.next_follow_up_date"
          name="next_follow_up_date"
          label="Next Follow Up Date"
          placeholder="Select Assigned Date"
          format="yyyy-MM-dd"
        />

        <template v-if="isCarLOB">
          <!-- Vehicle specific filters -->
          <x-input
            v-model="filters.plate_number"
            type="text"
            name="plate_number"
            label="Plate Number"
            placeholder="Search by Plate Number"
            class="w-full"
          />
          <x-select
            v-model="filters.vehicle_make"
            label="Vehicle Make"
            placeholder="Select Vehicle Make"
            :options="vehicleMakeOptions"
            filterable
            filterPlaceholder="Filter Vehicle Make...."
            clearable
          />
          <x-select
            v-model="filters.vehicle_model"
            label="Vehicle Model"
            placeholder="Select Vehicle Model"
            :options="vehicleModelOptions"
            filterable
            filterPlaceholder="Filter Vehicle Model...."
            clearable
          />
          <x-input
            v-model="filters.vehicle_year"
            type="number"
            name="vehicle_year"
            label="Vehicle Year"
            placeholder="Search by Vehicle Year"
            class="w-full"
          />
        </template>
      </div>
      <div class="flex justify-between gap-3 mb-4 mt-4">
        <x-button
            v-if="can(permissionsEnum.CLAIMS_EXPORT_DATA)"
            size="sm"
            color="emerald"
            class="justify-self-start mr-3"
             @click="exportClaims"
          :loading="loader.export"
          >
            Export
          </x-button>
          <div class="flex gap-3 justify-self-end">
            <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
            <x-button size="sm" color="primary" @click.prevent="onReset">  Reset  </x-button>
          </div>
       
      </div>
    </x-form>

    <!-- Data Table -->
    <DataTable
      table-class-name="tablefixed"
      :headers="tableHeader"
      :loading="loader.table"
      :items="claims.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #item-ref_id="{ ref_id }">
        <Link
          :href="`/claims/${ref_id}`"
          class="text-primary-500 hover:underline"
        >
          {{ ref_id }}
        </Link>
      </template>

      <template #item-first_name="{ first_name }">
        {{ first_name }}
      </template>

      <template #item-last_name="{ last_name }">
        {{ last_name }}
      </template>

      <template #item-email_address="{ email_address }">
        {{ email_address }}
      </template>

      <template #item-phone_number="{ phone_number }">
        {{ phone_number }}
      </template>

      <template #item-line_of_business="{ line_of_business }">
        {{ line_of_business?.text }}
      </template>

      <template #item-claim_type="{ claim_type }">
        {{ claim_type?.text }}
      </template>

      <template #item-policy_number="{ policy_number }">
        {{ policy_number }}
      </template>

      <template #item-insurer_claim_number="{ insurer_claim_number }">
        {{ insurer_claim_number }}
      </template>

      <template #item-plate_number="{ plate_number }">
        {{ plate_number }}
      </template>

      <template #item-vehicle_make="{ vehicle_make }">
        {{ vehicle_make }}
      </template>

      <template #item-vehicle_model="{ vehicle_model }">
        {{ vehicle_model }}
      </template>

      <template #item-vehicle_year="{ vehicle_year }">
        {{ vehicle_year }}
      </template>

      <template #item-claims_status="{ claims_status }">
        {{ claims_status?.text }}
      </template>

      <template #item-assigned_to="{ assigned_to }">
        {{ assigned_to?.name }}
      </template>

      <template #item-created_at="{ created_at }">
        {{ created_at }}
      </template>
       
    </DataTable>

     <!-- Pagination -->
    <Pagination
      :links="{
        next: claims.next_page_url,
        prev: claims.prev_page_url,
        current: claims.current_page,
        from: claims.from,
        to: claims.to,
      }"
    />
 
  </div>
</template>
