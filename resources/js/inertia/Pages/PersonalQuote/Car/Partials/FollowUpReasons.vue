<script setup>
const props = defineProps({
  modelValue: Boolean,
  uuid: String,
  source: String,
  followUpId: String,
  kyoEndPoint: String,
  quoteUuid: String,
});

const emit = defineEmits(['update:modelValue']);

const value = computed({
  get() {
    return props.modelValue;
  },
  set(value) {
    emit('update:modelValue', value);
  },
});

const { isRequired, allowEmpty } = useRules();

const notification = useToast();
const reasons = ref([]);
const selectedReason = ref('');
const notes = ref();
const date = ref('');
let isloading = ref(false);

const maxDate = computed(() => {
  let days = props.source == 'Renewal_upload' ? 14 : 9;
  return new Date(new Date().setDate(new Date().getDate() + days));
});

const showInput = computed(() =>
  selectedReason.value == 'followupLater' ? true : false,
);

const getReasonId = computed(() =>
  reasons.value.filter(x => (x.code == selectedReason.value ? x.id : 0)),
);

watch(
  () => selectedReason.value,
  () => {
    if (selectedReason.value == 'lostCase') date.value = '';
  },
);

const getPauseReaons = () => {
  try {
    axios
      .get(`${props.kyoEndPoint}/lookups/pause-followup-reasons`)
      .then(response => {
        reasons.value = response.data.data;
      })
      .catch(error => {
        console.log(error);
      });
  } catch (error) {
    console.log(error);
  }
};

const isValid = () => {
  let isValid = true;
  if (selectedReason.value == '') {
    notification.error({
      title: 'Please select a reason.',
      position: 'top',
    });
    isValid = false;
  } else if (selectedReason.value != 'lostCase' && date.value == '') {
    notification.error({
      title: 'Please select a date.',
      position: 'top',
    });
    isValid = false;
  }
  return isValid;
};

function onSubmit() {
  let valid = isValid();
  if (valid) {
    isloading.value = true;
    axios
      .post(`${props.kyoEndPoint}/followups/${props.followUpId}/pause`, {
        reason_id: getReasonId.value[0].id,
        action_by_email: usePage().props.auth.user.email,
        resume_date: date.value.split('T')[0],
        notes: notes.value,
      })
      .then(response => {
        if (response.data.success) {
          notification.success({
            title: response.data.message,
            position: 'top',
          });
          updatePauseCounter();
          emit('update:modelValue', response.data.success);
        }
      })
      .catch(error => {
        notification.error({
          title: 'Error! Unable to pause followups',
          position: 'top',
        });
      })
      .finally(response => {
        isloading.value = false;
      });
  }
}

const updatePauseCounter = () => {
  axios
    .post('/api/v1/quotes/car/pause-resume-followup', {
      quote_uuid: props.quoteUuid,
      action: 'pause',
    })
    .then(response => {
      if (response.data.success) {
        notification.success({
          title: 'success',
          position: 'top',
        });
      }
    })
    .catch(error => {
      notification.error({
        title: 'Error! Unable to update pause counter',
        position: 'top',
      });
    });
};
onMounted(() => getPauseReaons());
</script>
<template>
  <x-modal
    v-model="value"
    backdrop
    size="lg"
    :show-close="true"
    is-form
    @submit="onSubmit"
  >
    <!-- <div > -->
    <p class="font-bold">Select a reason:</p>
    <div class="py-1 px-5" v-for="reason in reasons" :key="reason.code">
      <x-radio
        v-model="selectedReason"
        :value="reason.code"
        :label="reason.text"
      />
      <x-field
        class="pt-2 ml-7"
        label="Reason for client request:"
        v-if="showInput && reason.code == 'followupLater'"
      >
        <x-input
          v-model="notes"
          type="text"
          class="w-full"
          placeholder="reason"
        />
      </x-field>
    </div>
    <x-field
      class="mt-5"
      label="Choose the date to resume Automated Follow-ups"
    >
      <DatePicker
        :disabled="selectedReason == 'lostCase'"
        v-model="date"
        :min-date="new Date()"
        :max-date="maxDate"
        class="w-full"
      />
    </x-field>
    <template #primary-action>
      <x-button type="submit" size="sm" color="primary" :loading="isloading">
        Ok
      </x-button>
    </template>
    <template #secondary-action>
      <x-button
        size="sm"
        color="rose"
        @click.prevent="emit('update:modelValue')"
      >
        Cancel
      </x-button>
    </template>
    <!-- </div> -->
  </x-modal>
</template>
