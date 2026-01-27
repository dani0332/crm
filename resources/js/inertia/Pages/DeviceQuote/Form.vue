<script setup>
const notification = useNotifications('toast');
const dateFormat = date =>
  date ? useDateFormat(date, 'YYYY-MM-DD').value : '-';

const props = defineProps({
  quote: { type: Object, default: null },
  lookUpData: { type: Object, required: true },
  deviceMakes: { type: Array, required: true },
});

// Format API data for dropdowns and selects
const mobileBrands = computed(() => {
  return (
    props.lookUpData?.mobileBrands?.map(item => ({
      value: item.id,
      label: item.text,
    })) || []
  );
});

// Generate year options (current year and past years)
const currentYear = new Date().getFullYear();
const yearOptions = computed(() => {
  const years = [];
  for (let year = currentYear; year >= currentYear - 10; year--) {
    years.push({ value: year, label: year.toString() });
  }
  return years;
});

const deviceMakesList = computed(() => {
  return props.deviceMakes.map(item => {
    return {
      value: item.id,
      label: item.text,
      models: item.device_models?.map(model => ({
        value: model.id,
        label: model.text,
      })),
    };
  });
});

const selectedDeviceMake = computed(() => {
  if (!quoteForm.make_id) {
    return [];
  }
  return (
    deviceMakesList.value.find(item => item.value === quoteForm.make_id)
      ?.models || []
  );
});

// Generate month options
const monthOptions = computed(() => {
  return [
    { value: '01', label: 'January' },
    { value: '02', label: 'February' },
    { value: '03', label: 'March' },
    { value: '04', label: 'April' },
    { value: '05', label: 'May' },
    { value: '06', label: 'June' },
    { value: '07', label: 'July' },
    { value: '08', label: 'August' },
    { value: '09', label: 'September' },
    { value: '10', label: 'October' },
    { value: '11', label: 'November' },
    { value: '12', label: 'December' },
  ];
});

const quoteForm = useForm({
  first_name: props.quote?.first_name || '',
  last_name: props.quote?.last_name || '',
  email: props.quote?.email || '',
  mobile_no: props.quote?.mobile_no || '',
  month_of_purchase: props.quote?.device_quote?.purchase_date
    ? String(
        new Date(props.quote?.device_quote?.purchase_date).getMonth() + 1,
      ).padStart(2, '0')
    : '',
  year_of_purchase: props.quote?.device_quote?.purchase_date
    ? Number(new Date(props.quote.device_quote.purchase_date).getFullYear())
    : '',
  make_id: props.quote?.device_quote?.make_id || '',
  model_id: props.quote?.device_quote?.model_id || '',
  imei: props.quote?.device_quote?.imei || '',
});

const { isRequired, isEmail, isMobileNo, isValidName } = useRules();

const editMode = computed(() => {
  return props.quote && props.quote.uuid ? true : false;
});

function onSubmit(isValid) {
  if (isValid) {
    quoteForm.clearErrors();
    let method = editMode.value ? 'put' : 'post';
    const url = editMode.value
      ? route('device-quotes-update', props.quote.uuid)
      : route('device-quotes-store');

    quoteForm.submit(method, url, {
      onError: errors => {
        console.log(quoteForm.setError(errors));
      },
    });
  }
}
</script>

<template>
  <div>
    <Head title="Device Quote" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        Device Quote <span v-if="quote">{{ quote?.uuid }}</span>
      </h2>
      <div>
        <Link :href="route('device-quotes-list')">
          <x-button size="sm" color="#ff5e00">
            Smartphone Quotes List
          </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <x-alert color="error" class="mb-5" v-if="quoteForm.errors.error">
        {{ quoteForm?.errors?.error }}
      </x-alert>

      <div class="grid sm:grid-cols-2 gap-4">
        <!-- Personal Details -->
        <x-input
          v-model="quoteForm.first_name"
          type="text"
          label="First Name"
          required
          :rules="[isRequired, isValidName]"
          class="w-full"
          maxLength="20"
          :error="quoteForm.errors.first_name"
        />
        <x-input
          v-model="quoteForm.last_name"
          type="text"
          label="Last Name"
          required
          maxLength="50"
          :rules="[isRequired, isValidName]"
          class="w-full"
          :error="quoteForm.errors.last_name"
        />
        <x-input
          v-model="quoteForm.mobile_no"
          type="tel"
          label="Your Phone Number"
          required
          :disabled="editMode"
          :rules="[isRequired, ...(editMode ? [] : [isMobileNo])]"
          class="w-full"
          :error="quoteForm.errors.mobile_no"
        />
        <x-input
          v-model="quoteForm.email"
          type="email"
          label="Your Email"
          required
          :disabled="editMode"
          :rules="[isRequired, isEmail]"
          class="w-full"
          :error="quoteForm.errors.email"
        />

        <!-- Device Details -->
        <x-select
          v-model="quoteForm.month_of_purchase"
          :options="monthOptions"
          class="w-full"
          :error="quoteForm.errors.month_of_purchase"
          :rules="[isRequired]"
          label="Month of purchase"
          placeholder="Select Month"
          required
        />

        <x-select
          v-model="quoteForm.year_of_purchase"
          :options="yearOptions"
          class="w-full"
          :error="quoteForm.errors.year_of_purchase"
          :rules="[isRequired]"
          label="Year of purchase"
          placeholder="Select Year"
          required
        />

        <x-select
          v-model="quoteForm.make_id"
          :options="deviceMakesList"
          class="w-full"
          :error="quoteForm.errors.make_id"
          :rules="[isRequired]"
          label="Device Make"
          filterable
          placeholder="Search by Device Make"
          required
        />

        <x-select
          v-model="quoteForm.model_id"
          :options="selectedDeviceMake"
          class="w-full"
          :error="quoteForm.errors.model_id"
          :rules="[isRequired]"
          label="Device Model"
          filterable
          placeholder="Search by Mobile Model"
          required
        />

        <x-input
          v-model="quoteForm.imei"
          type="text"
          label="IMEI Number"
          required
          :rules="[
            isRequired,
            v =>
              (!!v && /^\d{15}$/.test(v)) || 'IMEI must be a 15-digit number.',
          ]"
          class="w-full"
          :error="quoteForm.errors.imei"
          placeholder="Enter 15 digit IMEI number"
        />
      </div>
      <x-divider class="my-4" />
      <div class="flex justify-end gap-3 mb-4">
        <x-button
          size="md"
          color="emerald"
          type="submit"
          :loading="quoteForm.processing"
        >
          {{ editMode ? 'Update' : 'Create' }}
        </x-button>
      </div>
    </x-form>
  </div>
</template>

<style scoped>
/* Ensure tooltip appears on hover */
.group:hover .group-hover\:block {
  display: block;
}

/* Custom tooltip styling to match design */
.tooltip-box {
  border: 1px solid #1d83bc;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  color: #33333399;
  line-height: 1.5;
  padding: 12px;
  font-size: 14px;
  max-width: 280px;
  background-color: #f8fafc;
}

.radio-wrapper {
  cursor: pointer;
}
</style>
