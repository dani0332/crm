<script setup>
const notification = useNotifications('toast');
const page = usePage();

defineProps({
  payments: Array,
  isBetaUser: Boolean,
  can: Object,
  quoteRequest: Object,
  paymentMethods: Object,
  quote: Object,
});

const createPaymentModal = ref(false);

const hasPermission = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

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
      .post('/payments/Car/update', {
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
    .post('/payments/Car/store', {
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
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white" v-if="isBetaUser">
    <div class="flex justify-between gap-4 items-center mb-4">
      <h3 class="font-semibold text-primary-800 text-lg">Payments</h3>
      <x-button
        v-if="
          can.create_payments && !hasPermission(permissionsEnum.ApprovePayments)
        "
        size="sm"
        color="orange"
        @click="addPaymentModal"
      >
        Add Payment
      </x-button>
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
          <template v-if="hasPermission(permissionsEnum.ApprovePayments)">
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
          </template>
          <template v-else>
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
              v-if="
                hasPermission(permissionsEnum.PaymentsEdit) && item.edit_button
              "
              @click="editPaymentModal(item)"
            >
              Edit
            </x-button>
          </template>
        </div>
      </template>
    </DataTable>
    <x-modal
      v-model="createPaymentModal"
      :title="`${
        paymentMethodsForm.status == 'create' ? 'New Payment' : 'Update Payment'
      }`"
      size="lg"
      show-close
      backdrop
      is-form
      @submit="addPayment"
    >
      <div class="w-full grid md:grid-cols-2 gap-5">
        <x-input
          class="w-full"
          :rules="[rules.isRequired, rules.amount]"
          label="Capture Amount*"
          v-model="paymentMethodsForm.amount"
        />

        <x-select
          class="w-full"
          v-model="paymentMethodsForm.collection_type"
          :options="collectionTypes"
          label="Collection Type*"
          :rules="[rules.isRequired]"
        >
        </x-select>

        <x-select
          class="w-full md:col-span-2"
          v-model="paymentMethodsForm.payment_method"
          :options="paymentMethods"
          label="Payment Method*"
          :rules="[rules.isRequired]"
        >
        </x-select>

        <p class="text-sm text-gray-500">
          Provider Name:
          <span class="text-primary-800">{{ providerName }}</span>
        </p>

        <p class="text-sm text-gray-500">
          Plan Name :
          <span class="text-primary-800">{{ getPlanName }}</span>
        </p>

        <x-input
          class="w-full md:col-span-2"
          label="Payment Reference*"
          :rules="[rules.isRequired, rules.reference]"
          v-show="paymentMethodsForm.payment_method != 'CC'"
          v-model="paymentMethodsForm.payment_reference"
        />
      </div>
      <template #secondary-action>
        <x-button ghost tabindex="-1" @click="createPaymentModal = false">
          Cancel
        </x-button>
      </template>
      <template #primary-action>
        <div
          class="w-full md:col-span-2 flex justify-end"
          v-if="
            paymentMethodsForm.status == 'create' ||
            paymentMethodsForm.status == 'edit'
          "
        >
          <x-button color="primary" type="submit">
            {{ paymentMethodsForm.status == 'create' ? 'Create' : 'Update' }}
            Payment
          </x-button>
        </div>
      </template>
    </x-modal>
  </div>
</template>
