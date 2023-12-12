<script setup>
const props = defineProps({
  tmlead: Object,
  tmLeadStatuses: Array,
  tmInsuranceTypes: Array,
  handlers: Array,
  nationalities: Array,
  yearsOfDrivings: Array,
  carMakes: Array,
  carModels: Array,
  emiratesOfRegistrations: Array,
  carTypeInsurances: Array,
  tmLeadTypes: Array,
  isUserTmAdvisor: String,
});

const { isRequired, isEmail, isMobileNo } = useRules();

const isEdit = computed(() => {
  return route().current().includes('edit');
});

const leadForm = useForm({
  id: null,
  tm_lead_types_id: null,
  customer_name: null,
  tm_insurance_types_id: null,
  email_address: null,
  phone_number: null,
  enquiry_date: null,
  allocation_date: null,
  dob: null,
});

function onSubmit(isValid) {
  if (isValid) {
    leadForm.clearErrors();
    let method = isEdit.value ? 'put' : 'post';
    const url = isEdit.value
      ? route('tmleads-update', leadForm.id)
      : route('tmleads-store');

    leadForm.submit(method, url, {
      onError: errors => {
        leadForm.setError(errors);
      },
      onSuccess: response => {
        leadForm.reset();
      },
    });
  }
}
</script>
<template>
  <Head title="Add TM Lead" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">
      {{ isEdit ? 'Update' : 'Create' }} TM Lead
    </h2>
    <div>
      <Link :href="route('tmleads-list')">
        <x-button size="sm" color="#ff5e00"> TM Lead List </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 gap-4">
      <x-field label="Lead Type" required>
        <x-select
          v-model="leadForm.tm_lead_types_id"
          :options="
            tmLeadTypes.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
          :rules="[isRequired]"
        />
      </x-field>
      <x-field label="Customer Name" required>
        <x-input
          v-model="leadForm.customer_name"
          type="text"
          :rules="[isRequired]"
          class="w-full"
        />
      </x-field>
      <x-field label="Insurance Type" required>
        <x-select
          v-model="leadForm.tm_insurance_types_id"
          :options="
            tmInsuranceTypes.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
          :rules="[isRequired]"
        />
      </x-field>
      <x-field label="Email Address" required>
        <x-input
          v-model="leadForm.email_address"
          type="email"
          :rules="[isRequired, isEmail]"
          class="w-full"
        />
      </x-field>
      <x-field label="Phone Number" required>
        <x-input
          v-model="leadForm.phone_number"
          type="tel"
          :rules="[isRequired, isMobileNo]"
          class="w-full"
        />
      </x-field>
      <x-field label="Enquiry date" required>
        <DatePicker
          v-model="leadForm.enquiry_date"
          class="w-full"
          :rules="[isRequired]"
        />
      </x-field>
      <x-field label="Allocation date" required>
        <DatePicker
          class="w-full"
          v-model="leadForm.allocation_date"
          :rules="[isRequired]"
        />
      </x-field>
      <!-- <x-field label="DOB">
        <DatePicker class="w-full" v-model="leadForm.dob" />
      </x-field> -->
    </div>

    <x-divider class="my-4" />
    <div class="flex justify-end gap-3 mb-4">
      <x-button size="md" color="emerald" type="submit">
        {{ isEdit ? 'Update' : 'Save' }}
      </x-button>
    </div>
  </x-form>
</template>