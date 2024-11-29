<script setup>
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
const canAny = permissions => useCanAny(permissions);
const notification = useNotifications('toast');
const permissionsEnum = page.props.permissionsEnum;
const can = permission => useCan(permission);
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

watch(
  () => props.quote.quote_status_id,
  (newValue, oldValue) => {
    if (newValue !== oldValue) {
      quoteStatusForm.quote_status_id = newValue;
    }
  },
);
const readOnlyMode = reactive({
  isDisable: true,
});
onMounted(() => {
  readOnlyMode.isDisable = !can(permissionsEnum.All_QUOTES_VIEWONLY_ACCESS);
});

const [StatusUpdateButtonTemplate, StatusUpdateButtonReuseTemplate] =
  createReusableTemplate();

const allowStatusUpdate = computed(() => {
  if (canAny([permissionsEnum.SUPER_LEAD_STATUS_CHANGE])) {
    return page.props.quote.quote_status_id == quoteStatusEnum.PolicyBooked;
  }
  return (
    page.props.quote.quote_status_id == quoteStatusEnum.TransactionApproved
  );
});
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
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
                :disabled="
                  allowStatusUpdate ||
                  page.props.lockLeadSectionsDetails.lead_status
                "
                :rules="[rules.isRequired]"
                placeholder="Lead Status"
                class="w-full uppercase"
                filterable
              />
              <x-textarea
                v-model="quoteStatusForm.notes"
                type="text"
                label="Notes"
                placeholder="Lead Notes"
                class="w-full uppercase"
                :error="quoteStatusForm.errors.notes"
                :disabled="
                  allowStatusUpdate ||
                  page.props.lockLeadSectionsDetails.lead_status
                "
              />
            </div>
          </div>
          <div class="w-full md:w-2/3">
            <div class="flex flex-col gap-4">
              <x-field
                label="Lost Reason"
                class="uppercase"
                required
                v-if="
                  quoteStatusForm.quote_status_id ==
                  page.props.quoteStatusEnum?.Lost
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
                  v-model="quote.transaction_type_text"
                  class="w-full"
                  :disabled="true"
                />
              </x-field>
            </div>
          </div>
        </div>
        <StatusUpdateButtonTemplate v-slot="{ isDisabled }">
          <x-button
            class="mt-4"
            color="emerald"
            size="sm"
            :loading="quoteStatusForm.processing"
            @click.prevent="onLeadStatus"
            :disabled="allowStatusUpdate || isDisabled"
            v-if="readOnlyMode.isDisable === true"
          >
            Change Status
          </x-button>
        </StatusUpdateButtonTemplate>
        <div class="flex justify-end">
          <x-tooltip
            v-if="page.props.lockLeadSectionsDetails.lead_status"
            placement="bottom"
          >
            <StatusUpdateButtonReuseTemplate :isDisabled="true" />
            <template #tooltip>
              The lead status cannot be manually updated once it has reached
              'Transaction Approved'
            </template>
          </x-tooltip>
          <StatusUpdateButtonReuseTemplate v-else />
        </div>
      </template>
    </Collapsible>
  </div>
</template>
