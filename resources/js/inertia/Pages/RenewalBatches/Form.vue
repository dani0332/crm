<script setup>
const notification = useToast();
const { isRequired } = useRules();

const props = defineProps({
  renewalBatch: { type: Object, default: null },
  leadStatuses: [Array, Object],
});

const pageFor = props.renewalBatch ? 'Update' : 'Create';

const batchForm = useForm({
  name: props.renewalBatch?.name || '',
  deadline_date: props.renewalBatch?.deadline_date || '',
  quote_status_id: props.renewalBatch?.quote_status_id || null,
});

const leadStatusOptions = computed(() => {
  return props.leadStatuses.map(status => ({
    value: status.id,
    label: status.text,
  }));
});

function onSubmit(isValid) {
  if (isValid) {
    let method = 'post';
    let url = `/renewal-batches/`;
    let title = 'Renewal Batch saved successfully';
    let redirectUrl = '/renewal-batches';
    if (props.renewalBatch) {
      method = 'put';
      url = url + props.renewalBatch.id;
      title = 'Renewal Batch updated successfully';
    }

    batchForm.submit(method, url, {
      onError: errors => {
        batchForm.setError(errors);
      },
      onSuccess: () => {
        notification.success({
          title: title,
          position: 'top',
        });

        setTimeout(function () {
          router.get(redirectUrl);
        }, 500);
      },
    });
  }
}
</script>

<template>
  <div>
    <Head :title="`${pageFor} Renewal Batch`" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">{{ pageFor }} Renewal Batch</h2>
      <div>
        <Link href="/renewal-batches">
          <x-button size="sm" color="#ff5e00" tag="div">
            Renewal Batch List
          </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 gap-4">
        <x-field label="BATCH" required>
          <x-input
            v-model="batchForm.name"
            :rules="[isRequired]"
            class="w-full"
            :error="batchForm.errors.name"
          />
        </x-field>
        <x-field label="LEAD STATUS" required>
          <x-select
            v-model="batchForm.quote_status_id"
            :rules="[isRequired]"
            :options="leadStatusOptions"
            class="w-full"
            :error="batchForm.errors.quote_status_id"
          />
        </x-field>
        <x-field label="DEADLINE DATE" required>
          <DatePicker
            v-model="batchForm.deadline_date"
            :rules="[isRequired]"
            class="w-full"
            :error="batchForm.errors.deadline_date"
          />
        </x-field>
      </div>
      <x-divider class="my-4" />
      <div class="flex justify-end gap-3 mb-4">
        <x-button
          size="md"
          color="emerald"
          type="submit"
          :loading="batchForm.processing"
        >
          Save
        </x-button>
      </div>
    </x-form>
  </div>
</template>
