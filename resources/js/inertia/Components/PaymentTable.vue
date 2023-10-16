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
  paymentDocument: Object,
  quoteRequest: Object,
  paymentMethods: Array,
  quote: Object,
  quoteType: String,
  storageUrl: String,
});

const createPaymentModal = ref(false);
const isPaymentNoEnabled = ref(false);
const isCustomReasonEnabled = ref(false);
const isDiscountEnabled = ref(false);
const isDiscountReasonEnabled = ref(false);
const isCheckDetailsEnabled = ref(false);
const isExpandedSplitPayments = ref(false);
const isPaymentCalculationError = ref(false);
const isUploading = ref(false);
const isFileError = ref(false);
const fileErrorMessage = ref('');

const discountValue = ref(0); // Initial discount value
const totalPrice = ref(props.quoteRequest.premium); // Initial total price
const paymentTypesFiltered = ref([]);

const isPaymentMetodNotSelected = ref([]);
const paymentMethodsModels = ref([]);
const splitAmountModels = ref([]);
const dueDateModels = ref([]);
const fileUploadModels = ref([]);
const checkDetailModels = ref([]);

const totalAmount = ref(props.quoteRequest.premium); // Initial total price


const paymentTableHeaders = reactive({
  columns:[
    { text: 'Payment ID', value: 'cus', id:'cus', align: 'center' },
    { text: 'Payment Ref ID', value: 'code' },
    { text: 'Collection Date', value: 'collection_date' },
    { text: 'Due Date', value: 'collection_date', sortable: true },
    { text: 'Payment Method', value: 'payment_method.name' },
    { text: 'Total Price', value: 'total_price' },
    { text: 'Discount', value: 'discount_value' },
    { text: 'Total Amount', value: 'total_amount' },
    { text: 'Paid Amount', value: 'captured_amount' },
    { text: 'Payment Status', value: 'payment_status.text' },
    { text: 'Payment Allocation Status', value: 'payment_status_message' },
    { text: 'Actions', value: 'actions', sortable: false },
  ]
});

const calculateTotalAmount = () => {
  const discount = discountValue.value;
  if (discount > 50 && paymentMethodsForm.discount === 'refer_a_friend') {
    totalAmount.value = totalPrice.value;
  } else {
    totalAmount.value = totalPrice.value - discount;    
  }
  calculatePaymentBreakup();  
}

const discountError = computed(() => {
  const regex = /^\d+(\.\d{1,2})?$/;
  if (!regex.test(discountValue.value)) {
    return 'Discount must be a valid number';
  }    
  // Check if the discount exceeds 50 and return an error message
  if (discountValue.value > 50 && paymentMethodsForm.discount === 'refer_a_friend') {
    return 'Discount should not exceed 50 AED';
  }
  return '';
});

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
  notEmptyOrZero: v => {
    if (v !== '') {
      return true;
    }
    return 'Value cannot be empty';
  },
};

const validatePaymentOption = () => {  
      var totalSplitAmount = 0;
      for (let i = 1; i <= paymentMethodsForm.payment_no; i++) { 
        totalSplitAmount = (parseFloat(totalSplitAmount) + parseFloat(splitAmountModels.value[i]));       
        const validationResult = rules.notEmptyOrZero(paymentMethodsModels.value[i]);
        isPaymentMetodNotSelected.value[i] = false;
        if (validationResult !== true) {          
          isPaymentMetodNotSelected.value[i] = true;        
          return true;
        }
      }
      if(totalSplitAmount.toFixed(2) !== parseFloat(totalAmount.value).toFixed(2)){
        isPaymentCalculationError.value = true;
        return true;
      }
    return false;
  }

const collectionTypes = [
  { value: '', label: 'Select Collection' },
  { value: 'broker', label: 'Broker' , tooltip: props.paymentTooltipEnum.COLLECTOR_LIST_BROKER},
  { value: 'insurer', label: 'Insurer' , tooltip: props.paymentTooltipEnum.COLLECTOR_LIST_INSURER},
];
const totalPayments = ref([
  { value: '1', label: '1'},
 ]);

 const paymentTypes = ref(props.paymentMethods.filter(item => !['CR_FAYAZ', 'CR_HITESH', 'CR_MAHESH', 'CR'].includes(item.value)));
 paymentTypes.value.unshift({ value: '', label: 'Select Payment' });
  
