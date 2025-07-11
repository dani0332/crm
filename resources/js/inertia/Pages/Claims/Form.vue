<script setup>
const props = defineProps({
  claim: { type: Object, default: null },
  dropdowns: { type: Object, default: null },
  isEdit: { type: Boolean, default: false },
});

const page = usePage();
const notification = useToast();
const { isRequired, isEmail } = useRules();

const claimForm = useForm({
  first_name: page.props.claim?.first_name || '',
  last_name: page.props.claim?.last_name || '',
  email: page.props.claim?.email || '',
  mobile_no: page.props.claim?.mobile_no || '',
  line_of_business: page.props.claim?.line_of_business || '',
  claim_type: page.props.claim?.claim_type || '',
  policy_number: page.props.claim?.policy_number || '',
  insurer_claim_number: page.props.claim?.insurer_claim_number || '',
  plate_number: page.props.claim?.plate_number || '',
  vehicle_make: page.props.claim?.vehicle_make || '',
  vehicle_model: page.props.claim?.vehicle_model || '',
  vehicle_year: page.props.claim?.vehicle_year || '',
  claim_sub_status: page.props.claim?.claim_sub_status || '',
  description: page.props.claim?.description || '',
  incident_date: page.props.claim?.incident_date || '',
  report_date: page.props.claim?.report_date || '',
});

const lineOfBusinessOptions = computed(() => {
  return props.dropdowns.quote_types?.map(qt => ({
    value: qt.id,
    label: qt.title,
  })) || [];
});

const claimTypeOptions = computed(() => {
  return props.dropdowns.claim_types?.map(ct => ({
    value: ct.id,
    label: ct.text,
  })) || [];
});

const claimSubStatusOptions = computed(() => {
  return props.dropdowns.claim_sub_statuses?.map(css => ({
    value: css.id,
    label: css.text,
  })) || [];
});

const vehicleMakeOptions = computed(() => {
  return props.dropdowns.vehicle_makes?.map(vm => ({
    value: vm.id,
    label: vm.name,
  })) || [];
});

const vehicleModelOptions = computed(() => {
  return props.dropdowns.vehicle_models?.map(vm => ({
    value: vm.id,
    label: vm.name,
  })) || [];
});

// Show vehicle fields only for Car and Bike LOB
const isVehicleRelated = computed(() => {
  const selectedLob = props.dropdowns.quote_types?.find(qt => qt.id == claimForm.line_of_business);
  return selectedLob && (selectedLob.title?.toLowerCase() === 'car' || selectedLob.title?.toLowerCase() === 'bike');
});

function onSubmit(isValid) {
  if (isValid) {
    let method = 'post';
    let url = `/claims`;
    let title = 'Claim created successfully';

    if (props.isEdit && page.props.claim) {
      method = 'put';
      url = `/claims/${page.props.claim.id}`;
      title = 'Claim updated successfully';
    }

    claimForm.submit(method, url, {
      onError: errors => {
        console.log('Form errors:', errors);
        claimForm.setError(errors);
      },
      onSuccess: () => {
        notification.success({
          title: title,
          position: 'top',
        });

        // Redirect to claims list
        router.visit('/claims');
      },
    });
  } else {
    notification.error({
      title: 'Error while submitting claim. Please check the form and try again',
      position: 'top',
    });
  }
}

// Watch for changes in line of business and clear vehicle fields if not applicable
watch(() => claimForm.line_of_business, (newVal) => {
  if (!isVehicleRelated.value) {
    claimForm.plate_number = '';
    claimForm.vehicle_make = '';
    claimForm.vehicle_model = '';
    claimForm.vehicle_year = '';
  }
});
</script>

