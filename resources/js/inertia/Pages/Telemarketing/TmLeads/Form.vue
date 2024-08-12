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
  id: props.tmlead?.id ?? null,
  tm_lead_types_id: props.tmlead?.tm_lead_types_id ?? null,
  customer_name: props.tmlead?.customer_name ?? null,
  tm_insurance_types_id: props?.tmlead?.tm_insurance_types_id ?? null,
  email_address: props.tmlead?.email_address ?? null,
  phone_number: props.tmlead?.phone_number ?? null,
  enquiry_date: props.tmlead?.enquiry_date ?? null,
  allocation_date: props.tmlead?.allocation_date ?? null,
  dob: props.tmlead?.dob ?? null,
  car_type_insurance_id: props.tmlead?.car_type_insurance_id ?? null,
  years_of_driving_id: props.tmlead?.years_of_driving_id ?? null,
  car_make_id: props.tmlead?.car_make_id ?? null,
  car_model_id: props.tmlead?.car_model_id ?? null,
  year_of_manufacture: props.tmlead?.year_of_manufacture ?? null,
  emirates_of_registration_id:
    props.tmlead?.emirates_of_registration_id ?? null,
  car_value: props.tmlead?.car_value ?? null,
  nationality_id: props.tmlead?.nationality_id ?? null,
});

const tmInsuranceTypes = ref([...props.tmInsuranceTypes]);

const showCarKeys = ref(false);

const handleshowCarKeys = e => {
  let insuranceType = tmInsuranceTypes.value.find(x => {
    if (x.id == e || x.id == leadForm.tm_insurance_types_id) return x;
  });
  insuranceType && insuranceType.code == 'Car'
    ? (showCarKeys.value = true)
    : (showCarKeys.value = false);
};

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

onMounted(() => {
  handleshowCarKeys();
});
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
          @update:modelValue="handleshowCarKeys($event)"
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
      <x-field label="DOB">
        <DatePicker class="w-full" v-model="leadForm.dob" />
      </x-field>
    </div>
    <div v-show="showCarKeys" class="grid sm:grid-cols-2 gap-4 mt-4">
      <x-field label="Car Type of Insurance" required>
        <x-select
          v-model="leadForm.car_type_insurance_id"
          :options="
            carTypeInsurances.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
        />
      </x-field>
      <x-field label="Years of driving" required>
        <x-select
          v-model="leadForm.years_of_driving_id"
          :options="
            yearsOfDrivings.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
        />
      </x-field>
      <x-field label="Car Make" required>
        <x-select
          v-model="leadForm.car_make_id"
          :options="
            carMakes.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
        />
      </x-field>
      <x-field label="Car Model" required>
        <x-select
          v-model="leadForm.car_model_id"
          :options="
            carModels.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
        />
      </x-field>
      <x-field label="Year of Manufacture">
        <DatePicker class="w-full" v-model="leadForm.year_of_manufacture" />
      </x-field>
      <x-field label="Emirates of Registration" required>
        <x-select
          v-model="leadForm.emirates_of_registration_id"
          :options="
            emiratesOfRegistrations.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
        />
      </x-field>
      <x-field label="Car Value" required>
        <x-input v-model="leadForm.car_value" type="text" class="w-full" />
      </x-field>
      <x-field label="Nationality" required>
        <x-select
          v-model="leadForm.nationality_id"
          :options="
            nationalities.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
        />
      </x-field>
    </div>

    <x-divider class="my-4" />
    <div class="flex justify-end gap-3 mb-4">
      <x-button size="md" color="emerald" type="submit">
        {{ isEdit ? 'Update' : 'Save' }}
      </x-button>
    </div>
  </x-form>
</template>
