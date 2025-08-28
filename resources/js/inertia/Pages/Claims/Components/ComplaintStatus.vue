<script setup>
import { router } from '@inertiajs/vue3';

const props = defineProps({
  claim: Object,
  complaintStatuses: Object,
  expanded: {
    type: Boolean,
    required: false,
    default: false,
  },
});

const emit = defineEmits(['update']);

const page = usePage();
const can = permission => useCan(permission);
const canAny = permissions => useCanAny(permissions);
const permissionsEnum = page.props.permissionsEnum;
const notification = useToast();

const complaintStatusForm = useForm({
  complaint_status_id: props.claim?.complaint_status_id || '',
  notes: props.claim?.complaint_notes || '',
  complaint_datetime: props.claim?.complaint_datetime || '',
});

// Client-side validation rules
const validationErrors = ref({});
const hasInteracted = ref(false);

// Computed properties for date constraints
const maxDate = computed(() => new Date()); // Cannot be in the future

const complaintStatusOptions = computed(() => {
  return (
    props.complaintStatuses?.map(status => ({
      value: status.id,
      label: status.text,
    })) || []
  );
});

const validateForm = (showRequiredErrors = true) => {
  const errors = {};

  // Validate complaint_status_id - required (only show required error if user has interacted or explicitly requested)
  if (!complaintStatusForm.complaint_status_id) {
    if (showRequiredErrors && hasInteracted.value) {
      errors.complaint_status_id = 'The complaint status field is required.';
    }
  }

  // Validate complaint_datetime - required
  if (!complaintStatusForm.complaint_datetime) {
    if (showRequiredErrors && hasInteracted.value) {
      errors.complaint_datetime = 'The complaint date field is required.';
    }
  } else {
    // DatePicker returns Date object, so handle accordingly
    const selectedDate =
      complaintStatusForm.complaint_datetime instanceof Date
        ? complaintStatusForm.complaint_datetime
        : new Date(complaintStatusForm.complaint_datetime);
    const now = new Date();

    // Validate date format
    if (isNaN(selectedDate.getTime())) {
      errors.complaint_datetime = 'The complaint date must be a valid date.';
    }
    // Validate that date is not in the future
    else if (selectedDate > now) {
      errors.complaint_datetime = 'The complaint date cannot be in the future.';
    }
  }

  // Validate notes - max 250 characters
  if (complaintStatusForm.notes && complaintStatusForm.notes.length > 250) {
    errors.notes = 'The notes must be less than 250 characters.';
  }

  validationErrors.value = errors;
  return Object.keys(errors).length === 0;
};

// Reactive validation state
const isFormValid = ref(true);

// Method to validate form on input change
const validateFormOnChange = () => {
  hasInteracted.value = true;
  isFormValid.value = validateForm();
};

// Watch for changes and validate
watch(
  [
    () => complaintStatusForm.complaint_status_id,
    () => complaintStatusForm.complaint_datetime,
    () => complaintStatusForm.notes,
  ],
  () => {
    // Don't show required errors on initial load, only validate format/range errors
    isFormValid.value = validateForm(false);
  },
  { immediate: true },
);

// Computed property to disable submit button
const isSubmitDisabled = computed(() => {
  return complaintStatusForm.processing || !isFormValid.value;
});

const updateComplaintStatus = isValid => {
  console.log('updateComplaintStatus');

  // Perform client-side validation with all errors including required errors
  if (!validateForm(true)) {
    // Show validation errors only on submit
    Object.keys(validationErrors.value).forEach(function (key) {
      notification.error({
        title: validationErrors.value[key],
        position: 'top',
      });
    });
    return;
  }

  // Clear any previous client-side errors before submitting
  validationErrors.value = {};

  complaintStatusForm.post(
    route('claims.update.complaint-status', props.claim?.uuid),
    {
      preserveScroll: true,
      onSuccess: response => {
        console.log('response', response);
      },
      onError: errors => {
        Object.keys(errors).forEach(function (key) {
          notification.error({
            title: errors[key],
            position: 'top',
          });
        });
      },
    },
  );
};
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="expanded">
      <template #header>
        <div>
          <h3 class="font-semibold text-primary-800 text-lg">
            Complaint Status
          </h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <x-form :form="complaintStatusForm" @submit="updateComplaintStatus">
          <div class="space-y-4 flex flex-col gap-4">
            <!-- Status Dropdown -->
            <div class="w-1/2">
              <x-select
                v-model="complaintStatusForm.complaint_status_id"
                :error="
                  complaintStatusForm.errors.complaint_status_id ||
                  validationErrors.complaint_status_id
                "
                :options="complaintStatusOptions"
                placeholder="N/A"
                label="Status"
                class="w-full"
                filterable
                required
                @update:model-value="validateFormOnChange"
              />
            </div>
            <div class="w-1/2">
              <DatePicker
                v-model="complaintStatusForm.complaint_datetime"
                label="Complaint Date"
                :error="
                  complaintStatusForm.errors.complaint_datetime ||
                  validationErrors.complaint_datetime
                "
                placeholder="Please select complaint date"
                class="w-full"
                :max-date="maxDate"
                required
                :utc="true"
                :is-24="true"
                @update:model-value="validateFormOnChange"
              />
            </div>

            <!-- Notes -->
            <div class="w-full">
              <label
                class="block text-sm font-medium text-gray-700 mb-2 uppercase text-xs tracking-wide"
              >
                NOTES
                <span class="text-xs text-gray-500 normal-case ml-2">
                  ({{ complaintStatusForm.notes?.length || 0 }}/250)
                </span>
              </label>
              <x-textarea
                v-model="complaintStatusForm.notes"
                :error="
                  complaintStatusForm.errors.notes || validationErrors.notes
                "
                placeholder="null"
                rows="4"
                class="w-full"
                maxlength="250"
                @input="validateFormOnChange"
              />
            </div>
          </div>

          <x-divider class="my-4" />
          <div class="flex justify-end">
            <x-button
              :disabled="isSubmitDisabled"
              :loading="complaintStatusForm.processing"
              type="submit"
              color="emerald"
              size="md"
              class="px-8"
            >
              Update
            </x-button>
          </div>
        </x-form>
      </template>
    </Collapsible>
  </div>
</template>
