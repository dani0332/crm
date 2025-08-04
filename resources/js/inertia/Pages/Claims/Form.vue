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
  // IMCRM Required Fields
  first_name: props.claim?.first_name || '',
  last_name: props.claim?.last_name || '',
  email: props.claim?.email || props.claim?.email_address || '',
  mobile_no: props.claim?.mobile_no || props.claim?.phone_number || '',
  quote_type_id: props.claim?.quote_type_id || props.claim?.line_of_business_id || '',

  // Additional Fields
  claim_type_id: props.claim?.claim_type_id || '',
  incident: props.claim?.incident || props.claim?.incident_date || '',
  policy_number: props.claim?.policy_number || '',
  claim_status_id: props.claim?.claim_status_id || '',
  claim_sub_status_id: props.claim?.claim_sub_status_id || '',
  claim_request_type_id: props.claim?.claim_request_type_id || '',
  insurance_provider_id: props.claim?.insurance_provider_id || '',
  whatsapp_consent: props.claim?.whatsapp_consent || false,

  // Vehicle Details (for ClaimRequestDetail)
  car_make: props.claim?.claimRequestDetails?.[0]?.car_make || '',
  car_model: props.claim?.claimRequestDetails?.[0]?.car_model || '',
  service_type_id: props.claim?.claimRequestDetails?.[0]?.service_type_id || '',

  // System Fields
  source: props.claim?.source || 'IMCRM',
});

const lineOfBusinessOptions = computed(() => {
  return (
    props.dropdowns?.lineOfBusiness?.map(lob => ({
      value: lob.id,
      label: lob.text,
    })) || []
  );
});

const claimTypeOptions = computed(() => {
  return (
    props.dropdowns?.claimTypes?.map(ct => ({
      value: ct.id,
      label: ct.text,
    })) || []
  );
});

function onSubmit(isValid) {
  if (isValid) {
    let method = 'post';
    let url = `/claims`;
    let title = 'Claim created successfully';

    if (props.isEdit && props.claim) {
      method = 'put';
      url = `/claims/${props.claim.ref_id}`;
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
      title:
        'Error while submitting claim. Please check the form and try again',
      position: 'top',
    });
  }
}

// Simple form - no conditional field clearing needed
</script>

<template>
  <div>
    <Head :title="isEdit ? 'Edit Claim' : 'Create Claim Lead'" />
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-xl font-semibold">
          {{ isEdit ? 'Edit Claim' : 'Create New Claim Lead' }}
        </h2>
        <p class="text-sm text-gray-600 mt-1" v-if="!isEdit">
          Create a new claim lead in IMCRM. Fields marked with * are mandatory.
        </p>
      </div>
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

      <!-- Claim Lead Information -->
      <div class="bg-white p-6 rounded shadow mb-6">
        <h3 class="text-lg font-semibold mb-4">Claim Lead Information</h3>
        <p class="text-sm text-gray-600 mb-4">
          Fields marked with * are mandatory
        </p>
        <div class="grid sm:grid-cols-2 gap-4">
          <x-input
            v-model="claimForm.first_name"
            :rules="[isRequired]"
            class="w-full"
            type="text"
            label="First Name *"
            placeholder="Enter First Name"
            required
            :error="claimForm.errors.first_name"
          />
          <x-input
            v-model="claimForm.last_name"
            :rules="[isRequired]"
            class="w-full"
            type="text"
            label="Last Name *"
            placeholder="Enter Last Name"
            required
            :error="claimForm.errors.last_name"
          />
          <x-input
            v-model="claimForm.phone_number"
            :rules="[isRequired]"
            class="w-full"
            type="tel"
            label="Phone Number *"
            placeholder="Enter Phone Number"
            required
            :error="claimForm.errors.phone_number"
          />
          <x-input
            v-model="claimForm.email"
            :rules="[isRequired, isEmail]"
            class="w-full"
            type="email"
            label="Email Address *"
            placeholder="Enter Email Address"
            required
            :error="claimForm.errors.email"
          />
          <x-select
            v-model="claimForm.line_of_business_id"
            :rules="[isRequired]"
            label="Line of Business *"
            placeholder="Select Line of Business"
            :options="lineOfBusinessOptions"
            filterable
            filterPlaceholder="Filter Line of Business...."
            required
            :error="claimForm.errors.line_of_business_id"
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
            v-model="claimForm.claim_type_id"
            label="Claim Type"
            placeholder="Select Claim Type"
            :options="claimTypeOptions"
            filterable
            filterPlaceholder="Filter Claim Type...."
            :error="claimForm.errors.claim_type_id"
          />
          <DatePicker
            v-model="claimForm.incident_date"
            name="incident_date"
            label="Incident Date"
            placeholder="Select Incident Date"
            :hasError="claimForm.errors.incident_date"
          />
          <x-input
            v-model="claimForm.policy_number"
            type="text"
            label="Policy Number"
            placeholder="Enter Policy Number"
            class="w-full"
            :error="claimForm.errors.policy_number"
          />
        </div>
      </div>

      <!-- Form Actions -->
      <div class="flex justify-end gap-3 mb-4">
        <Link href="/claims">
          <x-button size="md" color="gray" type="button"> Cancel </x-button>
        </Link>
        <x-button
          size="md"
          color="emerald"
          type="submit"
          :loading="claimForm.processing"
        >
          {{ isEdit ? 'Update Claim' : 'Create Claim Lead' }}
        </x-button>
      </div>
    </x-form>
  </div>
</template>
