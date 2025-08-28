<script setup>
import { router } from '@inertiajs/vue3';

const props = defineProps({
  claim: Object,
  expanded: {
    type: Boolean,
    required: false,
    default: false,
  },
});

const page = usePage();
const can = permission => useCan(permission);
const canAny = permissions => useCanAny(permissions);
const permissionsEnum = page.props.permissionsEnum;
const notification = useToast();

const nextFollowUpForm = useForm({
  notes: props.claim?.next_followup_notes || '',
  next_follow_up_date: props.claim?.next_followup_datetime || '',
});

// Client-side validation rules
const validationErrors = ref({});
const hasInteracted = ref(false);

// Computed properties for date constraints
const minDate = computed(() => new Date());
const maxDate = computed(() => new Date(Date.now() + 15 * 24 * 60 * 60 * 1000)); // 15 days from now

const validateForm = (showRequiredErrors = true) => {
  const errors = {};

  // Validate next_follow_up_date - required (only show required error if user has interacted or explicitly requested)
  if (!nextFollowUpForm.next_follow_up_date) {
    if (showRequiredErrors && hasInteracted.value) {
      errors.next_follow_up_date = 'The next follow-up date field is required.';
    }
  } else {
    // DatePicker returns Date object, so handle accordingly
    const selectedDate =
      nextFollowUpForm.next_follow_up_date instanceof Date
        ? nextFollowUpForm.next_follow_up_date
        : new Date(nextFollowUpForm.next_follow_up_date);
    const now = new Date();
    const maxDateValue = new Date(now.getTime() + 15 * 24 * 60 * 60 * 1000); // 15 days from now

    // Validate date format
    if (isNaN(selectedDate.getTime())) {
      errors.next_follow_up_date =
        'The next follow-up date must be a valid date.';
    }
    // Validate future date (after now)
    else if (selectedDate <= now) {
      errors.next_follow_up_date =
        'The next follow-up date must be in the future.';
    }
    // Validate maximum 15 days in future
    else if (selectedDate > maxDateValue) {
      errors.next_follow_up_date =
        'The next follow-up date cannot be more than 15 days in the future.';
    }
  }

  // Validate notes - max 250 characters
  if (nextFollowUpForm.notes && nextFollowUpForm.notes.length > 250) {
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
  [() => nextFollowUpForm.next_follow_up_date, () => nextFollowUpForm.notes],
  () => {
    // Don't show required errors on initial load, only validate format/range errors
    isFormValid.value = validateForm(false);
  },
  { immediate: true },
);

// Follow-up reminder logic
const checkFollowUpReminder = () => {
  const followUpDateTime = props.claim?.next_followup_datetime;
  console.log('follow-up date', followUpDateTime);
  if (!followUpDateTime) {
    return; // No follow-up date set
  }

  const followUpDate = new Date(followUpDateTime);
  const now = new Date();
  // Validate date
  if (isNaN(followUpDate.getTime())) {
    console.log('Invalid follow-up date:', followUpDateTime);
    return;
  }

  // Check if it's the follow-up date (same day)
  const isFollowUpDate = followUpDate.toDateString() === now.toDateString();

  // Check if follow-up time has passed
  const isOverdue = followUpDate < now;
  console.log(
    'follow-up date',
    followUpDateTime,
    'isOverdue',
    isOverdue,
    isFollowUpDate && !isOverdue,
  );
  if (isFollowUpDate && !isOverdue) {
    console.log('Follow-up due today', followUpDateTime);
    // Show reminder toast for follow-up due today
    const timeString = followUpDate.toLocaleTimeString('en-US', {
      hour: '2-digit',
      minute: '2-digit',
      hour12: false, // Use 24-hour format
    });
    notification.success({
      title: `📅 Follow-up Reminder`,
      message: `Follow-up scheduled for today at ${timeString} for claim ${props.claim?.code || 'N/A'}`,
      position: 'top',
      timeout: 8000, // Show for 8 seconds
    });
  } else if (isOverdue) {
    console.log('Overdue follow-up', followUpDateTime);
  }
};

// Check for follow-up reminder when component mounts
onMounted(() => {
  checkFollowUpReminder();
});

// Watch for changes to claim follow-up datetime and re-check reminders
watch(
  () => props.claim?.next_followup_datetime,
  newDateTime => {
    if (newDateTime) {
      // Add a small delay to ensure the UI has updated
      setTimeout(() => {
        checkFollowUpReminder();
      }, 500);
    }
  },
);

// Computed property to disable submit button
const isSubmitDisabled = computed(() => {
  return nextFollowUpForm.processing || !isFormValid.value;
});

const updateNextFollowUp = isValid => {
  console.log('updateNextFollowUp');

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

  nextFollowUpForm.post(
    route('claims.update.next-follow-up', props.claim?.uuid),
    {
      preserveScroll: true,
      onSuccess: response => {
        console.log('response', response);
        router.visit(route('claims.show', props.claim?.uuid), {
          preserveScroll: true,
        });
        emit('update', response);
        // Reset notes after successful update
        nextFollowUpForm.notes = '';
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
          <h3 class="font-semibold text-primary-800 text-lg">Next Follow-Up</h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <x-form :form="nextFollowUpForm" @submit="updateNextFollowUp">
          <div class="space-y-4">
            <!-- Next Follow-up Date & Time -->
            <div class="w-1/2">
              <DatePicker
                v-model="nextFollowUpForm.next_follow_up_date"
                label="Next Follow-up Date & Time"
                withTime
                :min-date="minDate"
                :max-date="maxDate"
                :error="
                  nextFollowUpForm.errors.next_follow_up_date ||
                  validationErrors.next_follow_up_date
                "
                placeholder="Please select follow-up date & time"
                class="w-full"
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
                  ({{ nextFollowUpForm.notes?.length || 0 }}/250)
                </span>
              </label>
              <x-textarea
                v-model="nextFollowUpForm.notes"
                :error="nextFollowUpForm.errors.notes || validationErrors.notes"
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
              :loading="nextFollowUpForm.processing"
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
