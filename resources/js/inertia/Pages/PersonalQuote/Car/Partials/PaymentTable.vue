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
  paymentTooltipEnum: Object,
  quoteRequest: Object,
  paymentMethods: Array,
  quote: Object,
});

const createPaymentModal = ref(false);
const isPaymentNoEnabled = ref(false);

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
  { text: 'Authorize Amount', value: 'captured_amount'},
  { text: 'Status Change Date', value: 'status_changed_at' },
  { text: 'Authorized At', value: 'authorized_at' },
  { text: 'Captured At', value: 'captured_at' },
  { text: 'Payment method', value: 'payment_method.name' },
  { text: 'Captured Amount', value: 'premium_captured'},
  { text: 'Reference', value: 'reference' },
  { text: 'Status Details', value: 'payment_status_message' },
  { text: 'Actions', value: 'actions', sortable: false },
];

const collectionTypes = [
  { value: '', label: 'Select Collection Type' },
  { value: 'broker', label: 'Broker' , tooltip: props.paymentTooltipEnum.COLLECTOR_LIST_BROKER},
  { value: 'insurer', label: 'Insurer' , tooltip: props.paymentTooltipEnum.COLLECTOR_LIST_INSURER},
];


// Watch for changes in paymentMethodsForm.frequency
//watch(paymentMethodsForm.frequency, handleFrequencyTypeChange);

const totalPayments = [
  { value: '1', label: '1'},
  { value: '2', label: '2'},
  { value: '3', label: '3'},
  { value: '4', label: '4'},
  { value: '5', label: '5'},
];
const handleCollectionTypeChange = () => {
  if (paymentMethodsForm.collection_type === 'insurer') {
    paymentMethodsForm.payment_method = 'BT';
  } else {
    paymentMethodsForm.payment_method = 'CC';
  }
};

const handleFrequencyTypeChange = () => {
  var resetPaymentMethod = false;
  isPaymentNoEnabled.value = false;
  if (paymentMethodsForm.frequency === 'monthly') {
    resetPaymentMethod = true;
    paymentMethodsForm.payment_no = '12';
  } else if (paymentMethodsForm.frequency === 'quarterly') {
    resetPaymentMethod = true;
    paymentMethodsForm.payment_no = '4';
  } else if (paymentMethodsForm.frequency === 'semi_annual') {
    resetPaymentMethod = true;
    paymentMethodsForm.payment_no = '2';
  } else if (paymentMethodsForm.frequency === 'split_payments') {
    isPaymentNoEnabled.value = true;
    paymentMethodsForm.payment_no = '1';
    if(totalPayments.length > 5){
      totalPayments.splice(-7);
    }    
  } else if (paymentMethodsForm.frequency === 'custom') {
    isPaymentNoEnabled.value = true;
    paymentMethodsForm.payment_no = '1';

    
    for (let i = 6; i <= 12; i++) { // Append 7 more values to totalPayments
      totalPayments.push({ value: i.toString(), label: i.toString() });
    }
  } else {
    paymentMethodsForm.payment_no = '1';
  }

  if (paymentMethodsForm.payment_method==='CC' && resetPaymentMethod){
      paymentMethodsForm.payment_method = 'BT';
    }    
};

// Define a computed property to determine if 'insurer' should be disabled
const isCCDisabled = computed(() => {
  return (
          paymentMethodsForm.frequency === 'monthly' || 
          paymentMethodsForm.frequency === 'quarterly' || 
          paymentMethodsForm.frequency === 'semi_annual'
        
          );
});

const frequencyTypes = [
  { value: '', label: 'Select Frequency'},
  { value: 'upfront', label: 'Upfront' },
  { value: 'monthly', label: 'Monthly' },
  { value: 'quarterly', label: 'Quarterly' },
  { value: 'semi_annual', label: 'Semi Annual' },
  { value: 'split_payments', label: 'Split Payments' },
  { value: 'custom', label: 'Custom' },
];

const creditApprovalReasons = [
  { value: ' ', label: 'Select Approval Reason'},
  { value: 'available_credit_balance', label: 'Available credit balance' },
  { value: 'post_dated_cheque_payment', label: 'Post-dated cheque payment' },
  { value: 'cheque_under_clearance', label: 'Cheque under clearance' },
  { value: 'other_reasons', label: 'Other reasons' },  
];

