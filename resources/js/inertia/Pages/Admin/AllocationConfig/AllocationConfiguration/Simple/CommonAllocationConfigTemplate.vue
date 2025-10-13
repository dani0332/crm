<script setup>
import { ref, watch, onMounted } from 'vue';

const props = defineProps({
  configuration: {
    type: Object,
    default: null,
  },
  advisorOptions: {
    type: Array,
    default: () => [],
  },
  viewMode: {
    type: Boolean,
    default: false,
  },
  lobName: {
    type: String,
    required: true,
  },
});

const emit = defineEmits(['data-update']);

const advisorIds = ref([]);
const validationErrors = ref({});

const initializeData = () => {
  if (props.configuration && props.configuration.advisor_ids) {
    advisorIds.value = [...props.configuration.advisor_ids];
  } else {
    advisorIds.value = [];
  }
  emitData();
};

const emitData = () => {
  const data = {
    advisor_ids: advisorIds.value,
  };
  emit('data-update', data);
};

const validateAllData = () => {
  const errors = [];

  if (!advisorIds.value || advisorIds.value.length === 0) {
    errors.push(`${props.lobName}: Please select at least one advisor`);
  }

  return errors;
};

const validate = () => {
  const errors = validateAllData();
  validationErrors.value = errors;
  return {
    isValid: errors.length === 0,
    errors: errors,
  };
};

const clearValidationErrors = () => {
  validationErrors.value = {};
};

watch(
  advisorIds,
  () => {
    if (Object.keys(validationErrors.value).length > 0) {
      clearValidationErrors();
    }
    emitData();
  },
  { deep: true },
);

onMounted(() => {
  initializeData();
});

watch(
  () => props.configuration,
  newConfig => {
    if (newConfig) {
      initializeData();
    }
  },
  { deep: true },
);

defineExpose({
  validate,
  clearValidationErrors,
});
</script>

<template>
  <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 bg-white border-b border-gray-200">
      <h3 class="text-lg font-medium text-gray-900 mb-4">
        {{ lobName }} Allocation Configuration
      </h3>

      <div class="max-w-md">
        <x-select
          v-model="advisorIds"
          :options="advisorOptions"
          placeholder="Select advisors..."
          multiple
          filterable
          :disabled="viewMode"
          class="w-full min-h-[40px]"
          label="Advisors"
          required
          tooltip="Select one or more advisors or managers eligible to receive leads for this insurance type."
        >
          <template
            #content-footer
            v-if="advisorOptions.length > 0 && !viewMode"
          >
            <ui-select-actions
              @select-all="advisorIds = advisorOptions.map(item => item.value)"
              @clear="advisorIds = []"
            />
          </template>
        </x-select>

        <div v-if="!viewMode" class="mt-2 text-sm text-gray-500">
          Selected advisors will receive {{ lobName }} insurance leads in a
          round-robin allocation.
        </div>
      </div>
    </div>
  </div>
</template>
