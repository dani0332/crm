<script setup>
import { useForm } from '@inertiajs/vue3';

const { isRequired } = useRules();

const props = defineProps({
  sendUpdateLog: {
    type: Object,
    required: true,
  },
  insuranceProviders: {
    type: Array,
    required: true,
  },
  insuranceProviderId: {
    type: Number,
    required: false,
  },
});

const state = reactive({
  isEdit: false,
});

const notification = useToast();

const insuranceProvidersOptions = computed(() => {
  return props?.insuranceProviders?.map(provider => ({
    value: provider.id,
    label: provider.text,
  }));
});

const providerDetailsForm = useForm({
  insurance_provider_id:
    props.sendUpdateLog?.insurance_provider_id ||
    props?.insuranceProviderId ||
    null,
  send_update_log_id: props.sendUpdateLog.id,
});

const onUpdate = () => {
  providerDetailsForm.post(route('send-update.save-provider-details'), {
    preserverScroll: true,
    onSuccess: () => {
      notification.success({
        title: 'The request has been updated',
        position: 'top',
      });
      state.isEdit = false;
    },
    onError: errors => {
      Object.keys(errors).forEach(function (key) {
        notification.error({
          title: errors[key],
          position: 'top',
        });
      });
    },
  });
};

const onCancel = () => {
  state.isEdit = false;
  providerDetailsForm.insurance_provider_id =
    props.sendUpdateLog?.insurance_provider_id || null;
};
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible expanded>
      <template #header>
        <div class="flex justify-between gap-4 items-center">
          <h3 class="font-semibold text-primary-800 text-lg">
            Provider Details
          </h3>
        </div>
      </template>

      <template #body>
        <x-divider class="my-4" />
        <div class="text-sm">
          <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 break-words">
            <!-- Provider Name -->
            <div class="grid sm:grid-cols-2">
              <dt>
                <x-tooltip>
                  <label
                    class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                  >
                    PROVIDER NAME
                  </label>
                  <template #tooltip>
                    Name of the insurance company responsible for the coverage.
                  </template>
                </x-tooltip>
              </dt>
              <dd>
                <ComboBox
                  v-model="providerDetailsForm.insurance_provider_id"
                  :options="insuranceProvidersOptions"
                  placeholder="Provider Name"
                  :single="true"
                  :disabled="!state.isEdit"
                />
              </dd>
            </div>
          </dl>
        </div>
        <x-divider class="my-4 mt-10" />
        <div class="flex justify-end gap-2">
          <x-button size="sm" @click="state.isEdit = true" v-if="!state.isEdit">
            Edit
          </x-button>
          <template v-else>
            <x-button
              size="sm"
              color="orange"
              @click="onCancel"
              :loading="providerDetailsForm.processing"
              :disabled="providerDetailsForm.processing"
            >
              Cancel
            </x-button>
            <x-button
              size="sm"
              color="primary"
              @click="onUpdate"
              :loading="providerDetailsForm.processing"
              :disabled="providerDetailsForm.processing"
              >Update
            </x-button>
          </template>
        </div>
      </template>
    </Collapsible>
  </div>
</template>
