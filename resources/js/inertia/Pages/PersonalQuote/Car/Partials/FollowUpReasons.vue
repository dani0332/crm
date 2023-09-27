<script setup>
const props = defineProps({
  modelValue: Boolean,
  uuid: String,
  source: String,
  followUpId: String,
});

const emit = defineEmits(['update:modelValue']);
const { isRequired, allowEmpty } = useRules();

const notification = useToast();
const reasons = ref([]);
const selectedReason = ref('');
const notes = ref();
const date = ref('');
let isloading = ref(false);

const maxDate = computed(() => {
  let days = new Date().getDate() + props.source == 'Renewal_upload' ? 14 : 9;
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
  axios
    .get(`${process.env.MIX_KYO_END_POINT}/lookups/pause-followup-reasons`)
    .then(response => {
      reasons.value = response.data.data;
    })
    .catch(error => {
      console.log(error);
    });
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
      .post(
        `${process.env.MIX_KYO_END_POINT}/followups/${props.followUpId}/pause`,
        {
          reason_id: getReasonId.value[0].id,
          action_by_id: props.uuid,
          resume_date: date.value.split('T')[0],
          notes: notes.value,
        },
      )
      .then(response => {
        if (response.data.success);
        notification.success({
          title: response.data.message,
          position: 'top',
        });
      })
      .catch(error => {
        notification.error({
          title: 'Error! Unable to pause followups',
          position: 'top',
        });
      })
      .finally(() => {
        isloading.value = false;
        emit('update:modelValue', response.data.success);
      });
  }
}

onMounted(() => getPauseReaons());
</script>
<template>
  <x-modal v-model="props.modelValue" backdrop size="lg">
    <x-form class="p-5" @submit="onSubmit" :auto-focus="false">
      <!-- <div > -->
      <p>Select a reason:</p>
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
        class="mt-3"
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
      <div class="flex justify-end gap-3 mt-5">
        <x-button type="submit" size="sm" color="primary"> Send </x-button>
        <x-button
          size="sm"
          color="rose"
          @click.prevent="emit('update:modelValue')"
        >
          Cancel
        </x-button>
      </div>
      <!-- </div> -->
    </x-form>
  </x-modal>
</template>
