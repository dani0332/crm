<script setup>
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
  quote: Object,
  documentTypes: Object,
  quoteStatuses: Object,
  lostReasons: Object,
  storageUrl: String,
  quoteType: String,
  expanded: {
    type: Boolean,
    required: false,
    default: true,
  },
});

const page = usePage();
const notification = useNotifications('toast');
const quoteStatusEnum = page.props.quoteStatusEnum;
const quoteStatusOptions = computed(() => {
  return props.quoteStatuses.map(status => ({
    value: status.id,
    label: status.text,
  }));
});

const quoteStatusForm = useForm({
  quote_uuid: props.quote.uuid,
  quote_status_id: props.quote.quote_status_id,
  notes: props.quote.notes || null,
  transapp_code: props.quote?.quote_detail?.transapp_code || null,
  lost_reason_id: props.quote?.quote_detail?.lost_reason_id || null,
});

const onLeadStatus = () => {
  quoteStatusForm.patch(
    `/personal-quotes/${props.quoteType}/${props.quote.id}/update-status`,
    {
      preserveScroll: true,

      onError: errors => {
        notification.error({ title: errors.value, position: 'top' });
      },
      onSuccess: () => {
        router.reload({ only: ['quote'] });
        notification.success({
          title: 'Quote status is updated',
          position: 'top',
        });
      },
    },
  );
};

const rules = {
  isRequired: v => !!v || 'This field is required',
};

const allowStatusUpdate = computed(() => {
  return (
    (props.quote.quote_status_id == quoteStatusEnum.TransactionApproved ||
      props.quote.quote_status_id == quoteStatusEnum.Lost) ??
    false
  );
});
watch(
  () => props.quote.quote_status_id,
  (newValue, oldValue) => {
    if (newValue !== oldValue) {
      quoteStatusForm.quote_status_id = newValue;
    }
  },
);
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-primary-50/25">
    <Collapsible :expanded="expanded">
      <template #header>
        <div>
          <h3 class="font-semibold text-primary-800 text-lg">Lead Status</h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <div class="flex flex-wrap md:flex-nowrap gap-6 w-full">
          <div class="w-full md:w-1/2">
            <div class="flex flex-col gap-4">
              <x-select
                v-model="quoteStatusForm.quote_status_id"
                label="Status"
                :error="quoteStatusForm.errors.quote_status_id"
                :options="quoteStatusOptions"
                :disabled="allowStatusUpdate"
                :rules="[rules.isRequired]"
                placeholder="Lead Status"
                class="w-full uppercase"
              />
              <x-textarea
                v-model="quoteStatusForm.notes"
                type="text"
                label="Notes"
                placeholder="Lead Notes"
                class="w-full uppercase"
                :error="quoteStatusForm.errors.notes"
                :disabled="allowStatusUpdate"
              />
            </div>
          </div>
          <div class="w-full md:w-2/3">
            <x-field
              label="TransApp Code"
              class="uppercase"
              required
              v-if="
                quoteStatusForm.quote_status_id ==
                page.props.quoteStatusEnum.TransactionApproved
              "
            >
              <x-input
                v-model="quoteStatusForm.transapp_code"
                placeholder="TransApp Code is required"
                class="w-full"
                :disabled="allowStatusUpdate"
                :error="quoteStatusForm.errors.transapp_code"
              />
            </x-field>
            <x-field
              label="Lost Reason"
              class="uppercase"
              required
              v-if="
                quoteStatusForm.quote_status_id ==
                page.props.quoteStatusEnum.Lost
              "
            >
              <x-select
                v-model="quoteStatusForm.lost_reason_id"
                :options="
                  lostReasons?.map(item => ({
                    value: item.id,
                    label: item.text,
                  }))
                "
                placeholder="Lost Reason is required"
                class="w-full"
                :error="quoteStatusForm.errors.lost_reason_id"
              />
            </x-field>
            <x-field class="uppercase" label="Transaction Type">
              <x-input
                type="text"
                :value="quote.transaction_type_text"
                class="w-full"
                :disabled="true"
              />
            </x-field>
          </div>
        </div>
        <div class="flex justify-end">
          <x-button
            class="mt-4"
            color="emerald"
            size="sm"
            :loading="quoteStatusForm.processing"
            @click.prevent="onLeadStatus"
            :disabled="allowStatusUpdate"
          >
            Change Status
          </x-button>
        </div>
      </template>
    </Collapsible>
  </div>
</template>
