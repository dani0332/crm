<script setup>
const notification = useNotifications('toast');
const page = usePage();

const permissionEnum = page.props.permissionsEnum;
const rolesEnum = page.props.rolesEnum;

const hasRole = role => useHasRole(role);
const can = permission => useCan(permission);

const props = defineProps({
  payments: Array,
  can: Object,
  paymentStatusEnum: Object,
  quoteRequest: Object,
  paymentMethods: Array,
  quote: Object,
  isCommercialVehicles: Boolean,
  carInsuranceProviders: Array,
});

const insuranceProviderOptions = computed(() => {
  return page.props.carInsuranceProviders.map(provider => ({
    value: provider.id,
    label: provider.text,
  }));
});
const enableManageOptions = ref(false);
const createPaymentModal = ref(false);
const isLoading = ref(false);

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
  { text: 'Provider Name', value: 'insurance_provider.text' },
  { text: 'Plan Name', value: 'plan_name' },
  { text: 'Authorize Amount', value: 'captured_amount' },
  { text: 'Status Change Date', value: 'status_changed_at' },
  { text: 'Authorized At', value: 'authorized_at' },
  { text: 'Captured At', value: 'captured_at' },
  { text: 'Payment method', value: 'payment_method.name' },
  { text: 'Captured Amount', value: 'premium_captured' },
  { text: 'Reference', value: 'reference' },
  { text: 'Status Details', value: 'payment_status_message' },
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
      quoteId: props.quoteRequest.id,
      modelType: 'Car',
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

const insurance_provider_id = ref('');
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
  let plan_id = null;
  if (props.quoteRequest.plan && props.quoteRequest.plan.id) {
    plan_id = props.quoteRequest.plan.id;
  }
  isLoading.value = true;
  let data = {
    captured_amount: paymentMethodsForm.amount,
    code: paymentMethodsForm.payment_method,
    modelType: 'Car',
    quote_id: props.quoteRequest.id,
    plan_id: plan_id,
    insurance_provider_id: props.isCommercialVehicles
      ? insurance_provider_id.value
      : providerId.value,
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
          isLoading.value = false;
        },
        onError: () => {
          notification.error({
            title: 'Payment Update Failed',
            position: 'top',
          });
          isLoading.value = false;
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
        isLoading.value = false;
      },
      onError: () => {
        notification.error({
          title: 'Payment Add Failed',
          position: 'top',
        });
        isLoading.value = false;
      },
    });
};

const approvePayment = payment => {
  let data = {
    code: payment.code,
    modelType: 'Car',
    quote_id: props.quoteRequest.id,
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
  const plan = props.quoteRequest.plan;
  return plan ? plan.text : 'Not Available';
});

const providerName = computed(() => {
  const plan = props.quoteRequest.plan;
  if (plan && plan.insurance_provider) {
    return plan.insurance_provider.text;
  }
  return 'Not Available';
});

onMounted(() => {
  const plan = props.quoteRequest.plan;
  if (plan && plan.insurance_provider) {
    insurance_provider_id.value = plan.insurance_provider.id;
  }
});

