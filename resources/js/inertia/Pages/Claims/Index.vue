<script setup>
import NProgress from 'nprogress';
import {
  formattedDateDmyWithTime,
  formattedDateYmd,
  calculateDaysDifference,
  calculateMonthsDifference,
  logAndExportQuotes,
  useObjToUrl,
} from '@/inertia/Composables/utilities.js';
import { formattedDateYmdWithTime } from '../../Composables/utilities';
const { isEmail } = useRules();

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
  exportType: null,
  page: 1,
};

const filters = reactive({ ...availableFilters, ...props.filters });
const loader = reactive({
  table: false,
  export: false,
});

const tableHeader = [
  { text: 'Ref ID', value: 'code' },
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
  { text: 'VEHICLE YEAR', value: 'model_year' },
  { text: 'STATUS', value: 'claim_status' },
  { text: 'ASSIGNED TO', value: 'manager' },
  { text: 'CREATED AT', value: 'created_at' },
];

const statusOptions = computed(() => {
  if (!props.claimDropdownOptions?.claimStatuses) {
    return [];
  }
  return (
    props.claimDropdownOptions?.claimStatuses?.map(cs => ({
      value: cs.id,
      label: cs.text?.label,
    })) || []
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
  // Explicitly track dependencies to ensure reactivity
  const quoteTypeId = filters.quote_type_id;
  const businessTypeId = filters.business_type_of_insurance_id;
  const isHealth = isHealthLOB.value;
  let isBike = isBikeLOB.value;

  let quoteType = isHealth
    ? page.props.quoteTypeIds?.Health
    : isBike
      ? page.props.quoteTypeIds?.Car
      : quoteTypeId;
  return (
    props.claimDropdownOptions?.claimSubStatuses
      ?.filter(ct => ct.quote_type_id === quoteType)
      ?.map(ct => ({
        value: ct.id,
        label: ct.text?.label,
      })) || []
  );
});

const businessTypeOfInsuranceOptions = computed(() => {
  return (
    props.claimDropdownOptions?.businessTypeOfInsurance?.map(bt => ({
      value: bt.id,
      label: bt.text,
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

const claimsSelected = ref([]);
const assignForm = useForm({
  manager_id: null,
});

const onBulkAssign = () => {
  if (!assignForm.manager_id) {
    notification.error({
      title: 'Please select a claims manager to assign.',
      position: 'top',
    });
    return;
  }
  assignForm
    .transform(data => ({
      ...data,
      claim_uuids: claimsSelected.value.map(c => c.uuid),
    }))
    .post(route('claims.bulk-assign'), {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        claimsSelected.value = [];
        notification.success({
          title: 'Claims assigned successfully.',
          position: 'top',
        });
        router.visit(route('claims.index'), {
          method: 'get',
          data: filters,
          preserveState: true,
          preserveScroll: true,
        });
      },
      onError: errors => {
        Object.keys(errors).forEach(key => {
          notification.error({
            title: errors[key],
            position: 'top',
          });
        });
      },
    });
};

const complaintStatusOptions = computed(() => {
  return (
    props.claimDropdownOptions?.complaintStatuses?.map(cs => ({
      value: cs.id,
      label: cs.text?.label || cs.text?.value || '',
    })) || []
  );
});

const carMakeOptions = computed(() => {
  return (
    props.claimDropdownOptions?.carMake?.map(item => ({
      value: item.text,
      label: item.text,
    })) || []
  );
});

const carModelOptions = computed(() => {
  return (
    props.claimDropdownOptions?.carModel?.map(item => ({
      value: item.text,
      label: item.text,
    })) || []
  );
});

const getCarModel = reset => {
  let carMakeCode = props.claimDropdownOptions?.carMake.find(
    item => item.text === filters.car_make,
  )?.id;
  console.log('carMakeCode', carMakeCode, ', reset', reset);

  axios.get(`/car-model-by-id?id=${carMakeCode}`).then(({ data }) => {
    props.claimDropdownOptions.carModel = data;
    if (filters.car_model !== null && reset) {
      filters.car_model = null;
    }
  });
};

const carModelYearOptions = computed(() => {
  return (
    props.claimDropdownOptions?.carModelYear?.map(item => ({
      value: item.text,
      label: item.text,
    })) || []
  );
});

const claimRequestTypeOptions = computed(() => {
  return (
    props.claimDropdownOptions?.claimRequestTypes?.map(ct => ({
      value: ct.id,
      label: ct.text,
    })) || []
  );
});

const claimServiceTypeOptions = computed(() => {
  return (
    props.claimDropdownOptions?.claimServiceTypes?.map(ct => ({
      value: ct.id,
      label: ct.text,
    })) || []
  );
});

// Check if the claim request type is pending approval
const isPendingClaimRequestType = ref(
  page.props.claimsEnum?.CLAIM_REQUEST_TYPE_PENDING_APPROVALS_CODE ===
    filters.claim_request_type_id,
);

function searchClaims(isValid) {
  if (isValid) {
    if (filters.email) {
      const emailValidation = isEmail(filters.email);
      if (emailValidation !== true) {
        notification.error({
          title: emailValidation,
          position: 'top',
        });
        return;
      }
    }
    // Validate date range before proceeding
    if (!validateDateRange()) {
      notification.error({
        message: dateRangeError.value,
        position: 'top',
      });
      return;
    }

    filters.page = 1;
    Object.keys(filters).forEach(
      key =>
        (filters[key] === '' || filters[key]?.length === 0) &&
        delete filters[key],
    );

    // Format dates before sending the request
    if (filters.created_at_start) {
      filters.created_at_start = formattedDateYmd(filters.created_at_start);
    }
    if (filters.created_at_end) {
      filters.created_at_end = formattedDateYmd(filters.created_at_end);
    }
    if (filters.manager_assigned_date) {
      filters.manager_assigned_date = formattedDateYmd(
        filters.manager_assigned_date,
      );
    }
    if (filters.next_followup_datetime) {
      filters.next_followup_datetime = formattedDateYmd(
        filters.next_followup_datetime,
      );
    }

    NProgress.start();
    router.visit(route('claims.index'), {
      method: 'get',
      data: filters,
      preserveState: true,
      preserveScroll: true,
      onBefore: () => (loader.table = true),
      onSuccess: () => (loader.table = false),
      onError: errors => {
        loader.table = false;

        // Extract and display backend validation errors
        const errorMessages = Object.values(errors).flat();
        const errorMessage =
          errorMessages.length > 0
            ? errorMessages.join(' ')
            : 'Failed to search claims.';

        notification.error({
          message: errorMessage,
          position: 'top',
        });
      },
    });
    NProgress.done();
  }
}

function onReset() {
  NProgress.start();
  router.visit('/claim', {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
  NProgress.done();
}

const exportLoader = ref(false);

const onDataExport = exportType => {
  // Validate date range first
  if (!validateDateRange()) {
    notification.error({
      message: dateRangeError.value,
      position: 'top',
    });
    return;
  }

  // Check date range restriction for created dates
  if (filters.created_at_start && filters.created_at_end) {
    let diff, maxLimit, maxPeriod;

    if (exportType === 'email') {
      // For email export, use months-based validation
      diff = calculateMonthsDifference(
        filters.created_at_start,
        filters.created_at_end,
      );
      maxLimit = 3;
      maxPeriod = '3 months';
    } else {
      // For download export, use days-based validation
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

  // Format dates for export
  if (filters.created_at_start) {
    filters.created_at_start = useDateFormat(
      filters.created_at_start,
      'YYYY-MM-DD',
    ).value;
  }
  if (filters.created_at_end) {
    filters.created_at_end = useDateFormat(
      filters.created_at_end,
      'YYYY-MM-DD',
    ).value;
  }

  console.log('filters', filters, exportType);

  filters.exportType = exportType;

  exportLoader.value = true;

  // Use axios for both download and email exports
  axios
    .post(route('claims.export'), filters, {
      responseType: exportType === 'download' ? 'blob' : 'json',
    })
    .then(result => {
      if (exportType === 'download') {
        // Handle file download
        const url = window.URL.createObjectURL(new Blob([result.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'Claims-List.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);

        notification.success({
          title: 'Claims export downloaded successfully',
          position: 'top',
        });
      } else {
        // Handle email export response
        if (result.data.message) {
          notification.success({
            title: result.data.message,
            position: 'top',
          });
        }
      }

      setTimeout(() => {
        exportLoader.value = false;
      }, 1000);
    })
    .catch(err => {
      console.error('Export error:', err);
      notification.error({
        title:
          err.response?.data?.message ||
          `Unable to ${exportType === 'email' ? 'start email export' : 'download export'}`,
        position: 'top',
      });
      setTimeout(() => {
        exportLoader.value = false;
      }, 1000);
    });
};

// Check if selected line of business is car
const isCarLOB = computed(() => {
  return quoteTypeIds.Car === filters.quote_type_id;
});

// Check if selected line of business is car
const isBikeLOB = computed(() => {
  return quoteTypeIds.Bike === filters.quote_type_id;
});

// Check if the selected line of business is Health
const isHealthLOB = computed(() => {
  let isHealth = quoteTypeIds.Health === filters.quote_type_id;
  let isGroupMedical =
    page.props.quoteBusinessTypeIdEnum.GROUP_MEDICAL ===
    filters.business_type_of_insurance_id;
  return isHealth || isGroupMedical;
});

// Check if export is available based on date filters
const canExport = computed(() => {
  return (
    (filters.created_at_start && filters.created_at_end) ||
    Object.keys(filters).some(
      key =>
        key !== 'created_at_start' &&
        key !== 'created_at_end' &&
        filters[key] !== null &&
        filters[key] !== undefined &&
        filters[key] !== '',
    )
  );
});

// Validate date range for created_at filters
const dateRangeError = ref('');

const validateDateRange = () => {
  dateRangeError.value = '';

  if (filters.created_at_start && filters.created_at_end) {
    const startDate = new Date(filters.created_at_start);
    const endDate = new Date(filters.created_at_end);

    if (startDate > endDate) {
      dateRangeError.value =
        'The end date must be equal to or after the start date.';
      return false;
    }
  }

  return true;
};

// Get backend validation errors for date fields
const backendDateError = computed(() => {
  const errors = page.props.errors || {};
  return errors.created_at_end || errors.created_at_start || '';
});

// Check if any filters (excluding availableFilters) are filled
const hasActiveFilters = computed(() => {
  const excludeKeys = Object.keys(availableFilters);

  return Object.keys(filters).some(key => {
    // Skip keys from availableFilters
    if (excludeKeys.includes(key)) {
      return false;
    }

    // Check if value is not null, undefined, empty string, or empty array
    const value = filters[key];
    return (
      value !== null &&
      value !== undefined &&
      value !== '' &&
      !(Array.isArray(value) && value.length === 0)
    );
  });
});

// Initialize car model options on mount if car make is already selected
onMounted(() => {
  if (filters.car_make && (isCarLOB.value || isBikeLOB.value)) {
    getCarModel(false);
  }
});

// Watcher to clear vehicle-specific filters when line of business changes away from car/bike
watch(
  () => filters.quote_type_id,
  (newValue, oldValue) => {
    if (newValue !== oldValue) {
      console.log('Line of business changed to:', newValue);

      // Check if the new selection is car
      const isVehicleTypeCarOrBike =
        newValue === quoteTypeIds.Car || newValue === quoteTypeIds.Bike;

      console.log('Is vehicle type (Car/Bike):', isVehicleTypeCarOrBike);

      // Clear vehicle-specific filters if not a vehicle type
      if (!isVehicleTypeCarOrBike) {
        filters.plate_number = '';
        filters.car_make = '';
        filters.car_model = '';
        filters.model_year = '';

        console.log('Cleared vehicle-specific filters');
      }
    }
  },
);

watch(
  () => filters.claim_request_type_id,
  newClaimRequestId => {
    let requestTypeCode = props.claimDropdownOptions?.claimRequestTypes.find(
      item => item.id === newClaimRequestId,
    )?.code;
    if (
      requestTypeCode !==
      page.props.claimsEnum?.CLAIM_REQUEST_TYPE_PENDING_APPROVALS_CODE
    ) {
      filters.service_type_id = null;
      isPendingClaimRequestType.value = false;
    } else {
      isPendingClaimRequestType.value = true;
    }
  },
);

// Clear date range error when dates change
watch(
  () => [filters.created_at_start, filters.created_at_end],
  () => {
    if (dateRangeError.value) {
      dateRangeError.value = '';
    }
  },
);
</script>

<template>
  <div>
    <Head title="Claims List" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Claims List</h2>
      <div class="flex gap-2">
        <Link v-if="can(permissionsEnum.CLAIM_CREATE)" href="/claim/create">
          <x-button size="sm" color="primary">Create Claim</x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />

    <!-- Filters -->
    <x-form @submit="searchClaims" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
        <x-input
          v-model="filters.code"
          type="text"
          name="code"
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
          :rules="[isEmail]"
          type="email"
          name="email"
          label="Email Address"
          placeholder="Search by Email"
          class="w-full"
        />
        <x-input
          v-model="filters.mobile_no"
          type="text"
          name="mobile_no"
          label="Mobile Number"
          placeholder="Search by Mobile Number"
          class="w-full"
        />
        <!-- Date filters -->
        <DatePicker
          v-model="filters.created_at_start"
          name="created_at_start"
          label="Created Date Start "
          placeholder="Select Date From"
          format="yyyy-MM-dd"
          :error="backendDateError"
        />
        <DatePicker
          v-model="filters.created_at_end"
          name="created_at_end"
          label="Created Date End "
          placeholder="Select Date To"
          format="yyyy-MM-dd"
          :error="dateRangeError || backendDateError"
        />

        <x-select
          v-model="filters.quote_type_id"
          label="Line of Business"
          placeholder="Select Line of Business"
          :options="lineOfBusinessOptions"
          filterable
          filterPlaceholder="Filter Line of Business...."
          clearable
        />
        <x-select
          v-model="filters.business_type_of_insurance_id"
          label="Search by Business Type of Insurance"
          placeholder="Select  "
          :options="businessTypeOfInsuranceOptions"
          filterable
          filterPlaceholder="Filter Business Type of Insurance...."
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
          v-model="filters.manager_id"
          label="Claims Manager"
          placeholder="Select Manager"
          :options="managersOptions"
          filterable
          filterPlaceholder="Filter Manager...."
          clearable
        />
        <DatePicker
          v-model="filters.manager_assigned_date"
          name="manager_assigned_date"
          label="Claims Manager Assigned Date "
          placeholder="Select Assigned Date"
          format="yyyy-MM-dd"
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
          v-model="filters.complaint_status_id"
          label="Complaint Status"
          placeholder="Select  "
          :options="complaintStatusOptions"
          filterable
          filterPlaceholder="Filter Complaint Status...."
          clearable
        />

        <DatePicker
          v-model="filters.next_followup_datetime"
          name="next_followup_datetime"
          label="Next Follow Up Date"
          placeholder="Select Assigned Date"
          format="yyyy-MM-dd"
        />

        <template v-if="isCarLOB || isBikeLOB">
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
            v-model="filters.car_make"
            @update:modelValue="getCarModel(true)"
            label="Vehicle Make"
            placeholder="Select Vehicle Make"
            :options="carMakeOptions"
            filterable
            filterPlaceholder="Filter Vehicle Make...."
            clearable
          />
          <x-select
            v-model="filters.car_model"
            label="Vehicle Model"
            placeholder="Select Vehicle Model"
            :options="carModelOptions"
            filterable
            filterPlaceholder="Filter Vehicle Model...."
            clearable
          />
          <x-select
            v-model="filters.model_year"
            label="Vehicle Year"
            placeholder="Select Vehicle Year"
            :options="carModelYearOptions"
            filterable
            filterPlaceholder="Filter Vehicle Year...."
            clearable
          />
        </template>
        <template v-if="isHealthLOB">
          <x-select
            v-model="filters.claim_request_type_id"
            label="Claim Request Type"
            placeholder="Select Claim Request Type"
            :options="claimRequestTypeOptions"
            filterable
            filterPlaceholder="Filter Claim Request Type...."
          />
          <x-select
            v-if="isPendingClaimRequestType"
            v-model="filters.service_type_id"
            label="Service Type"
            placeholder="Select Service Type"
            :options="claimServiceTypeOptions"
            filterable
            filterPlaceholder="Filter Service Type...."
            clearable
          />
        </template>
      </div>
      <div class="flex justify-between gap-3 mb-4 mt-4">
        <div v-if="can(permissionsEnum.CLAIMS_EXPORT_DATA)">
          <template v-if="canExport">
            <x-button
              size="sm"
              color="emerald"
              :loading="exportLoader"
              @click.prevent="onDataExport('download')"
              class="justify-self-start mr-3"
            >
              Export
            </x-button>
            <x-button
              v-if="false"
              size="sm"
              color="emerald"
              :loading="exportLoader"
              @click.prevent="onDataExport('email')"
              class="justify-self-start mr-3"
            >
              Export via email
            </x-button>
          </template>
        </div>
        <div v-else />
        <div class="flex gap-3 justify-self-end">
          <x-button
            size="sm"
            color="#ff5e00"
            :loading="loader.table"
            :disabled="!hasActiveFilters"
            type="submit"
            >Search</x-button
          >
          <x-button
            size="sm"
            color="primary"
            @click.prevent="onReset"
            :loading="loader.table"
            :disabled="!hasActiveFilters"
          >
            Reset
          </x-button>
        </div>
      </div>
    </x-form>

    <Transition name="fade">
      <div
        v-if="
          claimsSelected.length > 0 && can(permissionsEnum.CLAIMS_MANUAL_ASSIGN)
        "
        class="mb-4"
      >
        <div class="px-4 py-6 rounded shadow mb-4 bg-primary-50/50">
          <h3 class="font-semibold text-primary-800">
            Assign Claims (Claims Lead)
          </h3>
          <p class="text-sm text-gray-500 mt-1">
            {{ claimsSelected.length }} claim(s) selected
          </p>
          <x-divider class="mb-4 mt-1" />
          <x-form
            @submit="
              e => {
                if (e && typeof e.preventDefault === 'function')
                  e.preventDefault();
                onBulkAssign();
              }
            "
            :auto-focus="false"
          >
            <div class="w-full flex flex-col md:flex-row gap-4 items-end">
              <div class="flex-1 w-auto min-w-[200px]">
                <x-select
                  v-model="assignForm.manager_id"
                  label="Assign to Claims Manager"
                  :options="managersOptions"
                  placeholder="Select claims manager"
                  class="w-full"
                  filterable
                  filterPlaceholder="Filter managers..."
                  :error="assignForm.errors.manager_id"
                />
              </div>
              <div class="mb-3 md:pb-1">
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

    <!-- Data Table -->
    <DataTable
      v-model:items-selected="claimsSelected"
      table-class-name="tablefixed"
      :headers="tableHeader"
      :loading="loader.table"
      :items="claims.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #item-code="{ code, uuid }">
        <Link :href="`/claim/${uuid}`" class="text-primary-500 hover:underline">
          {{ code }}
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

      <template #item-quote_type="{ quote_type }">
        {{ quote_type?.text }}
      </template>

      <template #item-insurance_provider="{ insurance_provider }">
        {{ insurance_provider?.text }}
      </template>

      <template #item-claim_status="{ claim_status }">
        {{ claim_status?.text }}
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

      <template #item-plate_number="{ claim_request_details }">
        {{ claim_request_details?.plate_number }}
      </template>

      <template #item-car_make="{ claim_request_details }">
        {{ claim_request_details?.car_make }}
      </template>

      <template #item-car_model="{ claim_request_details }">
        {{ claim_request_details?.car_model }}
      </template>

      <template #item-model_year="{ claim_request_details }">
        {{ claim_request_details?.model_year }}
      </template>

      <template #item-manager="{ manager }">
        {{ manager?.name }}
      </template>

      <template #item-created_at="{ created_at }">
        {{ formattedDateDmyWithTime(created_at) }}
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
