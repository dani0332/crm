<script setup>
const props = defineProps({
  uuid: String,
});

const emit = defineEmits(['success', 'error']);

const options = reactive({
  insurancePlans: [],
  loading: false,
});

const createForm = reactive({
  provider_id: null,
  plan_id: null,
  premium: null,
  loading: false,
});

const onSubmit = isValid => {
  if (!isValid) {
    return;
  }
  createForm.loading = true;
  axios
    .post('/car-plan-manual-create', {
      quoteUID: props.uuid,
      planId: createForm.plan_id,
      actualPremium: createForm.premium,
    })
    .then(res => {
      if (res.data == 200) {
        emit('success');
      } else {
        emit('error');
      }
    })
    .catch(err => {
      emit('error');
    })
    .finally(() => {
      createForm.loading = false;
    });
};

watch(
  () => createForm?.provider_id,
  value => {
    if (value) {
      options.loading = true;
      axios
        .get(
          `/insurance-provider-plans-car?insuranceProviderId=${value}&quoteUuId=${props.uuid}`,
        )
        .then(res => {
          if (res.data.length > 0) {
            options.insurancePlans = res.data;
          }
        })
        .finally(() => {
          options.loading = false;
          createForm.plan_id = null;
        });
    }
  },
);
</script>

<template>
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid gap-5">
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
        :loading="options.loading"
        :rules="[isRequired]"
      />
      <x-input
        v-model="createForm.premium"
        type="text"
        label="Premium"
        placeholder="Enter Premium (inclusive of VAT, Basmah and Policy fee)"
        class="w-full"
        :rules="[isRequired, isNumber]"
      />
      <x-button
        type="submit"
        class="w-full"
        color="primary"
        :loading="createForm.loading"
      >
        Add Quote
      </x-button>
    </div>
  </x-form>
</template>
