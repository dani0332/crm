<script setup>
const notification = useNotifications('toast');
const page = usePage();

const permissionEnum = page.props.permissionsEnum;
const rolesEnum = page.props.rolesEnum;

const hasRole = role => useHasRole(role);
const can = permission => useCan(permission);
const hasAnyRole = roles => useHasAnyRole(roles);

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
const isCheckDetailsEnabled = ref([]);
const isExpandedSplitPayments = ref(false);
const isPaymentCalculationError = ref(false);
const isFieldReadonly = ref(false);
const isUploading = ref(false);
const isFileError = ref(false);
const isViewEnabled = ref(false);
const fileErrorMessage = ref('');
const splitPaymentNo = ref(0);
const isApproveClicked = ref(false);
const isDeclineClicked = ref(false);
const isDeclineCustomReason = ref(false);
const isApprovePaymentError = ref(false);
const showDiscountOptions = ref(true);
const isApprovedDocumentNotUploaded = ref(false);
const approvedDocument = ref('');

const discountValue = ref(0); // Initial discount value
const totalPrice = ref(props.quoteRequest.premium); // Initial total price
const paymentTypesFiltered = ref([]);

const isPaymentMetodNotSelected = ref([]);
const isDocumentNotUploaded = ref([]);
const paymentMethodsModels = ref([]);
const splitAmountModels = ref([]);
const dueDateModels = ref([]);
const fileUploadModels = ref([]);
const checkDetailModels = ref([]);
const readOnlyPayments = ref([]);
const splitPaymentRecord = ref([]);
const filesTest = ref([]);
const currentFileIndex = ref(0);
const zoomLevel = ref(1);
const isGalleryModelOpen = ref(false);
const isDiscountReasonError = ref(false);

const totalAmount = ref(props.quoteRequest.premium); // Initial total price

const paidAmountSum = ref(0);
const totalPaidAmount = ref(0);
const masterPaymentStatus = ref('NEW');

const calculateTotalAmount = () => {
  const discount = discountValue.value;
  console.log('azharDISCOUNT='+paymentMethodsForm.discount+discount);
  if (discount > 50 && paymentMethodsForm.discount === 'refer_a_friend') {
    totalAmount.value = totalPrice.value;
  } else {
    totalAmount.value = totalPrice.value - discount;    
  }
  calculatePaymentBreakup();  
}

const { copy, copied } = useClipboard();
const onCopyPaymentLink = (paymentLink,paymentStatus) => {
  if (paymentStatus==props.paymentStatusEnum.PAID){
    notification.error({
        title: 'Payment already \'Paid\'; button deactivated for this transaction',
        position: 'top',
      });
  } else {
    copy(paymentLink);
    if (copied)
      notification.success({
        title: 'Link copied to clipboard',
        position: 'top',
      });
  }
};

   
  const openModal = () =>{
    isGalleryModelOpen.value = true;
  };
  const nextFile = () => {
    if (currentFileIndex.value < filesTest.value.length - 1) {
      currentFileIndex.value++;
    }      
  };

  const zoomIn = () => {
    zoomLevel.value = Math.min(zoomLevel.value + 0.25, 3); 
  };

  const zoomOut = () => {
    zoomLevel.value = Math.max(zoomLevel.value - 0.25, 0.25); 
  };

  const openInnerModal = (fileId) => {
    filesTest.value = fileUploadModels.value.flat();

    currentFileIndex.value = filesTest.value.findIndex(item => item.id === fileId);
    isGalleryModelOpen.value = true;
  };

  const previousFile = () => {
    if (currentFileIndex.value > 0) {
      currentFileIndex.value--;
    }      
  };

  const currentFile = computed(() => {
    return filesTest.value[currentFileIndex.value];
  });

  const closeInnerModal = () => {
    isGalleryModelOpen.value = false;      
  };

  const hasNextFile = computed(() => {
    return currentFileIndex.value < filesTest.value.length - 1;
  });

  const hasPreviousFile = computed(() => {
    return currentFileIndex.value > 0;
  });