const discountTypes = [
  { value: ' ', label: 'Select Discount Type'},
  { value: 'refer_a_friend', label: 'Refer-a-friend' },
  { value: 'incentive_offset', label: 'Incentive offset' },
  { value: 'managerial_approval_discount', label: 'Managerial approval discount' },
  { value: 'employee_discount', label: 'Employee discount' },
  { value: 'family_employee_discount', label: 'Family employee discount' }, 
];

const discountReasons = [
  { value: '', label: 'Select Discount Reason'},
  { value: 'promotional_campaign_discount', label: 'Promotional campaign discount' },
  { value: 'loyalty_reward_discount', label: 'Loyalty reward discount' },
  { value: 'competitive_pricing_discount', label: 'Competitive pricing discount' },
  { value: 'custom_discount_reason', label: 'Custom discount reason' },  
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
  paymentMethodsForm.collectionDate = new Date();
  paymentMethodsForm
  createPaymentModal.value = true;
  paymentMethodsForm.payment_no = '1';
  paymentMethodsForm.frequency = 'upfront';
  paymentMethodsForm.discount = ' ';
  paymentMethodsForm.credit_approval = ' ';
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
    modelType: 'Car',
    quote_id: props.quoteRequest.id,
    plan_id: props.quoteRequest.plan.id,
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
    <div class="flex justify-between gap-4 items-center mb-4">
      <h3 class="font-semibold text-primary-800 text-lg">Payments</h3>

      <x-button
        size="sm"
        color="emerald"
        @click="addPaymentModal"
      >
        Add Manual Payment
      </x-button>
      <!-- HAFEEZ TEMPORARY <x-button
        v-if="!permissionEnum.ApprovePayments && permissionEnum.PaymentsCreate && quoteRequest.plan"
        size="sm"
        color="orange"
        @click="addPaymentModal"
      >
        Add Payment
      </x-button> -->
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
        {{ item.payment_status_logs.length > 0 ? item.payment_status_logs.at(-1).created_at : '' }}
      </template>
      
      <template #item-actions="item">
            <div class="flex gap-2">
                <template v-if="!can(permissionEnum.ApprovePayments)">
                    <x-button v-if="item.payment_method_code == 'CC' && item.payment_status_id != paymentStatusEnum.PAID && item.payment_status_id != paymentStatusEnum.CAPTURED && item.payment_status_id != paymentStatusEnum.AUTHORISED && !hasRole(rolesEnum.PA)" 
                        size="xs" 
                        color="primary" 
                        outlined 
                        @click.prevent="generateCCLink(item.code)"
                    >
                        Copy Link
                    </x-button>
                    <x-button v-if="item.payment_status_id != paymentStatusEnum.PAID && item.payment_status_id != paymentStatusEnum.CAPTURED && item.payment_status_id != paymentStatusEnum.AUTHORISED && !hasRole(rolesEnum.PA) && can(permissionEnum.PaymentsEdit)"  size="xs" color="error" @click="editPaymentModal(item)">
                        Edit
                    </x-button>
                </template>
                <template v-if="can(permissionEnum.ApprovePayments)">
                    <x-button v-if="item.payment_method_code != 'CC' && ![paymentStatusEnum.PAID, paymentStatusEnum.CAPTURED].includes(item.payment_status_id) && !hasRole(rolesEnum.PA)" 
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
    <x-modal v-model="createPaymentModal" size="lg" show-close backdrop>
      <template #header>
        <span class="text-primary-800 font-semibold">
          {{
            paymentMethodsForm.status == 'create'
              ? 'Add Manual Payment'
              : 'Update Payment'
          }}
        </span>
      </template>
      <x-form @submit="addPayment" :auto-focus="false">
        <div class="w-full grid md:grid-cols-2 gap-5">
          <x-tooltip>
            <x-field label="COLLECTION DATE" class="w-full" required>
              <DatePicker
                  name="collection_date"
                  v-model="paymentMethodsForm.collectionDate"
                  :rules="[rules.isRequired]"                  
              />           
            </x-field>
            <template #tooltip>
               <span>{{ paymentTooltipEnum.COLLECTION_DATE }}</span>
            </template>
          </x-tooltip>
          <x-tooltip>
            <x-field label="TOTAL PRICE" class="w-full">
              <x-input
                  class="w-full"
                  value="100"
                  :disabled="true"
                />
            </x-field>
            <template #tooltip>
               <span>{{ paymentTooltipEnum.TOTAL_PRICE }}</span>
            </template>
          </x-tooltip>         
          <x-tooltip>
            <x-field label="COLLECTED BY" class="w-full" required>
            <select
                class="beautiful-select"
                v-model="paymentMethodsForm.collection_type"
                :options="collectionTypes"
                :rules="[rules.isRequired]"
                @change="handleCollectionTypeChange"
                >
                <template v-for="option in collectionTypes" :key="option.value">
                    <option :value="option.value" :title="option.tooltip">{{ option.label }}</option>                
                </template>
            </select>
            </x-field>
            <template #tooltip>
                <span>{{ paymentTooltipEnum.COLLECTED_BY }}</span>
            </template>
          </x-tooltip>

          <x-tooltip>
            <x-field label="PROVIDER NAME" class="w-full">
              <x-input
                  class="w-full"
                  value="provider name"
                  :disabled="true"
                />
            </x-field>
            <template #tooltip>
                <span>{{ paymentTooltipEnum.PROVIDER_NAME }}</span>
            </template>    
          </x-tooltip>
          
          <x-tooltip>
            <x-field label="FREQUENCY" class="w-full" required>
              
              <select
                  class="beautiful-select"
                  v-model="paymentMethodsForm.frequency"
                  :options="frequencyTypes"
                  :rules="[rules.isRequired]"
                  @change="handleFrequencyTypeChange"
                  >
                  <template v-for="option in frequencyTypes" :key="option.value">
                      <option :value="option.value" :title="option.tooltip">{{ option.label }}</option>                
                  </template>
              </select>
              
              
              
            </x-field>
            <template #tooltip>
                <span>{{ paymentTooltipEnum.FREQUENCY }}</span>
            </template>
          </x-tooltip>

          <x-tooltip>
            <x-field label="PLAN NAME" class="w-full">
              <x-input
                  class="w-full"
                  value="plan name"
                  :disabled="true"
                />
            </x-field>
            <template #tooltip>
                <span>{{ paymentTooltipEnum.PLAN_NAME }}</span>
            </template>
          </x-tooltip>  

          <x-tooltip>
            <x-field label="PAYMENT NO" class="w-full" required>
              
              <select
                  class="beautiful-select"
                  v-model="paymentMethodsForm.payment_no"
                  :options="totalPayments"
                  :rules="[rules.isRequired]"
                  :disabled="!isPaymentNoEnabled"
                  >
                  <template v-for="option in totalPayments" :key="option.value">
                      <option :value="option.value" :title="option.tooltip">{{ option.label }}</option>                
                  </template>
              </select>            
              
              
            </x-field>
            <template #tooltip>
                <span>{{ paymentTooltipEnum.PAYMENT_NO }}</span>
            </template>
          </x-tooltip>
          
          <x-tooltip>
            <x-field label="PAYMENT STATUS" class="w-full">
              <x-input
                  class="w-full"
                  value="payment status"
                  :disabled="true"
                />
            </x-field>
            <template #tooltip>
                <span>{{ paymentTooltipEnum.PAYMENT_STATUS }}</span>
            </template>
          </x-tooltip>
          
          <x-tooltip>
            <x-field label="CREDIT APPROVAL" class="w-full">
              <x-select
                class="w-full"
                v-model="paymentMethodsForm.credit_approval"                            
                :options="creditApprovalReasons"              
              >
              </x-select>
            </x-field>
            <template #tooltip>
                <span>{{ paymentTooltipEnum.CREDIT_APPROVAL }}</span>
            </template>
          </x-tooltip>
          
          <x-tooltip>
            <x-field label="DISCOUNT APPLICABLE (DISCOUNT TYPE)" class="w-full">
              <x-select
                class="w-full"
                :options="discountTypes"
                v-model="paymentMethodsForm.discount"              
              >
              </x-select>
            </x-field>
            <template #tooltip>
                <span>{{ paymentTooltipEnum.DISCOUNT_APPLICABLE }}</span>
            </template>
          </x-tooltip>
          
        </div>
        <x-divider class="mb-4 mt-1" />

        <div class="w-full grid">
          <!-- Header -->
          <div class="flex w-full">
            <div class="w-1/5 px-2">
              <x-tooltip>
                <span class="text-primary-800 font-semibold">
                  PAYMENT NO *
                </span>
                <template #tooltip>
                  <span>{{ paymentTooltipEnum.PAYMENT_NO_2 }}</span>
                </template>
              </x-tooltip>              
            </div>
            <div class="w-1/5 px-2">
              <x-tooltip>
                <span class="text-primary-800 font-semibold">
                  PAYMENT METHOD *
                </span>
                <template #tooltip>
                  <span>{{ paymentTooltipEnum.PAYMENT_METHOD }}</span>
                </template>
              </x-tooltip>
            </div>
            <div class="w-1/5 px-2">
              <x-tooltip>
                <span class="text-primary-800 font-semibold">
                  TOTAL AMOUNT *
                </span>
                <template #tooltip>
                  <span>{{ paymentTooltipEnum.TOTAL_AMOUNT }}</span>
                </template>
              </x-tooltip>
            </div>
            <div class="w-1/5 px-2">
              <x-tooltip>
                <span class="text-primary-800 font-semibold">
                  DUE DATE *
                </span>
                <template #tooltip>
                  <span>{{ paymentTooltipEnum.DUE_DATE }}</span>
                </template>
              </x-tooltip>
            </div>
            <div class="w-1/5 px-2">
              <x-tooltip>
                <span class="text-primary-800 font-semibold">
                  DOCUMENTS *
                </span>
                <template #tooltip>
                  <span>{{ paymentTooltipEnum.DOCUMENTS }}</span>
                </template>
              </x-tooltip>
            </div>
          </div>
          
          <!-- Fields -->
          <div v-for="count in parseInt(paymentMethodsForm.payment_no)" :key="count">
          <div class="flex w-full custombreak">
            <div class="w-1/5 px-2">{{ count }}</div>
            <div class="w-1/5 px-2">

              <select
                class="beautiful-select"
                v-model="paymentMethodsForm.payment_method"
                :options="paymentMethods"
                :rules="[rules.isRequired]"                
              >
                <!-- Use the title attribute to set the tooltip text -->
                <option
                  v-for="option in paymentMethods"
                  :key="option.value"
                  :value="option.value"
                  :title="option.tooltip"
                  :disabled="option.value === 'CC' && isCCDisabled"
                >{{ option.label }}</option>
              </select>

               
            </div>
            <div class="w-1/5 px-2">
                <x-input
                name="totalAmount[]"
                class="w-full"
                :rules="[rules.isRequired]"              
              />
            </div>
            <div class="w-1/5 px-2">
              <DatePicker
                  name="dueDate[]"
                  v-model="paymentMethodsForm.collectionDate"                      
              />  
            </div>
            <div class="w-1/5 px-2">Upload Document</div>
          </div>
        </div>

          
      </div>
  
      <x-divider class="mb-4 mt-1" />

      <div class="w-full grid">
        <x-field label="NOTES">
          <x-input
            class="w-full"            
          />
        </x-field>
      </div>

      <x-divider class="mb-4 mt-1" />
 
      <div
        class="w-full md:col-span-2 flex justify-end"
        v-if="
          paymentMethodsForm.status == 'create' ||
          paymentMethodsForm.status == 'edit'
        "
      >
        <x-button color="emerald" type="submit">
          {{ paymentMethodsForm.status == 'create' ? 'Add Manual' : 'Update' }}
          Payment
        </x-button>
      </div>
      </x-form>
    </x-modal>
  </div>
</template>
<style scoped>
/* Add your beautiful styling here */
.beautiful-select {
  /* Example styles */
  border: 2px solid #e5e7eb;
  padding: 10px;
  border-radius: 5px;
  background-color: #fff;
  color: #333;
  font-size: 16px;
  width: 100%;
  /* You can customize these styles to your liking */
}


</style>
