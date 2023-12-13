<script setup>
import ToolTip from './../Components/ToolTip.vue';
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
  quoteType: String,
  storageUrl: String,
  eCommercePrice: {
    type: String,
    default: '0',
  },
});
console.log('QUOTEREQUEST='+JSON.stringify(props.quoteRequest));
const createPaymentModal = ref(false);
const isPaymentNoEnabled = ref(false);
const isCustomReasonEnabled = ref(false);
const isCustomDiscountReasonEnabled = ref(false);
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
const isApproveConfirm = ref(false);
const isDeclineClicked = ref(false);
const isDeclinedReasonError = ref(false);
const isDeclineCustomReason = ref(false);
const isApprovePaymentError = ref(false);
const showDiscountOptions = ref(true);
const isApprovedDocumentNotUploaded = ref(false);
const isMultipleDocumentEnabled = ref(true);
const approvedDocument = ref('');
const resetDiscountReason = ref('');
const approveErrorMessage = ref('');

const discountValue = ref(0); // Initial discount value

const paymentTypesFiltered = ref([]);
const approvedDocumentModel = ref([]);
const isPaymentMetodNotSelected = ref([]);
const isDocumentNotUploaded = ref([]);
const paymentMethodsModels = ref([]);
const splitAmountModels = ref([]);
const dueDateModels = ref([]);
const collectionAmountModels = ref([]);
const fileUploadModels = ref([]);
const checkDetailModels = ref([]);
const readOnlyPayments = ref([]);
const splitPaymentRecord = ref([]);
const filesTest = ref([]);
const isCreditPaymentInvalid = ref([]);
const isCreditPaymentInvalidError = ref([]);
const currentFileIndex = ref(0);
const zoomLevel = ref(1);
const isGalleryModelOpen = ref(false);
const isDiscountReasonError = ref(false);
const isCreditApprovalView = ref(false);
const isCreditCardView = ref(false);

const modal2Ref = ref(null);

// Array of quote types to check against
const quoteTypesToCheck = ['Car', 'Health', 'Travel']; //Ecommerce LOBs
// Declare initialAmount variable
let initialAmount;

// Check quoteType and set initialAmount accordingly
if (props.quoteType === 'Health') {
  initialAmount = props.eCommercePrice;
} else {
  initialAmount = quoteTypesToCheck.includes(props.quoteType)
    ? props.quoteRequest.premium
    : props.quoteRequest.price_with_vat;
}
const totalPrice = ref(initialAmount); // Initial total price
const totalAmount = ref(initialAmount); // Initial total price

const planDetail = quoteTypesToCheck.includes(props.quoteType)
? props.quoteRequest.plan
: props.quoteRequest.insurance_provider_details

console.log('azharPLAN='+props.quoteRequest.quoteType);
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
  calculatePaymentBreakup(false);  
}

const { copy, copied } = useClipboard();
const onCopyPaymentLink = (paymentLink,paymentStatus) => {
  if (paymentStatus==props.paymentStatusEnum.PAID){
    notification.error({
        title: 'Payment already \'Paid\', button deactivated for this transaction',
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
      zoomLevel.value = 1;
    }      
  };

  const zoomIn = () => {
    zoomLevel.value = Math.min(zoomLevel.value + 0.25, 3); 
  };

  const zoomOut = () => {
    zoomLevel.value = Math.max(zoomLevel.value - 0.25, 0.25); 
  };

  const openInnerModal = (fileId) => {
    //filesTest.value = fileUploadModels.value.flat();
    filesTest.value = [
      ...fileUploadModels.value.flat(),
      ...approvedDocumentModel.value.flat()
    ];
    currentFileIndex.value = filesTest.value.findIndex(item => item.id === fileId);
    isGalleryModelOpen.value = true;
    setTimeout(() => {
      if (modal2Ref.value) {
        modal2Ref.value.focus();
      }
    }, 0);
  };

  const previousFile = () => {
    if (currentFileIndex.value > 0) {
      currentFileIndex.value--;
      zoomLevel.value = 1;
    }      
  };

  const handleKeyDown = (event) => {
    if (event.key === 'ArrowLeft' && hasPreviousFile) {
      previousFile();
    } else if (event.key === 'ArrowRight' && hasNextFile) {
      nextFile();
    }
  } 

  const currentFile = computed(() => {
    return filesTest.value[currentFileIndex.value];
  });

  const closeInnerModal = () => {
    zoomLevel.value = 1;
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
  if (parseFloat(discountValue.value) > parseFloat(totalPrice.value)) {
    totalAmount.value = totalPrice.value;
    calculatePaymentBreakup();
    return 'Discount should not exceed total amount';
  }

  return '';
});

const rules = {
  isRequired: v => !!v || 'This field is required',
  isBankReferenceRequird: v => {
      if (paymentMethodsForm.collection_type === 'insurer'
          && paymentMethodsModels.value[splitPaymentNo.value] === 'CHQ'
          && paymentMethodsForm.credit_approval != '') {
            return true;
      } else {
        return !!v || 'This field is required';
      }
      return true;
  },
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
  isDeclineCustomReason.value = false;
  paymentMethodsForm.declined_reason = '';
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
      console.log('paymentMethod='+issueFound);
      if(totalSplitAmount.toFixed(2) !== parseFloat(totalAmount.value).toFixed(2)){
        isPaymentCalculationError.value = true;
        issueFound = true;
      }
      console.log('paymentCalc='+issueFound);
      
      if(
        (paymentMethodsForm.frequency === 'monthly' || paymentMethodsForm.frequency === 'quarterly' 
        || paymentMethodsForm.frequency === 'semi_annual' || paymentMethodsForm.frequency === 'custom')
        && paymentMethodsModels.value[1]=='IP' && paymentMethodsForm.collection_type === 'insurer'
        ){
          if ((fileUploadModels.value[1]===undefined || fileUploadModels.value[1].length===0)) {
            isDocumentNotUploaded.value[1] = true;
            issueFound = true;
          }          
      } else {
        for (let i = 1; i <= paymentMethodsForm.payment_no; i++) { 
          isDocumentNotUploaded.value[i] = false;

          console.log('azharLEN='+fileUploadModels.value[i]);
          if ( (
            paymentMethodsModels.value[i]=='BT' || paymentMethodsModels.value[i]=='CHQ' 
            || paymentMethodsModels.value[i]=='PDC' || paymentMethodsModels.value[i]=='IP' ||
            ( paymentMethodsForm.discount !== '' && i===1 && (paymentMethodsModels.value[i]=='CC' || paymentMethodsModels.value[i]=='CSH') )   
            ) 
          && (fileUploadModels.value[i]===undefined || fileUploadModels.value[i].length===0)       
          ) {
            isDocumentNotUploaded.value[i] = true;
            issueFound = true;
          } else if (paymentMethodsModels.value[i]=='CA' && i===1  && (fileUploadModels.value[i]===undefined || fileUploadModels.value[i].length===0)) {
            isDocumentNotUploaded.value[i] = true;
            issueFound = true;
          }
        }
      }

      console.log('DocError='+issueFound);
      if(
      paymentMethodsForm.discount === 'refer_a_friend' ||
      paymentMethodsForm.discount === 'incentive_offset' ||
      paymentMethodsForm.discount === 'managerial_approval_discount'      
      ){
        isDiscountReasonEnabled.value = true;
        if(paymentMethodsForm.discount_reason === ''){          
          issueFound = true;
          isDiscountReasonError.value = true;
        } else {
          isDiscountReasonError.value = false;
        }      
      } else {
        isDiscountReasonEnabled.value = false;
        isDiscountReasonError.value = false;
      }
      console.log('DiscountError='+issueFound);   
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
  { value: '', label: 'Select Reason'},
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
  { value: 'discount_custom_reason', label: 'Custom discount reason', tooltip: props.paymentTooltipEnum.DISCOUNT_REASON_LIST_CUSTOM_REASON },  
];

const handleDiscountReasonChange = () => {
  isDiscountReasonError.value=false
  isCustomDiscountReasonEnabled.value=false;
  if (paymentMethodsForm.discount_reason === 'discount_custom_reason' ){
    paymentMethodsForm.discount_custom_reason = '';
    isCustomDiscountReasonEnabled.value=true;
  }
}

const handleDeclinedReasonChange = () => {
  if (paymentMethodsForm.declined_reason !== ''){
    isDeclinedReasonError.value = false;
  }  
  if(paymentMethodsForm.declined_reason === '6'){
    isDeclineCustomReason.value = true;
  }else{
    isDeclineCustomReason.value = false;
  }
};

const handleCancelChanges = () => {
  isDeclineClicked.value=!isDeclineClicked.value;
  isApproveClicked.value=false;
  isDeclinedReasonError.value=false;
};

const handleNoButtonChange = () => {
  isApproveClicked.value = !isApproveClicked.value; 
  isApproveConfirm.value = !isApproveConfirm.value;
  paymentMethodsForm.collection_amount = '';
  isDeclinedReasonError.value=false;
  isApprovePaymentError.value = false;
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
    paymentTypesFiltered.value = paymentTypesFiltered.value.filter(item => !['CC', 'BT', 'CHQ', 'CSH'].includes(item.value));
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
    if (paymentMethodsForm.collection_type === 'insurer') {
      if (paymentMethodsForm.frequency === 'upfront' || paymentMethodsForm.frequency === 'split_payments' ){
        paymentTypesFiltered.value = paymentTypesFiltered.value.filter(item => !['PDC', 'CHQ','CSH','CC','BT'].includes(item.value));    
      } else {
        paymentTypesFiltered.value = paymentTypesFiltered.value.filter(item => !['CHQ','CSH','CC','BT'].includes(item.value));    
      }      
    } else {      
      if (paymentMethodsForm.frequency === 'upfront' || paymentMethodsForm.frequency === 'split_payments' ){
        paymentTypesFiltered.value = paymentTypesFiltered.value.filter(item => !['PDC','IP'].includes(item.value));    
      } else {
        paymentTypesFiltered.value = paymentTypesFiltered.value.filter(item => !['IP'].includes(item.value));
      }    
    } 
    for (let i = 1; i <= paymentMethodsForm.payment_no; i++) {
      paymentMethodsModels.value[i] = 'CA';
    }
  } else {
    handleCollectionTypeChange();
  }
};

