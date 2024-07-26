<script setup>
const notification = useNotifications('toast');
const page = usePage();

defineProps({
  payments: Array,
  isBetaUser: Boolean,
  can: Object,
  quoteRequest: Object,
  paymentMethods: Object,
  insuranceProviders: Array,
  quote: Object,
  expanded: {
    required: false,
    type: Boolean,
    default: true,
  },
});

const createPaymentModal = ref(false);
const enableManageOptions = ref(false);

const can = permission => useCan(permission);
const hasRole = role => useHasRole(role);
const permissionsEnum = page.props.permissionsEnum;
const rolesEnum = page.props.rolesEnum;

const rules = {
  isRequired: v => !!v || 'This field is required',
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
  { text: 'Payment Status', value: 'payment_status.text' },
  { text: 'Provider Name', value: 'insurance_provider.text' },
  { text: 'Plan Name', value: '' },
  { text: 'Authorize Amount', value: 'captured_amount', sortable: true },
  { text: 'Status Change Date', value: 'payment_status_log.created_at' },
  { text: 'Authorized At', value: 'authorized_at' },
  { text: 'Captured At', value: 'captured_at' },
  { text: 'Payment method', value: 'payment_method.name' },
  { text: 'Captured Amount', value: 'premium_captured' },
  { text: 'Reference', value: 'reference' },
  { text: 'Status Detail', value: 'payment_status_message' },
  { text: 'Actions', value: 'actions', sortable: false },
];

const collectionTypes = [{ value: 'broker', label: 'Broker' }];

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
  paymentMethodsForm.payment_method = 'CC';
  paymentMethodsForm.collection_type = 'broker';
  paymentMethodsForm.amount = '';
  paymentMethodsForm.paymentCode = '';
  paymentMethodsForm.insurance_provider_id = '';

  paymentMethodsForm.status = 'create';
  createPaymentModal.value = true;
};

const editPaymentModal = payment => {
  paymentMethodsForm.reset();
  paymentMethodsForm.status = 'edit';
  paymentMethodsForm.payment_method = payment.payment_method.code;
  paymentMethodsForm.collection_type = payment.collection_type;
  paymentMethodsForm.amount = payment.captured_amount;
  paymentMethodsForm.insurance_provider_id = payment.insurance_provider_id;
  paymentMethodsForm.paymentCode = payment.code;
  createPaymentModal.value = true;
};

const paymentMethodsForm = useForm({
  payment_method: '',
  collection_type: '',
  amount: '',
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
    insurance_provider_id: paymentMethodsForm.insurance_provider_id,
    collection_type: paymentMethodsForm.collection_type,
    payment_methods: paymentMethodsForm.payment_method,
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
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible expanded>
      <template #header>
        <div class="flex justify-between gap-4 items-center">
          <h3 class="font-semibold text-primary-800 text-lg">
            Payments <x-tag size="sm">{{ payments.length || 0 }}</x-tag>
          </h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <div class="my-4 flex justify-end">
          <x-button
            v-if="
              can(permissionsEnum.PaymentsCreate) &&
              !can(permissionsEnum.ApprovePayments) &&
              !hasRole(rolesEnum.PA) &&
              enableManageOptions
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
              <template v-if="can(permissionsEnum.ApprovePayments)">
                <x-button
                  size="xs"
                  color="error"
                  v-if="item.approve_button && enableManageOptions"
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
                  v-if="item.copy_link_button && enableManageOptions"
                  @click="generateCCLink(item.code)"
                >
                  Copy Link
                </x-button>
                <x-button
                  size="xs"
                  color="emerald"
                  v-if="
                    can(permissionsEnum.PaymentsEdit) &&
                    item.edit_button &&
                    enableManageOptions
                  "
                  @click="editPaymentModal(item)"
                >
                  Edit
                </x-button>
              </template>
            </div>
          </template>
        </DataTable>
      </template>
    </Collapsible>
    <x-modal
      v-model="createPaymentModal"
      size="lg"
      :title="`${paymentMethodsForm.status == 'create' ? 'New Payment' : 'Update Payment'}`"
      show-close
      backdrop
    >
      <x-form @submit="addPayment" :auto-focus="false">
        <div class="w-full grid md:grid-cols-2 gap-5">
          <x-input
            class="w-full"
            :rules="[rules.isRequired, rules.amount]"
            label="Price Including VAT*"
            v-model="paymentMethodsForm.amount"
          />

          <x-select
            class="w-full"
            v-model="paymentMethodsForm.collection_type"
            :options="collectionTypes"
            disabled
            label="Collection Type*"
            :rules="[rules.isRequired]"
          >
          </x-select>

          <x-select
            class="w-full md:col-span-2"
            disabled
            v-model="paymentMethodsForm.payment_method"
            :options="paymentMethods"
            label="Payment Method*"
            :rules="[rules.isRequired]"
          >
          </x-select>
          <x-select
            class="w-full md:col-span-2"
            v-model="paymentMethodsForm.insurance_provider_id"
            :options="insuranceProviders"
            label="Provider Name*"
            :rules="[rules.isRequired]"
          >
          </x-select>

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
        </div>
      </x-form>
    </x-modal>
  </div>
</template>
