<script setup>
import { useForm } from '@inertiajs/vue3';

const createForm = useForm({
  provider_id: null,
  plan_id: null,
  network_id: null,
  premium: null,
});

const onSubmit = () => {
  // createForm.post('/health/insurance/create', {
  //   preserveScroll: true,
  //   preserveState: true,
  //   only: ['insuranceProviders', 'insurancePlans', 'insuranceNetworks'],
  // });
};
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
      />
      <x-select
        v-model="createForm.network_id"
        label="Network"
        placeholder="Select Network"
        :disabled="!createForm.plan_id"
        class="w-full"
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
