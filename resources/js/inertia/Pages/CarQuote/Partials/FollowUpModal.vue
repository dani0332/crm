<script setup>
const props = defineProps({
  modelValue: Boolean,
  isLoading: Boolean,
});
const emit = defineEmits(['update:modelValue', 'sendTemplateForm']);

const value = computed({
  get() {
    return props.modelValue;
  },
  set(value) {
    emit('update:modelValue', value);
  },
});

const followUpForm = ref({
  quote_type: 'Car',
  type: 'HOLIDAY',
  renewal_batch: '',
  quote_batch_id: '',
  template_id: 1,
});
</script>

<template>
  <x-modal v-model="value" backdrop size="lg">
    <div class="m-4">
      <p>Please Select one of the templates provided below to use:</p>
      <div class="px-3 py-2">
        <x-radio
          class="my-2"
          v-model="followUpForm.template_id"
          :value="523"
          label="Holiday Template"
        />
        <div class="mb-2">
          <x-radio
            v-model="followUpForm.template_id"
            :value="511"
            label="Follow up 1: Preview Text: Time Sensitive: Secure Your Motor Insurance Today!"
          />
        </div>
        <div class="mb-2">
          <x-radio
            v-model="followUpForm.template_id"
            :value="512"
            label="Follow up 2: Preview Text: Secure Your Motor Insurance Today!"
          />
        </div>
        <div class="mb-2">
          <x-radio
            v-model="followUpForm.template_id"
            :value="513"
            label="Follow up 3: Making Sure You Don't Miss Out on Motor Insurance Coverage"
          />
        </div>
      </div>
    </div>
    <template #actions>
      <div class="flex gap-2 justify-end">
        <x-button
          size="sm"
          color="#ff5e00"
          type="button"
          :loading="isLoading"
          @click.prevent="emit('sendTemplateForm', followUpForm)"
          >Send
        </x-button>
        <x-button
          size="sm"
          color="error"
          type="button"
          @click.prevent="emit('update:modelValue', false)"
          >Cancel
        </x-button>
      </div>
    </template>
  </x-modal>
</template>