const providerId = computed(() => {
  const plan = props.quoteRequest.plan;
  if (plan && plan.insurance_provider) {
    return plan.insurance_provider.id;
  }
  return null;
});
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible expanded>
      <template #header>
        <div class="flex justify-between gap-4 items-center">
          <h3 class="font-semibold text-primary-800 text-lg">Payments</h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <div class="mb-4 flex justify-end">
          <x-button
            v-if="
              (can(permissionEnum.PaymentsCreate) &&
                !can(permissionEnum.ApprovePayments) &&
                !hasRole(rolesEnum.PA) &&
                quoteRequest.plan &&
                enableManageOptions) ||
              (isCommercialVehicles && enableManageOptions)
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
          <template #item-plan_name="item">
            {{ quoteRequest.plan ? quoteRequest.plan.text : '' }}
          </template>
          <template #item-status_changed_at="item">
            {{
              item.payment_status_logs.length > 0
                ? item.payment_status_logs.at(-1).created_at
                : ''
            }}
          </template>

          <template #item-actions="item">
            <div class="flex gap-2">
              <template
                v-if="
                  !can(permissionEnum.ApprovePayments) && enableManageOptions
                "
              >
                <x-button
                  v-if="
                    (item.payment_methods_code == 'CC' ||
                      item.payment_methods_code == 'IN_PL') &&
                    item.payment_status_id != paymentStatusEnum.PAID &&
                    item.payment_status_id != paymentStatusEnum.CAPTURED &&
                    item.payment_status_id != paymentStatusEnum.AUTHORISED &&
                    !hasRole(rolesEnum.PA)
                  "
                  size="xs"
                  color="primary"
                  outlined
                  @click.prevent="generateCCLink(item.code)"
                >
                  Copy Link
                </x-button>
                <x-button
                  v-if="
                    item.payment_status_id != paymentStatusEnum.PAID &&
                    item.payment_status_id != paymentStatusEnum.CAPTURED &&
                    item.payment_status_id != paymentStatusEnum.AUTHORISED &&
                    !hasRole(rolesEnum.PA) &&
                    can(permissionEnum.PaymentsEdit)
                  "
                  size="xs"
                  color="error"
                  @click="editPaymentModal(item)"
                >
                  Edit
                </x-button>
              </template>
              <template
                v-if="
                  can(permissionEnum.ApprovePayments) && enableManageOptions
                "
              >
                <x-button
                  v-if="
                    item.payment_methods_code != 'CC' &&
                    ![
                      paymentStatusEnum.PAID,
                      paymentStatusEnum.CAPTURED,
                    ].includes(item.payment_status_id) &&
                    !hasRole(rolesEnum.PA)
                  "
                  size="xs"
                  color="primary"
                  outlined
                  @click="approvePayment(item)"
                >
                  Approve
                </x-button>
              </template>
              <template v-if="item.payment_status_id == paymentStatusEnum.PAID">
                <x-button size="xs" color="primary" outlined disabled>
                  Approve
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
      is-form
      @submit="addPayment"
    >
      <div class="w-full grid md:grid-cols-2 gap-5">
        <x-field label="Price Including VAT" required>
          <x-input
            class="w-full"
            :rules="[rules.isRequired, rules.amount]"
            v-model="paymentMethodsForm.amount"
          />
        </x-field>
        <x-field label="Collection Type" required>
          <x-select
            class="w-full"
            v-model="paymentMethodsForm.collection_type"
            :disabled="true"
            :options="collectionTypes"
            :rules="[rules.isRequired]"
          >
          </x-select>
        </x-field>
        <x-field label="Payment Method" required>
          <x-select
            class="w-full md:col-span-2"
            v-model="paymentMethodsForm.payment_method"
            :options="paymentMethods"
            :disabled="true"
            :rules="[rules.isRequired]"
          >
          </x-select>
        </x-field>
        <x-field
          v-if="isCommercialVehicles"
          label="Insurance Provider"
          required
        >
          <x-select
            class="w-full md:col-span-2"
            v-model="insurance_provider_id"
            :options="insuranceProviderOptions"
            placeholder="Select Insurance provider"
            :rules="[rules.isRequired]"
          >
          </x-select>
        </x-field>
        <p class="text-sm text-gray-500" v-else>
          Provider Name:
          <span class="text-primary-800">{{ providerName }}</span>
        </p>

        <p class="text-sm text-gray-500">
          Plan Name :
          <span class="text-primary-800">{{ getPlanName }}</span>
        </p>
        <x-field
          label="Payment Reference"
          required
          v-if="paymentMethodsForm.payment_method != 'CC'"
        >
          <x-input
            class="w-full md:col-span-2"
            :rules="[rules.isRequired, rules.reference]"
            v-model="paymentMethodsForm.payment_reference"
          />
        </x-field>
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
          <x-button color="primary" type="submit" :loading="isLoading">
            {{ paymentMethodsForm.status == 'create' ? 'Create' : 'Update' }}
            Payment
          </x-button>
        </div>
      </template>
    </x-modal>
  </div>
</template>
