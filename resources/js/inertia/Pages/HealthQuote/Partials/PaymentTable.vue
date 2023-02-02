<script setup>

import { ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { useNotifications } from '@indielayer/ui';
const notification = useNotifications('toast');
const page = usePage();

defineProps({
  payments: Array,
  isBetaUser: Boolean,
  permissions: Object,
  quoteRequest: Object,
});

const paymentTableHeaders = [
  { text: 'Payment ID', value: 'code', align: 'center' },
  { text: 'Payment Status', value: 'payment_status.code' },
  { text: 'Plan Name', value: 'health_plan.text' },
  { text: 'Captured Amount', value: 'captured_amount', sortable: true },
  { text: 'Status Change Date', value: 'payment_status_log.created_at' },
  { text: 'Captured At', value: 'captured_at' },
  { text: 'Authorized At', value: 'authorized_at' },
  { text: 'Payment method', value: 'payment_method.name' },
  { text: 'Reference', value: 'reference' },
  { text: 'Actions', value: 'actions', sortable: false },
];

const generateCCLink = async code => {
  try {
    const response = await axios.post('/generate-payment-link', {
      quoteId: page.props.quoteRequest.id,
      modelType: page.props.modelType,
      paymentCode: code,
      isInertia: true,
    });

    if (response.data.success) {
      const el = document.createElement('textarea');
      el.value = response.data.payment_link;
      document.body.appendChild(el);
      el.select();
      document.execCommand('copy');
      document.body.removeChild(el);

      notification.success({
        title: 'Payment Link Generated',
        position: 'top',
      });
    } else {
      notification.error({
        title: 'Payment Link Generation Failed',
        position: 'top',
      });
    }
  } catch (err) {
    notification.error({
      title: 'Payment Link Generation Failed',
      position: 'top',
    });
  }
};

//on editPayment
const editPayment = async payment => {
  try {
  } catch (err) {
    notification.error({
      title: 'Payment Edit Failed',
      position: 'top',
    });
  }
};
</script>


<template>
    <div class="p-4 rounded shadow mb-6 bg-white" v-if="isBetaUser">
      <div class="flex justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">Payments</h3>
        <x-button size="xs" color="orange" v-fi="permissions.can.create_payments">
          App Payment
        </x-button>
      </div>
      <DataTable
        table-class-name="tablefixed compact"
        :headers="paymentTableHeaders"
        :items="payments || []"
        border-cell
        hide-rows-per-page
        hide-footer
        :data-table-props="{
          permissions: page.props.permissions,
        }"
      >
        <template #item-code="{ code }">
          {{ code.toUpperCase() }}
        </template>
        <template #item-actions="item">
          <div class="flex gap-2">
            <div v-if="!permissions.can.approve_payments">
              <x-button
                size="xs"
                color="orange"
                v-if="item.copy_link_button"
                @click="generateCCLink(item.code)"
              >
                Copy Link
              </x-button>
              <x-button
                size="xs"
                color="emerald"
                v-if="permissions.can.edit_payments && item.edit_button"
                @click="editPayment(item)"
              >
                Edit
              </x-button>
            </div>
            <div v-if="permissions.can.approve_payments">
              <x-button size="xs" color="error" v-if="item.approve_button">
                Approve
              </x-button>
              <x-button
                size="xs"
                disabled
                color="error"
                v-if="item.approved_button"
              >
                Approved
              </x-button>
            </div>
          </div>
        </template>
      </DataTable>
    </div>

</template>
