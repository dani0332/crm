<script setup>
import {useCan} from "../../../Composables/can";

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
});

const paymentModal = ref(false);

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

const collectionTypes = [
  { value: '', label: 'Select Collection Type' },
  { value: 'broker', label: 'Broker' },
  { value: 'insurer', label: 'Insurer' },
];

const paymentMethodOptions = computed(() => {
  return page.props.paymentMethods.map(method => ({
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

// Unused Code: Use or remove it
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
  paymentForm.payment_method_code = '';
  paymentForm.collection_type = '';
  paymentForm.amount = '';
  paymentForm.payment_reference = '';
  paymentForm.paymentCode = '';
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

  if (paymentForm.status == 'edit') {
    url += '/' + paymentForm.paymentCode;
    method = 'patch';
  }

  paymentForm.submit(method, url, {
    preserveScroll: true,
    onFinish: () => {
      paymentForm.reset();
    },
    onSuccess: () => {
      notification.success({
        title: 'Payment Added',
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
  { text: 'Payment Status', value: 'payment_status.code' },
  { text: 'Plan Name', value: 'personal_plan?.text' },
  { text: 'Captured Amount', value: 'captured_amount', sortable: true },
  { text: 'Status Change Date', value: 'payment_status_log.created_at' },
  { text: 'Captured At', value: 'captured_at' },
  { text: 'Authorized At', value: 'authorized_at' },
  { text: 'Reference', value: 'reference' },
  { text: 'Actions', value: 'actions', sortable: false },
];

const generateCCLink = async payment => {
  try {
    paymentLoader.value = payment.code;

    const response = await axios.post('/generate-payment-link', {
      quoteId: page.props.quote.id,
      modelType: 'personal',
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


</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white" v-if="isBetaUser">
    <div class="flex justify-between gap-4 items-center mb-4">
      <h3 class="font-semibold text-primary-800 text-lg">Payments</h3>
      <x-button
        v-if="can(permissionsEnum.PaymentsCreate) && !can(permissionsEnum.ApprovePayments) && !hasRole(rolesEnum.PA)"
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
            <x-button size="xs" color="error" @click="approvePayment(item)">
              Approve
            </x-button>

            <x-button size="xs" disabled color="error"> Approved </x-button>
          </template>
          <template v-else>
            <x-button
              size="xs"
              color="orange"
              v-if="item.copy_link_button"
              @click="generateCCLink(item)"
              :loading="paymentLoader == item.code"
            >
              Copy Link
            </x-button>
            <x-button
              size="xs"
              color="emerald"
              v-if="can(permissionsEnum.PaymentsEdit) && item.edit_button"
              @click="editPaymentModal(item)"
            >
              Edit
            </x-button>
          </template>
        </div>
      </template>
    </DataTable>

    <x-modal v-model="paymentModal" size="lg" show-close backdrop>
      <template #header>
        <span class="text-primary-800 font-semibold">
          {{
            paymentForm.status == 'create' ? 'New Payment' : 'Update Payment'
          }}
        </span>
      </template>
      <x-form @submit="addPayment" :auto-focus="false">
        <div class="w-full grid md:grid-cols-2 gap-5">
          <x-input
            class="w-full"
            :rules="[rules.isRequired]"
            label="Capture Amount*"
            v-model="paymentForm.captured_amount"
            :error="paymentForm.errors.captured_amount"
          />

          <x-select
            class="w-full"
            v-model="paymentForm.collection_type"
            :options="collectionTypes"
            label="Collection Type*"
            :rules="[rules.isRequired]"
            :error="paymentForm.errors.collection_type"
          >
          </x-select>

          <x-select
            class="w-full md:col-span-2"
            v-model="paymentForm.payment_methods_code"
            :options="paymentMethodOptions"
            label="Payment Method*"
            :rules="[rules.isRequired]"
            :error="paymentForm.errors.payment_methods_code"
          >
          </x-select>

          <x-select
            class="w-full"
            v-model="paymentForm.insurance_provider_id"
            :options="insuranceProviderOptions"
            label="Insurance Provider**"
            :rules="[rules.isRequired]"
            :error="paymentForm.errors.insurance_provider_id"
          >
          </x-select>

          <x-select
            class="w-full"
            v-model="paymentForm.plan_id"
            :options="
              planOptions.data?.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            label="Plan*"
            :rules="[rules.isRequired]"
            :error="paymentForm.errors.plan_id"
          >
          </x-select>

          <x-input
            class="w-full md:col-span-2"
            label="Payment Reference*"
            :rules="[rules.isRequired, rules.reference]"
            v-show="paymentForm.payment_method != 'CC'"
            v-model="paymentForm.reference"
            :error="paymentForm.errors.reference"
          />

          <div
            class="w-full md:col-span-2 flex justify-end"
            v-if="
              paymentForm.status == 'create' || paymentForm.status == 'edit'
            "
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
        </div>
      </x-form>
    </x-modal>
  </div>
</template>
