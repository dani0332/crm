<script setup>
const notification = useNotifications('toast');
const dateFormat = date =>
  date ? useDateFormat(date, 'YYYY-MM-DD').value : '-';

const props = defineProps({
  quote: { type: Object, default: null },
  lookUpData: { type: Object, required: true },
});

// Format API data for dropdowns and selects
const nationalities = computed(() => {
  return props.lookUpData.nationality.map(item => ({
    value: item.id,
    label: item.text,
  }));
});

const emiratesOfRegistration = computed(() => {
  return props.lookUpData.emiratesOfRegistration.map(item => ({
    value: item.id,
    label: item.text,
  }));
});

const quoteForm = useForm({
  first_name: props.quote?.first_name || '',
  last_name: props.quote?.last_name || '',
  email: props.quote?.email || '',
  mobile_no: props.quote?.mobile_no || '',
  dob: props.quote?.dob ? dateFormat(props.quote?.dob) : '',
  nationality_id: props.quote?.nationality_id || '',
  emirate_of_registration_id:
    props.quote?.cyber_quote_request?.emirate_of_registration_id || '',
});

const { isRequired, isEmail, isMobileNo, isValidName } = useRules();

const editMode = computed(() => {
  return props.quote && props.quote.uuid ? true : false;
});

const isEmptyField = ref(false);

function onSubmit(isValid) {
  if (isValid) {
    quoteForm.clearErrors();
    let method = editMode.value ? 'put' : 'post';
    const url = editMode.value
      ? route('cyber-quotes-update', props.quote.uuid)
      : route('cyber-quotes-store');

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
    <Head title="Cyber Quote" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        Cyber Quote <span v-if="quote">{{ quote?.uuid }}</span>
      </h2>
      <div>
        <Link :href="route('cyber-quotes-list')">
          <x-button size="sm" color="#ff5e00"> Cyber Quotes List </x-button>
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
          v-model="quoteForm.email"
          type="email"
          label="Email"
          required
          :disabled="editMode"
          :rules="[isRequired, isEmail]"
          class="w-full"
          :error="quoteForm.errors.email"
        />
        <x-input
          v-model="quoteForm.mobile_no"
          type="tel"
          label="Phone Number"
          required
          :disabled="editMode"
          :rules="[isRequired, ...(editMode ? [] : [isMobileNo])]"
          class="w-full"
          :error="quoteForm.errors.mobile_no"
        />

        <DatePicker
          v-model="quoteForm.dob"
          class="w-full"
          :rules="[isRequired]"
          :max-date="new Date()"
          label="Date of Birth"
          format="dd-MM-yyyy"
          required
        />

        <x-select
          v-model="quoteForm.nationality_id"
          :options="nationalities"
          class="w-full"
          :error="quoteForm.errors.nationality_id"
          :rules="[isRequired]"
          label="Nationality"
          filterable
          placeholder="Search by Nationality"
          required
        />

        <x-select
          v-model="quoteForm.emirate_of_registration_id"
          :options="emiratesOfRegistration"
          class="w-full"
          :error="quoteForm.errors.emirate_of_registration_id"
          :rules="[isRequired]"
          label="Emirate of Registration"
          filterable
          placeholder="Search by Emirate of Registration"
          required
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
