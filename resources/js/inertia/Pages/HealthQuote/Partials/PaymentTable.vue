<script setup>
import { onMounted, computed, ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { useNotifications } from '@indielayer/ui';
import axios from 'axios';

const notification = useNotifications('toast');
const page = usePage();

defineProps({
  payments: Array,
  isBetaUser: Boolean,
  can: Object,
  quoteRequest: Object,
  paymentMethods: Object,
  sendPolicy: Boolean,
  quote: Object,
});

const createPaymentModal = ref(false);
const paymentMethod = ref('');
const collectionType = ref('');

const rules = {
  isRequired: v => !!v || 'This field is required',
  reference: v => {
    if (paymentMethodsForm.payment_method !== 'CC') {
      return !!v || 'This field is required';
    }
    return true;
  },
  amount: v => {
    const regex = /^\d+(\.\d{1,2})?$/;
    if (regex.test(v)) {
      return true;
    }
    return 'Amount must be a valid number';
  },
};

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

const collectionTypes = [
  { value: '', label: 'Select Collection Type' },
  { value: 'broker', label: 'Broker' },
  { value: 'insurer', label: 'Insurer' },
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

const addPaymentModal = () => {
  paymentMethodsForm.reset();
  paymentMethodsForm.payment_method = '';
  paymentMethodsForm.collection_type = '';
  paymentMethodsForm.amount = '';
  paymentMethodsForm.payment_reference = '';
  paymentMethodsForm.paymentCode = '';

  paymentMethodsForm.status = 'create';
  createPaymentModal.value = true;
};

const editPaymentModal = payment => {
  paymentMethodsForm.reset();
  paymentMethodsForm.status = 'edit';
  paymentMethodsForm.payment_method = payment.payment_method.code;
  paymentMethodsForm.collection_type = payment.collection_type;
  paymentMethodsForm.amount = payment.captured_amount;
  paymentMethodsForm.payment_reference = payment.reference;
  paymentMethodsForm.paymentCode = payment.code;
  createPaymentModal.value = true;
};

const paymentMethodsForm = useForm({
  payment_method: '',
  collection_type: '',
  amount: '',
  payment_reference: '',
  paymentCode: '',
  status: 'create',
});

const addPayment = isValid => {
  if (!isValid) return;

  let data = {
    captured_amount: paymentMethodsForm.amount,
    code: paymentMethodsForm.payment_method,
    modelType: page.props.modelType,
    quote_id: page.props.quoteRequest.id,
    plan_id: page.props.quoteRequest.plan.id,
    insurance_provider_id: providerId.value,
    collection_type: paymentMethodsForm.collection_type,
    payment_methods: paymentMethodsForm.payment_method,
    reference: paymentMethodsForm.payment_reference,
    isInertia: true,
  };

  if (paymentMethodsForm.status === 'edit') {
    let editData = {
      ...data,
      paymentCode: paymentMethodsForm.paymentCode,
    };
    paymentMethodsForm
      .transform(data => editData)
      .post('/payments/Health/update', {
        preserveScroll: true,
        onSuccess: () => {
          notification.success({
            title: 'Payment Updated',
            position: 'top',
          });
          createPaymentModal.value = false;
        },
        onError: () => {
          notification.error({
            title: 'Payment Update Failed',
            position: 'top',
          });
        },
      });
    return;
  }
  let storeData = {
    ...data,
  };
  paymentMethodsForm
    .transform(data => storeData)
    .post('/payments/Health/store', {
      preserveScroll: true,
      onSuccess: () => {
        notification.success({
          title: 'Payment Added',
          position: 'top',
        });
        createPaymentModal.value = false;
      },
      onError: () => {
        notification.error({
          title: 'Payment Add Failed',
          position: 'top',
        });
      },
    });
};

const approvePayment = payment => {
  let data = {
    code: payment.code,
    modelType: page.props.modelType,
    quote_id: page.props.quoteRequest.id,
  };
  if (confirm('Are you sure you want to approve this payment?')) {
    axios.post('/update-payment-status', data).then(response => {
      if (response.data.success) {
        notification.success({
          title: 'Payment Approved',
          position: 'top',
        });
      } else {
        notification.error({
          title: 'Payment Approval Failed',
          position: 'top',
        });
      }
    });
  }
};

const sendPolicyToClient = () => {
  if (confirm('Are you sure you want to send documents to customer?')) {
    let quoteType = page.props.modelType;
    let quoteUuId = page.props.quote.uuid;
    let url =
      '/quotes/' + quoteType + '/' + quoteUuId + '/send-policy-documents';
    axios.post(url).then(response => {
      if (response.data.success) {
        notification.success({
          title: 'Documents Sent',
          position: 'top',
        });
      } else {
        notification.error({
          title: 'Documents Sending Failed',
          position: 'top',
        });
      }
    });
  }
};

const getPlanName = computed(() => {
  const plan = page.props.quoteRequest.plan;
  return plan ? plan.text : 'Not Available';
});

const providerName = computed(() => {
  const plan = page.props.quoteRequest.plan;
  if (plan && plan.insurance_provider) {
    return plan.insurance_provider.text;
  }
  return 'Not Available';
});

const providerId = computed(() => {
  const plan = page.props.quoteRequest.plan;
  if (plan && plan.insurance_provider) {
    return plan.insurance_provider.id;
  }
  return null;
});

onMounted(() => {

});
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white" v-if="isBetaUser">
    <div class="flex justify-between items-center mb-4">
      <h3 class="font-semibold text-primary-800 text-lg">Payments</h3>
      <div class="flex gap-2">
        <x-button
          size="xs"
          color="orange"
          v-if="can.create_payments && !can.approve_payments"
          @click="addPaymentModal"
        >
          App Payment
        </x-button>
        <x-button
          size="xs"
          color="red"
          v-if="sendPolicy"
          @click="sendPolicyToClient"
        >
          Send Policy
        </x-button>
      </div>
    </div>
    <DataTable
      table-class-name="tablefixed compact"
      :headers="paymentTableHeaders"
      :items="payments || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #item-code="{ code }">
        {{ code.toUpperCase() }}
      </template>
      <template #item-actions="item">
        <div class="flex gap-2">
          <div v-if="!can.approve_payments">
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
              v-if="can.edit_payments && item.edit_button"
              @click="editPaymentModal(item)"
            >
              Edit
            </x-button>
          </div>
          <div v-if="can.approve_payments">
            <x-button
              size="xs"
              color="error"
              v-if="item.approve_button"
              @click="approvePayment(item)"
            >
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
    <x-modal v-model="createPaymentModal" size="xl" show-close backdrop>
      <template #header>
        <i class="fa fa-cog text-primary-800 mr-2"></i>
        <span class="text-primary-800 font-semibold">
          {{
            paymentMethodsForm.status == 'create'
              ? 'New Payment'
              : 'Update Payment'
          }}
        </span>
      </template>
      <x-form @submit="addPayment" :auto-focus="false">
        <div class="w-full">
          <x-input
            class="w-full"
            :rules="[rules.isRequired, rules.amount]"
            label="Capture Amount*"
            v-model="paymentMethodsForm.amount"
          />
        </div>
        <div class="w-full">
          <x-select
            class="w-full"
            v-model="paymentMethodsForm.collection_type"
            :options="collectionTypes"
            label="Collection Type*"
            :rules="[rules.isRequired]"
          >
          </x-select>
        </div>
        <div class="w-full">
          <x-select
            class="w-full"
            v-model="paymentMethodsForm.payment_method"
            :options="paymentMethods"
            label="Payment Method*"
            :rules="[rules.isRequired]"
          >
          </x-select>
        </div>

        <div class="flex gap-6 w-full">
          <div class="w-full md:w-1/2">
            <p class="text-sm text-gray-500 mt-2">
              Provider Name:
              <span class="text-primary-800">{{ providerName }}</span>
            </p>
          </div>
          <div class="w-full md:w-1/2">
            <p class="text-sm text-gray-500 mt-2">
              Plan Name :
              <span class="text-primary-800">{{ getPlanName }}</span>
            </p>
          </div>
        </div>
        <div class="mt-3 w-full">
          <x-input
            class="w-full"
            label="Payment Reference*"
            :rules="[rules.isRequired, rules.reference]"
            v-show="paymentMethodsForm.payment_method != 'CC'"
            v-model="paymentMethodsForm.payment_reference"
          />
        </div>
        <div class="text-center" v-if="paymentMethodsForm.status == 'create'">
          <x-button color="primary" type="submit"> Create Payment </x-button>
        </div>
        <div class="text-center" v-if="paymentMethodsForm.status == 'edit'">
          <x-button color="primary" type="submit"> Update Payment </x-button>
        </div>
      </x-form>
    </x-modal>
  </div>
</template>