const frequencyTypes = [
  { value: '', label: 'Select Frequency'},
  { value: 'upfront', label: 'Upfront', tooltip: props.paymentTooltipEnum.FREQUENCY_LIST_UPFRONT },
  { value: 'monthly', label: 'Monthly', tooltip: props.paymentTooltipEnum.FREQUENCY_LIST_MONTHLY },
  { value: 'quarterly', label: 'Quarterly', tooltip: props.paymentTooltipEnum.FREQUENCY_LIST_QUARTERLY },
  { value: 'semi_annual', label: 'Semi Annual', tooltip: props.paymentTooltipEnum.FREQUENCY_LIST_SEMI_ANNUAL },
  { value: 'split_payments', label: 'Split Payments', tooltip: props.paymentTooltipEnum.FREQUENCY_LIST_SPLIT_PAYMENTS },
  { value: 'custom', label: 'Custom', tooltip: props.paymentTooltipEnum.FREQUENCY_LIST_CUSTOM },
];

const creditApprovalReasons = [
  { value: '', label: 'Approval Reason'},
  { value: 'available_credit_balance', label: 'Available credit balance', tooltip: props.paymentTooltipEnum.CREDIT_APPROVAL_LIST_AVAILABLE },
  { value: 'post_dated_cheque_payment', label: 'Post-dated cheque payment', tooltip: props.paymentTooltipEnum.CREDIT_APPROVAL_LIST_POSTDATED },
  { value: 'cheque_under_clearance', label: 'Cheque under clearance', tooltip: props.paymentTooltipEnum.CREDIT_APPROVAL_LIST_CLEARANCE },
  { value: 'other_reasons', label: 'Other reasons', tooltip: props.paymentTooltipEnum.CREDIT_APPROVAL_LIST_REASON },  
];

const discountTypes = [
  { value: '', label: 'Discount Type'},
  { value: 'refer_a_friend', label: 'Refer a friend', tooltip: props.paymentTooltipEnum.DISCOUNT_TYPE_LIST_REFER },
  { value: 'incentive_offset', label: 'Incentive offset', tooltip: props.paymentTooltipEnum.DISCOUNT_TYPE_LIST_INCENTIVE },
  { value: 'managerial_approval_discount', label: 'Managerial approval discount', tooltip: props.paymentTooltipEnum.DISCOUNT_TYPE_LIST_MANAGERIAL },
  { value: 'employee_discount', label: 'Employee discount', tooltip: props.paymentTooltipEnum.DISCOUNT_TYPE_LIST_EMPLOYEE },
  { value: 'family_employee_discount', label: 'Family employee discount', tooltip: props.paymentTooltipEnum.DISCOUNT_TYPE_LIST_FAMILY }, 
];

const discountReasons = [
  { value: '', label: 'Discount Reason'},
  { value: 'promotional_campaign_discount', label: 'Promotional campaign discount', tooltip: props.paymentTooltipEnum.DISCOUNT_REASON_LIST_PROMOTIONAL },
  { value: 'loyalty_reward_discount', label: 'Loyalty reward discount', tooltip: props.paymentTooltipEnum.DISCOUNT_REASON_LIST_LOYALTY },
  { value: 'competitive_pricing_discount', label: 'Competitive pricing discount', tooltip: props.paymentTooltipEnum.DISCOUNT_REASON_LIST_COMPETITIVE },
  { value: 'custom_discount_reason', label: 'Custom discount reason', tooltip: props.paymentTooltipEnum.DISCOUNT_REASON_LIST_CUSTOM_REASON },  
];

const handlePaymentOptions = () => {
  if( paymentMethodsModels.value[1] === 'CHQ' || paymentMethodsModels.value[1] === 'PDC' ) {
    isCheckDetailsEnabled.value = true;
  } else {
    isCheckDetailsEnabled.value = false;
  }
}

const handleCollectionTypeChange = () => {
  //customize payment method based on collection type
  paymentTypesFiltered.value = paymentTypes.value;

  paymentTypesFiltered.value = paymentTypesFiltered.value.filter(item => !['IN_PL', 'PPR', 'CA', 'MP', 'PP'].includes(item.value));    
  if (paymentMethodsForm.collection_type === 'insurer') {    
    paymentTypesFiltered.value = paymentTypesFiltered.value.filter(item => !['CC', 'BT', 'CSH'].includes(item.value));
    paymentMethodsModels.value[1] = 'IP';
  } else {
    paymentTypesFiltered.value = paymentTypesFiltered.value.filter(item => !['IP'].includes(item.value));
    paymentMethodsModels.value[1] = '';
  }
};

const handlePaymentTypes = (count) => {
  var paymentTypesWithoutCheck = paymentTypesFiltered.value;
  if (  count >= 2  && 
        (
          paymentMethodsForm.frequency === 'semi_annual' ||
          paymentMethodsForm.frequency === 'quarterly' ||
          paymentMethodsForm.frequency === 'monthly' ||
          paymentMethodsForm.frequency === 'custom'
        )
    ){
    paymentTypesWithoutCheck =  paymentTypesFiltered.value.filter(item => !['CHQ','CC'].includes(item.value));    
  }

  if(paymentMethodsForm.frequency === 'upfront' || paymentMethodsForm.frequency === 'split_payments' ){
    paymentTypesWithoutCheck =  paymentTypesWithoutCheck.filter(item => !['PDC'].includes(item.value));    
  }

  return paymentTypesWithoutCheck;  
}