const discountError = computed(() => {
  const regex = /^\d+(\.\d{1,2})?$/;
  if (!regex.test(discountValue.value)) {
    return 'Discount must be a valid number';
  }    
  // Check if the discount exceeds 50 and return an error message
  if (discountValue.value > 50 && paymentMethodsForm.discount === 'refer_a_friend') {
    return 'Discount should not exceed 50 AED';
  }
  if(discountValue.value > totalAmount.value){
    totalAmount.value = totalPrice.value;
    calculatePaymentBreakup();
    return 'Discount should not exceed total amount';
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

const handleDeclinedChange = () => {
  isDeclineClicked.value = true;
  isApproveClicked.value = false;
  return true;  
};

const calculateTotalSplitAmount = () => {
  let totalSplitAmount = 0;
  for (let i = 1; i <= paymentMethodsForm.payment_no; i++) {
    totalSplitAmount += parseFloat(splitAmountModels.value[i]);
  }
  return totalSplitAmount;
};

const validatePaymentOption = () => {  
      var totalSplitAmount = 0;
      var issueFound = false;
      isPaymentCalculationError.value = false;
      for (let i = 1; i <= paymentMethodsForm.payment_no; i++) { 
        totalSplitAmount = (parseFloat(totalSplitAmount) + parseFloat(splitAmountModels.value[i]));       
        const validationResult = rules.notEmptyOrZero(paymentMethodsModels.value[i]);
        isPaymentMetodNotSelected.value[i] = false;
        if (validationResult !== true) {          
          isPaymentMetodNotSelected.value[i] = true;        
          issueFound = true;
        }
      }
      console.log('azhar11='+issueFound);
      if(totalSplitAmount.toFixed(2) !== parseFloat(totalAmount.value).toFixed(2)){
        isPaymentCalculationError.value = true;
        issueFound = true;
      }
      console.log('azhar22='+issueFound);
      for (let i = 1; i <= paymentMethodsForm.payment_no; i++) { 
        isDocumentNotUploaded.value[i] = false;

        console.log('azharLEN='+fileUploadModels.value[i]);
        if ( (
          paymentMethodsModels.value[i]=='BT' || paymentMethodsModels.value[i]=='CHQ' || 
          paymentMethodsModels.value[i]=='CA' || paymentMethodsModels.value[i]=='PDC' || 
          paymentMethodsForm.discount !== ''   
          ) 
        && (fileUploadModels.value[i]===undefined || fileUploadModels.value[i].length===0)       
        ) {
          isDocumentNotUploaded.value[i] = true;
          issueFound = true;
        }
      }

      if(
      paymentMethodsForm.discount === 'refer_a_friend' ||
      paymentMethodsForm.discount === 'incentive_offset' ||
      paymentMethodsForm.discount === 'managerial_approval_discount'      
      ){
        if(paymentMethodsForm.discount_reason === ''){
          isDiscountReasonEnabled.value = true;
          issueFound = true;
          isDiscountReasonError.value = true;
        } else {
          isDiscountReasonEnabled.value = false;
          isDiscountReasonError.value = false;
        }      
      }



      console.log('azhar33='+issueFound);
    if(issueFound){
      return true;
    }
    return false;
  };


const totalPayments = ref([
  { value: '1', label: '1'},
 ]);

 const paymentTypes = ref(props.paymentMethods.filter(item => !['CR_FAYAZ', 'CR_HITESH', 'CR_MAHESH', 'CR'].includes(item.value)));
 paymentTypes.value.unshift({ value: '', label: 'Select Payment' });
 

const getPaymentTypeLabel = (code) => {
  const paymentType = paymentTypes.value.find(item => item.value === code);
  if (paymentType) {
    return paymentType.label;
  }
  return '';
};

const collectionTypes = [
  { value: 'broker', label: 'Broker' , tooltip: props.paymentTooltipEnum.COLLECTOR_LIST_BROKER},
  { value: 'insurer', label: 'Insurer' , tooltip: props.paymentTooltipEnum.COLLECTOR_LIST_INSURER},
];

const frequencyTypes = [
  { value: 'upfront', label: 'Upfront', tooltip: props.paymentTooltipEnum.FREQUENCY_LIST_UPFRONT },
  { value: 'monthly', label: 'Monthly', tooltip: props.paymentTooltipEnum.FREQUENCY_LIST_MONTHLY },
  { value: 'quarterly', label: 'Quarterly', tooltip: props.paymentTooltipEnum.FREQUENCY_LIST_QUARTERLY },
  { value: 'semi_annual', label: 'Semi Annual', tooltip: props.paymentTooltipEnum.FREQUENCY_LIST_SEMI_ANNUAL },
  { value: 'split_payments', label: 'Split Payments', tooltip: props.paymentTooltipEnum.FREQUENCY_LIST_SPLIT_PAYMENTS },
  { value: 'custom', label: 'Custom', tooltip: props.paymentTooltipEnum.FREQUENCY_LIST_CUSTOM },
];

const declinedReasons = [
  { value: '1', label: props.paymentTooltipEnum.DECLINED_REASON_1},
  { value: '2', label: props.paymentTooltipEnum.DECLINED_REASON_2},
  { value: '3', label: props.paymentTooltipEnum.DECLINED_REASON_3},
  { value: '4', label: props.paymentTooltipEnum.DECLINED_REASON_4},
  { value: '5', label: props.paymentTooltipEnum.DECLINED_REASON_5},
  { value: '6', label: props.paymentTooltipEnum.DECLINED_REASON_6},
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
  { value: '', label: ''},
  { value: 'promotional_campaign_discount', label: 'Promotional campaign discount', tooltip: props.paymentTooltipEnum.DISCOUNT_REASON_LIST_PROMOTIONAL },
  { value: 'loyalty_reward_discount', label: 'Loyalty reward discount', tooltip: props.paymentTooltipEnum.DISCOUNT_REASON_LIST_LOYALTY },
  { value: 'competitive_pricing_discount', label: 'Competitive pricing discount', tooltip: props.paymentTooltipEnum.DISCOUNT_REASON_LIST_COMPETITIVE },
  { value: 'custom_discount_reason', label: 'Custom discount reason', tooltip: props.paymentTooltipEnum.DISCOUNT_REASON_LIST_CUSTOM_REASON },  
];

const handleDeclinedReasonChange = () => {
  if(paymentMethodsForm.declined_reason === '6'){
    isDeclineCustomReason.value = true;
  }else{
    isDeclineCustomReason.value = false;
  }
};

const handlePaymentOptions = (count) => {
  isPaymentMetodNotSelected.value[count] = false;
  if( paymentMethodsModels.value[count] === 'CHQ' || paymentMethodsModels.value[count] === 'PDC' ) {
    isCheckDetailsEnabled.value[count] = true;
  } else {
    isCheckDetailsEnabled.value[count] = false;
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
  //customize payment method based on collection type
  if(paymentMethodsForm.credit_approval !== ''){
    paymentTypesFiltered.value = paymentTypes.value;
    paymentTypesFiltered.value = paymentTypesFiltered.value.filter(item => !['IN_PL', 'PPR', 'MP', 'PP'].includes(item.value));  
    paymentMethodsModels.value[1] = 'CA';
  } else {
    handleCollectionTypeChange();
  }
};

const resetCreditApproval = () => {
  paymentMethodsForm.credit_approval = '';
  isCustomReasonEnabled.value = false;  
  handleApprovalReasonChange();
};

const resetDiscount = () => {
  isDiscountEnabled.value = false;
  isDiscountReasonEnabled.value = false;
  paymentMethodsForm.discount = '';
  totalAmount.value = totalPrice.value;
  discountValue.value = 0;
  handleDiscountChange();
  calculateTotalAmount();
};

const handleDiscountChange = () => {

  if( paymentMethodsForm.status === 'create' ){
    discountValue.value = 0;
    paymentMethodsForm.discount_reason = '';
  }

  isDiscountReasonEnabled.value = false;
  if(paymentMethodsForm.discount === '' || paymentMethodsForm.discount === 'none'){
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
    isDiscountReasonEnabled.value = true;
  } else {
    isDiscountReasonEnabled.value = false;
  }

  if(paymentMethodsForm.discount === 'employee_discount'){
    discountValue.value = (totalPrice.value * (12.5 / 100)).toFixed(2); // for car
  }

  if(paymentMethodsForm.discount === 'family_employee_discount'){
    discountValue.value = (totalPrice.value * (7.5 / 100)).toFixed(2); // for car
  }
  calculateTotalAmount();
};

const calculateDueDates = () => {
  dueDateModels.value[1] = paymentMethodsForm.collection_date;
  if(paymentMethodsForm.frequency === 'split_payments' || paymentMethodsForm.frequency === 'upfront'){
     //dueDateModels.value[1] = new Date();
    for (let i = 1; i <= paymentMethodsForm.payment_no; i++) { 
      
      if(readOnlyPayments.value[i]!=undefined && readOnlyPayments.value[i]===true) {
        continue;
      }      
      dueDateModels.value[i] = paymentMethodsForm.collection_date;
    
    
    }
  } else if(paymentMethodsForm.frequency === 'custom'){
    for (let i = 2; i <= paymentMethodsForm.payment_no; i++) { 
      dueDateModels.value[i] = '';
    }
  } else if(paymentMethodsForm.frequency === 'monthly'){
    dueDateModels.value[1] = paymentMethodsForm.collection_date;
    for (let i = 2; i <= paymentMethodsForm.payment_no; i++) { 
      const nextDueDate = new Date(dueDateModels.value[i - 1]);
      nextDueDate.setMonth(nextDueDate.getMonth() + 1);
      nextDueDate.setDate(1);  // Set the day to 1st of the month
      dueDateModels.value[i] = nextDueDate;     
    }
  } else if(paymentMethodsForm.frequency === 'quarterly'){
    dueDateModels.value[1] = paymentMethodsForm.collection_date;
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
    dueDateModels.value[1] = paymentMethodsForm.collection_date;
    const nextDueDate = new Date(dueDateModels.value[1]);
    nextDueDate.setDate(nextDueDate.getDate() + 180);
    dueDateModels.value[2] = nextDueDate;
  }

}

const calculatePaymentBreakup = () => {  

  var perInstallmentPrice = parseFloat(((totalAmount.value-paidAmountSum.value)/(paymentMethodsForm.payment_no-totalPaidAmount.value)).toFixed(2));
  console.log('azhar9991='+perInstallmentPrice);
  if ( paymentMethodsForm.status === 'edit') {
    splitAmountModels.value = [];
  }

  for (let i = 1; i <= paymentMethodsForm.payment_no; i++) { 
    if(readOnlyPayments.value[i]!=undefined && readOnlyPayments.value[i]===true) {
      continue;
    }
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

  function formatString(input) {
    // Replace underscores with spaces
    let formattedString = input.replace(/_/g, ' ');
    return formattedString;
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

const handleFrequencyChange = (noPaymentUpdate=true) => {
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
    if(noPaymentUpdate){
      paymentMethodsForm.payment_no = '1';
    }
    //paymentMethodsForm.payment_no = '1';
    totalPayments.value.splice(-7);    
  } else if (paymentMethodsForm.frequency === 'custom') {
    isPaymentNoEnabled.value = true;
    paymentMethodsForm.payment_no = '1';
  } else {
    paymentMethodsForm.payment_no = '1';
    calculatePaymentBreakup();
  }
  if (paymentMethodsModels.value[1]==='CC' && resetPaymentMethod){
    paymentMethodsModels.value[1] = 'BT';
  }    
};

// Define a computed property to determine if 'insurer' should be disabled
const isCCDisabled = computed(() => {
  return;
  /*return (
    paymentMethodsForm.frequency === 'monthly' || 
    paymentMethodsForm.frequency === 'quarterly' || 
    paymentMethodsForm.frequency === 'semi_annual'
  
    );*/
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

  if ( props.payments.length>0 ) {
      notification.error({
        title: 'Payment already added; click \'Edit\' for changes.',
        position: 'top',
      });
      return;   
  }
  paymentMethodsForm.reset();
  paymentMethodsForm.payment_method = 'CHQ';
  paymentMethodsModels.value = []; 
  splitAmountModels.value = []; 
  dueDateModels.value = []; 
  fileUploadModels.value = []; 
  checkDetailModels.value = [];   
  
  isPaymentCalculationError.value = false;
  showDiscountOptions.value = true;
  isDiscountReasonError.value = false;

  if (totalPrice.value > 0 && props.quoteRequest.plan) {
    totalAmount.value = totalPrice.value;
  } else {
    let errorMsg = 'Please select a plan.';
    if( !(totalPrice.value>0) ) {
      errorMsg = 'Please update the Total Price in the Plan Details section.';
    }    
    notification.error({
      title: errorMsg,
      position: 'top',
    });
    return;
  }

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
  paymentMethodsForm.collection_date = new Date();
  paymentMethodsForm
  createPaymentModal.value = true;
  paymentMethodsForm.payment_no = '1';
  paymentMethodsForm.frequency = 'upfront';
  paymentMethodsForm.discount = '';
  paymentMethodsForm.credit_approval = '';
  handleCollectionTypeChange();
  calculatePaymentBreakup();
};

const editPaymentModal = (payment,split_payment_id,sr_no) => {
  paymentMethodsForm.reset();
  splitPaymentNo.value = 0;
  isFieldReadonly.value = false;
  isViewEnabled.value = false;
  paymentMethodsForm.splitPaymentId = 0;
  paymentMethodsForm.status = 'edit';
  paymentMethodsForm.declined_reason = '1';
  isPaymentCalculationError.value = false;
  isApproveClicked.value = false;
  isFileError.value = false;
  isApprovePaymentError.value = false;
  totalPaidAmount.value = 0;
  isDeclineClicked.value = false;
  isDiscountReasonError.value = false;

  isApprovedDocumentNotUploaded.value = false;
  approvedDocument.value = '';
  if(sr_no>0){
    splitPaymentNo.value = sr_no;
    isFieldReadonly.value = true;
    isViewEnabled.value = true;
    paymentMethodsForm.splitPaymentId = split_payment_id;
    paymentMethodsForm.status = 'view';
    paymentMethodsForm.collection_amount = '';
    paymentMethodsForm.bank_reference_number = '';
    splitPaymentRecord.value = payment.payment_splits.find(item => item.sr_no === sr_no);
  }  

  //paymentMethodsForm.masterPaymentStatus = payment.
  masterPaymentStatus.value = payment.payment_status.text;

  paymentMethodsForm.paymentCode = payment.code;
  paymentMethodsForm.insurance_provider_id= payment.insurance_provider_id;
  paymentMethodsForm.collection_type= payment.collection_type;  
  paymentMethodsForm.payment_no= payment.total_payments;
  paymentMethodsForm.frequency= payment.frequency;
  paymentMethodsForm.credit_approval= payment.credit_approval !== null ? payment.credit_approval : '';
  
  showDiscountOptions.value = true;
  paymentMethodsForm.discount = payment.discount_type !== null ? payment.discount_type : '';    
  paymentMethodsForm.discount_reason = payment.discount_reason !== null ? payment.discount_reason : '';    
  
  /*
  if (payment.discount_type=='' || payment.discount_type==null) {
    paymentMethodsForm.discount= payment.discount_type;
  } else {
    paymentMethodsForm.discount= payment.discount_type;    
  }*/
 
  paymentMethodsForm.custom_reason= payment.custom_reason;  
  paymentMethodsForm.notes= payment.notes;
  paymentMethodsForm.total_amount= payment.total_amount; // after discount calculation
  paymentMethodsForm.total_price= payment.total_price;
  paymentMethodsForm.collection_date= payment.collection_date;
  discountValue.value = payment.discount_value; // discount amount
  console.log('azhar20='+payment.total_payments);
  //console.log('azha221='+JSON.stringify(paymentMethodsForm));
  handleCollectionTypeChange();
  handleFrequencyChange(false);
  handleApprovalReasonChange();
  handleDiscountChange();
  calculateTotalAmount();
  
  console.log('azhar200='+JSON.stringify(payment.payment_splits));

  var isAnyPaid = false;
  for(let i=1; i<=payment.total_payments; i++){
    if(payment.payment_splits[i-1].payment_status_id===props.paymentStatusEnum.PAID) { 
      readOnlyPayments.value[i] = true;
      totalPaidAmount.value++;
      paidAmountSum.value = parseFloat(paidAmountSum.value) + parseFloat(payment.payment_splits[i-1].payment_amount);
      isAnyPaid = true;
    } else {
      readOnlyPayments.value[i] = false;
    } 
   
    fileUploadModels.value[i] = [];
    paymentMethodsModels.value[i] = payment.payment_splits[i-1].payment_method.code;
    splitAmountModels.value[i] = payment.payment_splits[i-1].payment_amount;
    dueDateModels.value[i] = payment.payment_splits[i-1].due_date;

    if(payment.payment_splits[i-1].payment_method.code === 'CHQ' || payment.payment_splits[i-1].payment_method.code === 'PDC'){
      isCheckDetailsEnabled.value[i] = true;
      checkDetailModels.value[i] = payment.payment_splits[i-1].check_detail;
    }
    if(payment.payment_splits[i-1].documents.length>0){
      for(let doc in payment.payment_splits[i-1].documents){
        if (!fileUploadModels.value[i]) {
          fileUploadModels.value[i] = [];
        }            
        fileUploadModels.value[i].push(payment.payment_splits[i-1].documents[doc]);        
        
      }
    }
  }
  
  if (paymentMethodsForm.status == 'edit') {
    if ( isAnyPaid ) {
      isFieldReadonly.value = true;    
    } else {
      isFieldReadonly.value = false;      
    }
  }
  console.log('azhar523='+paymentMethodsModels.value);
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

const validateViewPayment = () => {  
  if (parseFloat(splitAmountModels.value[splitPaymentNo.value]).toFixed(2) > parseFloat(paymentMethodsForm.collection_amount).toFixed(2)) {
     isApprovePaymentError.value = true;
    return true;
  }
  return false;
}

const validateApprovedDocument = () => {  
  if (paymentMethodsForm.collection_type==='insurer') {      
      console.log('sssazhar1='+approvedDocument.value);
      if (approvedDocument.value==='' ) {
        console.log('sssazhar122='+approvedDocument.value);
        isApprovedDocumentNotUploaded.value = true;
        return true;
      } else {
        isApprovedDocumentNotUploaded.value = false;
      }      
    }
    return false;
}

const addPayment = isValid => {
  
  if (!isValid) return;  
  if (paymentMethodsForm.status === 'view' && isApproveClicked.value) {
    if (validateViewPayment()) return;
    if (validateApprovedDocument()) return;
  } else {
    if (validatePaymentOption()) return;  
  }   

  //define main payment method
  let mainPaymentMethod = 'CR';
  if(paymentMethodsForm.credit_approval!=='' && paymentMethodsForm.credit_approval!==null){
    mainPaymentMethod = 'CA';
  } else if(paymentMethodsForm.frequency === 'custom' || paymentMethodsForm.frequency === 'monthly'
    || paymentMethodsForm.frequency === 'quarterly' || paymentMethodsForm.frequency === 'semi_annual'
   ){
    mainPaymentMethod = 'PP';
  } else if(paymentMethodsForm.frequency === 'split_payments'){
    mainPaymentMethod = 'MP';
  } else if(paymentMethodsForm.frequency === 'upfront'){
    mainPaymentMethod = 'CR';
  }

  let data = {
    captured_amount: paymentMethodsForm.amount,
    code: paymentMethodsForm.payment_method,
    modelType: props.quoteType,
    quote_id: props.quoteRequest.id,
    plan_id: props.quoteRequest.plan.id,
    insurance_provider_id: providerId.value,
    collection_type: paymentMethodsForm.collection_type,
    payment_methods: mainPaymentMethod,
    reference: paymentMethodsForm.payment_reference,
    payment_no: paymentMethodsForm.payment_no,
    frequency: paymentMethodsForm.frequency,
    credit_approval: paymentMethodsForm.credit_approval,
    discount: paymentMethodsForm.discount,
    discount_reason: paymentMethodsForm.discount_reason,
    custom_reason: paymentMethodsForm.custom_reason,
    collection_date: paymentMethodsForm.collection_date,
    notes: paymentMethodsForm.notes,
    total_amount: totalAmount.value, // after discount calculation
    total_price: totalPrice.value, 
    collection_date: paymentMethodsForm.collection_date,
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

  if (paymentMethodsForm.status === 'view') {    
    let viewData = {
      modelType: props.quoteType,
      quote_id: props.quoteRequest.id,
      plan_id: props.quoteRequest.plan.id,
      collection_amount: paymentMethodsForm.collection_amount,
      bank_reference_number: paymentMethodsForm.bank_reference_number,
      splitPaymentId: paymentMethodsForm.splitPaymentId,
      is_declined: isDeclineClicked.value,
      is_approved: isApproveClicked.value,
      declined_reason: paymentMethodsForm.declined_reason,
      declined_custom_reason: paymentMethodsForm.declined_custom_reason,
    };
    paymentMethodsForm
      .transform(data => viewData)
      .post('/payments/Car/split-update', {
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

  if (paymentMethodsForm.status === 'edit') {
    if(totalPaidAmount.value == paymentMethodsForm.payment_no) {
      notification.error({
            title: 'No further actions allowed to paid payments',
            position: 'top',
          });
      return false;
    };
    let editData = {
      ...data,
      paymentCode: paymentMethodsForm.paymentCode,
    };
    paymentMethodsForm
      .transform(data => editData)
      .post('/payments/Car/update', {
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
    .post('/payments/Car/store', {
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

  let allUploadedDocuments = fileUploadModels.value.flat();
  if (allUploadedDocuments && allUploadedDocuments.some(file => file.original_name === files[0].file.name)) {    
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
        }); return;
      },
      onSuccess: (data) => {
        
        if (paymentMethodsForm.status === 'view') {
          isApprovedDocumentNotUploaded.value = false;
          approvedDocument.value = data.props.quoteDocuments[0];
          console.log('approveddoc='+JSON.stringify(approvedDocument.value));        
          return;
        } 
        
        isDocumentNotUploaded.value[count] = false;
        fileUploadModels.value[count].push(data.props.quoteDocuments[0]);

        console.log('azhar9999='+JSON.stringify(fileUploadModels.value));        
        return;        
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
      <h3 class="font-semibold text-primary-800 text-lg">Manage Payments</h3>
      <template v-if="payments.length>0">
        <x-button
            v-if="hasRole(rolesEnum.CarAdvisor)"
            size="sm"
            color="emerald"
            @click="addPaymentModal"          
          >
            Add Manual Payment
          </x-button>
      </template>
      <template v-else>
        <x-tooltip>
          <x-button
            v-if="hasRole(rolesEnum.CarAdvisor)"
            size="sm"
            color="emerald"
            @click="addPaymentModal"          
          >
            Add Manual Payment
          </x-button>
          <template #tooltip>
              <span>{{ paymentTooltipEnum.PAYMENT_MANAGEMENT_ADD_PAYMENT }}</span>
          </template>
        </x-tooltip>  
      </template>
    </div>
    <div class="vue3-easy-data-table tablefixed custom-height">
      <div class="vue3-easy-data-table__main fixed-header hoverable border-cell custom-height">
        <table>
          <thead class="vue3-easy-data-table__header">
            <tr>
              <th style="text-align: center; width: 80px;" ><x-tooltip>
                  Payment No
                  <template #tooltip>
                      <span class="custom-tooltip-content">{{ paymentTooltipEnum.PAYMENT_MANAGEMENT_PAYMENT_NO }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th class="inner-th-class">
                <x-tooltip>
                  Payment Ref ID
                  <template #tooltip>
                      <span class="custom-tooltip-content">{{ paymentTooltipEnum.PAYMENT_MANAGEMENT_PAYMENT_REF_ID }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th class="inner-th-class">
                <x-tooltip>
                  Collection Date
                  <template #tooltip>
                      <span class="custom-tooltip-content">{{ paymentTooltipEnum.PAYMENT_MANAGEMENT_COLLECTION_DATE }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th class="inner-th-class">
                <x-tooltip>
                  Due Date
                  <template #tooltip>
                      <span class="custom-tooltip-content">{{ paymentTooltipEnum.PAYMENT_MANAGEMENT_DUE_DATE }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th class="inner-th-class">
                <x-tooltip>
                  Payment Method
                  <template #tooltip>
                      <span class="custom-tooltip-content">{{ paymentTooltipEnum.PAYMENT_MANAGEMENT_PAYMENT_METHOD }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th class="inner-th-class">
                <x-tooltip>
                  Total Price
                  <template #tooltip>
                      <span class="custom-tooltip-content">{{ paymentTooltipEnum.PAYMENT_MANAGEMENT_TOTAL_PRICE }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th class="inner-th-class">
                <x-tooltip>
                  Discount Value
                  <template #tooltip>
                      <span class="custom-tooltip-content">{{ paymentTooltipEnum.PAYMENT_MANAGEMENT_DISCOUNT_VALUE }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th class="inner-th-class">
                <x-tooltip>
                  Total Amount
                  <template #tooltip>
                      <span class="custom-tooltip-content">{{ paymentTooltipEnum.PAYMENT_MANAGEMENT_TOTAL_AMOUNT }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th class="inner-th-class">
                <x-tooltip>
                  Collected Amount
                  <template #tooltip>
                      <span class="custom-tooltip-content">{{ paymentTooltipEnum.PAYMENT_MANAGEMENT_COLLECTED_AMOUNT }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th class="inner-th-class">
                <x-tooltip>
                  Payment Status
                  <template #tooltip>
                      <span class="custom-tooltip-content">{{ paymentTooltipEnum.PAYMENT_MANAGEMENT_PAYMENT_STATUS }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th class="inner-th-class">
                <x-tooltip>
                  Payment Allocation Status
                  <template #tooltip>
                      <span class="custom-tooltip-content">{{ paymentTooltipEnum.PAYMENT_MANAGEMENT_PAYMENT_ALLOCATION_STATUS }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th style="min-width: 200px;">
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
             <tr v-for="(item,index) in payments" :key="item.code">  
              <template v-if="index===0">
              <td class="text-center">
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
              <td>{{ formatString(item.payment_status.text.toLowerCase()) }}</td>
              <td>{{ item.payment_status_message }}</td>             
              <td>
                <div class="flex gap-2">
                <template v-if="hasRole(rolesEnum.CarAdvisor)">                    
                    <x-button size="xs" color="primary" outlined @click="editPaymentModal(item,0,0)">
                        Edit
                    </x-button>
                </template>
            </div>
              </td>
            </template>           
            </tr>
            <template v-if="isExpandedSplitPayments">
            <tr v-for="splitPayment in payments[0].payment_splits" :key="splitPayment.id">
              <td class="text-center">{{ splitPayment.sr_no }}</td>
              <td></td>
              <td>{{ formatDate(splitPayment.due_date) }}</td>
              <td>{{ formatDate(splitPayment.due_date) }}</td>
              <td>{{ splitPayment.payment_method.name }}</td>
              <td></td>
              <td></td>
              <td>{{ formatAmount(splitPayment.payment_amount) }}</td>
              <td>{{ (splitPayment.collection_amount>0) ? formatAmount(splitPayment.collection_amount):'' }}</td>
              <td>{{ splitPayment.payment_status.text.toLowerCase() }}</td>
              <td></td>
              <td>
                <x-button size="xs" color="primary" @click="editPaymentModal(payments[0],splitPayment.id,splitPayment.sr_no)" outlined >View</x-button>
                <x-button v-if="splitPayment.payment_method.code=='CC'" class="ml-2" size="xs" color="emerald"  @click.prevent="onCopyPaymentLink(splitPayment.payment_link,splitPayment.payment_status_id);" outlined >Copy Payment Link</x-button>

              
              </td>
            </tr>
          </template>
          </tbody>
        </table>
        <div v-if="!payments.length>0" data-v-32683533="" class="vue3-easy-data-table__message">No Available Data</div>
      </div>
    </div>

    <x-modal v-model="createPaymentModal" size="xl" show-close backdrop>
      <template #header>
        <span class=" ">
          <template v-if="isViewEnabled">View Payment</template>
          <template v-else>
          {{
            paymentMethodsForm.status == 'create'
              ? 'Add Manual Payment'
              : 'Edit Payment'
          }}
                
        </template>
        </span>
      </template>      
      <x-form @submit="addPayment" :auto-focus="false">
        <div class="w-full grid md:grid-cols-2 gap-5">          
          <x-tooltip>
            <x-field label="COLLECTION DATE" class="w-full" required>
              <span v-if="isFieldReadonly">{{ formatDate(paymentMethodsForm.collection_date) }}</span>
              <DatePicker
                  v-if="!isFieldReadonly"
                  name="collection_date"
                  v-model="paymentMethodsForm.collection_date"
                  :rules="[rules.isRequired]"                  
              />
            </x-field>
            <template #tooltip>
               <span>{{ paymentTooltipEnum.COLLECTION_DATE }}</span>
            </template>
          </x-tooltip>
          <x-tooltip>
            <x-field label="TOTAL PRICE" class="w-full">              
              <span v-if="isFieldReadonly">{{ totalPrice }}</span>              
              <x-input
                  v-if="!isFieldReadonly"
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
            <span v-if="isFieldReadonly">{{ collectionTypes.find(item => item.value === paymentMethodsForm.collection_type).label }}</span>
            <select
                v-if="!isFieldReadonly"
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
              <span v-if="isFieldReadonly">{{ providerName }}</span>
              <x-input
                  v-if="!isFieldReadonly"
                  class="w-full"
                  :value="providerName"
                  :disabled="true"
                />
            </x-field>
            <template #tooltip>
                <span v-if="isFieldReadonly">{{ paymentTooltipEnum.PROVIDER_NAME_VIEW }}</span>
                <span v-else >{{ paymentTooltipEnum.PROVIDER_NAME }}</span>
            </template>    
          </x-tooltip>
          
          <x-tooltip>
            <x-field label="FREQUENCY" class="w-full" required>
              <span v-if="isFieldReadonly">{{ frequencyTypes.find(item => item.value === paymentMethodsForm.frequency).label }}</span>            
              <select
                  v-if="!isFieldReadonly"
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
              <span v-if="isFieldReadonly">{{ getPlanName }}</span>
              <x-input
                  v-if="!isFieldReadonly"
                  class="w-full"
                  :value="getPlanName"
                  :disabled="true"
                />
            </x-field>
            <template #tooltip>
                <span v-if="isFieldReadonly">{{ paymentTooltipEnum.PLAN_NAME_VIEW }}</span>
                <span v-else >{{ paymentTooltipEnum.PLAN_NAME }}</span>
            </template>
          </x-tooltip>  

          <x-tooltip>
            <x-field label="PAYMENT NO" class="w-full" required>
              <span v-if="isFieldReadonly">{{ paymentMethodsForm.payment_no }}</span>              
              <select
                  v-if="!isFieldReadonly"
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
              <span v-if="isFieldReadonly">{{ formatString(masterPaymentStatus.toLowerCase()) }}</span>
              <x-input
                  v-if="!isFieldReadonly"
                  class="w-full"
                  :value="formatString(masterPaymentStatus.toLowerCase())"
                  :disabled="true"
                />
            </x-field>
            <template #tooltip>
                <span>{{ paymentTooltipEnum.PAYMENT_STATUS }}</span>
            </template>
          </x-tooltip>
          
          <x-tooltip>
            <x-field label="CREDIT APPROVAL" class="w-full">
              <span v-if="isFieldReadonly">{{ creditApprovalReasons.find(item => item.value === paymentMethodsForm.credit_approval)?.label || 'Approval Reason' }}</span>
              <div v-if="!isFieldReadonly" class="custom-dropdown">
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
            <span v-if="isFieldReadonly">{{ paymentMethodsForm.custom_reason }}</span>
            <x-input
                v-if="!isFieldReadonly"
                class="w-full"
                v-model="paymentMethodsForm.custom_reason"                  
              />
          </x-field>
          <x-tooltip v-if="showDiscountOptions">
            <x-field label="DISCOUNT APPLICABLE (DISCOUNT TYPE)" class="w-full">
              <span v-if="isFieldReadonly">{{ discountTypes.find(item => item.value === paymentMethodsForm.discount).label }}</span>
              <div v-if="!isFieldReadonly" class="custom-dropdown">
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
                <span v-if="isFieldReadonly">{{ paymentTooltipEnum.DISCOUNT_APPLICABLE_VIEW }}</span>
                <span v-else >{{ paymentTooltipEnum.DISCOUNT_APPLICABLE }}</span>
            </template>
          </x-tooltip>

          <x-tooltip v-if="isDiscountReasonEnabled">
            <x-field label="DISCOUNT REASON" class="w-full" required>
              <span v-if="isFieldReadonly">{{ discountReasons.find(item => item.value === paymentMethodsForm.discount_reason).label }}</span>
              <select
                  v-if="!isFieldReadonly"
                  class="custom-select"
                  v-model="paymentMethodsForm.discount_reason"
                  :options="discountReasons"
                  :rules="[rules.isRequired]"                  
                  >
                  <template v-for="option in discountReasons" :key="option.value">
                      <option :value="option.value" :title="option.tooltip">{{ option.label }}</option>                
                  </template>
              </select>
              <p v-if="isDiscountReasonError" class="text-sm text-red-500 dark:text-red-400 mt-1">This field is required</p>
            </x-field>
            <template #tooltip>
                <span>{{ paymentTooltipEnum.DISCOUNT_REASON }}</span>
            </template>
          </x-tooltip>

          
            <x-tooltip v-if="isDiscountEnabled">
              <x-field label="DISCOUNT VALUE" class="w-full" required>
                <span v-if="isFieldReadonly">{{ discountValue }}</span>
                <x-input
                    v-if="!isFieldReadonly"                  
                    class="w-full"
                    v-model="discountValue"
                    name="discount_value"
                    :rules="[rules.isRequired,rules.amount]"
                    @keyup="calculateTotalAmount()"                   
                />                
                <p v-if="discountError" class="text-sm text-red-500 dark:text-red-400 mt-1">{{ discountError }}</p>
              </x-field>
              
              <template #tooltip>
                  <span>{{ paymentTooltipEnum.DISCOUNT_VALUE }}</span>
              </template>
            </x-tooltip>
            
            <x-tooltip v-if="isDiscountEnabled || isFieldReadonly">
              <x-field label="TOTAL AMOUNT" class="w-full">
                <span v-if="isFieldReadonly">{{ formatAmount(totalAmount) }}</span>
                <x-input
                    v-if="!isFieldReadonly"
                    class="w-full"
                    :value="formatAmount(totalAmount)"
                    :disabled="true"
                  />
              </x-field>
              <template #tooltip>
                  <span>{{ paymentTooltipEnum.TOTAL_AMOUNT_VIEW }}</span>
              </template>
            </x-tooltip>
          
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
                <span class="text-sm  ">
                  PAYMENT NO *
                </span>
                <template #tooltip>
                  <span>{{ paymentTooltipEnum.PAYMENT_NO_2 }}</span>
                </template>
              </x-tooltip>              
            </div>
            <div class="w-1/5 px-2">
              <x-tooltip>
                <span class="text-sm  ">
                  PAYMENT METHOD *
                </span>
                <template #tooltip>
                  <span v-if="isFieldReadonly" >{{ paymentTooltipEnum.PAYMENT_METHOD_VIEW }}</span>
                  <span v-else >{{ paymentTooltipEnum.PAYMENT_METHOD }}</span>
                </template>
              </x-tooltip>
            </div>
            <div class="w-1/5 px-2">
              <x-tooltip>
                <span class="text-sm  ">
                  TOTAL AMOUNT *
                </span>
                <template #tooltip>
                  <span v-if="isFieldReadonly" >{{ paymentTooltipEnum.TOTAL_AMOUNT_SPLIT_VIEW }}</span>
                  <span v-else >{{ paymentTooltipEnum.TOTAL_AMOUNT }}</span>                  
                </template>
              </x-tooltip>
            </div>
            <div class="w-1/5 px-2">
              <x-tooltip>
                <span class="text-sm">
                  DUE DATE *
                </span>
                <template #tooltip>
                  <span v-if="isFieldReadonly" >{{ paymentTooltipEnum.DUE_DATE_VIEW }}</span>
                  <span v-else >{{ paymentTooltipEnum.DUE_DATE }}</span>
                </template>
              </x-tooltip>
            </div>
            <div class="w-1/5 px-2">
              <x-tooltip>
                <span class="text-sm  ">
                  DOCUMENTS *
                </span>
                <template #tooltip>
                  <span v-if="isFieldReadonly" >{{ paymentTooltipEnum.DOCUMENTS_VIEW }}</span>
                  <span v-else >{{ paymentTooltipEnum.DOCUMENTS }}</span>
                </template>
              </x-tooltip>
            </div>
          </div>
          
          <!-- Fields -->

          <template v-if="isViewEnabled">
            <div class="flex w-full custombreak" >
              <div class="w-1/6 px-2 text-center">{{ splitPaymentNo }}</div>

              <div class="w-1/5 px-2">
                  {{ getPaymentTypeLabel(paymentMethodsModels[splitPaymentNo]) }}
                  <p>{{ checkDetailModels[splitPaymentNo] }}</p>                    
              </div>

              <div class="w-1/5 px-2">
                {{ splitAmountModels[splitPaymentNo] }}                
              </div>

              <div class="w-1/5 px-2">
                {{ formatDate(dueDateModels[splitPaymentNo]) }}                  
              </div>

              <div class="w-1/5 px-2">
                <div v-for="fileData in fileUploadModels[splitPaymentNo]" :key="fileData.id">
                  <span style="display: flex; align-items: center;">
                    <span 
                          :key="fileData.id"
                          class="block px-2 py-1 border rounded mt-1 text-xs hover:text-primary-600 truncate"
                          style="flex: 1; text-decoration: none; cursor: pointer;"
                          @click="openInnerModal(fileData.id)"
                        >
                          {{ fileData.original_name }}
                      </span>
                  </span>
                </div>
              </div>
            </div>
            
            <div class="flex w-full custombreak pt-5" >
              <div class="w-1/6 px-2 text-center"></div>
              <div class="w-1/5 px-2">                
                <x-tooltip>
                  <span class="text-sm  ">
                    CC PAYMENT STATUS INFO
                  </span>
                  <template #tooltip>
                    <span>{{ paymentTooltipEnum.PAYMENT_VIEW_CC_PAYMENT_STATUS }}</span>
                  </template>
                </x-tooltip>
              </div>
              <div class="w-1/5 px-2">
                <x-tooltip>
                  <span class="text-sm  ">
                    CC PAYMENT ID
                  </span>
                  <template #tooltip>
                    <span>{{ paymentTooltipEnum.PAYMENT_VIEW_CC_ID }}</span>
                  </template>
                </x-tooltip>
              </div>
              <div class="w-1/5 px-2">
                <x-tooltip>
                  <span class="text-sm  ">
                    CC PAYMENT GATEWAY
                  </span>
                  <template #tooltip>
                    <span>{{ paymentTooltipEnum.PAYMENT_VIEW_CC_GATEWAY }}</span>
                  </template>
                </x-tooltip>                
              </div>
              <div class="w-1/5 px-2">
                <x-tooltip>
                  <span class="text-sm  ">
                    DIGITAL WALLET
                  </span>
                  <template #tooltip>
                    <span>{{ paymentTooltipEnum.PAYMENT_VIEW_WALLET }}</span>
                  </template>
                </x-tooltip>
              </div>
            </div>

            <div class="flex w-full custombreak pt-1 pb-5" >
              <div class="w-1/6 px-2 text-center"></div>
              <div class="w-1/5 px-2">{{ splitPaymentRecord.cc_payment_status_info !== null ? splitPaymentRecord.cc_payment_status_info : '' }}</div>
              <div class="w-1/5 px-2">{{ splitPaymentRecord.cc_payment_id !== null ? splitPaymentRecord.cc_payment_id : '' }}</div>
              <div class="w-1/5 px-2">{{ splitPaymentRecord.cc_payment_gateway !== null ? splitPaymentRecord.cc_payment_gateway : '' }}</div>
              <div class="w-1/5 px-2">{{ splitPaymentRecord.digital_wallet !== null ? splitPaymentRecord.digital_wallet : '' }}</div>
            </div> 

            <div class="flex w-full custombreak" >
              <div class="w-1/6 px-2 text-center"></div>
              <div class="w-1/5 px-2">
                <x-tooltip>
                  <span class="text-sm  ">
                    SAGE RECIEPT ID
                  </span>
                  <template #tooltip>
                    <span>{{ paymentTooltipEnum.PAYMENT_VIEW_SAGE_RECIPT }}</span>
                  </template>
                </x-tooltip>                
              </div>
              <div class="w-1/5 px-2">
                <x-tooltip>
                  <span class="text-sm  ">
                    PAYMENT STATUS
                  </span>
                  <template #tooltip>
                    <span>{{ paymentTooltipEnum.PAYMENT_VIEW_STATUS }}</span>
                  </template>
                </x-tooltip>                  
              </div>
              <div class="w-1/5 px-2">
                <x-tooltip>
                  <span class="text-sm  ">
                    PAYMENT ALLOCATION STATUS
                  </span>
                  <template #tooltip>
                    <span>{{ paymentTooltipEnum.PAYMENT_VIEW_ALLO_STATUS }}</span>
                  </template>
                </x-tooltip>                
              </div>
              <div class="w-1/5 px-2">
                <x-tooltip>
                  <span class="text-sm  ">
                    COLLECTED AMOUNT
                  </span>
                  <template #tooltip>
                    <span>{{ paymentTooltipEnum.PAYMENT_VIEW_COLLECTED_TEXT }}</span>
                  </template>
                </x-tooltip>               
              </div>
            </div>

            <div class="flex w-full custombreak pb-5" >
              <div class="w-1/6 px-2 text-center"></div>
              <div class="w-1/5 px-2">{{ splitPaymentRecord.sage_reciept_id !== null ? splitPaymentRecord.sage_reciept_id : '' }}</div>
              <div class="w-1/5 px-2">{{ splitPaymentRecord.payment_status.text.toLowerCase() }}</div>
              <div class="w-1/5 px-2">{{ splitPaymentRecord.invoice_link_status !== null ? splitPaymentRecord.invoice_link_status : '' }}</div>
              <div class="w-1/5 px-2">{{ splitPaymentRecord.collection_amount !== null ? splitPaymentRecord.collection_amount : '0.00' }}</div>
            </div>            

          </template>
            <template v-else>
              {{ console.log('azhar6969='+parseInt(paymentMethodsForm.payment_no))}}
              <div v-for="count in parseInt(paymentMethodsForm.payment_no)" :key="count" class="mb-2">
                <div class="flex w-full custombreak" >
                  <div class="w-1/6 px-2 text-center">{{ count }}</div>
                  <div class="w-1/5 px-2">
                    <template v-if="readOnlyPayments[count]">
                      {{ getPaymentTypeLabel(paymentMethodsModels[count]) }}
                      <p>{{ checkDetailModels[count] }}</p>
                    </template>
                    <template v-else >
                      <select
                        :class="{'custom-select-error': isPaymentMetodNotSelected[count]}"
                        class="w-full custom-select"
                        v-model="paymentMethodsModels[count]"
                        :options="handlePaymentTypes(count)"
                        @change="handlePaymentOptions(count)"
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
                       <x-tooltip>
                            <x-input
                            class="w-full mt-2"
                            v-if = "isCheckDetailsEnabled[count] && (count==1 || paymentMethodsModels[count] === 'PDC')"
                            v-model="checkDetailModels[count]"
                            placeholder="Cheque Details"         
                          />
                        <template #tooltip>
                            <span>{{ paymentTooltipEnum.CHECK_DETAILS }}</span>
                        </template>
                      </x-tooltip>
                    </template>
                  </div>
                  <div class="w-1/5 px-2">                    
                    <template v-if="readOnlyPayments[count]">
                      {{ splitAmountModels[count] }}
                    </template>
                    <template v-else >
                      <x-input
                        v-model="splitAmountModels[count]"
                        class="w-full"
                        :rules="[rules.isRequired]"              
                      />
                    </template> 
                  </div>
                  <div class="w-1/5 px-2">
                    <template v-if="readOnlyPayments[count]">
                      {{ formatDate(dueDateModels[count]) }}
                    </template>
                    <template v-else >
                      <DatePicker
                        v-model="dueDateModels[count]"
                        class="w-full"
                        :rules="[rules.isRequired]"              
                      /> 
                    </template> 
                  </div>
                  <div class="w-1/5 px-2 mb-2">
                    <x-tooltip>
                          <Dropzone
                          :id="paymentDocument.id"
                          :accept="paymentDocument.accepted_files"
                          :max-files="paymentDocument.max_files"
                          :max-size="paymentDocument.max_size"
                          :loading="documentForm.processing"
                          @change="uploadDocument(paymentDocument, $event, count)"
                          v-if="!readOnlyPayments[count]"
                        />
                      <template #tooltip>
                        <span>{{ paymentTooltipEnum.DOCUMENTS_UPLOAD }}</span>
                      </template>
                    </x-tooltip>                   
                    <p v-if="isDocumentNotUploaded[count]" class="text-sm text-red-500 dark:text-red-400 mt-1">This field is required</p>
                    <div v-for="fileData in fileUploadModels[count]" :key="fileData.id">
                      <span style="display: flex; align-items: center;">                        
                      <span 
                          :key="fileData.id"
                          class="block px-2 py-1 border rounded mt-1 text-xs hover:text-primary-600 truncate"
                          style="flex: 1; text-decoration: none; cursor: pointer;"
                          @click="openInnerModal(fileData.id)"
                        >
                          {{ fileData.original_name }}
                      </span>
                        <span
                          class="delete-pointer"
                          @click="deleteDocument(fileData.doc_name, count)"
                          v-if = "!readOnlyPayments[count]"
                        >
                        <x-tooltip>
                          x
                          <template #tooltip>
                              <span>{{ paymentTooltipEnum.DOCUMENT_DELETE_ICON }}</span>
                          </template>
                        </x-tooltip>
                        </span>


                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </template>          
      </div>
  
      <x-divider class="mb-4 mt-1" />

      <div v-if="isViewEnabled" class="p-1 mb-2">
        <h3>NOTES</h3>
      </div>
      
      <div class="w-full grid">
        <x-field>
          <label v-if="!isViewEnabled">NOTES</label>
          <p v-if="isFieldReadonly">{{ paymentMethodsForm.notes }}</p>
          <x-input
            v-if="!isFieldReadonly"
            class="w-full"
            v-model="paymentMethodsForm.notes"          
          />
        </x-field>
      </div>
      <template v-if="isViewEnabled && isDeclineClicked">
        <div class="p-1 mb-2">
          <h3 class="text-white">PAYMENT DECLINE</h3>
        </div>
        <x-divider class="mb-4 mt-1" />        
        <div class="flex w-full" >
              <div class="px-2">
                
                <x-field label="DECLINE REASON" required>
                    <select                  
                      class="custom-select"
                      v-model="paymentMethodsForm.declined_reason"
                      :options="declinedReasons"
                      :rules="[rules.isRequired]"
                      @change="handleDeclinedReasonChange"
                      >
                      <template v-for="option in declinedReasons" :key="option.value">
                          <option :value="option.value">{{ option.label }}</option>                
                      </template>
                  </select>
                </x-field>
              </div>
              <div v-if="isDeclineCustomReason" class="px-2">                
                <x-field label="CUSTOM REASON" required>
                <x-input
                  class="w-full"
                  v-model="paymentMethodsForm.decline_custom_reason"
                  :rules="[rules.isRequired]"         
                />
              </x-field>
              </div>              
        </div>
      </template>

      <template v-if="isViewEnabled && isApproveClicked && paymentMethodsModels[splitPaymentNo]!='CC'">
        <x-divider class="mb-4 mt-1" />
        <div class="p-1 mb-2">
          <x-tooltip>
          <h3>PAYMENT VERIFICATION</h3>
          <template #tooltip>
              <span>{{ paymentTooltipEnum.PAYMENT_VIEW_VERIFICATION_HEADER }}</span>
          </template>
        </x-tooltip>
        </div>
        <x-alert
            v-if="isApprovePaymentError"
						color="error"
						class="mb-5"						
					>
          The entered amount is smaller than the total amount
				</x-alert>        
        <div class="flex w-full" >
              <div class="w-1/4 px-2">                
                <x-tooltip>
                    <x-field label="COLLECTION AMOUNT" required>
                    <x-input
                      class="w-full"
                      v-model="paymentMethodsForm.collection_amount"
                      :rules="[rules.isRequired,rules.amount]"         
                    />
                  </x-field>
                  <template #tooltip>
                     <span>{{ paymentTooltipEnum.PAYMENT_ADD_DELETE_DOCUMENT }}</span>
                  </template>
                </x-tooltip>
              </div>
              <div class="w-1/4 px-2" v-if="paymentMethodsModels[splitPaymentNo]=='BT' || paymentMethodsModels[splitPaymentNo]=='CHQ'">
                <x-tooltip>
                  <x-field label="BANK REFERENCE NUMBER" required>
                  <x-input
                    class="w-full"
                    v-model="paymentMethodsForm.bank_reference_number"
                    :rules="[rules.isRequired]"         
                  />
                </x-field>
                <template #tooltip>
                      <span>{{ paymentTooltipEnum.PAYMENT_VIEW_BANK_REFERENCE }}</span>
                </template>
                </x-tooltip>
              </div>
              <div class="w-1/4 px-2">
                <x-tooltip>
                <x-field label="DOCUMENT" class="dropzone-field">
                <Dropzone
                  :id="paymentDocument.id"
                  :accept="paymentDocument.accepted_files"
                  :max-files="paymentDocument.max_files"
                  :max-size="paymentDocument.max_size"
                  :loading="documentForm.processing"
                  @change="uploadDocument(paymentDocument, $event, splitPaymentNo)"                  
                />
                <p v-if="isApprovedDocumentNotUploaded" class="text-sm text-red-500 dark:text-red-400 mt-1">This field is required</p>
                </x-field>
                <template #tooltip>
                      <span>{{ paymentTooltipEnum.PAYMENT_VIEW_DOCUMENTS }}</span>
                </template>
                </x-tooltip>
              </div>
        </div>
      </template>

      <x-divider class="mb-4 mt-1" />
      <template  v-if="isViewEnabled">        
        
          <template v-if="isApproveClicked">
            <div class="w-full text-right">Do you wish to proceed with payment confirmation?</div>
            <div class="w-full flex justify-end">
              <div class="mr-4">
                <x-button size="sm" @click="isApproveClicked = !isApproveClicked">
                  No
                </x-button>
              </div>
              <div>
                <x-button class="mr-2" size="sm" color="#ff5e00" type="submit">
                  Yes
                </x-button>
              </div>
            </div>
          </template>          
          <template v-else >
            <div v-if="(splitPaymentRecord.payment_status_id!=paymentStatusEnum.PAID && hasRole(rolesEnum.CarAdvisor))" class="w-full flex justify-end">
              <div v-if="isDeclineClicked" class="mr-4">
                <x-button size="sm" @click="isDeclineClicked=!isDeclineClicked;isApproveClicked=false">
                  Cancel
                </x-button>
              </div>            
              <div v-if="!isApproveClicked && !isDeclineClicked" class="mr-4">
                <x-button size="sm"  @click="handleDeclinedChange">
                  Decline
                </x-button>
              </div>
              <div v-if="!isApproveClicked && isDeclineClicked" class="mr-4">
                <x-button size="sm"  type="submit" >
                  Decline
                </x-button>
              </div>
              <div v-if="!isDeclineClicked && paymentMethodsModels[splitPaymentNo]!='CC'">
                <x-button class="mr-2" size="sm" color="#ff5e00" @click="isApproveClicked = !isApproveClicked">
                  Approve
                </x-button>
              </div>
            </div>
          </template>
        
      </template>
      <template v-else>        
        <div class="w-full md:col-span-4 flex justify-end"> 
            <div v-if="paymentMethodsForm.status == 'edit'" class="mr-4">
              <x-button @click="createPaymentModal=!createPaymentModal">
                Cancel
              </x-button>
            </div>
            <div
              v-if="
                paymentMethodsForm.status == 'create' ||
                paymentMethodsForm.status == 'edit'
              "
            >
              <x-button color="emerald" type="submit">
                {{ paymentMethodsForm.status == 'create' ? 'Add Manual Payment' : 'Update' }}          
              </x-button>
            </div>          
        </div>
      </template>
      </x-form>

    <div class="modal-overlay" v-if="isGalleryModelOpen">
      <div class="modal-container">
        <div class="modal-header">
          <h2 class="text-xl font-semibold">Document Viewer</h2>   
          <x-button type="button" class="btn btn-primary" @click="closeInnerModal">Close</x-button>
        </div>

        <div class="modal-body">
            
          <div v-if="currentFile.doc_mime_type === 'image/jpeg' || currentFile.doc_mime_type === 'image/png'" class="text-center">
          <img :src="storageUrl + currentFile.doc_url" :style="{ transform: `scale(${zoomLevel})` }" />
          <div class="mt-2">
            <x-button type="button" color="gray" class="mr-2 text-sm" @click="zoomIn">Zoom In</x-button>
            <x-button type="button" color="gray" class="text-sm" @click="zoomOut">Zoom Out</x-button>
          </div>
        </div>
            <div v-else-if="currentFile.doc_mime_type === 'application/pdf'">
              <embed :src="storageUrl + currentFile.doc_url" type="application/pdf" width="100%" height="600px" />
            </div>
        </div>

        <div class="modal-footer">
          <x-button type="button" color="emerald" class="mr-2" @click="previousFile" :disabled="!hasPreviousFile">Previous</x-button>
          <x-button type="button" color="emerald" class="btn btn-secondary" @click="nextFile" :disabled="!hasNextFile">Next</x-button>                        
        </div>
      </div>
    </div>
    </x-modal> 
    
  </div>
</template>
<style scoped>
/* Modal overlay */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 90%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 1040;
}
/* Modal container */
.modal-container {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 100%;
  height: 100%; 
  background-color: #fff;
  border-radius: 4px;
  padding: 20px;
  z-index: 1050;
}
/* Modal header */
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #eee;
  padding-bottom: 10px;
}
/* Modal body */
.modal-body {
  padding: 20px 0;
}
/* Modal footer */
.modal-footer {
  display: flex;
  justify-content: space-between;
  padding: 20px;
  border-top: 1px solid #eee;
}
.inner-th-class {
  min-width: 160px;
}
/* Add your custom styling here */
.custom-select {
  border: 2px solid #e5e7eb;
  padding: 7px;
  border-radius: 5px;
  background-color: #fff;
  color: #333;
  font-size: 16px;
  width: 100%;  
  height: 40px;  
}
.custom-select-error {
  border: 2px solid red; /* Add a red border for the error state */
  outline: none; /* Remove the default blue outline */
}
.custom-dropdown {
  position: relative;
}
.close-icon {
  position: absolute;
  top: 8px;
  left: 0;
  margin-left: 445px;
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
    font-weight: bold;
    color: #1d83bc;
}

.custom-tooltip-content {
  max-width: 200px; /* Adjust the max-width as needed */
  white-space: normal; /* Allow the text to wrap */
  z-index: 999;
  position: relative;
  font-size: 12px;
  text-transform: none;
  }
  .custom-height {
    min-height: 160px;
  }
</style>
