<script setup>
const props = defineProps({
  dropdownSource: Object,
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
  allowEmpty: v => true || 'This field is required',
};

function onSubmit(isValid) {
  if (isValid) {
    quoteForm.post(`/quotes/travel`, {
      onError: errors => {},
      onSuccess: () => {
        router.get(`/quotes/travel/`);
      },
      onStart: () => {
        quoteForm.clearErrors();
      },
    });
  }
}

onMounted(() => {});
</script>

<template>
  <div>
    <Head title="Create Travel" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Create Travel</h2>
      <div>
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
              v-model="quoteForm[index]"
              :rules="[
                field.required === true ? rules.isRequired : rules.allowEmpty,
              ]"
              :disabled="field.disabled"
              :error="quoteForm.errors[index]"
              class="w-full"
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
                field.required === true ? rules.isRequired : rules.allowEmpty,
                rules.isEmail,
              ]"
              :disabled="field.disabled"
              class="w-full"
              :error="quoteForm.errors[index]"
            />
          </label>

          <label v-if="field.type == 'number'">
            <p>
              {{ field.label }}
              <sup v-if="field.required" class="text-red-500">*</sup>
            </p>
            <x-input
              v-model="quoteForm[index]"
              :rules="[
                field.required === true ? rules.isRequired : rules.allowEmpty,
              ]"
              :disabled="field.disabled"
              class="w-full"
              :error="quoteForm.errors[index]"
            />
          </label>

          <DatePicker
            v-if="field.type == 'date'"
            v-model="quoteForm[index]"
            :label="field.label"
            :disabled="field.disabled"
            class="w-full"
            :hasError="quoteForm.errors[index]"
          />

          <label v-if="field.type == 'select'">
            <p>
              {{ field.label }}
              <sup v-if="field.required" class="text-red-500">*</sup>
            </p>
            <x-select
              v-model="quoteForm[index]"
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
          </label>

          <label v-if="field.type == 'textarea'">
            <p>
              {{ field.label }}
              <sup v-if="field.required" class="text-red-500">*</sup>
            </p>
            <x-textarea
              v-model="quoteForm[index]"
              :rules="[
                field.required === true ? rules.isRequired : rules.allowEmpty,
              ]"
              :disabled="field.disabled"
              class="w-full"
              :error="quoteForm.errors[index]"
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
          Create
        </x-button>
      </div>
    </x-form>
  </div>
</template>
