<script setup>
import { reactive, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
  uuid: String,
});

const emit = defineEmits(['success']);

const options = reactive({
  insurancePlans: [],
});

const createForm = useForm({
  quote_uuid: props.uuid,
  provider_id: null,
  plan_id: null,
  premium: null,
});

const onSubmit = () => {
  createForm.post('/health-plan-manual-create', {
    preserveScroll: true,
    onSuccess: () => {
      emit('success');
    },
    onError: errors => {
      console.log(errors);
    },
  });
};

watch(
  () => createForm?.provider_id,
  value => {
    if (value) {
      axios
        .get(
          `/insurance-provider-plans?insuranceProviderId=${value}&quoteUuId=${props.uuid}`,
        )
        .then(res => {
          if (res.data.length > 0) {
            options.insurancePlans = res.data;
          }
        });

      createForm.plan_id = null;
    }
  },
);
</script>

<template>
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid gap-5 p-4">
      <x-select
        v-model="createForm.provider_id"
        :options="
          $page.props.insuranceProviders?.map(item => ({
            value: item.id,
            label: item.text,
          }))
        "
        label="Provider"
        placeholder="Select Provider"
        :disabled="$page.props.insuranceProviders?.length == 0"
        class="w-full"
      />
      <x-select
        v-model="createForm.plan_id"
        label="Plan"
        placeholder="Select Plan"
        :disabled="!createForm.provider_id"
        class="w-full"
        :helper="!createForm.provider_id ? 'Select a provider first' : ''"
        :options="
          options.insurancePlans?.map(item => ({
            value: item.id,
            label: item.text,
          }))
        "
      />
      <x-input
        v-model="createForm.premium"
        type="text"
        label="Premium"
        placeholder="Enter Premium (inclusive of VAT, Basmah and Policy fee)"
        class="w-full"
      />
      <x-button
        type="submit"
        class="w-full"
        color="primary"
        :loading="createForm.processing"
      >
        Add Quote
      </x-button>
    </div>
  </x-form>
</template>
