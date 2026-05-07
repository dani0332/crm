<script setup>
const COURTESY_EMAIL_FLOW_TYPE_VALUE = 50;

const props = defineProps({
  emailStatuses: Array,
  expanded: {
    type: Boolean,
    default: true,
  },
  showIndex: {
    type: Boolean,
    default: false,
  },
  paginate: {
    type: Boolean,
    default: false,
  },
  rowsPerPage: {
    type: Number,
    default: 15,
  },
});

const page = usePage();

const isWhatsAppType = t => String(t ?? '').toLowerCase() === 'whatsapp';

const tableItems = computed(() =>
  (props.emailStatuses || []).filter(
    row => row?.flow_type !== COURTESY_EMAIL_FLOW_TYPE_VALUE,
  ),
);

const emailStatusTable = reactive({
  columns: [
    { text: 'Channel', value: 'type' },
    { text: 'Subject', value: 'email_subject' },
    { text: 'Recipient', value: 'email_address' },
    { text: 'Status', value: 'email_status' },
    { text: 'Reason', value: 'reason' },
    { text: 'Template Id', value: 'template_id' },
    { text: 'Customer Id', value: 'customer_id' },
    { text: 'Client Replied', value: 'customer_replied' },
    { text: 'Created At', value: 'created_at' },
    { text: 'Updated At', value: 'updated_at' },
  ],
});

const emailStatusesTableColumns = computed(() =>
  emailStatusTable.columns.filter(column => {
    if (!page.props.isAdmin) {
      return column.value !== 'customer_id' && column.value !== 'template_id';
    }
    return true;
  }),
);

const hideFooter = computed(() => {
  if (!props.paginate) {
    return true;
  }
  return tableItems.value.length < props.rowsPerPage;
});
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="expanded">
      <template #header>
        <div class="flex justify-between items-center w-full gap-4 flex-wrap">
          <h3 class="font-semibold text-primary-800 text-lg">
            Follow-up status
            <span class="text-sm font-normal text-gray-500 ml-1"
              >(Email & WhatsApp)</span
            >
          </h3>
          <div class="flex items-center gap-2 flex-shrink-0">
            <slot name="actions" />
          </div>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <DataTable
          table-class-name="tablefixed compact"
          :headers="emailStatusesTableColumns"
          :items="tableItems"
          border-cell
          hide-rows-per-page
          :hide-footer="hideFooter"
          :rows-per-page="rowsPerPage"
          :show-index="showIndex"
        >
          <template #item-type="{ type }">
            <span
              class="text-xs font-semibold px-2 py-0.5 rounded capitalize"
              :class="
                isWhatsAppType(type)
                  ? 'bg-green-100 text-green-800'
                  : 'bg-slate-100 text-slate-700'
              "
            >
              {{ type }}
            </span>
          </template>
          <template #item-email_status="item">
            <span class="text-sm text-primary-600 uppercase">{{
              item.email_status
            }}</span>
          </template>
          <template #item-reason="item">
            <span class="text-sm text-primary-600 uppercase">{{
              item.reason || '—'
            }}</span>
          </template>
          <template #item-customer_replied="item">
            <span class="text-sm">{{
              item.customer_replied ? 'Yes' : 'No'
            }}</span>
          </template>
          <template #item-email_address="item">
            <span class="text-sm">{{
              isWhatsAppType(item.type)
                ? '+' + item.mobile_no
                : item.email_address || '—'
            }}</span>
          </template>
        </DataTable>
      </template>
    </Collapsible>
  </div>
</template>
