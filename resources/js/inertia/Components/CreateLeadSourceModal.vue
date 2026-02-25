<script setup>
const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:modelValue', 'created']);

const { isRequired } = useRules();

const leadSourceForm = useForm({
  name: '',
});

const isModalOpen = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value),
});

const onSubmit = isValid => {
  if (!isValid) return;

  leadSourceForm.processing = true;

  axios
    .post(route('lead-source.store'), {
      name: leadSourceForm.name,
    })
    .then(response => {
      const newLeadSource = response.data.data;

      // Emit the created event with the new lead source data
      emit('created', newLeadSource);

      // Reset form and close modal
      resetForm();
      isModalOpen.value = false;

      // Show success notification
      const notifications = useNotifications();
      notifications.addNotification({
        type: 'success',
        title: 'Success',
        message: response.data.message || 'Lead source created successfully.',
      });
    })
    .catch(error => {
      leadSourceForm.processing = false;

      // Handle validation errors
      if (error.response && error.response.data && error.response.data.errors) {
        Object.keys(error.response.data.errors).forEach(key => {
          leadSourceForm.setError(key, error.response.data.errors[key][0]);
        });
      } else {
        // Show generic error notification
        const notifications = useNotifications();
        notifications.addNotification({
          type: 'error',
          title: 'Error',
          message:
            error.response?.data?.message || 'Failed to create lead source.',
        });
      }
    });
};

const onCancel = () => {
  isModalOpen.value = false;
  resetForm();
};

const resetForm = () => {
  leadSourceForm.name = '';
  leadSourceForm.processing = false;
  leadSourceForm.clearErrors();
};

// Watch for modal close to reset form
watch(isModalOpen, newValue => {
  if (!newValue) {
    resetForm();
  }
});
</script>

<template>
  <x-modal
    v-model="isModalOpen"
    size="lg"
    title="Create Lead Source"
    show-close
    backdrop
    persistent
  >
    <x-form id="createLeadSourceForm" @submit="onSubmit" :auto-focus="false">
      <div class="grid gap-4">
        <x-input
          v-model="leadSourceForm.name"
          label="Lead Source"
          placeholder="Enter lead source"
          :error="leadSourceForm.errors.name"
          :rules="[isRequired]"
          required
        />
      </div>
    </x-form>

    <template #actions>
      <x-button
        ghost
        tabindex="-1"
        size="md"
        type="button"
        :disabled="leadSourceForm.processing"
        @click.prevent="onCancel"
      >
        Cancel
      </x-button>
      <x-button
        size="md"
        color="emerald"
        type="submit"
        form="createLeadSourceForm"
        :loading="leadSourceForm.processing"
      >
        Create
      </x-button>
    </template>
  </x-modal>
</template>
