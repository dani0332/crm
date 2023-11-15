<script setup>
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  nationalities: Object,
});
const { isRequired } = useRules();

const payerForm = useForm({
  name: null,
  nationality_id: null,
  date_of_birth: null,
});

const showModal = computed({
  get: () => props.modelValue,
  set: val => emit('update:modelValue', val),
});

const nationalitiesOptions = computed(() => {
  return props.nationalities.map(nat => ({
    value: nat.id,
    label: nat.text,
  }));
});

function onSumbit(isValid) {
  if (isValid) {
  }
}
</script>
<template>
  <AppModal :showClose="true" :showHeader="true" v-model:modelValue="showModal">
    <template #header>KYC Indiviual Details</template>
    <template #default>
      <x-form @submit="onSumbit" :auto-focus="false">
        <div class="grid md:grid-cols-2 mb-5">
          <x-field label="Payer Name">
            <x-input
              v-model="payerForm.name"
              :rules="[isRequired]"
              placeholder="Customer ID"
              type="text"
              class="w-full"
            />
          </x-field>
          <x-field label="Nationality">
            <ComboBox
              :single="true"
              v-model="payerForm.nationality_id"
              placeholder="Select Nationality"
              :options="nationalitiesOptions"
              class="w-full"
            />
          </x-field>
          <x-field label="Date Of Birth">
            <DatePicker
              v-model="payerForm.date_of_birth"
              placeholder="Date of Birth"
              class="w-full"
            />
          </x-field>
        </div>
        <div class="flex justify-end">
          <x-button type="submit" size="sm" color="orange"> Save </x-button>
        </div>
      </x-form>
    </template>
  </AppModal>
</template>