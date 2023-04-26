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

const notification = useNotifications('toast');

const quoteForm = useForm({
  ...formFields.value.reduce((acc, field) => {
    acc[field.value] = '';
    return acc;
  }, {}),
});

const { isRequired, isEmail, isNumber, allowEmpty } = useRules();

function onSubmit(isValid) {
  if (isValid) {
    quoteForm.post(`/quotes/life`, {
      onError: errors => {
        quoteForm.setError(errors);
      },
      onSuccess: () => {
        notification.success({
          title: 'Quote saved successfully',
          position: 'top',
        });
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
          <label v-if="field.type == 'text'">
            <p>
              {{ field.label }}
              <sup v-if="field.required" class="text-red-500">*</sup>
            </p>

            <x-input
              v-model="quoteForm[index]"
              :rules="[field.required === true ? isRequired : allowEmpty]"
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
                field.required === true ? isRequired : allowEmpty,
                isEmail,
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
              type="number"
              v-model="quoteForm[index]"
              :rules="[field.required === true ? isRequired : allowEmpty]"
              :disabled="field.disabled"
              class="w-full"
              :error="quoteForm.errors[index]"
            />
          </label>

          <DatePicker
            v-if="field.type == 'date'"
            v-model="quoteForm[index]"
            :label="field.label"
            type="date"
            :disabled="field.disabled"
            class="w-full"
            :hasError="quoteForm.errors[index]"
          />

          <label v-if="field.type == 'select'">
            <p>
              {{ field.label }}
              <sup v-if="field.required" class="text-red-500">*</sup>
            </p>

            <ComboBox
              v-if="field.type == 'select'"
              v-model="quoteForm[index]"
              :rules="[field.required === true ? isRequired : allowEmpty]"
              :disabled="field.disabled"
              :options="
                field.options.map(option => ({
                  value: option.id,
                  label: option.text,
                }))
              "
              class="w-full"
              :hasError="quoteForm.errors[index]"
              :single="true"
            />
          </label>

          <label v-if="field.type == 'textarea'">
            <p>
              {{ field.label }}
              <sup v-if="field.required" class="text-red-500">*</sup>
            </p>

            <x-textarea
              v-model="quoteForm[index]"
              :rules="[field.required === true ? isRequired : allowEmpty]"
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
