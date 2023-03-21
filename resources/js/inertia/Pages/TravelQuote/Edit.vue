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
    if (field.value === 'dob' || field.value === 'policy_start_date') {
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
          <x-button size="sm" tag="div"> View </x-button>
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
          <x-input
            v-if="field.type == 'text'"
            v-model="quoteForm[index]"
            :label="field.label"
            :rules="[field.required === true ? rules.isRequired : false]"
            :disabled="field.disabled"
            class="w-full"
            :errors="quoteForm.errors[index]"
          />

          <x-input
            v-if="field.type == 'email'"
            v-model="quoteForm[index]"
            type="email"
            :label="field.label"
            :rules="[
              field.required === true ? rules.isRequired : false,
              rules.isEmail,
            ]"
            :disabled="field.disabled"
            class="w-full"
            :errors="quoteForm.errors[index]"
          />

          <x-input
            v-if="field.type == 'number'"
            v-model="quoteForm[index]"
            :label="field.label"
            :disabled="field.disabled"
            class="w-full"
          />

          <x-input
            v-if="field.type == 'date'"
            v-model="quoteForm[index]"
            type="date"
            :label="field.label"
            :disabled="field.disabled"
            class="w-full"
          />

          <x-select
            v-if="field.type == 'select'"
            v-model="quoteForm[index]"
            :label="field.label"
            :rules="[field.required === true ? rules.isRequired : false]"
            :disabled="field.disabled"
            :options="
              field.options.map(option => ({
                value: option.id,
                label: option.text,
              }))
            "
            class="w-full"
            :errors="quoteForm.errors[index]"
          />

          <x-textarea
            v-if="field.type == 'textarea'"
            v-model="quoteForm[index]"
            :label="field.label"
            :rules="[field.required === true ? rules.isRequired : false]"
            :disabled="field.disabled"
            class="w-full"
            :errors="quoteForm.errors[index]"
          />
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