const resetCreditApproval = () => {
  paymentMethodsForm.credit_approval = '';
  isCustomReasonEnabled.value = false;  
  handleApprovalReasonChange();
  handleFrequencyChange(false);
};

const resetDiscount = () => {
  isDiscountEnabled.value = false;
  isDiscountReasonEnabled.value = false;
  paymentMethodsForm.discount = '';
  totalAmount.value = totalPrice.value;
  discountValue.value = 0;
  paymentMethodsForm.discount_reason = '';
  handleDiscountChange();
  handleDiscountReasonChange();
  calculateTotalAmount();
};

const handleDiscountChange = () => {

  if( paymentMethodsForm.status === 'create' ){
    discountValue.value = 0;
    paymentMethodsForm.discount_reason = '';
  }
  if(resetDiscountReason.value === ''){
    paymentMethodsForm.discount_reason = '';
  }

  isDiscountReasonEnabled.value = false;
  console.log('HAFDISC='+paymentMethodsForm.discount);
  if(paymentMethodsForm.discount === '' || paymentMethodsForm.discount === undefined ){
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
    if( props.quoteType === 'Health' ) {
      discountValue.value = (totalPrice.value * (5 / 100)).toFixed(2); 
    } else if( props.quoteType === 'Home' || props.quoteType === 'Travel' ) {
      discountValue.value = (totalPrice.value * (15 / 100)).toFixed(2); 
    } else {
      discountValue.value = (totalPrice.value * (12.5 / 100)).toFixed(2); // for car
    }
  }

  if(paymentMethodsForm.discount === 'family_employee_discount'){
    if( props.quoteType === 'Health' ) {
      discountValue.value = (totalPrice.value * (2.5 / 100)).toFixed(2); 
    } else if( props.quoteType === 'Home' || props.quoteType === 'Travel' ) {
      discountValue.value = (totalPrice.value * (12.5 / 100)).toFixed(2); 
    } else {
      discountValue.value = (totalPrice.value * (7.5 / 100)).toFixed(2); // for car
    }
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
        const currentDueDate = dueDateModels.value[i - 1];
        const nextDueDate = new Date(currentDueDate);
        // Set the month to the next month
        nextDueDate.setMonth(nextDueDate.getMonth() + 1);
        // Update the due date model
        dueDateModels.value[i] = nextDueDate;
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
      const nextDueDate = new Date(paymentMethodsForm.collection_date);
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

const calculatePaymentBreakup = (changeMethod = true) => {  
  isDocumentNotUploaded.value = [];
  var perInstallmentPrice = parseFloat(((totalAmount.value-paidAmountSum.value)/(paymentMethodsForm.payment_no-totalPaidAmount.value)).toFixed(2));
  console.log('azhar9991='+JSON.stringify(paymentMethodsModels.value));
  if ( paymentMethodsForm.status === 'edit') {
    splitAmountModels.value = [];
  }

  for (let i = 1; i <= paymentMethodsForm.payment_no; i++) { 
    if(readOnlyPayments.value[i]!=undefined && readOnlyPayments.value[i]===true) {
      continue;
    }
    splitAmountModels.value[i] = perInstallmentPrice.toFixed(2);    
    if(i>1 && changeMethod){
      if (
          paymentMethodsModels.value[i] !== undefined 
          && paymentMethodsModels.value[i] !== null
          && (paymentMethodsForm.frequency === 'split_payments' || paymentMethodsForm.frequency === 'custom')
          ) {
        paymentMethodsModels.value[i] = paymentMethodsModels.value[i];
      } else {
        paymentMethodsModels.value[i] = '';
      }
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
  const lowercaseString = input.toLowerCase();
  const words = lowercaseString.replace(/_/g, ' ').split(' ');
  for (let i = 0; i < words.length; i++) {
    words[i] = words[i][0].toUpperCase() + words[i].slice(1);
  }
  const formattedString = words.join(' ');
  return formattedString;
}

const formatAmount = (amount) => {
  const parsedAmount = parseFloat(amount);
  if (isNaN(parsedAmount)) {
    return "0.00";
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
  if ( paymentMethodsForm.status === 'create' ) {
    paymentMethodsForm.credit_approval = '';
  }
  
  isCustomReasonEnabled.value = false; 
  handleApprovalReasonChange();

  for (let i = 1; i <= 12; i++) { // Append 7 more values to totalPayments
    totalPayments.value.push({ value: i.toString(), label: i.toString() });
  }
  calculatePaymentBreakup();
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
    if(noPaymentUpdate){
      paymentMethodsForm.payment_no = '2';
    }
    totalPayments.value.splice(-7);
    totalPayments.value.splice(0, 1);     
  } else if (paymentMethodsForm.frequency === 'custom') {
    isPaymentNoEnabled.value = true;
    if(noPaymentUpdate){
      paymentMethodsForm.payment_no = '2';
    }
    totalPayments.value.splice(0, 1);      
  } else {
    paymentMethodsForm.payment_no = '1';    
  }
  calculatePaymentBreakup();
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

const generateCCLink = async (code,splitPaymentId,paymentStatus) => {

  if (paymentStatus==props.paymentStatusEnum.PAID){
    notification.error({
        title: 'Payment already \'Paid\', button deactivated for this transaction',
        position: 'top',
      });
  } else {
    try {
      const response = await axios.post('/generate-payment-link', {
        quoteId: props.quoteRequest.id,
        modelType: props.quoteType,
        paymentCode: code,
        splitPaymentId: splitPaymentId,
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
          title: 'Link copied to clipboard',
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
  }
};

const addPaymentModal = () => {

  if ( props.payments.length>0 ) {
      notification.error({
        title: 'Payment already added, click \'Edit\' for changes.',
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
  isDiscountReasonEnabled.value = false;
  isDiscountEnabled.value = false;
  isPaymentCalculationError.value = false;
  showDiscountOptions.value = true;
  isDiscountReasonError.value = false;
  isPaymentMetodNotSelected.value[1] = false;
  isDocumentNotUploaded.value = [];

  if (totalPrice.value > 0 && planDetail) {
    totalAmount.value = totalPrice.value;
  } else {
    let errorMsg = 'Please update the Total Price in the Plan Details section.'; 
    if(quoteTypesToCheck.includes(props.quoteType)) {
      errorMsg = 'Please select a plan.';
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
  
  paymentMethodsForm.frequency = 'upfront';
  paymentMethodsForm.discount = '';
  paymentMethodsForm.credit_approval = '';
  totalPayments.value = [];
  totalPayments.value.push({ value: '1', label: '1' });
  paymentMethodsForm.payment_no = '1';  
  handleCollectionTypeChange();
  calculatePaymentBreakup();
};

const editPaymentModal = (payment,split_payment_id,sr_no,capture_approval) => {

  if( sr_no===0 && (payment.payment_status.id === props.paymentStatusEnum.PAID) && capture_approval===0 ) {
    notification.error({
          title: 'No further actions allowed to paid payments',
          position: 'top',
        });
    return false;
  };
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
  approvedDocumentModel.value = [];
  isPaymentMetodNotSelected.value = [];
  isDocumentNotUploaded.value = [];
  resetDiscountReason.value = '';
  isApproveConfirm.value = false;
  isCreditApprovalView.value = false;
  isCreditCardView.value = false;
  approveErrorMessage.value = "";
  isCreditPaymentInvalid.value = [];
  isCreditPaymentInvalidError.value = [];
  paymentMethodsForm.declined_custom_reason = '';
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
  showDiscountOptions.value = true;
  paymentMethodsForm.discount_reason = payment.discount_reason !== null ? payment.discount_reason : '';
  
  if(payment.discount_reason !== null && payment.discount_type !== null) {
    resetDiscountReason.value = payment.discount_reason;
  } 
  
  /*
  if (payment.discount_type=='' || payment.discount_type==null) {
    paymentMethodsForm.discount= payment.discount_type;
  } else {
    paymentMethodsForm.discount= payment.discount_type;    
  }*/
 
  paymentMethodsForm.custom_reason= payment.custom_reason;  
  paymentMethodsForm.discount_custom_reason= payment.discount_custom_reason;  
  paymentMethodsForm.notes= payment.notes;
  paymentMethodsForm.total_amount= payment.total_amount; // after discount calculation
  paymentMethodsForm.total_price= payment.total_price;
  paymentMethodsForm.collection_date= payment.collection_date;
  discountValue.value = payment.discount_value; // discount amount
  //console.log('azhar20='+payment.total_payments);

  if (paymentMethodsForm.status === 'view' || capture_approval>0 ) {
    paymentMethodsForm.credit_approval= payment.credit_approval !== null ? payment.credit_approval : 'N/A';  
    paymentMethodsForm.discount = payment.discount_type !== null ? payment.discount_type : 'N/A';     
  } else {
    paymentMethodsForm.credit_approval= payment.credit_approval !== null ? payment.credit_approval : '';  
    paymentMethodsForm.discount = payment.discount_type !== null ? payment.discount_type : '';  
  }   
  console.log('AZZZ='+paymentMethodsForm.discount);
  
  handleCollectionTypeChange();
  handleFrequencyChange(false);
  handleApprovalReasonChange();
  handleDiscountChange();
  handleDeclinedReasonChange();
  calculateTotalAmount();

  console.log('azha221='+JSON.stringify(paymentMethodsModels.value));
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
    collectionAmountModels.value[i] = payment.payment_splits[i-1].collection_amount;

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

  if(capture_approval>0) {
    isApproveClicked.value = true;
    if(capture_approval==1) {
      isCreditCardView.value = true;
    }    
    for(let i=1; i<=payment.total_payments; i++){
      readOnlyPayments.value[i] = true;
    }
    isFieldReadonly.value = true;    
    isCreditApprovalView.value = true;
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

const validateViewPayment = (isValid) => {  
  let amountExceeded = false;
  if (parseFloat(splitAmountModels.value[splitPaymentNo.value]) > parseFloat(paymentMethodsForm.collection_amount)) {
    approveErrorMessage.value = "The entered amount is smaller than the total amount.";
    isApprovePaymentError.value = true;
    return true;
  }

  if (parseFloat(paymentMethodsForm.collection_amount) > parseFloat(splitAmountModels.value[splitPaymentNo.value])) {
    approveErrorMessage.value = "Collected amount exceeds total amount, do you still want to continue?";
    isApprovePaymentError.value = true;
    amountExceeded = true;
    //return true;
  }

  if (paymentMethodsForm.collection_type==='insurer') {  
    if (approvedDocumentModel.value[splitPaymentNo.value]===undefined 
    || approvedDocumentModel.value[splitPaymentNo.value].length===0 ) {
        isApprovedDocumentNotUploaded.value = true;
        return true;
      } else {        
        isApprovedDocumentNotUploaded.value = false;
      }      
  }

  if(isApproveConfirm.value === false && isValid) {
    if (!amountExceeded) {
      isApprovePaymentError.value = false;
    }
    isApproveConfirm.value = true;
    return true;
  }
  return false;
}

const validateCapturePayment = (isValid) => {
  if(isApproveConfirm.value === false && isValid) {    
    let noError = true;
    isCreditPaymentInvalid.value = [];
    if (isCreditCardView.value === true) {
      for (let i = 1; i <= paymentMethodsForm.payment_no; i++) { 
        //isCreditPaymentInvalid.value[i] = false;
        console.log('INNN11'+JSON.stringify(collectionAmountModels.value[i]));
        
        if (paymentMethodsModels.value[i] === 'CC'){
          if (collectionAmountModels.value[i]===null || collectionAmountModels.value[i]===undefined){
            isCreditPaymentInvalid.value[i] = true;
            isCreditPaymentInvalidError.value[i] = "This field is required";
          }
          if (parseFloat(collectionAmountModels.value[i]) > parseFloat(splitAmountModels.value[i])) {          
            isCreditPaymentInvalid.value[i] = true;
            isCreditPaymentInvalidError.value[i] = "Capture amount should not exceed total amount";                
          }
        }
      }      
    }        
    if (isCreditPaymentInvalid.value.includes(true)) {
      return true;
    }
    isApproveConfirm.value = true;
    return true;
  }
  return false;
}

const addPayment = isValid => {  
  
  if(isCreditApprovalView.value === true && isDeclineClicked.value === false){
    if (validateCapturePayment(isValid)) return;
  } else if (paymentMethodsForm.status === 'view' && isApproveClicked.value) {
    if (validateViewPayment(isValid)) return;    
  } else if (paymentMethodsForm.status !== 'view') {
    console.log('INNN22');
    if (validatePaymentOption()) return;  
  }  
  if (!isValid) return;  

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
  } else if(splitAmountModels.value.length===2){
    mainPaymentMethod = paymentMethodsModels.value[1];
  }
  
  let data = {
    captured_amount: paymentMethodsForm.amount,
    code: paymentMethodsForm.payment_method,
    modelType: props.quoteType,
    quote_id: props.quoteRequest.id,
    plan_id: planDetail.id,
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
    discount_custom_reason: paymentMethodsForm.discount_custom_reason,
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


  let declinedCustomReason= paymentMethodsForm.declined_custom_reason;
  if (paymentMethodsForm.status === 'view' || isCreditApprovalView.value === true) { 
    if(isDeclineClicked.value === true && paymentMethodsForm.declined_reason === ''){
      isDeclinedReasonError.value = true; return;
    } else {
      isDeclinedReasonError.value = false;
    }    
    if(paymentMethodsForm.declined_reason != '6' && isDeclineClicked.value===true){
      declinedCustomReason = declinedReasons.find(reason => reason.value === paymentMethodsForm.declined_reason).label;
    }
  }

  if (isCreditApprovalView.value === true) {   
    let viewData = {
      modelType: props.quoteType,
      quote_id: props.quoteRequest.id,
      plan_id: planDetail.id,
      customer_id: props.quoteRequest.customer_id,      
      collection_amount: collectionAmountModels.value,      
      is_declined: isDeclineClicked.value,
      is_capture: isCreditCardView.value,
      is_approved: isApproveClicked.value,
      declined_reason: paymentMethodsForm.declined_reason,
      declined_custom_reason: declinedCustomReason,      
    };
    paymentMethodsForm
      .transform(data => viewData)
      .post('/payments/Car/split-payments-approve', {
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

  if (paymentMethodsForm.status === 'view') {   
    let viewData = {
      modelType: props.quoteType,
      quote_id: props.quoteRequest.id,
      plan_id: planDetail.id,
      customer_id: props.quoteRequest.customer_id,
      collection_amount: paymentMethodsForm.collection_amount,
      bank_reference_number: paymentMethodsForm.bank_reference_number,
      splitPaymentId: paymentMethodsForm.splitPaymentId,
      is_declined: isDeclineClicked.value,
      is_approved: isApproveClicked.value,
      declined_reason: paymentMethodsForm.declined_reason,
      approved_document_model: approvedDocumentModel.value,
      declined_custom_reason: declinedCustomReason,      
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
  quote_id: props.quoteRequest.id || null,
  quote_uuid: props.quoteRequest.code || null,
  quote_type_id: null,
  document_type_code: null,
  file: null,
});

const deleteDocument = (docName,count) => {
   router.post(
    `/documents/delete`,
    {
      docName: docName,
      quoteId: props.quoteRequest.id,
    },
    {
      preserveScroll: true,
      onFinish: () => {
        fileUploadModels.value[count] = fileUploadModels.value[count].filter(item => item.doc_name !== docName);
        approvedDocumentModel.value[count] = approvedDocumentModel.value[count].filter(item => item.doc_name !== docName);
        console.log('azhar19=deleted');
      },
    },
  );
};

const uploadDocument = (doc, files, count) => {
  let url = '/quotes/'+props.quoteType+'/documents/store-multiple';

  if (files.length == 0) return;

  if (!fileUploadModels.value[count]) {
    fileUploadModels.value[count] = [];
  }

  if (!approvedDocumentModel.value[count]) {
    approvedDocumentModel.value[count] = [];
  }

  let allUploadedDocuments = fileUploadModels.value.flat();
  let duplicateFileNames = files.map((file) => file.file.name);
  isFileError.value = false;

  if (allUploadedDocuments && allUploadedDocuments.some((uploadedFile) => duplicateFileNames.includes(uploadedFile.original_name))) {
    isFileError.value = true;
    fileErrorMessage.value = props.paymentTooltipEnum.PAYMENT_ADD_DUPLICATE_FILES;
    console.log('File already exists');
    return false;
  }

  isUploading.value = true;

  return new Promise((resolve, reject) => {
    documentForm
      .transform(data => ({
        ...data,
        quote_type_id: doc.quote_type_id,
        document_type_code: doc.code,
        folder_path: doc.folder_path,
        file: files,
      }))
      .post(url, {
        preserveScroll: true,
        preserveState: true,
        onError: errors => {
          documentForm.setError(errors.error);
          notification.error({
            title: 'File upload failed',
            position: 'top',
          });
          reject(errors);
        },
        onSuccess: (data) => {
          let quoteDocuments = [];
          if (quoteTypesToCheck.includes(props.quoteType) || props.quoteType === 'Home') {
              quoteDocuments = data.props.quoteDocuments;
          } else {
              quoteDocuments = data.props.quote.documents;
          }
          if (paymentMethodsForm.status === 'view') {
            isApprovedDocumentNotUploaded.value = false;
            for (let i = 0; i < files.length; i++) {
              approvedDocumentModel.value[count].push(quoteDocuments[i]);
            }
            console.log('approveddoc=' + JSON.stringify(approvedDocumentModel.value));
            resolve(data);
            return;
          }

          for (let i = 0; i < files.length; i++) {
            fileUploadModels.value[count].push(quoteDocuments[i]);
          }
          isDocumentNotUploaded.value[count] = false;
          console.log('azhar9999=' + JSON.stringify(quoteDocuments[0]));
          resolve(data);
        },
        onFinish: () => {
          isUploading.value = false;
        },
      });
  });
};


const getCaptureValidation = computed(() => {  
  if ( props.payments.length>0 ) {
    if(props.payments[0].is_approved===1){
      return false;
    }
    let paymentRecord = props.payments[0]
    if ( paymentRecord.frequency==='upfront' ){
        let paymentSplitRec = paymentRecord.payment_splits[0];
        if (paymentSplitRec.payment_method.code==='CC' &&  paymentSplitRec.payment_status_id===props.paymentStatusEnum.AUTHORISED) {
          return true;
        } else if (paymentSplitRec.payment_method.code==='IP' &&  paymentSplitRec.payment_status_id===props.paymentStatusEnum.PENDING) {
          return true;
        } else if (paymentSplitRec.payment_method.code==='CA' &&  paymentSplitRec.payment_status_id===props.paymentStatusEnum.CREDIT_APPROVAL) {
          return true;
        } else if (paymentSplitRec.payment_status_id===props.paymentStatusEnum.PAID) {
          return true;
        }
    } else if ( paymentRecord.frequency==='split_payments' ){
      const paymentMethodCC = paymentRecord.payment_splits.filter(item => item.payment_method.code === "CC");
      if (paymentMethodCC.length > 0) {
        let ccPaymentStatus = paymentMethodCC.filter(item => item.payment_status_id===props.paymentStatusEnum.AUTHORIZED);
        if( ccPaymentStatus.length===paymentMethodCC.length ) {
          return true;
        }        
        /*paymentMethodCC.forEach(element => {
          if (element.payment_status.code===props.paymentStatusEnum.AUTHORIZED) {
            return true;
          }
        });*/
        
      } else {
        let ipPaymentStatus = paymentRecord.payment_splits.filter(item => item.payment_method.code === "IP");
        if (ipPaymentStatus.length > 0) {
          let ipPending = ipPaymentStatus.filter(item => item.payment_status_id===props.paymentStatusEnum.PENDING 
            || item.payment_status_id===props.paymentStatusEnum.PAID);
          if( ipPending.length===ipPaymentStatus.length ) {
            return true;
          } 
        } else {
          let caPaymentStatus = paymentRecord.payment_splits.filter(item => item.payment_method.code === "CA");
          if (caPaymentStatus.length > 0) {
            let caApproved = caPaymentStatus.filter(item => item.payment_status_id===props.paymentStatusEnum.CREDIT_APPROVAL);
            if( caApproved.length===caPaymentStatus.length ) {
              return true;
            } 
          } else {
            let paidPaymentStatus = paymentRecord.payment_splits.filter(item => item.payment_status_id===props.paymentStatusEnum.PAID);
            if( paidPaymentStatus.length===paymentRecord.payment_splits.length ) {
              return true;
            } 
          }
        }
      }
    } else {
      if (
        (paymentRecord.payment_splits[0].payment_method.code==='IP' ||
        paymentRecord.payment_splits[0].payment_method.code==='PDC'
        ) &&
        paymentRecord.payment_splits[0].payment_status.code===props.paymentStatusEnum.PENDING) {
        return true;
      } else if(paymentRecord.payment_splits[0].payment_status.code===props.paymentStatusEnum.PAID){
        return true;
      }
      /*
      let paidPaymentStatus = paymentRecord.payment_splits.filter(item => item.payment_status.code===props.paymentStatusEnum.PAID);
      if( paidPaymentStatus.length===paymentRecord.payment_splits.length ) {
        return true;
      }*/
      return true;


    }
  }
  return false;
});

const alertCapture = () => {  
  let errorMsg = 'Pending payment';
  if(props.payments[0].is_approved===1){
    errorMsg = 'Transaction already approved';   
  }
  notification.error({
      title: errorMsg,
      position: 'top',
    });
};

const getCaptureOption = computed(() => {  
  if ( props.payments.length>0 ) {
    const paymentMethodCC = props.payments[0].payment_splits.filter(item => item.payment_method.code === "CC");
    if (paymentMethodCC.length > 0) {
      return 'capture';
    } else {
      return 'approve';
    }
  }
  return;
});

const getPlanName = computed(() => {
  const plan = planDetail;
  return (quoteTypesToCheck.includes(props.quoteType) && plan) ? plan.text : 'Not Available';
});

const providerName = computed(() => {
  const plan = planDetail;
    if (quoteTypesToCheck.includes(props.quoteType) && plan.insurance_provider) {
      return plan ? plan.insurance_provider.text : 'Not Available';
    } else {
      return plan ? plan.text : 'Not Available';
    }  
});

const providerId = computed(() => {
  const plan = planDetail;
  console.log('TRAVELPLAN='+JSON.stringify(props.quoteRequest));
  if (plan && plan.insurance_provider) {
    return plan.insurance_provider.id;
  } else if (plan && plan.provider_id) {
    return plan.provider_id;
  }  else if (plan && plan.id) {
    return plan.id;
  }
  return null;
});

// Watch for changes in paymentMethodsForm.collection_date
watch(() => paymentMethodsForm.collection_date, (newValue, oldValue) => {
  calculateDueDates();
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
          <span class="border-b border-dotted">Add Manual Payment</span>
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
              <th class="relative group text-center">
                <span class="border-b border-dotted">Payment No</span>
                <div class="absolute text-left hidden group-hover:block transform transition-transform z-40 h-fit _popoverContent_1wc81_3 top-full bottom-0 _popoverBottom_1wc81_14 left-1/2 right-full -translate-x-1/2 max-w-xs">
                  <div class="dark">
                    <div class="x-popover-container block w-full bg-white dark:bg-gray-700 shadow-lg rounded-md border border-gray-200 dark:border-gray-800 p-2 text-white text-sm w-max max-w-xs">
                      <span data-v-d0063695="" class="custom-tooltip-content">
                        {{ paymentTooltipEnum.PAYMENT_MANAGEMENT_PAYMENT_NO }}
                      </span>
                    </div>
                  </div>
                </div>
              </th>
              <th class="inner-th-class">
                <x-tooltip>
                  <span class="border-b border-dotted">Payment Ref ID</span>
                  <template #tooltip>
                      <span class="custom-tooltip-content">{{ paymentTooltipEnum.PAYMENT_MANAGEMENT_PAYMENT_REF_ID }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th class="inner-th-class">
                <x-tooltip>
                  <span class="border-b border-dotted">Collection Date</span>
                  <template #tooltip>
                      <span class="custom-tooltip-content">{{ paymentTooltipEnum.PAYMENT_MANAGEMENT_COLLECTION_DATE }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th class="inner-th-class">
                <x-tooltip>
                  <span class="border-b border-dotted">Due Date</span>
                  <template #tooltip>
                      <span class="custom-tooltip-content">{{ paymentTooltipEnum.PAYMENT_MANAGEMENT_DUE_DATE }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th class="inner-th-class">
                <x-tooltip>
                  <span class="border-b border-dotted">Payment Method</span>
                  <template #tooltip>
                      <span class="custom-tooltip-content">{{ paymentTooltipEnum.PAYMENT_MANAGEMENT_PAYMENT_METHOD }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th class="inner-th-class">
                <x-tooltip>
                  <span class="border-b border-dotted">Total Price</span>
                  <template #tooltip>
                      <span class="custom-tooltip-content">{{ paymentTooltipEnum.PAYMENT_MANAGEMENT_TOTAL_PRICE }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th class="inner-th-class">
                <x-tooltip>
                  <span class="border-b border-dotted">Discount Value</span>
                  <template #tooltip>
                      <span class="custom-tooltip-content">{{ paymentTooltipEnum.PAYMENT_MANAGEMENT_DISCOUNT_VALUE }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th class="inner-th-class">
                <x-tooltip>
                  <span class="border-b border-dotted">Total Amount</span>
                  <template #tooltip>
                      <span class="custom-tooltip-content">{{ paymentTooltipEnum.PAYMENT_MANAGEMENT_TOTAL_AMOUNT }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th class="inner-th-class">
                <x-tooltip>
                  <span class="border-b border-dotted">Collected Amount</span>
                  <template #tooltip>
                      <span class="custom-tooltip-content">{{ paymentTooltipEnum.PAYMENT_MANAGEMENT_COLLECTED_AMOUNT }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th class="inner-th-class">
                <x-tooltip>
                  <span class="border-b border-dotted">Payment Status</span>
                  <template #tooltip>
                      <span class="custom-tooltip-content">{{ paymentTooltipEnum.PAYMENT_MANAGEMENT_PAYMENT_STATUS }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th class="inner-th-class">
                <x-tooltip>
                  <span class="border-b border-dotted">Payment Allocation Status</span>
                  <template #tooltip>
                      <span class="custom-tooltip-content">{{ paymentTooltipEnum.PAYMENT_MANAGEMENT_PAYMENT_ALLOCATION_STATUS }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th style="min-width: 200px;">
                <x-tooltip>
                  <span class="border-b border-dotted">Action</span>
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
              <td>{{ formatString(item.payment_status.text) }}</td>
              <td>{{ item.payment_allocation_status !== null ? formatString(item.payment_allocation_status) : '' }}</td>             
              <td>
                <div class="flex gap-2">
                <template v-if="hasRole(rolesEnum.CarAdvisor)">                    
                    <x-button size="xs" color="primary" outlined @click="editPaymentModal(item,0,0,0)">
                        Edit
                    </x-button>
                    <template v-if="can(permissionEnum.ApprovePayments)">
                      <x-button v-if="getCaptureOption==='capture'" size="xs" color="orange" outlined 
                      @click="getCaptureValidation ? editPaymentModal(item, 0, 0, 1) : alertCapture()">
                          Capture
                      </x-button>
                      <x-button v-if="getCaptureOption==='approve'" size="xs" color="orange" outlined 
                      @click="getCaptureValidation ? editPaymentModal(item, 0, 0, 2) : alertCapture()">
                          Approve
                      </x-button>
                    </template>
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
              <td>{{ formatString(splitPayment.payment_status.text) }}</td>
              <td>{{ splitPayment.payment_allocation_status !== null ? formatString(splitPayment.payment_allocation_status) : ''}}</td>
              <td>
                <x-button size="xs" color="primary" @click="editPaymentModal(payments[0],splitPayment.id,splitPayment.sr_no,0)" outlined >View</x-button>                
                <x-button v-if="splitPayment.payment_method.code=='CC'" class="ml-2" size="xs" color="emerald"  @click.prevent="generateCCLink(splitPayment.code,splitPayment.sr_no,splitPayment.payment_status_id);" outlined >Copy Payment Link</x-button>                
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
          <template v-if="isCreditCardView">Capture Transaction</template>
          <template v-else-if="isCreditApprovalView">Approve Transaction</template>
          <template v-else-if="isViewEnabled">View Payment</template>
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
        <div class="w-full grid md:grid-cols-2 gap-3">          
          <div>
            <ToolTip
             title="COLLECTION DATE"
            :tooltip="paymentTooltipEnum.COLLECTION_DATE"
            :required="!isFieldReadonly"
            />            
            <x-field class="w-full">
                <span v-if="isFieldReadonly">
                  {{ formatDate(paymentMethodsForm.collection_date)}}
                </span>
                <DatePicker
                v-if="!isFieldReadonly"
                name="collection_date"                
                v-model="paymentMethodsForm.collection_date"
                :rules="[rules.isRequired]"                
              />
            </x-field>
          </div>
          <div>
          <x-tooltip>
            <span class="border-b-2 border-dotted border-black text-sm">TOTAL PRICE</span>
            <template #tooltip>
               <span>{{ paymentTooltipEnum.TOTAL_PRICE }}</span>
            </template>
          </x-tooltip> 
             <x-field class="w-full">
              <span v-if="isFieldReadonly">
                {{ formatAmount(totalPrice) }}
              </span>              
              <x-input
                  v-if="!isFieldReadonly"
                  class="w-full"
                  :value="formatAmount(totalPrice)"
                  :disabled="true"
                />
            </x-field>            
        </div>
          <div>
            <ToolTip
            title="COLLECTED BY"
            :tooltip="paymentTooltipEnum.COLLECTED_BY"
            :required="!isFieldReadonly"
            />          
          <x-field class="w-full">
            <span v-if="isFieldReadonly">
              {{  collectionTypes.find(item => item.value === paymentMethodsForm.collection_type).label }}
            </span>
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
          </div>
          <div>
          <x-tooltip>
            <span class="border-b-2 border-dotted border-black text-sm">PROVIDER NAME</span>           
            <template #tooltip>
                <span v-if="isFieldReadonly">{{ paymentTooltipEnum.PROVIDER_NAME_VIEW }}</span>
                <span v-else >{{ paymentTooltipEnum.PROVIDER_NAME }}</span>
            </template>    
          </x-tooltip>
          <x-field class="w-full">
              <span v-if="isFieldReadonly">
                {{ providerName }}
              </span>
              <x-input
                  v-if="!isFieldReadonly"
                  class="w-full"
                  :value="providerName"
                  :disabled="true"
                />
            </x-field>
          </div>  

          <div>
            <ToolTip
             title="FREQUENCY"
            :tooltip="paymentTooltipEnum.FREQUENCY"
            :required="!isFieldReadonly"
            />          
           <x-field class="w-full">  
              <span v-if="isFieldReadonly">                
                {{ frequencyTypes.find(item => item.value === paymentMethodsForm.frequency).label }}  
              </span>            
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
          </div>

          <div>
          <x-tooltip>
            <span class="border-b-2 border-dotted border-black text-sm">PLAN NAME</span>
            <template #tooltip>
                <span v-if="isFieldReadonly">{{ paymentTooltipEnum.PLAN_NAME_VIEW }}</span>
                <span v-else >{{ paymentTooltipEnum.PLAN_NAME }}</span>
            </template>            
          </x-tooltip> 
          <x-field class="w-full">
              <span v-if="isFieldReadonly">                
                {{ getPlanName }}
              </span>
              <x-input
                  v-if="!isFieldReadonly"
                  class="w-full"
                  :value="getPlanName"
                  :disabled="true"
                />
            </x-field>
          </div> 

          <div>
            <ToolTip
               title="PAYMENT NO"
              :tooltip="paymentTooltipEnum.PAYMENT_NO"
              :required="!isFieldReadonly"
              />
           <x-field class="w-full">
              <span v-if="isFieldReadonly">
                {{ paymentMethodsForm.payment_no }}
              </span>              
              <select
                  v-if="!isFieldReadonly"
                  class="custom-select"
                  v-model="paymentMethodsForm.payment_no"
                  :options="totalPayments"
                  :rules="[rules.isRequired]"
                  :disabled="!isPaymentNoEnabled"
                  @change="calculatePaymentBreakup()"
                  >
                  <template v-for="option in totalPayments" :key="option.value">
                      <option :value="option.value" :title="option.tooltip">{{ option.label }}</option>                
                  </template>
              </select>
            </x-field>
           </div>
          <div>
          <x-tooltip>
            <span class="border-b-2 border-dotted border-black text-sm">PAYMENT STATUS</span>            
            <template #tooltip>
                <span>{{ paymentTooltipEnum.PAYMENT_STATUS }}</span>
            </template>
          </x-tooltip>
          <x-field class="w-full">
              <span v-if="isFieldReadonly">
                {{ formatString(masterPaymentStatus) }}        
              </span>
              <x-input
                  v-if="!isFieldReadonly"
                  class="w-full"
                  :value="formatString(masterPaymentStatus)"
                  :disabled="true"
                />
            </x-field>
          </div>

          <div>
            <ToolTip
              title="CREDIT APPROVAL"
              :tooltip="paymentTooltipEnum.CREDIT_APPROVAL"              
            />
            <x-field class="w-full">
              <span v-if="isFieldReadonly">                
                {{  creditApprovalReasons.find(item => item.value === paymentMethodsForm.credit_approval)?.label || 'N/A' }}
              </span>
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
          </div>         
          
          <x-field v-if="isCustomReasonEnabled" label="CUSTOM REASON" required class="w-full">
            <span v-if="isFieldReadonly">{{ paymentMethodsForm.custom_reason }}</span>
            <x-input
                v-if="!isFieldReadonly"
                class="w-full"
                v-model="paymentMethodsForm.custom_reason"
                :rules="[rules.isRequired]"                
              />
          </x-field>          
          <div v-if="showDiscountOptions">
            <x-tooltip>
              <span class="border-b-2 border-dotted border-black text-sm">DISCOUNT APPLICABLE (DISCOUNT TYPE)</span> 
              <template #tooltip>
                  <span v-if="isFieldReadonly">{{ paymentTooltipEnum.DISCOUNT_APPLICABLE_VIEW }}</span>
                  <span v-else >{{ paymentTooltipEnum.DISCOUNT_APPLICABLE }}</span>
              </template>
            </x-tooltip>            
            <x-field class="w-full">
            <span v-if="isFieldReadonly">              
              {{ discountTypes.find(item => item.value === paymentMethodsForm.discount)?.label  || 'N/A'}}          
            </span>
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
          </div>
          
          <div v-if="isDiscountReasonEnabled" class="">
            <ToolTip
               title="DISCOUNT REASON"
              :tooltip="(isFieldReadonly)? discountReasons.find(item => item.value === paymentMethodsForm.discount_reason).tooltip: paymentTooltipEnum.DISCOUNT_REASON"
              :required="!isFieldReadonly"
            />          
          <x-field class="w-full">
              <span v-if="isFieldReadonly">                 
                {{ discountReasons.find(item => item.value === paymentMethodsForm.discount_reason).label }}
              </span>
              <select
                  v-if="!isFieldReadonly"
                  :class="{'custom-select-error': isDiscountReasonError}"
                  class="custom-select"
                  v-model="paymentMethodsForm.discount_reason"
                  :options="discountReasons"
                  :rules="[rules.isRequired]"
                  @change="handleDiscountReasonChange"                  
                  >
                  <template v-for="option in discountReasons" :key="option.value">
                      <option :value="option.value" :title="option.tooltip">{{ option.label }}</option>                
                  </template>
              </select>
              <p v-if="isDiscountReasonError" class="text-sm text-red-500 dark:text-red-400 mt-1">This field is required</p>
            </x-field>
          </div>
          
          <x-field v-if="isCustomDiscountReasonEnabled" label="CUSTOM DISCOUNT REASON" :required="!isFieldReadonly" class="w-full">
            <span v-if="isFieldReadonly">{{ paymentMethodsForm.discount_custom_reason }}</span>
            <x-input
                v-if="!isFieldReadonly"
                class="w-full"
                v-model="paymentMethodsForm.discount_custom_reason"
                :rules="[rules.isRequired]"                
              />
          </x-field>
            <div v-if="isDiscountEnabled && paymentMethodsForm.discount!='N/A'">             
              <ToolTip
                  title="DISCOUNT VALUE"
                  :tooltip="paymentTooltipEnum.DISCOUNT_VALUE"
                  class="w-3/6"
                  :required="!isFieldReadonly"                
                />                            
              <x-field class="w-full">
                <span v-if="isFieldReadonly">                  
                  {{ formatAmount(discountValue) }}
                </span>
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
            </div>            
            
            <div v-if="isDiscountEnabled && paymentMethodsForm.discount!='N/A'">            
              <ToolTip
                title="TOTAL AMOUNT"
                :tooltip="paymentTooltipEnum.TOTAL_AMOUNT_VIEW"                
              />
              <x-field class="w-full">
                <span v-if="isFieldReadonly">                  
                  {{ formatAmount(totalAmount)}}            
                </span>
                <x-input
                    v-if="!isFieldReadonly"
                    class="w-full"
                    :value="formatAmount(totalAmount)"
                    :disabled="true"
                  />
              </x-field>
            </div>          
        </div>
        <x-divider class="mb-4 mt-10" />
        
        <div v-if="isPaymentCalculationError" class="flex items-center p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 border border-red-500" role="alert">
          <svg class="flex-shrink-0 inline w-4 h-4 mr-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
          </svg>
          <div>
            The system has detected a discrepancy. Before you hit 'Add Manual Payment,' please check the each payment transaction. If you spot any discrepancies, make the necessary adjustments. Once everything lines up, you're good to proceed.	
          </div>
        </div>
        <div v-if="isFileError" class="flex items-center p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 border border-red-500" role="alert">
          <svg class="flex-shrink-0 inline w-4 h-4 mr-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
          </svg>
          <div>
            {{ fileErrorMessage }}
          </div>
        </div>

        <div class="w-full grid">
          <!-- Header -->          
          <div class="mb-3">
            <h3>Payment Schedule</h3>
          </div>
          <div class="flex w-full">
            <div class="w-1/6 px-2 text-center">
              <span class="relative group text-sm">
                <span class="border-b-2 border-dotted border-black text-sm">PAYMENT NO</span> <sup v-if="!isViewEnabled && !isCreditApprovalView" class="text-red-500">*</sup>
                  <div class="absolute text-left hidden group-hover:block transform transition-transform z-40 h-fit _popoverContent_1wc81_3 top-full bottom-0 _popoverBottom_1wc81_14 left-1/2 right-full -translate-x-1/2 max-w-xs">
                  <div class="dark">
                    <div class="x-popover-container block w-full bg-white dark:bg-gray-700 shadow-lg rounded-md border  border-gray-200 dark:border-gray-800 p-2 text-white text-sm w-max max-w-xs">
                      <span data-v-d0063695="" >
                        {{ paymentTooltipEnum.PAYMENT_NO_2 }}
                      </span>
                    </div>
                  </div>
              </div>
              </span>
            </div>
            <div class="w-1/5 px-2">
              <x-tooltip>
                <span class="text-sm  ">
                  <span class="border-b-2 border-dotted border-black text-sm">PAYMENT METHOD</span> <sup v-if="!isViewEnabled && !isCreditApprovalView" class="text-red-500">*</sup>
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
                  <span class="border-b-2 border-dotted border-black text-sm">TOTAL AMOUNT</span> <sup v-if="!isViewEnabled && !isCreditApprovalView" class="text-red-500">*</sup>
                </span>
                <template #tooltip>
                  <span v-if="isFieldReadonly" >{{ paymentTooltipEnum.TOTAL_AMOUNT_SPLIT_VIEW }}</span>
                  <span v-else >{{ paymentTooltipEnum.TOTAL_AMOUNT }}</span>                  
                </template>
              </x-tooltip>
            </div>
            <div class="w-1/5 px-2">
              
              <x-tooltip v-if="isCreditApprovalView">
                <span v-if="isCreditCardView" class="text-sm">
                  <span class="border-b-2 border-dotted border-black text-sm">CAPTURE AMOUNT</span> <sup class="text-red-500">*</sup>
                </span>
                <span v-else class="text-sm">
                  <span class="border-b-2 border-dotted border-black text-sm">COLLECTED AMOUNT</span> 
                </span>
                <template #tooltip>
                  <span>{{ paymentTooltipEnum.CAPTURE_AMOUNT }}</span>                  
                </template>
              </x-tooltip>
              
              <x-tooltip v-else >
                <span class="text-sm">
                  <span class="border-b-2 border-dotted border-black text-sm">DUE DATE</span> <sup v-if="!isViewEnabled" class="text-red-500">*</sup>
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
                  <span class="border-b-2 border-dotted border-black text-sm">DOCUMENTS</span> <sup v-if="!isViewEnabled && !isCreditApprovalView" class="text-red-500">*</sup>
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
                {{ formatAmount(splitAmountModels[splitPaymentNo]) }}                
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
                    <span class="border-b-2 border-dotted border-black text-sm">CC PAYMENT STATUS INFO</span>
                  </span>
                  <template #tooltip>
                    <span>{{ paymentTooltipEnum.PAYMENT_VIEW_CC_PAYMENT_STATUS }}</span>
                  </template>
                </x-tooltip>
              </div>
              <div class="w-1/5 px-2">
                <x-tooltip>
                  <span class="text-sm  ">
                    <span class="border-b-2 border-dotted border-black text-sm">CC PAYMENT ID</span>
                  </span>
                  <template #tooltip>
                    <span>{{ paymentTooltipEnum.PAYMENT_VIEW_CC_ID }}</span>
                  </template>
                </x-tooltip>
              </div>
              <div class="w-1/5 px-2">
                <x-tooltip>
                  <span class="text-sm  ">
                    <span class="border-b-2 border-dotted border-black text-sm">CC PAYMENT GATEWAY</span>
                  </span>
                  <template #tooltip>
                    <span>{{ paymentTooltipEnum.PAYMENT_VIEW_CC_GATEWAY }}</span>
                  </template>
                </x-tooltip>                
              </div>
              <div class="w-1/5 px-2">
                <x-tooltip>
                  <span class="text-sm  ">
                    <span class="border-b-2 border-dotted border-black text-sm">DIGITAL WALLET</span>
                  </span>
                  <template #tooltip>
                    <span>{{ paymentTooltipEnum.PAYMENT_VIEW_WALLET }}</span>
                  </template>
                </x-tooltip>
              </div>
            </div>

            <div class="flex w-full custombreak pt-1 pb-5" >
              <div class="w-1/6 px-2 text-center"></div>
              <div class="w-1/5 px-2">{{ (splitPaymentRecord.cc_payment_status_info !== null) ? splitPaymentRecord.cc_payment_status_info : 'N/A' }}</div>
              <div class="w-1/5 px-2">{{ splitPaymentRecord.cc_payment_id !== null ? splitPaymentRecord.cc_payment_id : 'N/A' }}</div>
              <div class="w-1/5 px-2">{{ splitPaymentRecord.cc_payment_gateway !== null ? splitPaymentRecord.cc_payment_gateway : 'N/A' }}</div>
              <div class="w-1/5 px-2">{{ splitPaymentRecord.digital_wallet !== null ? splitPaymentRecord.digital_wallet : 'N/A' }}</div>
            </div> 

            <div class="flex w-full custombreak" >
              <div class="w-1/6 px-2 text-center"></div>
              <div class="w-1/5 px-2">
                <x-tooltip>
                  <span class="text-sm  ">
                    <span class="border-b-2 border-dotted border-black text-sm">SAGE RECIEPT ID</span>
                  </span>
                  <template #tooltip>
                    <span>{{ paymentTooltipEnum.PAYMENT_VIEW_SAGE_RECIPT }}</span>
                  </template>
                </x-tooltip>                
              </div>
              <div class="w-1/5 px-2">
                <x-tooltip>
                  <span class="text-sm  ">
                    <span class="border-b-2 border-dotted border-black text-sm">PAYMENT STATUS</span>
                  </span>
                  <template #tooltip>
                    <span>{{ paymentTooltipEnum.PAYMENT_VIEW_STATUS }}</span>
                  </template>
                </x-tooltip>                  
              </div>
              <div class="w-1/5 px-2">
                <x-tooltip>
                  <span class="text-sm  ">
                    <span class="border-b-2 border-dotted border-black text-sm">PAYMENT ALLOCATION STATUS</span>
                  </span>
                  <template #tooltip>
                    <span>{{ paymentTooltipEnum.PAYMENT_VIEW_ALLO_STATUS }}</span>
                  </template>
                </x-tooltip>                
              </div>
              <div class="w-1/5 px-2">
                <x-tooltip>
                  <span class="text-sm  ">
                    <span class="border-b-2 border-dotted border-black text-sm">COLLECTED AMOUNT</span>
                  </span>
                  <template #tooltip>
                    <span>{{ paymentTooltipEnum.PAYMENT_VIEW_COLLECTED_TEXT }}</span>
                  </template>
                </x-tooltip>               
              </div>
            </div>

            <div class="flex w-full custombreak pb-5" >
              <div class="w-1/6 px-2 text-center"></div>
              <div class="w-1/5 px-2">{{ splitPaymentRecord.sage_reciept_id !== null ? splitPaymentRecord.sage_reciept_id : 'N/A' }}</div>
              <div class="w-1/5 px-2">{{ formatString(splitPaymentRecord.payment_status.text) }}</div>
              <div class="w-1/5 px-2">{{ splitPaymentRecord.payment_allocation_status !== null ? formatString(splitPaymentRecord.payment_allocation_status) : 'N/A' }}</div>
              <div class="w-1/5 px-2">{{ splitPaymentRecord.collection_amount !== null ? formatAmount(splitPaymentRecord.collection_amount) : '0.00' }}</div>
            </div>            

          </template>
            <template v-else>              
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
                            v-if = "isCheckDetailsEnabled[count] && (paymentMethodsModels[count] === 'CHQ' || paymentMethodsModels[count] === 'PDC')"
                            v-model="checkDetailModels[count]"
                            placeholder="Cheque Number"
                            :rules="[rules.isRequired]"      
                          />
                        <template #tooltip>
                            <span>{{ paymentTooltipEnum.CHECK_DETAILS }}</span>
                        </template>
                      </x-tooltip>
                    </template>
                  </div>
                  <div class="w-1/5 px-2">                    
                    <template v-if="readOnlyPayments[count]">
                      {{ formatAmount(splitAmountModels[count]) }}
                    </template>
                    <template v-else >
                      <x-input
                        v-model="splitAmountModels[count]"
                        class="w-full"
                        :rules="[rules.isRequired]"              
                      />
                    </template> 
                  </div>
                  
                  
                  <div class="w-1/5 px-2" v-if="isCreditApprovalView">
                    <template v-if="readOnlyPayments[count] && !isCreditCardView">
                      {{ formatAmount(collectionAmountModels[count]) }}
                    </template>
                    <template v-else >                      
                      <x-input
                        v-model="collectionAmountModels[count]"
                        class="w-full"
                        :class="{'custom-select-error': isCreditPaymentInvalid[count]}"                                           
                      />
                      <p v-if="isCreditPaymentInvalid[count]" class="text-sm text-red-500 dark:text-red-400">{{  isCreditPaymentInvalidError[count] }}</p>
                    </template> 
                  </div>
                  <div class="w-1/5 px-2" v-else>
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
                    <x-tooltip v-if="!readOnlyPayments[count]">
                          <Dropzone
                          :id="paymentDocument[0].id"
                          multiple="true"
                          customDisplay="true"
                          :accept="paymentDocument[0].accepted_files"
                          :max-files="paymentDocument[0].max_files"
                          :max-size="paymentDocument[0].max_size"
                          :loading="documentForm.processing"
                          @change="uploadDocument(paymentDocument[0], $event, count)"                          
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
                          &#10006;                
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
        <h3>Notes</h3>
      </div>
      
      <div class="w-full grid">
        <x-field>
          <label v-if="!isViewEnabled">Notes</label>
          <p v-if="(isFieldReadonly && isViewEnabled) || isCreditApprovalView">{{ paymentMethodsForm.notes }}</p>
          <x-input
            v-if="!isViewEnabled && !isCreditApprovalView"
            class="w-full"
            v-model="paymentMethodsForm.notes"       
          />
        </x-field>
      </div>
      <template v-if="(isViewEnabled || isCreditApprovalView) && isDeclineClicked">
        <div class="p-1 mb-2">
          <h3 class="text-white">PAYMENT DECLINE</h3>
        </div>
        <x-divider class="mb-4 mt-1" />        
        <div class="flex w-full" >
              <div class="px-2">
                
                <x-field label="DECLINE REASON" required>
                    <select
                      :class="{'custom-select-error': isDeclinedReasonError}"                  
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
                  <p v-if="isDeclinedReasonError" class="text-sm text-red-500 dark:text-red-400 mt-1">This field is required</p>
                </x-field>
              </div>
              <div v-if="isDeclineCustomReason" class="px-2">                
                <x-field label="CUSTOM REASON" required>
                <x-input
                  class="w-full"
                  v-model="paymentMethodsForm.declined_custom_reason"
                  :rules="[rules.isRequired]"         
                />
              </x-field>
              </div>              
        </div>
      </template>

      <template v-if="isViewEnabled && isApproveClicked && paymentMethodsModels[splitPaymentNo]!='CC'">
        <x-divider class="mb-4 mt-1" />
        <div class="w-1/2 px-2 p-1 mb-2">
          <x-tooltip class="tooltip-display">
          <h3  class="font-bold">Payment Verification</h3>
          <template #tooltip>
              <span>{{ paymentTooltipEnum.PAYMENT_VIEW_VERIFICATION_HEADER }}</span>
          </template>
        </x-tooltip>
        </div>
        <div v-if="isApprovePaymentError" class="flex items-center p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 border border-red-500" role="alert">
          <svg class="flex-shrink-0 inline w-4 h-4 mr-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
          </svg>
          <div>
            {{  approveErrorMessage }}
          </div>
        </div>               
        <div class="flex w-full" >
              <div class="w-1/2 px-2">                
                
                <div>
                <x-tooltip class="tooltip-display">
                    <span class="border-b-2 border-dotted border-black text-sm">COLLECTED AMOUNT <sup class="text-red-500">*</sup></span>       
                    <template #tooltip>
                     <span>{{ paymentTooltipEnum.PAYMENT_VIEW_COLLECTED_AMOUNT }}</span>
                  </template>
                </x-tooltip>
                <x-field class="w-full">
                    <x-input
                      class="w-full"
                      v-model="paymentMethodsForm.collection_amount"
                      :rules="[rules.isRequired,rules.amount]"                      
                    />
                  </x-field>
                </div> 
              </div>
              <div class="w-1/2 px-2" v-if="paymentMethodsModels[splitPaymentNo]=='BT' || paymentMethodsModels[splitPaymentNo]=='CHQ'">                
                <x-tooltip>
                  <span class="border-b-2 border-dotted border-black text-sm">BANK REFERENCE NUMBER 
                    <sup v-if="!(paymentMethodsForm.collection_type === 'insurer'
                      && paymentMethodsModels[splitPaymentNo] === 'CHQ'
                      && paymentMethodsForm.credit_approval != ''
                      )" class="text-red-500">*</sup>
                  
                  </span>   
                  <template #tooltip>
                      <span>{{ paymentTooltipEnum.PAYMENT_VIEW_BANK_REFERENCE }}</span>
                </template>
                </x-tooltip>
                <x-field>
                  <x-input
                    class="w-full"
                    v-model="paymentMethodsForm.bank_reference_number"
                    :rules="[rules.isBankReferenceRequird]"      
                  />
                </x-field>
              </div>
              <div class="w-1/3 px-2">
                <x-tooltip>
                  <span class="border-b-2 border-dotted border-black text-sm">DOCUMENT <sup v-if="paymentMethodsForm.collection_type==='insurer'" class="text-red-500">*</sup></span>   
                <template #tooltip>
                      <span>{{ paymentTooltipEnum.PAYMENT_VIEW_DOCUMENTS }}</span>
                </template>
                </x-tooltip>
                <x-field>
                <Dropzone
                  :id="paymentDocument[1].id"
                  customDisplay="true"
                  multiple="true"
                  :accept="paymentDocument[1].accepted_files"
                  :max-files="paymentDocument[1].max_files"
                  :max-size="paymentDocument[1].max_size"
                  :loading="documentForm.processing"
                  @change="uploadDocument(paymentDocument[1], $event, splitPaymentNo)"                  
                />
                <p v-if="isApprovedDocumentNotUploaded" class="text-sm text-red-500 dark:text-red-400 mt-1">This field is required</p>
                </x-field>
                <div v-for="fileData in approvedDocumentModel[splitPaymentNo]" :key="fileData.id">
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
                      @click="deleteDocument(fileData.doc_name, splitPaymentNo)"
                      v-if = "!readOnlyPayments[splitPaymentNo]"
                    >
                    &#10006; 
                    </span>
                  </span>
                </div>
              </div>
        </div>
      </template>

      <x-divider class="mb-4 mt-1" />     
      
      <template  v-if="isViewEnabled || isCreditApprovalView">
          <template v-if="isApproveConfirm">
            <div class="w-full text-right">Do you wish to proceed with payment confirmation?</div>
            <div class="w-full flex justify-end">
              <div class="mr-4">
                <x-button size="sm" @click="handleNoButtonChange" tabindex="0" class="focus:outline-black">
                  No
                </x-button>
              </div>
              <div>
                <x-button class="mr-2 focus:outline-black" size="sm" color="#ff5e00" type="submit" tabindex="0">
                  Yes
                </x-button>
              </div>
            </div>
          </template>          
          <template v-else-if="isCreditApprovalView || (paymentMethodsModels[splitPaymentNo]!=='CA' && paymentMethodsModels[splitPaymentNo]!=='CC')" >
            <div v-if="isCreditApprovalView || (splitPaymentRecord.payment_status_id!=paymentStatusEnum.PAID && can(permissionEnum.ApprovePayments))" class="w-full flex justify-end">
              <div v-if="isDeclineClicked" class="mr-4">
                <x-button size="sm" @click="handleCancelChanges" tabindex="0" class="focus:outline-black">
                  Cancel
                </x-button>
              </div>            
              <div v-if="(!isApproveClicked && !isDeclineClicked) || (isCreditApprovalView && !isDeclineClicked)" class="mr-4">
                <x-button size="sm"  @click="handleDeclinedChange" tabindex="0" class="focus:outline-black">
                  Decline
                </x-button>
              </div>
              <div v-if="!isApproveClicked && isDeclineClicked" class="mr-4">
                <x-button size="sm"  type="submit" tabindex="0" class="focus:outline-black" >
                  Decline
                </x-button>
              </div>
              <div v-if="!isDeclineClicked && (paymentMethodsModels[splitPaymentNo]!='CC' || isCreditApprovalView)">
                <x-button v-if="!isApproveClicked && isViewEnabled" class="mr-2 focus:outline-black" size="sm" color="#ff5e00" @click="isApproveClicked = !isApproveClicked" tabindex="0">
                  Approve
                </x-button>
                <x-button v-if="isApproveClicked || (isCreditApprovalView && !isDeclineClicked)" class="mr-2 focus:outline-black" size="sm" color="#ff5e00" type="submit" tabindex="0">
                  <template v-if="isCreditApprovalView && isCreditCardView">
                  Capture
                  </template>
                  <template v-else>
                  Approve
                  </template>
                </x-button>
              </div>
            </div>
          </template>        
      </template>
      <template v-else>        
        <div class="w-full md:col-span-4 flex justify-end"> 
            <div v-if="paymentMethodsForm.status == 'edit'" class="mr-4">
              <x-button @click="createPaymentModal=!createPaymentModal" tabindex="0" class="focus:outline-black">
                Cancel
              </x-button>
            </div>
            <div
              v-if="
                paymentMethodsForm.status == 'create' ||
                paymentMethodsForm.status == 'edit'
              "
            >
              <x-button color="emerald" type="submit" tabindex="0" class="focus:outline-black">
                {{ paymentMethodsForm.status == 'create' ? 'Add Manual Payment' : 'Update' }}          
              </x-button>
            </div>          
        </div>
      </template>
      </x-form>
    
      <div class="modal-overlay fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center" v-if="isGalleryModelOpen">
        <div class="modal-container bg-white w-full max-w-full overflow-hidden rounded-lg" tabindex="0" ref="modal2Ref" @keydown="handleKeyDown">        
          <div class="modal-header text-base text-white bg-gray-800">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-2">
                {{ currentFile.original_name }}
              </div>          
              <div class="flex items-center space-x-2" >
                <span @click="closeInnerModal" class="text-gray-300 font-bold cursor-pointer pr-1" >
                  <!-- SVG for Close Modal -->
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" tabindex="0" viewBox="0 0 24 24" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                  </svg>
                </span>
              </div>
            </div>
            <div class="flex items-center justify-between">              
              <div class="flex items-center space-x-2 cursor-pointer" @click="previousFile" :class="{ 'opacity-50 cursor-not-allowed': !hasPreviousFile }">
                <!-- SVG for Previous -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-6 h-6 text-gray-300">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>Previous                
              </div>
              <div class="flex items-center space-x-2" v-if="currentFile.doc_mime_type != 'application/pdf'">
                <div class="flex items-center space-x-2 cursor-pointer" @click="zoomOut">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="h-6 w-6 text-gray-300">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                  </svg>
                </div>
                <div class="flex flex-initial w-24 justify-center">
                  <span class="text-gray-300 font-bold">{{ zoomLevel * 100 }}%</span>
                </div>
                <div class="flex items-center space-x-2 cursor-pointer" @click="zoomIn">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="h-6 w-6 text-gray-300">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                  </svg>
                </div>
              </div>
              <div class="flex items-center space-x-2 cursor-pointer" @click="nextFile" :class="{ 'opacity-50 cursor-not-allowed': !hasNextFile }">
                <!-- SVG for Next -->
                Next
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-6 h-6 text-gray-300">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>                
              </div>            
            </div>
          </div>
          <div class="modal-body w-full h-full mt-2">
            <div v-if="currentFile.doc_mime_type === 'image/jpeg' || currentFile.doc_mime_type === 'image/png'" class="flex items-center justify-center">
              <div class="overflow-auto items-center justify-center">
                <img :src="storageUrl + currentFile.doc_url" :style="{ transform: `scale(${zoomLevel})` }" class="max-w-full max-h-full" />
              </div>
            </div>
            <div v-else-if="currentFile.doc_mime_type === 'application/pdf'" class="w-full h-80vh">
              <embed :src="storageUrl + currentFile.doc_url" type="application/pdf" class="w-full h-full" />
            </div>     
          </div>
        </div>
      </div>
    </x-modal>    
  </div>
</template>
<style scoped>
.h-80vh {
  height: 85vh;
} 
.tooltip-display {
  display: inherit;
} 
/* Modal overlay */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
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
  background-color: hsl(0, 4%, 9%);
  border-radius: 4px;
  padding: 5px;
  z-index: 1050;
}
/* Modal header */
.modal-header {
  background-color: hsl(0, 4%, 9%);
  
}
/* Modal body */
.modal-body {
  padding: 10px 0;
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
  height: 38px;  
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
  margin-left: calc(100% - 39px);
  cursor: pointer;
  color: #333; /* Customize the close icon color */
  font-size: 1.rem;
  font-weight: normal;
}

.close-icon22 {
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
