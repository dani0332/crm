<script setup>
const props = defineProps({
  quote: Object,
  dropdownSource: Object,
  model: String,
  genderOptions: Object,
  fields: Object,
});

const genderSelect = computed(() => {
  return Object.keys(props.genderOptions).map(status => ({
    value: status,
    label: props.genderOptions[status],
  }));
});

const formFields = computed(() => {
  return Object.keys(props.fields).map(field => ({
    value: field,
    label: props.fields[field].label,
  }));
});

const quoteForm = useForm({
  modelType: '"Travel"',
  model: props.model,
  ...formFields.value.reduce((acc, field) => {
    acc[field.value] = props.quote[field.value];
    if (field.value === 'dob') {
      acc[field.value] = props.quote[field.value]
        ? props.quote[field.value].split('-').reverse().join('-')
        : null;
    }
    return acc;
  }, {}),
});

const rules = {
  isEmail: v =>
    /^\w+([.-]?\w+)*@\w+([.-]?\w+)*(\.\w{2,3})+$/.test(v) ||
    'E-mail must be valid',
  isRequired: v => !!v || 'This field is required',
  isNumber: v => /^\d+$/.test(v) || 'This field must be a number',
};

const isEmptyField = ref(false);

function onSubmit(isValid) {
  if (isValid) {
    quoteForm.put(`/quotes/travel/${props.quote.uuid}`, {
      onSuccess: () => {
        router.get(`/quotes/travel/${props.quote.uuid}`);
      },
      onStart: () => {
        quoteForm.clearErrors();
      },
    });
  }
}
</script>

<template>
  <div>
    <Head title="Edit Travel" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Edit Traveler</h2>
      <div class="space-x-4">
        <Link :href="`/quotes/travel/${props.quote.uuid}`">
          <x-button size="sm" tag="div"> Cancel </x-button>
        </Link>
        <Link href="/quotes/travel">
          <x-button size="sm" color="#ff5e00" tag="div"> Travel List </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 gap-4">
        <template v-for="(field, index) in props.fields">
          <label v-if="field.type == 'text'">
            <p>
              {{ field.label }}
              <sup v-if="field.required" class="text-red-500">*</sup>
            </p>

            <x-input
              v-if="field.type == 'text'"
              v-model="quoteForm[index]"
              :rules="[field.required === true ? rules.isRequired : false]"
              :disabled="field.disabled"
              class="w-full"
              :error="quoteForm.errors[index]"
              maxlength="255"
            />
          </label>

          <label v-if="field.type == 'email'">
            <p>
              {{ field.label }}
              <sup v-if="field.required" class="text-red-500">*</sup>
            </p>
            <x-input
              v-model="quoteForm[index]"
              type="email"
              :rules="[
                field.required === true ? rules.isRequired : false,
                rules.isEmail,
              ]"
              :disabled="field.disabled"
              class="w-full"
              :error="quoteForm.errors[index]"
              maxlength="255"
            />
          </label>

          <label v-if="field.type == 'number'">
            <p>
              {{ field.label }}
              <sup v-if="field.required" class="text-red-500">*</sup>
            </p>
            <x-input
              v-if="field.type == 'number'"
              v-model="quoteForm[index]"
              :disabled="field.disabled"
              class="w-full"
              :error="quoteForm.errors[index]"
              maxlength="20"
            />
          </label>

          <DatePicker
            v-if="field.type == 'date'"
            v-model="quoteForm[index]"
            :label="field.label"
            :disabled="field.disabled"
            :hasError="quoteForm.errors[index]"
          />

          <label v-if="field.type == 'select'">
            <p>
              {{ field.label }}
              <sup v-if="field.required" class="text-red-500">*</sup>
            </p>
            <ComboBox
              v-model="quoteForm[index]"
              :rules="[field.required === true ? rules.isRequired : false]"
              :disabled="field.disabled"
              :options="
                field.options.map(option => ({
                  value: option.id,
                  label: option.text,
                }))
              "
              :single="true"
              class="w-full"
              :hasError="quoteForm.errors[index]"
            />
          </label>

          <label v-if="field.type == 'textarea'">
            <p>
              {{ field.label }}
              <sup v-if="field.required" class="text-red-500">*</sup>
            </p>

            <x-textarea
              v-model="quoteForm[index]"
              :rules="[field.required === true ? rules.isRequired : false]"
              :disabled="field.disabled"
              class="w-full"
              :error="quoteForm.errors[index]"
              maxlength="1000"
            />
          </label>
        </template>
      </div>
      <x-divider class="my-4" />
      <div class="flex justify-end gap-3 mb-4">
        <x-button
          size="md"
          color="emerald"
          type="submit"
          :loading="quoteForm.processing"
        >
          Update
        </x-button>
      </div>
    </x-form>
  </div>
</template>
