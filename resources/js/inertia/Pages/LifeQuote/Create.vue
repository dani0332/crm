<script setup>
const props = defineProps({
  dropdownSource: Object,
  genderOptions: Object,
  fields: Object,
});

const formFields = computed(() => {
  return Object.keys(props.fields).map(key => ({
    value: key,
    label: props.fields[key].label,
  }));
});

const quoteForm = useForm({
  ...formFields.value.reduce((acc, field) => {
    acc[field.value] = '';
    return acc;
  }, {}),
});

const rules = {
  isEmail: v =>
    /^\w+([.-]?\w+)*@\w+([.-]?\w+)*(\.\w{2,3})+$/.test(v) ||
    'E-mail must be valid',
  isRequired: v => !!v || 'This field is required',
  isNumber: v => /^\d+$/.test(v) || 'This field must be a number',
  allowEmpty: v => true || 'This field is required',
};

function onSubmit(isValid) {
  if (isValid) {
    quoteForm.post(`/quotes/life`, {
      onError: errors => {
        console.log(errors);
      },
      onSuccess: () => {
        router.get(`/quotes/life/`);
      },
    });
  }
}

onMounted(() => {});
</script>

<template>
  <div>
    <Head title="Create Life Quote" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Create Life Quote</h2>
      <div>
        <Link href="/quotes/life">
          <x-button size="sm" color="#ff5e00" tag="div"> Quote List </x-button>
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
            :rules="[
              field.required === true ? rules.isRequired : rules.allowEmpty,
            ]"
            :disabled="field.disabled"
            :error="quoteForm.errors[index]"
            class="w-full"
          />

          <x-input
            v-if="field.type == 'email'"
            v-model="quoteForm[index]"
            type="email"
            :label="field.label"
            :rules="[
              field.required === true ? rules.isRequired : rules.allowEmpty,
              rules.isEmail,
            ]"
            :disabled="field.disabled"
            class="w-full"
            :error="quoteForm.errors[index]"
          />

          <x-input
            v-if="field.type == 'number'"
            type="number"
            v-model="quoteForm[index]"
            :label="field.label"
            :rules="[
              field.required === true ? rules.isRequired : rules.allowEmpty,
            ]"
            :disabled="field.disabled"
            class="w-full"
            :error="quoteForm.errors[index]"
          />

          <x-input
            v-if="field.type == 'date'"
            v-model="quoteForm[index]"
            type="date"
            :label="field.label"
            :disabled="field.disabled"
            class="w-full"
            :error="quoteForm.errors[index]"
          />

          <x-select
            v-if="field.type == 'select'"
            v-model="quoteForm[index]"
            :label="field.label"
            :rules="[
              field.required === true ? rules.isRequired : rules.allowEmpty,
            ]"
            :disabled="field.disabled"
            :options="
              field.options.map(option => ({
                value: option.id,
                label: option.text,
              }))
            "
            class="w-full"
            :error="quoteForm.errors[index]"
          />

          <x-textarea
            v-if="field.type == 'textarea'"
            v-model="quoteForm[index]"
            :label="field.label"
            :rules="[
              field.required === true ? rules.isRequired : rules.allowEmpty,
            ]"
            :disabled="field.disabled"
            class="w-full"
            :error="quoteForm.errors[index]"
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
          Create
        </x-button>
      </div>
    </x-form>
  </div>
</template>
