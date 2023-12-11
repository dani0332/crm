<script setup>
const props = defineProps({
  quoteId: String,
  sendUpdateLog: Object,
  sendUpdateOptions: Array,
});

const notification = useToast();

const state = reactive({
  edit: false,
});

const sendUpdateForm = useForm({
  notes: props.sendUpdateLog?.notes || '',
  option_id: props.sendUpdateLog?.option_id || null,
});

const currentOption = computed(() => {
  let cat = null;
  props.sendUpdateOptions.forEach(option => {
    if (option.childs.find(op => op.id === props.sendUpdateLog.category_id)) {
      cat = option;
    }
  });
  return cat;
});

const selectedType = computed(() => {
  return currentOption?.value?.childs.find(
    child => child.id === props.sendUpdateLog.category_id,
  );
});

const updateLogOptions = computed(() => {
  return selectedType.value?.childs.map(child => ({
    value: child.id,
    label: child.title,
  }));
});

const redirectBack = () => {
  history.back();
};

const onUpdateLog = () => {
  sendUpdateForm.patch(
    route('send-update-logs.update', { id: props.sendUpdateLog.id }),
    {
      preserverScroll: true,
      onSuccess: ({ props }) => {
        notification.success({
          title: 'Log updated successfully',
          position: 'top',
        });
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
  <div>
    <div class="p-4 rounded shadow mb-6 bg-white">
      <Collapsible expanded>
        <template #header>
          <div class="flex gap-2 w-100 flex-grow justify-between">
            <h3 class="text-lg font-semibold text-primary-800 capitalize">
              {{ selectedType.title }}
            </h3>
            <x-button
              color="primary"
              size="sm"
              @click="redirectBack"
              class="mr-5"
              >Go back to lead page</x-button
            >
          </div>
        </template>
        <template #body>
          <x-divider class="my-4" />
          <div class="text-sm">
            <dl class="grid md:grid-cols-2 gap-y-4">
              <div class="grid sm:grid-cols-2">
                <dt class="font-bold text-right mr-10">SU REF ID</dt>
                <dd>{{ sendUpdateLog.code }}</dd>
              </div>
              <div class="grid sm:grid-cols-2 ml-[-250px]">
                <dt class="font-bold text-right mr-10">Notes</dt>
                <dd>
                  <x-input
                    v-model="sendUpdateForm.notes"
                    size="xs"
                    :disabled="!state.edit"
                  />
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-bold text-right mr-10">Transaction Type</dt>
                <dd>{{ currentOption.title }}</dd>
              </div>
              <div class="grid sm:grid-cols-2 ml-[-250px]">
                <dt class="font-bold text-right mr-10">STATUS</dt>
                <dd>{{ sendUpdateLog.status }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-bold text-right mr-10">Sub Type</dt>
                <dd>
                  <x-select
                    size="xs"
                    :disabled="!state.edit"
                    v-model="sendUpdateForm.option_id"
                    :options="updateLogOptions"
                  />
                </dd>
              </div>
            </dl>
          </div>
          <div class="flex justify-end">
            <x-button size="sm" @click="state.edit = true" v-if="!state.edit"
              >Edit</x-button
            >
            <template v-else>
              <x-button
                size="sm"
                color="orange"
                @click="state.edit = false"
                class="mr-3"
                :loading="sendUpdateForm.processing"
                :disabled="sendUpdateForm.processing"
                >Cancel</x-button
              >
              <x-button
                size="sm"
                color="primary"
                @click="onUpdateLog"
                :loading="sendUpdateForm.processing"
                :disabled="sendUpdateForm.processing"
                >Update</x-button
              >
            </template>
          </div>
        </template>
      </Collapsible>
    </div>
  </div>
</template>