const handleApprovalReasonChange = () => {
  if(paymentMethodsForm.credit_approval === 'other_reasons'){
    isCustomReasonEnabled.value = true;
  }else{
    isCustomReasonEnabled.value = false;
  }
};

const resetCreditApproval = () => {
  paymentMethodsForm.credit_approval = '';
  isCustomReasonEnabled.value = false;  
};

const resetDiscount = () => {
  isDiscountEnabled.value = false;
  isDiscountReasonEnabled.value = false;
  paymentMethodsForm.discount = '';
};

const handleDiscountChange = () => {
  discountValue.value = 0;
  isDiscountReasonEnabled.value = false;
  if(paymentMethodsForm.discount === ''){
    isDiscountEnabled.value = false;    
  }else{
    isDiscountEnabled.value = true; 
    isDiscountReasonEnabled.value = true;   
  }  
  if(
    paymentMethodsForm.discount === 'refer_a_friend' ||
    paymentMethodsForm.discount === 'incentive_offset' ||
    paymentMethodsForm.discount === 'managerial_approval_discount'
    
    ){
    paymentMethodsForm.discount_reason = '';
    isDiscountReasonEnabled.value = true;
  }

  if(paymentMethodsForm.discount === 'employee_discount'){
    discountValue.value = totalPrice.value * (12.5 / 100).toFixed(2); // for car
  }

  if(paymentMethodsForm.discount === 'family_employee_discount'){
    discountValue.value = totalPrice.value * (7.5 / 100).toFixed(2); // for car
  }  
};

const calculateDueDates = () => {
  dueDateModels.value[1] = paymentMethodsForm.collectionDate;
  if(paymentMethodsForm.frequency === 'split_payments' || paymentMethodsForm.frequency === 'upfront'){
     //dueDateModels.value[1] = new Date();
    for (let i = 1; i <= paymentMethodsForm.payment_no; i++) { 
      dueDateModels.value[i] = paymentMethodsForm.collectionDate;
    }
  } else if(paymentMethodsForm.frequency === 'custom'){
    for (let i = 2; i <= paymentMethodsForm.payment_no; i++) { 
      dueDateModels.value[i] = '';
    }
  } else if(paymentMethodsForm.frequency === 'monthly'){
    dueDateModels.value[1] = paymentMethodsForm.collectionDate;
    for (let i = 2; i <= paymentMethodsForm.payment_no; i++) { 
      const nextDueDate = new Date(dueDateModels.value[i - 1]);
      nextDueDate.setMonth(nextDueDate.getMonth() + 1);
      nextDueDate.setDate(1);  // Set the day to 1st of the month
      dueDateModels.value[i] = nextDueDate;     
    }
  } else if(paymentMethodsForm.frequency === 'quarterly'){
    dueDateModels.value[1] = paymentMethodsForm.collectionDate;
    for (let i = 2; i <= paymentMethodsForm.payment_no; i++) {
      const nextDueDate = new Date(dueDateModels.value[i - 1]);
      if (i === 2) {
        nextDueDate.setDate(nextDueDate.getDate() + 90);
      } else if (i === 3) {
        nextDueDate.setDate(nextDueDate.getDate() + 180);
      } else if (i === 4) {
        nextDueDate.setDate(nextDueDate.getDate() + 270);
      }
      dueDateModels.value[i] = nextDueDate;
    }

  } else if(paymentMethodsForm.frequency === 'semi_annual'){
    dueDateModels.value[1] = paymentMethodsForm.collectionDate;
    const nextDueDate = new Date(dueDateModels.value[1]);
    nextDueDate.setDate(nextDueDate.getDate() + 180);
    dueDateModels.value[2] = nextDueDate;
  }

}

const calculatePaymentBreakup = () => {
  var perInstallmentPrice = parseFloat((totalAmount.value/paymentMethodsForm.payment_no).toFixed(2));
  
  for (let i = 1; i <= paymentMethodsForm.payment_no; i++) { 
    splitAmountModels.value[i] = perInstallmentPrice;
    if(i>1){
      paymentMethodsModels.value[i] = '';
    }    
  }
  calculateDueDates();
}

const  formatDate = (date) =>  {
    const parsedDate = new Date(date);
    const day = parsedDate.getDate().toString().padStart(2, '0');
    const month = (parsedDate.getMonth() + 1).toString().padStart(2, '0');
    const year = parsedDate.getFullYear();
    return `${day}-${month}-${year}`;
  }