<template>
  <div>
    <Head :title="isEdit ? 'Edit Claim' : 'Create Claim'" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">{{ isEdit ? 'Edit Claim' : 'Create New Claim' }}</h2>
      <div>
        <Link href="/claims">
          <x-button size="sm" color="#ff5e00">Claims List</x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />

    <x-form @submit="onSubmit" :autofocus="false">
      <x-alert color="error" class="mb-5" v-if="claimForm.errors.error">
        {{ claimForm?.errors?.error }}
      </x-alert>

      <!-- Personal Information Section -->
      <div class="bg-white p-6 rounded shadow mb-6">
        <h3 class="text-lg font-semibold mb-4">Personal Information</h3>
        <div class="grid sm:grid-cols-2 gap-4">
          <x-input
            v-model="claimForm.first_name"
            :rules="[isRequired]"
            class="w-full"
            type="text"
            label="First Name"
            placeholder="Enter First Name"
            required
            :error="claimForm.errors.first_name"
          />
          <x-input
            v-model="claimForm.last_name"
            :rules="[isRequired]"
            class="w-full"
            type="text"
            label="Last Name"
            placeholder="Enter Last Name"
            required
            :error="claimForm.errors.last_name"
          />
          <x-input
            v-model="claimForm.email"
            :rules="[isRequired, isEmail]"
            class="w-full"
            type="email"
            label="Email Address"
            placeholder="Enter Email Address"
            required
            :error="claimForm.errors.email"
          />
          <x-input
            v-model="claimForm.mobile_no"
            :rules="[isRequired]"
            class="w-full"
            type="tel"
            label="Phone Number"
            placeholder="Enter Phone Number"
            required
            :error="claimForm.errors.mobile_no"
          />
        </div>
      </div>

      <!-- Claim Information Section -->
      <div class="bg-white p-6 rounded shadow mb-6">
        <h3 class="text-lg font-semibold mb-4">Claim Information</h3>
        <div class="grid sm:grid-cols-2 gap-4">
          <x-select
            v-model="claimForm.line_of_business"
            :rules="[isRequired]"
            label="Line of Business"
            placeholder="Select Line of Business"
            :options="lineOfBusinessOptions"
            filterable
            filterPlaceholder="Filter Line of Business...."
            required
            :error="claimForm.errors.line_of_business"
          />
          <x-select
            v-model="claimForm.claim_type"
            label="Claim Type"
            placeholder="Select Claim Type"
            :options="claimTypeOptions"
            filterable
            filterPlaceholder="Filter Claim Type...."
            :error="claimForm.errors.claim_type"
          />
          <x-input
            v-model="claimForm.policy_number"
            type="text"
            label="Policy Number"
            placeholder="Enter Policy Number"
            class="w-full"
            :error="claimForm.errors.policy_number"
          />
          <x-input
            v-model="claimForm.insurer_claim_number"
            type="text"
            label="Insurer Claim Number"
            placeholder="Enter Insurer Claim Number"
            class="w-full"
            :error="claimForm.errors.insurer_claim_number"
          />
          <x-select
            v-model="claimForm.claim_sub_status"
            label="Claim Sub Status"
            placeholder="Select Claim Sub Status"
            :options="claimSubStatusOptions"
            filterable
            filterPlaceholder="Filter Claim Sub Status...."
            :error="claimForm.errors.claim_sub_status"
          />
          <DatePicker
            v-model="claimForm.incident_date"
            name="incident_date"
            label="Incident Date"
            placeholder="Select Incident Date"
            :hasError="claimForm.errors.incident_date"
          />
          <DatePicker
            v-model="claimForm.report_date"
            name="report_date"
            label="Report Date"
            placeholder="Select Report Date"
            :hasError="claimForm.errors.report_date"
          />
        </div>
      </div>

      <!-- Vehicle Information Section (only for Car/Bike LOB) -->
      <div class="bg-white p-6 rounded shadow mb-6" v-if="isVehicleRelated">
        <h3 class="text-lg font-semibold mb-4">Vehicle Information</h3>
        <div class="grid sm:grid-cols-2 gap-4">
          <x-input
            v-model="claimForm.plate_number"
            type="text"
            label="Plate Number"
            placeholder="Enter Plate Number"
            class="w-full"
            :error="claimForm.errors.plate_number"
          />
          <x-select
            v-model="claimForm.vehicle_make"
            label="Vehicle Make"
            placeholder="Select Vehicle Make"
            :options="vehicleMakeOptions"
            filterable
            filterPlaceholder="Filter Vehicle Make...."
            :error="claimForm.errors.vehicle_make"
          />
          <x-select
            v-model="claimForm.vehicle_model"
            label="Vehicle Model"
            placeholder="Select Vehicle Model"
            :options="vehicleModelOptions"
            filterable
            filterPlaceholder="Filter Vehicle Model...."
            :error="claimForm.errors.vehicle_model"
          />
          <x-input
            v-model="claimForm.vehicle_year"
            type="number"
            label="Vehicle Year"
            placeholder="Enter Vehicle Year"
            class="w-full"
            min="1900"
            :max="new Date().getFullYear()"
            :error="claimForm.errors.vehicle_year"
          />
        </div>
      </div>

      <!-- Description Section -->
      <div class="bg-white p-6 rounded shadow mb-6">
        <h3 class="text-lg font-semibold mb-4">Description</h3>
        <x-textarea
          v-model="claimForm.description"
          label="Claim Description"
          placeholder="Enter detailed description of the claim"
          rows="4"
          class="w-full"
          :error="claimForm.errors.description"
        />
      </div>

      <!-- Form Actions -->
      <div class="flex justify-end gap-3 mb-4">
        <Link href="/claims">
          <x-button size="md" color="gray" type="button">
            Cancel
          </x-button>
        </Link>
        <x-button
          size="md"
          color="emerald"
          type="submit"
          :loading="claimForm.processing"
        >
          {{ isEdit ? 'Update Claim' : 'Create Claim' }}
        </x-button>
      </div>
    </x-form>
  </div>
</template>
