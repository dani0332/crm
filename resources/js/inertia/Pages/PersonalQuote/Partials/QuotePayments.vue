<script setup>
const notification = useNotifications('toast');
const page = usePage();
const paymentLoader = ref(``);

defineProps({
  payments: Object,
  isBetaUser: Boolean,
  can: Object,
  quoteRequest: Object,
  paymentMethods: Object,
  insuranceProviders: Object,
  personalPlans: Object,
  quote: Object,
  quoteType: String,
  expanded: {
    type: Boolean,
    required: false,
    default: true,
  },
});

const paymentModal = ref(false);

const enableManageOptions = ref(false);

const rules = {
  isRequired: v => !!v || 'This field is required',
  reference: v => {
    if (paymentForm.payment_method !== 'CC') {
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

const collectionTypes = [{ value: 'broker', label: 'Broker' }];

const paymentMethodOptions = computed(() => {
  return page.props.paymentMethods
    .filter(opt => opt.code == 'CC')
    .map(method => ({
      value: method.code,
      label: method.name,
    }));
});

const insuranceProviderOptions = computed(() => {
  return page.props.insuranceProviders.map(method => ({
    value: method.id,
    label: method.text,
  }));
});

const personalPlanOptions = computed(() => {
  return page.props.personalPlans.map(method => ({
    value: method.id,
    label: method.text,
  }));
});

const paymentForm = useForm({
  collection_type: '',
  captured_amount: '',
  payment_methods_code: '',
  insurance_provider_id: '',
  plan_id: '',
  reference: '',
  paymentCode: '',
  status: 'create',
  paymentId: '',
});

const addPaymentModal = () => {
  paymentForm.reset();
  paymentForm.payment_methods_code = 'CC';
  paymentForm.collection_type = 'broker';
  paymentForm.captured_amount = '';
  paymentForm.payment_reference = '';
  paymentForm.paymentCode = '';
  paymentForm.insurance_provider_id = '';
  paymentForm.status = 'create';
  paymentModal.value = true;
};

const editPaymentModal = payment => {
  paymentForm.reset();
  paymentForm.status = 'edit';
  paymentForm.payment_methods_code = payment.payment_methods_code;
  paymentForm.collection_type = payment.collection_type;
  paymentForm.captured_amount = payment.captured_amount;
  paymentForm.reference = payment.reference;
  paymentForm.insurance_provider_id = payment.insurance_provider_id;
  paymentForm.plan_id = payment.plan_id;
  paymentForm.paymentCode = payment.code;
  paymentModal.value = true;
};

const addPayment = isValid => {
  if (!isValid) return;

  paymentForm.clearErrors();
  let url = '/personal-quotes/' + page.props.quote.id + '/payments';
  let method = 'post';
  let messageTitle = 'Payment created successfully';

  if (paymentForm.status == 'edit') {
    url += '/' + paymentForm.paymentCode;
    method = 'patch';
    messageTitle = 'Payment updated successfully';
  }

  paymentForm.submit(method, url, {
    preserveScroll: true,
    onFinish: () => {
      paymentForm.reset();
    },
    onSuccess: () => {
      notification.success({
        title: messageTitle,
        position: 'top',
      });
      paymentModal.value = false;
    },
    onError: () => {
      notification.error({
        title: 'Payment Add Failed',
        position: 'top',
      });
    },
  });
};

const planOptions = reactive({
  data: [],
  loading: false,
});

watch(
  () => paymentForm.insurance_provider_id,
  value => {
    if (value) {
      planOptions.loading = true;
      paymentForm.plan_id = null;
      planOptions.data = null;
      axios
        .get(
          `/personal-plans/list?insurance_provider_id=${value}&quote_type=${page.props.quoteType}`,
        )
        .then(res => {
          if (res.data.length > 0) {
            planOptions.data = res.data;
          }
        })
        .finally(() => {
          planOptions.loading = false;
        });
    }
  },
);

const getPlanName = computed(() => {
  const plan = page.props.quote.plan;
  return plan ? plan.text : 'Not Available';
});

const paymentTableHeaders = [
  { text: 'Payment ID', value: 'code', align: 'center' },
  { text: 'Payment Status', value: 'payment_status.text' },
  { text: 'Provider Name', value: 'insurance_provider.text' },
  { text: 'Plan Name', value: 'plan_name' },
  { text: 'Authorize Amount', value: 'captured_amount', sortable: true },
  { text: 'Status Change Date', value: 'change_date' },
  { text: 'Authorized At', value: 'authorized_at' },
  { text: 'Captured At', value: 'captured_at' },
  { text: 'Payment method', value: 'payment_methods_code' },
  { text: 'Captured Amount', value: 'premium_captured' },
  { text: 'Reference', value: 'reference' },
  { text: 'Status Detail', value: 'payment_status_message' },
  { text: 'Actions', value: 'actions', sortable: false },
];

const generateCCLink = async payment => {
  try {
    paymentLoader.value = payment.code;

    const response = await axios.post('/generate-payment-link', {
      quoteId: page.props.quote.id,
      modelType: page.props.quoteType,
      paymentCode: payment.code,
      isInertia: true,
    });

    paymentLoader.value = ``;

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

const can = permission => useCan(permission);
const hasRole = role => useHasRole(role);
const permissionsEnum = page.props.permissionsEnum;
const rolesEnum = page.props.rolesEnum;

const readOnlyMode = reactive({
  isDisable: true,
});
onMounted(() => {
  readOnlyMode.isDisable = !can(permissionsEnum.All_QUOTES_VIEWONLY_ACCESS);
});
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="expanded">
      <template #header>
        <div class="flex justify-between gap-4 items-center">
          <h3 class="font-semibold text-primary-800 text-lg">Payments</h3>
        </div>
      </template>

      <template #body>
        <x-divider class="my-4" />
        <div
          class="mb-4 flex justify-end"
          v-if="readOnlyMode.isDisable === true"
        >
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
          <template #item-plan_name="{ personal_plan }">
            {{ personal_plan?.text }}
          </template>
          <template #item-change_date="{ payment_status_logs }">
            {{ payment_status_logs[0]?.created_at }}
          </template>
          <template #item-payment_methods_code="{ payment_method }">
            {{ payment_method?.name }}
          </template>
          <template #item-actions="item">
            <div class="flex gap-2" v-if="readOnlyMode.isDisable === true">
              <template v-if="can(permissionsEnum.ApprovePayments)">
                <x-button
                  size="xs"
                  color="error"
                  @click="approvePayment(item)"
                  v-if="enableManageOptions"
                >
                  Approve
                </x-button>
                <x-button size="xs" disabled color="error"> Approved </x-button>
              </template>
              <template v-else>
                <x-button
                  size="xs"
                  color="orange"
                  v-if="item.copy_link_button && enableManageOptions"
                  @click="generateCCLink(item)"
                  :loading="paymentLoader == item.code"
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
      v-model="paymentModal"
      size="lg"
      :title="`${paymentForm.status == 'create' ? 'New Payment' : 'Update Payment'}`"
      show-close
      backdrop
      is-form
      @submit="addPayment"
    >
      <div class="w-full grid md:grid-cols-2 gap-5">
        <x-input
          class="w-full"
          :rules="[rules.isRequired]"
          label="Price Including VAT*"
          v-model="paymentForm.captured_amount"
          :error="paymentForm.errors.captured_amount"
        />

        <x-select
          class="w-full"
          v-model="paymentForm.collection_type"
          :options="collectionTypes"
          label="Collection Type*"
          disabled
          :rules="[rules.isRequired]"
          :error="paymentForm.errors.collection_type"
        >
        </x-select>

        <x-select
          class="w-full md:col-span-2"
          v-model="paymentForm.payment_methods_code"
          :options="paymentMethodOptions"
          label="Payment Method*"
          disabled
          :rules="[rules.isRequired]"
          :error="paymentForm.errors.payment_methods_code"
        >
        </x-select>

        <x-select
          class="w-full md:col-span-2"
          v-model="paymentForm.insurance_provider_id"
          :options="insuranceProviderOptions"
          label="Provider Name*"
          :rules="[rules.isRequired]"
          :error="paymentForm.errors.insurance_provider_id"
        >
        </x-select>
      </div>
      <template #secondary-action>
        <x-button
          ghost
          tabindex="-1"
          @click.prevent="paymentModal = false"
          size="sm"
        >
          Cancel
        </x-button>
      </template>
      <template #primary-action>
        <div
          class="w-full md:col-span-2 flex justify-end"
          v-if="paymentForm.status == 'create' || paymentForm.status == 'edit'"
        >
          <x-button
            :loading="paymentForm.processing"
            color="primary"
            type="submit"
          >
            {{ paymentForm.status == 'create' ? 'Create' : 'Update' }}
            Payment
          </x-button>
        </div>
      </template>
    </x-modal>
  </div>
</template>