const formatAmount = (amount) => {
  const parsedAmount = parseFloat(amount);
  if (isNaN(parsedAmount)) {
    return "Invalid Amount";
  }  
  const formattedAmount = parsedAmount.toLocaleString("en-US", {
    style: "decimal",
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
  return formattedAmount;
}

const handleFrequencyChange = () => {
  var resetPaymentMethod = false;
  totalPayments.value = [];
  for (let i = 1; i <= 12; i++) { // Append 7 more values to totalPayments
    totalPayments.value.push({ value: i.toString(), label: i.toString() });
  }
  calculatePaymentBreakup();
  isPaymentNoEnabled.value = false;
  if (paymentMethodsForm.frequency === 'monthly') {
    resetPaymentMethod = true;
    paymentMethodsForm.payment_no = '12';
    calculatePaymentBreakup();
  } else if (paymentMethodsForm.frequency === 'quarterly') {
    resetPaymentMethod = true;
    paymentMethodsForm.payment_no = '4';
    calculatePaymentBreakup();
  } else if (paymentMethodsForm.frequency === 'semi_annual') {
    resetPaymentMethod = true;
    paymentMethodsForm.payment_no = '2';
    calculatePaymentBreakup();
  } else if (paymentMethodsForm.frequency === 'split_payments') {
    isPaymentNoEnabled.value = true;
    paymentMethodsForm.payment_no = '1';
    totalPayments.value.splice(-7);    
  } else if (paymentMethodsForm.frequency === 'custom') {
    isPaymentNoEnabled.value = true;
    paymentMethodsForm.payment_no = '1';
  } else {
    paymentMethodsForm.payment_no = '1';
  }
  if (paymentMethodsModels.value[1]==='CC' && resetPaymentMethod){
    paymentMethodsModels.value[1] = 'BT';
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


const generateCCLink = async code => {
  try {
    const response = await axios.post('/generate-payment-link', {
      quoteId: props.quoteRequest.id,
      modelType: props.quoteType,
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
  paymentMethodsForm.payment_method = 'CHQ';
  paymentMethodsModels.value[1] = '';
  totalAmount.value = totalPrice.value;
  isPaymentCalculationError.value = false;

  if( props.quoteType === 'Health' || props.quoteType === 'Group Medical' || 
      props.quoteType === 'Life' || props.quoteType === 'Marine'){
    paymentMethodsForm.collection_type = 'insurer';
  } else {
    paymentMethodsForm.collection_type = 'broker';
  }

  paymentMethodsForm.amount = '';
  paymentMethodsForm.payment_reference = '';
  paymentMethodsForm.paymentCode = '';

  paymentMethodsForm.status = 'create';
  paymentMethodsForm.collectionDate = new Date();
  paymentMethodsForm
  createPaymentModal.value = true;
  paymentMethodsForm.payment_no = '1';
  paymentMethodsForm.frequency = 'upfront';
  paymentMethodsForm.discount = '';
  paymentMethodsForm.credit_approval = '';
  handleCollectionTypeChange();
  calculatePaymentBreakup();
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
  if (validatePaymentOption()) return;  

  let data = {
    captured_amount: paymentMethodsForm.amount,
    code: paymentMethodsForm.payment_method,
    modelType: props.quoteType,
    quote_id: props.quoteRequest.id,
    plan_id: props.quoteRequest.plan.id,
    insurance_provider_id: providerId.value,
    collection_type: paymentMethodsForm.collection_type,
    payment_methods: 'CHQ',
    reference: paymentMethodsForm.payment_reference,
    payment_no: paymentMethodsForm.payment_no,
    frequency: paymentMethodsForm.frequency,
    credit_approval: paymentMethodsForm.credit_approval,
    discount: paymentMethodsForm.discount,
    discount_reason: paymentMethodsForm.discount_reason,
    custom_reason: paymentMethodsForm.custom_reason,
    collection_date: paymentMethodsForm.collectionDate,
    notes: paymentMethodsForm.notes,
    total_amount: totalAmount.value, // after discount calculation
    total_price: totalPrice.value, 
    collection_date: paymentMethodsForm.collectionDate,
    discount_value: discountValue.value, // discount amount
    isInertia: true,
  };

  // Combine split_amount and payment_type into a single object
  data.split_payment_details = {
    split_amount: splitAmountModels.value,
    payment_type: paymentMethodsModels.value,
    due_date: dueDateModels.value,
    check_detail: checkDetailModels.value,
    document_detail: fileUploadModels.value,
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

const documentForm = useForm({
  quote_id: props.quote.id || null,
  quote_uuid: props.quote.code || null,
  quote_type_id: null,
  document_type_code: null,
  file: null,
});

const deleteDocument = (docName,count) => {
   router.post(
    `/documents/delete`,
    {
      docName: docName,
      quoteId: props.quote.id,
    },
    {
      preserveScroll: true,
      onFinish: () => {
        isFileError.value = true;
        fileErrorMessage.value = 'File deleted successfully';
        fileUploadModels.value[count] = fileUploadModels.value[count].filter(item => item.doc_name !== docName);
        console.log('azhar19=deleted');
      },
    },
  );
};

const uploadDocument = (doc, files, count) => {
  let url = '/quotes/car/documents/store'; 
  if (files.length == 0) return;

  if (!fileUploadModels.value[count]) {
    fileUploadModels.value[count] = [];
  }        

  if (fileUploadModels.value[count] && fileUploadModels.value[count].some(file => file.original_name === files[0].file.name)) {    
    isFileError.value = true;
    fileErrorMessage.value = props.paymentTooltipEnum.PAYMENT_ADD_DUPLICATE_FILES;
    console.log('File already exists');
    return false;
  }

  isUploading.value = true;
  documentForm
    .transform(data => ({
      ...data,
      quote_type_id: doc.quote_type_id,
      document_type_code: doc.code,
      folder_path: doc.folder_path,
      file: files[0].file,
    }))
    .post(url, {
      preserveScroll: true,
      preserveState: true,
      onError: errors => {
        documentForm.setError(errors.error);
		console.log("errors");
        console.log(errors);
        notification.error({
          title: 'File upload failed',
          position: 'top',
        });
      },
      onSuccess: (data) => {
        fileUploadModels.value[count].push(data.props.quoteDocuments[0]);
        console.log('azhar22='+JSON.stringify(fileUploadModels.value[count]));return;
        /*notification.success({
          title: 'File Uploaded',
          position: 'top',
        });*/
      },
      onFinish: () => {
        isUploading.value = false;
      },
    });
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
      <x-tooltip>
        <x-button
          size="sm"
          color="emerald"
          @click="addPaymentModal"
          :disabled="payments.length>0"
        >
          Add Manual Payment
        </x-button>
        <template #tooltip>
            <span>{{ paymentTooltipEnum.PAYMENT_MANAGEMENT_ADD_PAYMENT }}</span>
        </template>
      </x-tooltip>
      <!-- HAFEEZ TEMPORARY <x-button
        v-if="!permissionEnum.ApprovePayments && permissionEnum.PaymentsCreate && quoteRequest.plan"
        size="sm"
        color="orange"
        @click="addPaymentModal"
      >
        Add Payment
      </x-button> -->
    </div>
    <div class="vue3-easy-data-table tablefixed custom-height">
      <div class="vue3-easy-data-table__main fixed-header hoverable border-cell custom-height">
        <table>
          <thead class="vue3-easy-data-table__header">
            <tr>
              <th><x-tooltip>
                  Payment No
                  <template #tooltip>
                      <span class="custom-tooltip-content">{{ paymentTooltipEnum.PAYMENT_MANAGEMENT_PAYMENT_NO }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th>
                <x-tooltip>
                  Payment Ref ID
                  <template #tooltip>
                      <span class="custom-tooltip-content">{{ paymentTooltipEnum.PAYMENT_MANAGEMENT_PAYMENT_REF_ID }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th>
                <x-tooltip>
                  Collection Date
                  <template #tooltip>
                      <span class="custom-tooltip-content">{{ paymentTooltipEnum.PAYMENT_MANAGEMENT_COLLECTION_DATE }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th>
                <x-tooltip>
                  Due Date
                  <template #tooltip>
                      <span class="custom-tooltip-content">{{ paymentTooltipEnum.PAYMENT_MANAGEMENT_DUE_DATE }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th>
                <x-tooltip>
                  Payment Method
                  <template #tooltip>
                      <span class="custom-tooltip-content">{{ paymentTooltipEnum.PAYMENT_MANAGEMENT_PAYMENT_METHOD }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th>
                <x-tooltip>
                  Total Price
                  <template #tooltip>
                      <span class="custom-tooltip-content">{{ paymentTooltipEnum.PAYMENT_MANAGEMENT_TOTAL_PRICE }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th>
                <x-tooltip>
                  Discount Value
                  <template #tooltip>
                      <span class="custom-tooltip-content">{{ paymentTooltipEnum.PAYMENT_MANAGEMENT_DISCOUNT_VALUE }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th>
                <x-tooltip>
                  Total Amount
                  <template #tooltip>
                      <span class="custom-tooltip-content">{{ paymentTooltipEnum.PAYMENT_MANAGEMENT_TOTAL_AMOUNT }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th>
                <x-tooltip>
                  Collected Amount
                  <template #tooltip>
                      <span class="custom-tooltip-content">{{ paymentTooltipEnum.PAYMENT_MANAGEMENT_COLLECTED_AMOUNT }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th>
                <x-tooltip>
                  Payment Status
                  <template #tooltip>
                      <span class="custom-tooltip-content">{{ paymentTooltipEnum.PAYMENT_MANAGEMENT_PAYMENT_STATUS }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th>
                <x-tooltip>
                  Payment Allocation Status
                  <template #tooltip>
                      <span class="custom-tooltip-content">{{ paymentTooltipEnum.PAYMENT_MANAGEMENT_PAYMENT_ALLOCATION_STATUS }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th>
                <x-tooltip>
                  Action
                  <template #tooltip>
                      <span class="custom-tooltip-content">{{ paymentTooltipEnum.PAYMENT_MANAGEMENT_ACTION }}</span>
                  </template>
                </x-tooltip>
              </th>
            </tr>
          </thead>         
          
          <tbody class="vue3-easy-data-table__body">
            <tr v-for="item in payments" :key="item.code">
              <td>
                <span
                  class="expand-pointer"
                  @click="isExpandedSplitPayments=!isExpandedSplitPayments"
                >
                  {{ isExpandedSplitPayments ? '&and;' : '&or;' }}
                </span>
              </td>
              <td>{{ item.code }}</td>             
              <td>{{ formatDate(item.collection_date) }}</td>
              <td>{{ formatDate(item.collection_date) }}</td>
              <td>{{ item.payment_method.name }}</td>
              <td>{{ formatAmount(item.total_price) }}</td>
              <td>{{ formatAmount(item.discount_value) }}</td>
              <td>{{ formatAmount(item.total_amount) }}</td>
              <td>{{ formatAmount(item.captured_amount) }}</td>
              <td>{{ item.payment_status.text }}</td>
              <td>{{ item.payment_status_message }}</td>             
              <td>
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
                    <x-button v-if="item.payment_status_id != paymentStatusEnum.PAID && item.payment_status_id != paymentStatusEnum.CAPTURED && item.payment_status_id != paymentStatusEnum.AUTHORISED && !hasRole(rolesEnum.PA) && can(permissionEnum.PaymentsEdit)"  size="xs" color="primary" outlined @click="editPaymentModal(item)">
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
              </td>
            </tr>
            <template v-if="isExpandedSplitPayments">
            <tr v-for="splitPayment in payments[0].payment_splits" :key="splitPayment.id">
              <td>{{ splitPayment.sr_no }}</td>
              <td></td>
              <td>{{ formatDate(splitPayment.due_date) }}</td>
              <td>{{ formatDate(splitPayment.due_date) }}</td>
              <td>{{ splitPayment.payment_method.name }}</td>
              <td></td>
              <td></td>
              <td>{{ formatAmount(splitPayment.payment_amount) }}</td>
              <td></td>
              <td>{{ splitPayment.payment_status.text }}</td>
              <td></td>
              <td><x-button size="xs" color="primary" outlined >View</x-button></td>
            </tr>
          </template>
          </tbody>
        </table>
        <div v-if="!payments.length>0" data-v-32683533="" class="vue3-easy-data-table__message">No Available Data</div>
      </div>
    </div>
    <x-modal v-model="createPaymentModal" size="xl" show-close backdrop>
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
                  :value="totalPrice"
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
                class="custom-select"
                v-model="paymentMethodsForm.collection_type"
                :options="collectionTypes"
                :rules="[rules.isRequired]"
                @change="handleCollectionTypeChange"
                >
                <template v-for="option in collectionTypes" :key="option.value">
                    <option :value="option.value" :title="option.tooltip" >{{ option.label }}</option>                
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
                  :value="providerName"
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
                  class="custom-select"
                  v-model="paymentMethodsForm.frequency"
                  :options="frequencyTypes"
                  :rules="[rules.isRequired]"
                  @change="handleFrequencyChange"
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
                  :value="getPlanName"
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
                  class="custom-select"
                  v-model="paymentMethodsForm.payment_no"
                  :options="totalPayments"
                  :rules="[rules.isRequired]"
                  :disabled="!isPaymentNoEnabled"
                  @change="calculatePaymentBreakup(paymentMethodsForm.payment_no)"
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
                  value="NEW"
                  :disabled="true"
                />
            </x-field>
            <template #tooltip>
                <span>{{ paymentTooltipEnum.PAYMENT_STATUS }}</span>
            </template>
          </x-tooltip>
          
          <x-tooltip>
            <x-field label="CREDIT APPROVAL" class="w-full">              
              <div class="custom-dropdown">
                <span v-if="paymentMethodsForm.credit_approval!=''" class="close-icon"  @mousedown.stop="resetCreditApproval()">
                &#10006; 
              </span>
              <select
                  class="custom-select"
                  v-model="paymentMethodsForm.credit_approval"
                  :options="creditApprovalReasons"
                  @change="handleApprovalReasonChange"
                  >
                  <template v-for="option in creditApprovalReasons" :key="option.value">
                      <option :value="option.value" :title="option.tooltip">
                        {{ option.label }}                        
                      </option>                
                  </template>
              </select>              
            </div>
            </x-field>
            <template #tooltip>
                <span>{{ paymentTooltipEnum.CREDIT_APPROVAL }}</span>
            </template>
          </x-tooltip>          
          <x-field v-if="isCustomReasonEnabled" label="CUSTOM REASON" class="w-full">
            <x-input
                class="w-full"
                v-model="paymentMethodsForm.custom_reason"                  
              />
          </x-field>
          <x-tooltip>
            <x-field label="DISCOUNT APPLICABLE (DISCOUNT TYPE)" class="w-full">
              <div class="custom-dropdown">
                <span v-if="paymentMethodsForm.discount!=''" class="close-icon"  @mousedown.stop="resetDiscount()">
                &#10006; 
              </span> 
              <select
                  class="custom-select"
                  v-model="paymentMethodsForm.discount"
                  :options="discountTypes"
                  @change="handleDiscountChange"
                  >
                  <template v-for="option in discountTypes" :key="option.value">
                      <option :value="option.value" :title="option.tooltip">
                        {{ option.label }}                        
                      </option>                
                  </template>
              </select>
              </div>
            </x-field>
            <template #tooltip>
                <span>{{ paymentTooltipEnum.DISCOUNT_APPLICABLE }}</span>
            </template>
          </x-tooltip>

          <x-tooltip v-if="isDiscountReasonEnabled">
            <x-field label="DISCOUNT REASON" class="w-full" required>              
              <select
                  class="custom-select"
                  v-model="paymentMethodsForm.discount_reason"
                  :options="discountReasons"
                  :rules="[rules.isRequired]"                  
                  >
                  <template v-for="option in discountReasons" :key="option.value">
                      <option :value="option.value" :title="option.tooltip">{{ option.label }}</option>                
                  </template>
              </select>
            </x-field>
            <template #tooltip>
                <span>{{ paymentTooltipEnum.PAYMENT_NO }}</span>
            </template>
          </x-tooltip>

          <template v-if="isDiscountEnabled">
            <x-tooltip>
              <x-field label="DISCOUNT VALUE" class="w-full">
                <x-input
                    class="w-full"
                    v-model="discountValue"
                    name="discount_value"
                    :rules="[rules.amount]"
                    @keyup="calculateTotalAmount()"                    
                />                
                <p v-if="discountError" class="text-sm text-red-500 dark:text-red-400 mt-1">{{ discountError }}</p>
              </x-field>
              
              <template #tooltip>
                  <span>{{ paymentTooltipEnum.PAYMENT_STATUS }}</span>
              </template>
            </x-tooltip>
            
            <x-tooltip>
              <x-field label="TOTAL AMOUNT" class="w-full">
                <x-input
                    class="w-full"
                    :value="totalAmount"
                    :disabled="true"
                  />
              </x-field>
              <template #tooltip>
                  <span>{{ paymentTooltipEnum.PAYMENT_STATUS }}</span>
              </template>
            </x-tooltip>
          </template>


          
        </div>
        <x-divider class="mb-4 mt-10" />
        <x-alert
            v-if="isPaymentCalculationError"
						color="error"
						class="mb-5"						
					>
					The system has detected a discrepancy. Before you hit 'Add Manual Payment,' please check the each payment transaction. If you spot any discrepancies, make the necessary adjustments. Once everything lines up, you're good to proceed.	
				</x-alert>
        <x-alert
            v-if="isFileError"
						color="error"
						class="mb-5"						
					>
					{{ fileErrorMessage }}
				</x-alert>
        <div class="w-full grid">
          <!-- Header -->
          <div class="flex w-full">
            <div class="w-1/6 px-2 text-center">
              <x-tooltip>
                <span class="text-sm text-primary-800 font-semibold">
                  PAYMENT NO *
                </span>
                <template #tooltip>
                  <span>{{ paymentTooltipEnum.PAYMENT_NO_2 }}</span>
                </template>
              </x-tooltip>              
            </div>
            <div class="w-1/5 px-2">
              <x-tooltip>
                <span class="text-sm text-primary-800 font-semibold">
                  PAYMENT METHOD *
                </span>
                <template #tooltip>
                  <span>{{ paymentTooltipEnum.PAYMENT_METHOD }}</span>
                </template>
              </x-tooltip>
            </div>
            <div class="w-1/5 px-2">
              <x-tooltip>
                <span class="text-sm text-primary-800 font-semibold">
                  TOTAL AMOUNT *
                </span>
                <template #tooltip>
                  <span>{{ paymentTooltipEnum.TOTAL_AMOUNT }}</span>
                </template>
              </x-tooltip>
            </div>
            <div class="w-1/5 px-2">
              <x-tooltip>
                <span class="text-sm text-primary-800 font-semibold">
                  DUE DATE *
                </span>
                <template #tooltip>
                  <span>{{ paymentTooltipEnum.DUE_DATE }}</span>
                </template>
              </x-tooltip>
            </div>
            <div class="w-1/5 px-2">
              <x-tooltip>
                <span class="text-sm text-primary-800 font-semibold">
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
              <div class="w-1/6 px-2 text-center">{{ count }}</div>
              <div class="w-1/5 px-2">
                <select
                  class="w-full custom-select"
                  v-model="paymentMethodsModels[count]"
                  :options="handlePaymentTypes(count)"
                  @change="handlePaymentOptions()"
                >
                  <!-- Use the title attribute to set the tooltip text -->
                  <option
                    v-for="option in handlePaymentTypes(count)"
                    :key="option.value"
                    :value="option.value"
                    :title="option.tooltip"
                    :disabled="option.value === 'CC' && isCCDisabled"
                  >{{ option.label }}</option>
                </select>
                <p v-if="isPaymentMetodNotSelected[count]" class="text-sm text-red-500 dark:text-red-400 mt-1">This field is required</p>
                <x-input
                  class="w-full mt-2"
                  v-if = "isCheckDetailsEnabled && (count==1 || paymentMethodsModels[count] === 'PDC')"
                  v-model="checkDetailModels[count]"
                  placeholder="Cheque Details"         
                />
              </div>
              <div class="w-1/5 px-2">
                <x-input
                  v-model="splitAmountModels[count]"
                  class="w-full"
                  :rules="[rules.isRequired]"              
                />
              </div>
              <div class="w-1/5 px-2">
                <DatePicker
                  v-model="dueDateModels[count]"
                  class="w-full"
                  :rules="[rules.isRequired]"              
                />  
              </div>
              <div class="w-1/5 px-2 mb-2">
                <Dropzone
                  :id="paymentDocument.id"
                  :accept="paymentDocument.accepted_files"
                  :max-files="paymentDocument.max_files"
                  :max-size="paymentDocument.max_size"
                  :loading="documentForm.processing"
                  @change="uploadDocument(paymentDocument, $event, count)"
                />
                {{ console.log('azhar333='+JSON.stringify(fileUploadModels[count]))  }}
                <div v-for="fileData in fileUploadModels[count]" :key="fileData.id">
                  <span style="display: flex; align-items: center;">
                    <a
                      :key="fileData.id"
                      :href="storageUrl + fileData.doc_url"
                      target="_blank"
                      class="block px-2 py-1 border rounded mt-1 text-xs hover:text-primary-600 truncate"
                      style="flex: 1; text-decoration: none;"
                    >
                      {{ fileData.original_name }}
                    </a>
                    
                    <span
                      class="delete-pointer"
                      @click="deleteDocument(fileData.doc_name, count)"
                    >
                    <x-tooltip>
                      x
                      <template #tooltip>
                          <span>{{ paymentTooltipEnum.PAYMENT_ADD_DELETE_DOCUMENT }}</span>
                      </template>
                    </x-tooltip>
                    </span>


                  </span>
                </div>
              </div>
            </div>
          </div>

          
      </div>
  
      <x-divider class="mb-4 mt-1" />

      <div class="w-full grid">
        <x-field label="NOTES">
          <x-input
            class="w-full"
            v-model="paymentMethodsForm.notes"          
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
.custom-select {
  /* Example styles */
  border: 2px solid #e5e7eb;
  padding: 7px;
  border-radius: 5px;
  background-color: #fff;
  color: #333;
  font-size: 16px;
  width: 100%;  
  /* You can customize these styles to your liking */
}
.custom-dropdown {
  position: relative;
}
.close-icon {
  position: absolute;
  top: 8px;
  left: 0;
  padding-left: 445px;
  cursor: pointer;
  color: #333; /* Customize the close icon color */
}

.delete-pointer {
  cursor: pointer;
  padding-left: 5px;
  font-weight: bold;
}

.expand-pointer {
  cursor: pointer;
    font-size: 20px;
    padding-left: 25px;
    font-weight: bold;
    color: #1d83bc;
}

.custom-tooltip-content {
  max-width: 200px; /* Adjust the max-width as needed */
  white-space: normal; /* Allow the text to wrap */
  z-index: 999;
  position: relative;
  font-size: 10px;
  }
  .custom-height {
    min-height: 160px;
  }
</style>
