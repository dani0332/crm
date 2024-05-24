<script setup>
const can = permission => useCan(permission);

const { isRequired } = useRules();

const props = defineProps({
  sendUpdateLog: {
    type: Object,
    required: true,
  },
  insuranceProviders: {
    type: Array,
    required: true,
  },
  selectedCategory: {
    type: Object,
    required: true,
  },
  quote: {
    type: Object,
    required: true,
  },
  quoteType: {
    type: Object,
    required: true,
  },
  bookingDetails: {
    type: Array,
    required: true,
    default: () => {},
  },
  payments: {
    type: Object,
    required: true,
    default: () => [],
  },
  isNegativeValue: {
    type: Boolean,
    required: false,
  },
  realQuote: {
    type: Object,
    required: true,
  },
  updateBtn: {
    type: String,
    required: false,
  },
  uploadedDocuments: {
    type: Array,
    required: false,
  },
  paymentStatusEnum: Object,
});

const state = reactive({
  isEdit: false,
  reversalSectionEdit: false,
});

const page = usePage();
const notification = useToast();
const sendUpdateStatusEnum = page.props.sendUpdateStatusEnum;
const paymentStatusEnum = page.props.paymentStatusEnum;

const dateToYMD = date => {
  if (date) {
    const [year, month, day] = date.split('-');
    return `${year}-${month}-${day}`;
  }
  return '';
};

const isEF = computed(() => {
  return props.selectedCategory.subCategory.slug === sendUpdateStatusEnum.EF;
});

const isCI = computed(() => {
  return props.selectedCategory?.subCategory.slug === sendUpdateStatusEnum.CI;
});

const isCIR = computed(() => {
  return props.selectedCategory?.subCategory.slug === sendUpdateStatusEnum.CIR;
});

const isCPD = computed(() => {
  return props.selectedCategory?.subCategory.slug === sendUpdateStatusEnum.CPD;
});

const hasTaxDocuments = computed(() => {
  // tax invoice and tax invoice raised by buyer.
  return (
    props.uploadedDocuments.includes('SUTAXINV') &&
    props.uploadedDocuments.includes('SUTAXINVRB')
  );
});

const checkSectionTwoEdit = () => {
  const taxInvoiceDoc = [
    sendUpdateStatusEnum.EF,
    sendUpdateStatusEnum.CI,
    sendUpdateStatusEnum.CIR,
    sendUpdateStatusEnum.CPD,
  ];
  const checkTaxInvoiceDoc = taxInvoiceDoc.includes(
    props.selectedCategory.subCategory.slug,
  );

  if (isCPD.value && bookingDetailsForm.reversal_invoice === null) {
    notification.error({
      title: 'Please select tax invoice number for reversal. ',
      position: 'top',
    });
    return;
  }

  if (checkTaxInvoiceDoc && !hasTaxDocuments.value) {
    notification.error({
      title: 'Please upload tax invoice and tax invoice raised by buyer. ',
      position: 'top',
    });
    return;
  }

  state.isEdit = !state.isEdit;
};

const transactionPaymentStatus = computed(() => {
  if (Number(props?.quote?.price_with_vat) === 0) {
    return 'Not Paid';
  }
  if (Number(props?.quote?.premium) > Number(props?.quote?.price_with_vat)) {
    return 'Partially Paid';
  }
  if (Number(props?.quote?.premium) === Number(props?.quote?.price_with_vat)) {
    return 'Paid';
  }
});

function isNotZero(value) {
  if (value === 0 || value === '0.00' || value === null || value === undefined) {
    return false;
  }

  return value;
}

const bookingDetailsForm = useForm({
  id: props.sendUpdateLog.id,
  send_update_type: props.selectedCategory.subCategory.slug,
  booking_date: props.bookingDetails?.booking_date,
  invoice_description: props.bookingDetails?.invoice_description || '',
  broker_invoice_number: props.bookingDetails?.broker_invoice_number || '',
  transaction_payment_status:
    props.bookingDetails?.transaction_payment_status ||
    transactionPaymentStatus.value,
  invoice_date:
    props.bookingDetails?.invoice_date ||
    dateToYMD(props?.payments[0]?.insurer_invoice_date) ||
    '',
  insurer_tax_invoice_number:
    props.bookingDetails?.insurer_tax_invoice_number ||
    props?.payments[0]?.insurer_tax_number ||
    '',
  discount:
    isNotZero(props.bookingDetails?.discount) ||
    props?.payments[0]?.discount_value ||
    '0.00',
  insurer_commission_invoice_number:
    props.bookingDetails?.insurer_commission_invoice_number ||
    props?.payments[0]?.insurer_commmission_invoice_number ||
    '',
  commission_percentage:
    props.bookingDetails?.commission_percentage ||
    props?.payments[0]?.commmission_percentage ||
    '',
  commission_vat_not_applicable:
    props.bookingDetails?.commission_vat_not_applicable ||
    props?.payments[0]?.commission_vat_not_applicable ||
    '0.00',
  vat_on_commission:
    props.bookingDetails?.vat_on_commission ||
    props?.payments[0]?.commission_vat ||
    '',
  commission_vat_applicable:
    props.bookingDetails?.commission_vat_applicable ||
    props?.payments[0]?.commission_vat_applicable ||
    '',
  total_commission:
    props.bookingDetails?.total_commission ||
    props?.payments[0]?.commission ||
    '',
  total_vat_amount: props.bookingDetails?.total_vat_amount || null,
  price_vat_applicable: props.bookingDetails?.price_vat_applicable || '',
  price_vat_not_applicable:
    props.bookingDetails?.price_vat_not_applicable || '0.00',
  total_price: props.bookingDetails?.total_price || '0.00',
  // new entry section related.
  reversal_invoice: props.bookingDetails?.reversal_invoice || null,
});

// convertToNegative function will replace all values in negative if the isNegativeValue is true.
const calculateCommission = () => {
  if (bookingDetailsForm.commission_vat_applicable > 0) {
    if (Number(bookingDetailsForm.price_vat_applicable > 0)) {
      let vat_on_commission =
        bookingDetailsForm.commission_vat_applicable * Number(5 / 100);
      bookingDetailsForm.vat_on_commission =
        convertToNegative(vat_on_commission);

      let total_commission =
        Number(bookingDetailsForm.commission_vat_not_applicable) +
        Number(bookingDetailsForm.commission_vat_applicable) +
        vat_on_commission;
      bookingDetailsForm.total_commission = convertToNegative(total_commission);

      // in this calculation, number 5 is not VAT amount, we need to * the price_vat and price_not_vat with 5% to get the total VAT amount.
      let total_price_with_vat_and_not_vat_applicable =
        Number(bookingDetailsForm.price_vat_applicable) +
        Number(bookingDetailsForm.price_vat_not_applicable);
      let total_vat_amount =
        Number(bookingDetailsForm.price_vat_applicable) * Number(5 / 100);
      bookingDetailsForm.total_vat_amount = convertToNegative(total_vat_amount);

      let total_price =
        total_price_with_vat_and_not_vat_applicable + Number(total_vat_amount);
      bookingDetailsForm.total_price = convertToNegative(total_price);

      bookingDetailsForm.commission_percentage = convertToNegative(
        (total_commission / total_price) * 100,
      );
    } else {
      notification.error({
        title: 'Please add Policy Detail Price (VAT APPLICABLE)',
        position: 'top',
      });
    }
  } else if (bookingDetailsForm.commission_vat_not_applicable > 0) {
    if (Number(props.sendUpdateLog?.price_vat_not_applicable) > 0) {
      bookingDetailsForm.commission_percentage = (
        (bookingDetailsForm.commission_vat_not_applicable /
          props.sendUpdateLog?.price_vat_not_applicable) *
        100
      ).toFixed(2);

      bookingDetailsForm.total_commission =
        bookingDetailsForm.commission_vat_not_applicable;
    } else {
      notification.error({
        title: 'Please add Policy Detail Price (VAT NOT APPLICABLE)',
        position: 'top',
      });
    }
  } else {
    bookingDetailsForm.commission_percentage = '';
    bookingDetailsForm.vat_on_commission = '';
    bookingDetailsForm.total_commission = '';
  }
};

// this function is used to convert the value to negative if the isNegativeValue is true.
function convertToNegative(value) {
  if (props.isNegativeValue) {
    value = -value;
  }
  value = isNaN(value) ? 0 : Number(value);

  return value.toFixed(2);
}

function thousandSeparator(value) {
  if (value === null || value === undefined || value === '') {
    return '';
  }
  return value.toLocaleString('en-US', { minimumFractionDigits: 2 });
}

const saveBookingDetail = isValid => {
  if (!isValid) return;
  // it will check payment related condition.
  let childOptions = [
    sendUpdateStatusEnum.MPC,
    sendUpdateStatusEnum.MDOM,
    sendUpdateStatusEnum.MDOV,
    sendUpdateStatusEnum.ED,
    sendUpdateStatusEnum.DM,
  ];
  if (
    isEF.value &&
    !childOptions.includes(props.selectedCategory.subCategory.option.slug)
  ) {
    /* alert('payment condition will goes here. ');
    return; */
  }
  bookingDetailsForm.post(route('send-update-logs.save-booking-details'), {
    preserveScroll: true,
    onSuccess: () => {
      notification.success({
        title: 'The request has been updated.',
        position: 'top',
      });
      state.isEdit = false;
      location.reload();
    },
    onError: errors => {
      Object.keys(errors).forEach(function (key) {
        notification.error({
          title: errors[key],
          position: 'top',
        });
      });
    },
  });
};

const paymentInvoiceNumberOptions = computed(() => {
  return page.props.paymentInvoices.map(invoice_number => {
    return { label: invoice_number, value: invoice_number };
  });
});

const reversalEntry = reactive({
  booking_date: null,
  invoice_description: props.bookingDetails?.reversal_invoice_description || '',
  broker_invoice_number: null,
  transaction_payment_status: null,
  invoice_date: null,
  insurer_tax_invoice_number: null,
  discount: null,
  insurer_commission_invoice_number: null,
  commission_percentage: null,
  commission_vat_not_applicable: null,
  vat_on_commission: null,
  commission_vat_applicable: null,
  total_commission: null,
  total_vat_amount: null,
  price_vat_applicable: null,
  price_vat_not_applicable: null,
  total_price: null,
});

const loader = reactive({
  sendUpdateSectionBtn: false,
  sendUpdate: false,
  selectInvoice: false,
});

const selectedInvoice = () => {
  loader.selectInvoice = true;
  let url = route('send-update-logs.get-reversal-entries');
  let data = {
    quoteType: props.quoteType,
    quoteUuid: props.realQuote.uuid,
    quoteId: props.realQuote.id,
    taxInvoiceNo: bookingDetailsForm.reversal_invoice,
  };
  axios
    .post(url, data)
    .then(response => {
      updateReversalEntries(response.data);
    })
    .catch(error => {
      // handle the error
    })
    .finally(() => {
      loader.selectInvoice = false;
    });
};

function reverseValue(value) {
  if (value === null || value === undefined || value === '') {
    return '';
  }
  const numericValue = parseFloat(value.toString().replace(/,/g, ''));
  const reversedValue = -numericValue;

  return reversedValue.toLocaleString('en-US', { minimumFractionDigits: 2 });
}

function updateReversalEntries(response) {
  reversalEntry.transaction_payment_status = '';
  reversalEntry.invoice_date = response.insurer_invoice_date || '';
  reversalEntry.insurer_tax_invoice_number = (response.insurer_tax_number !== '') ? response.insurer_tax_number + '-REV' : '';
  reversalEntry.broker_invoice_number = (response.broker_invoice_number !== '') ? response.broker_invoice_number + '-REV' : '';
  reversalEntry.insurer_commission_invoice_number = (response.insurer_commmission_invoice_number !== '') ? response.insurer_commmission_invoice_number + '-REV' : '';
  reversalEntry.discount = response.discount_value || '';
  reversalEntry.price_vat_applicable = response.send_update_log?.price_vat_applicable || '';
  reversalEntry.commission_percentage = ((response.commmission_percentage !== null) ? response.commmission_percentage : response.send_update_log?.commission_percentage) ?? '';
  reversalEntry.price_vat_not_applicable = response.send_update_log?.price_vat_not_applicable || '';
  reversalEntry.vat_on_commission = ((response.commission_vat !== null) ? response.commission_vat : response.send_update_log?.vat_on_commission) ?? '';
  reversalEntry.commission_vat_applicable = response.commission_vat_applicable || '';
  reversalEntry.total_commission = response.commission || '';
  reversalEntry.commission_vat_not_applicable = response.commission_vat_not_applicable || '';
  reversalEntry.total_vat_amount = ((response.total_amount !== null) ? response.total_amount : response.send_update_log?.total_vat_amount) ?? '';
  reversalEntry.total_price = ((response.total_price !== null && response.total_price > 0) ? response.total_price : response.send_update_log?.total_price) ?? '' ;
}

onMounted(() => {
  if (
    props.bookingDetails?.reversal_invoice &&
    props.bookingDetails?.reversal_invoice !== null
  ) {
    selectedInvoice();
  }
});

const onUpdateReversal = () => {
  state.reversalSectionEdit = !state.reversalSectionEdit;
  bookingDetailsForm.transaction_payment_status = '';
  bookingDetailsForm.invoice_date = reversalEntry.invoice_date || '';
  bookingDetailsForm.insurer_tax_invoice_number = (reversalEntry.insurer_tax_invoice_number).replace('REV', 'NEW');
  bookingDetailsForm.broker_invoice_number = (reversalEntry.broker_invoice_number).replace('REV', 'NEW') || '';
  bookingDetailsForm.insurer_commission_invoice_number = (reversalEntry.insurer_commission_invoice_number).replace('REV', 'NEW') || '';
  bookingDetailsForm.discount = reversalEntry.discount || '';
  bookingDetailsForm.commission_percentage = reversalEntry.commission_percentage || '';
  bookingDetailsForm.vat_on_commission = reversalEntry.vat_on_commission || '';
  bookingDetailsForm.commission_vat_applicable = reversalEntry.commission_vat_applicable || '';
  bookingDetailsForm.total_commission = reversalEntry.total_commission || '';
  bookingDetailsForm.commission_vat_not_applicable = reversalEntry.commission_vat_not_applicable || '';
  bookingDetailsForm.total_price = reversalEntry.total_price;
};

function convertToNumber(value) {
  if (value === null || value === undefined || value === '') {
    return 'NaN';
  }

  return -parseFloat(value.toString().replace(/,/g, ''));
}

const modals = reactive({
  sendConfirm: false,
  isConfirmed: false,
  paymentConfirmation: false,
  attestRecord: false,
});

const confirmationCheck = ref(false);
const isStating = ref(false);

const sendUpdatePermissionCheck = computed(() => {
  if (props.updateBtn === sendUpdateStatusEnum.SU) {
    return ! can(page.props.permissionsEnum.BOOK_UPDATE_BUTTON);
  } else if (props.updateBtn === sendUpdateStatusEnum.SUC) {
    return ! can(page.props.permissionsEnum.SEND_UPDATE_TO_CUSTOMER_BUTTON);
  } else if (props.updateBtn === sendUpdateStatusEnum.SNBU) {
    return ! can(page.props.permissionsEnum.SEND_AND_BOOK_UPDATE_BUTTON);
  }

  return true;
});

const sendUpdateValidationURL = computed(() => {
  return (props.updateBtn === sendUpdateStatusEnum.SU || props.sendUpdateLog.status === sendUpdateStatusEnum.UPDATE_SENT_TO_CUSTOMER)
    ? 'send-update'
    : 'send-update-customer-validation';
});
const paymentConfirmationMessage = reactive({ status: '', message: '' });

const sendUpdateValidation = () => {
  loader.sendUpdateSectionBtn = true;
  axios
    .post(sendUpdateValidationURL.value, {
      quoteType: props.quoteType,
      quoteUuid: props.realQuote.uuid,
      sendUpdateId: props.sendUpdateLog.id,
      quoteRefId: props.realQuote.id,
    })
    .then(response => {
      if (response.status == 200) {
        if (props.updateBtn === sendUpdateStatusEnum.SU) {
          if (response.data.insufficientPaymentCheck == true) {
            insuficientPaymentConfirmation(response);
          } else if (response.data.insufficientPaymentCheck == false) {
            attestRecord();
          }
        } else {
          modals.sendConfirm = true;
          isStating.value = response.data.message;
        }
        loader.sendUpdateSectionBtn = false;
      }
    })
    .catch(function (errors) {
      loader.sendUpdateSectionBtn = false;
      if (errors.response.data.errors.error) {
        let responseError = errors.response.data.errors.error;
        Object.keys(responseError).forEach(function (key) {
          if (
            responseError[key] === 'Please select Addons' ||
            responseError[key] === 'Please select Emirate' ||
            responseError[key] === 'Please select Seating capacity'
          ) {
            window.scrollTo(0, 0);
          }
          notification.error({
            title: responseError[key],
            position: 'top',
          });
        });
      } else {
        notification.error({
          title: errors.response.data.message,
          position: 'top',
        });
      }
    });
};

function insuficientPaymentConfirmation(response) {
  let paymentOptions = [
    paymentStatusEnum.PENDING,
    paymentStatusEnum.PARTIALLY_PAID,
    paymentStatusEnum.CREDIT_APPROVED,
  ];
  if (paymentOptions.includes(response.data.parentPaymentStatus)) {
    paymentConfirmationMessage.message =
      'Unpaid policies breach our Code of Conduct and will be escalated to management. Do you still want to continue?';
    switch (response.data.parentPaymentStatus) {
      case paymentStatusEnum.PENDING:
        paymentConfirmationMessage.status = 'Payment not yet completed';
        break;

      case paymentStatusEnum.PARTIALLY_PAID:
        paymentConfirmationMessage.status = 'Insufficient payment received';
        break;

      case paymentStatusEnum.CREDIT_APPROVED:
        paymentConfirmationMessage.status =
          "Pending payment under 'Credit approval'";
        break;
    }

    modals.paymentConfirmation = true;
  } else {
    loader.sendUpdateSectionBtn = false;
    notification.error({
      title: 'Payment status is not valid.',
      position: 'top',
    });
  }
}

function attestRecord() {
  modals.paymentConfirmation = false;
  modals.attestRecord = true;
}

function confirmationModalClose() {
  modals.paymentConfirmation = false;
  modals.attestRecord = false;
  loader.sendUpdateSectionBtn = false;
}

function sendUpdate(prePaymentCheck = true) {
  loader.sendUpdate = true;
  axios
    .post('send-update', {
      quoteType: props.quoteType,
      quoteUuid: props.realQuote.uuid,
      sendUpdateId: props.sendUpdateLog.id,
      quoteRefId: props.realQuote.id,
      paymentValidated: true,
      reversalInvoice: bookingDetailsForm.reversal_invoice ?? '',
    })
    .then(response => {
      loader.sendUpdate = false;
      loader.sendUpdateSectionBtn = false;
      modals.attestRecord = false;
      router.reload({ preserveState: true });
      notification.success({
        title: response.data.message,
        position: 'top',
      });
    })
    .catch(function (errors) {
      loader.sendUpdateSectionBtn = false;
      if (errors.response.data.message !== '') {
        notification.error({
          title: errors.response.data.message,
          position: 'top',
        });
      } else if (errors.response.data.errors.error) {
        let responseError = errors.response.data.errors.error;
        Object.keys(responseError).forEach(function (key) {
          notification.error({
            title: responseError[key],
            position: 'top',
          });
        });
      } else {
        notification.error({
          title: 'Something went wrong',
          position: 'top',
        });
      }
    });
}

const isLoading = ref(false);
const isNotConfirmed = ref(false);

const submitToCustomer = () => {
  if (!modals.isConfirmed) {
    isNotConfirmed.value = true;
    return;
  }
  isLoading.value = true;
  let url = 'send-update-to-customer';
  let data = {
    sendUpdateId: props.sendUpdateLog.id,
    quoteType: props.quoteType,
  };
  axios
    .post(url, data)
    .then(response => {
      if (response.status == 200) {
        notification.success({
          title: 'Update Sent to the Customer',
          position: 'top',
        });
        router.reload({ preserveState: true });
        modals.sendConfirm = isLoading.value = false;
      }
    })
    .catch(err => {
      const flash_messages = err.response.data.errors;
      Object.keys(flash_messages).forEach(function (key) {
        notification.error({
          title: flash_messages[key],
          position: 'top',
        });
      });
    })
    .finally(() => {
      modals.sendConfirm = false;
      isLoading.value = false;
      isNotConfirmed.value = false;
    });
};

const onCancel = () => {
  state.isEdit = false;
  bookingDetailsForm.invoice_date = props.bookingDetails?.invoice_date || null;
  bookingDetailsForm.insurer_tax_invoice_number =
    props.bookingDetails?.insurer_tax_invoice_number || '';
  bookingDetailsForm.insurer_commission_invoice_number =
    props.bookingDetails?.insurer_commission_invoice_number || '';
  bookingDetailsForm.price_vat_applicable =
    props.bookingDetails?.price_vat_applicable || '';
  bookingDetailsForm.commission_vat_applicable =
    props.bookingDetails?.commission_vat_applicable || '';
};

const [sendUpdateConfirmBtnTemp, SendUpdateReuseBtnTemp] = createReusableTemplate();
const [sendUpdateCustConfirmBtnTemp, SendUpdateCustReuseBtnTemp] = createReusableTemplate();

</script>

<template>
  <!-- Reversal Entry -->
  <div class="p-4 rounded shadow mb-6 bg-white" v-if="isCPD">
    <Collapsible expanded>
      <template #header>
        <div class="flex justify-between gap-4 items-center">
          <h3 class="font-semibold text-primary-800 text-lg">
            Booking Details - Reversal Entry
          </h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <div class="text-xs">
          <div class="grid md:grid-cols-2 gap-x-4 gap-y-2 py-4 items-center">
            <div class="grid sm:grid-cols-2 pb-1.5">
              <div class="text-right"></div>
              <div>
                <x-tooltip position="left">
                  <label
                    class="text-[#308BCA] text-sm font-bold underline decoration-dotted decoration-primary-700"
                  >
                    INSURER TAX INVOICE FOR REVERSAL
                  </label>
                  <template #tooltip>
                    This field displays the insurer's tax invoice number
                    associated with this specific lead that requires a reversal.
                  </template>
                </x-tooltip>
              </div>
            </div>
            <div class="grid sm:grid-cols-2 pb-1.5">
              <div>
                <ComboBox
                  v-model="bookingDetailsForm.reversal_invoice"
                  class="w-full"
                  placeholder="Select Tax invoice number"
                  @update:model-value="selectedInvoice"
                  :options="paymentInvoiceNumberOptions"
                  :single="true"
                  :disabled="!state.reversalSectionEdit"
                />
              </div>
            </div>

            <div class="grid sm:grid-cols-2 pb-1.5">
              <div>
                <x-tooltip position="left">
                  <label
                    class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                  >
                    INVOICE DESCRIPTION
                  </label>
                  <template #tooltip>
                    This field provides a brief description of the invoice,
                    summarizing its content or purpose within the booking.
                  </template>
                </x-tooltip>
              </div>
              <div>
                <span>{{ reversalEntry.invoice_description !== '' ? reversalEntry.invoice_description : 'N/A' }}</span>
              </div>
            </div>
            <div class="grid sm:grid-cols-2 pb-1.5">
              <div>
                <x-tooltip position="left">
                  <label
                    class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                  >
                    BOOKING DATE
                  </label>
                  <template #tooltip>
                    The exact date when the booking details was successfully
                    recorded in the system.
                  </template>
                </x-tooltip>
              </div>
              <div>
                <span>{{ reversalEntry.booking_date ?? 'N/A' }}</span>
              </div>
            </div>
            <div class="grid sm:grid-cols-2 pb-1.5">
              <div>
                <x-tooltip position="left">
                  <label
                    class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                  >
                    TRANSACTION PAYMENT STATUS
                  </label>
                  <template #tooltip>
                    This status provides a real-time snapshot of the payment
                    progress for each insurer tax invoice. Make sure to update
                    these statuses regularly to maintain financial accuracy.
                  </template>
                </x-tooltip>
              </div>
              <div>
                <span>{{ (reversalEntry.transaction_payment_status !== '') ? reversalEntry.transaction_payment_status : 'N/A' }}</span>
              </div>
            </div>
            <div class="grid sm:grid-cols-2 pb-1.5">
              <div>
                <x-tooltip position="left">
                  <label
                    class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                  >
                    LINE OF BUSINESS
                  </label>
                  <template #tooltip>
                    Signifies the specific category or type of insurance coverage associated with this booking. It helps categorize the booking by its primary insurance focus, allowing for better organization and classification of insurance transactions.
                  </template>
                </x-tooltip>
              </div>
              <div>
                <span>{{ quoteType ?? 'N/A' }}</span>
              </div>
            </div>
            <div class="grid sm:grid-cols-2 pb-1.5">
              <div>
                <x-tooltip position="left">
                  <label
                    class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                  >
                    INSURER INVOICE DATE
                  </label>
                  <template #tooltip>
                    Signifies the date when the insurer's invoice within the
                    booking was issued.
                  </template>
                </x-tooltip>
              </div>
              <div>
                <span>{{ reversalEntry.invoice_date ?? 'N/A' }}</span>
              </div>
            </div>
            <div class="grid sm:grid-cols-2 pb-1.5">
              <div>
                <x-tooltip position="left">
                  <label
                    class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                  >
                    SUB CLASS
                  </label>
                  <template #tooltip>
                    Identifies the specific coverage or insurance plan offered
                    by the provider.
                  </template>
                </x-tooltip>
              </div>
              <div>N/A</div>
            </div>
            <div class="grid sm:grid-cols-2 pb-1.5">
              <div>
                <x-tooltip position="left">
                  <label
                    class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                  >
                    INSURER TAX INVOICE NUMBER
                  </label>
                  <template #tooltip>
                    Enter the unique tax invoice number provided by the insurer.
                    It helps in proper identification and tracking of
                    transactions
                  </template>
                </x-tooltip>
              </div>
              <div>
                <span>{{ reversalEntry.insurer_tax_invoice_number ?? 'N/A'}}</span>
              </div>
            </div>
            <div class="grid sm:grid-cols-2 pb-1.5">
              <div>
                <x-tooltip position="left">
                  <label
                    class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                  >
                    BROKER INVOICE NUMBER
                  </label>
                  <template #tooltip>
                    Invoice number provided by the broker.
                  </template>
                </x-tooltip>
              </div>
              <div>
                <span>{{ reversalEntry.broker_invoice_number ?? 'N/A' }}</span>
              </div>
            </div>
            <div class="grid sm:grid-cols-2 pb-1.5">
              <div>
                <x-tooltip position="left">
                  <label
                    class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                  >
                    INSURER COMMISSION INVOICE NUMBER
                  </label>
                  <template #tooltip>
                    Input the invoice number issued by the insurer for
                    commission purposes. Double-check for accuracy.
                  </template>
                </x-tooltip>
              </div>
              <div>
                <span>{{
                  reversalEntry.insurer_commission_invoice_number ?? 'N/A'
                }}</span>
              </div>
            </div>
            <div class="grid sm:grid-cols-2 pb-1.5">
              <div>
                <x-tooltip position="left">
                  <label
                    class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                  >
                    DISCOUNT
                  </label>
                  <template #tooltip>
                    If applicable, this field indicates the exact amount or
                    percentage reduced from the original price.
                  </template>
                </x-tooltip>
              </div>
              <div>
                <span>{{ reverseValue(reversalEntry.discount) ?? 'N/A' }}</span>
              </div>
            </div>
            <div class="grid sm:grid-cols-2 pb-1.5">
              <div>
                <x-tooltip position="left">
                  <label
                    class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                  >
                    PRICE (VAT APPLICABLE)
                  </label>
                  <template #tooltip>
                    Price as per the insurer's tax invoice that VAT is
                    applicable. Please enter the price without including Value
                    Added Tax (VAT). VAT will be calculated separately.
                  </template>
                </x-tooltip>
              </div>
              <div>
                <span>{{ (reversalEntry.price_vat_applicable !== '') ? reverseValue(reversalEntry.price_vat_applicable) : 'N/A' }}</span>
              </div>
            </div>
            <div class="grid sm:grid-cols-2 pb-1.5">
              <div>
                <x-tooltip position="left">
                  <label
                    class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                  >
                    COMMISSION (%)
                  </label>
                  <template #tooltip>
                    Commission percentage for this transaction.
                  </template>
                </x-tooltip>
              </div>
              <div>
                <span>{{ reverseValue(reversalEntry.commission_percentage) ?? 'N/A' }}</span>
              </div>
            </div>
            <div class="grid sm:grid-cols-2 pb-1.5">
              <div>
                <x-tooltip position="left">
                  <label
                    class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                  >
                    PRICE (VAT NOT APPLICABLE)
                  </label>
                  <template #tooltip>
                    Price that VAT is not applicable. Remember, VAT is exempt
                    for Life Insurance policies.
                  </template>
                </x-tooltip>
              </div>
              <div>
                <span>{{ (reversalEntry.price_vat_not_applicable !== '') ? reverseValue(reversalEntry.price_vat_not_applicable) : 'N/A' }}</span>
              </div>
            </div>
            <div class="grid sm:grid-cols-2 pb-1.5">
              <div>
                <x-tooltip position="left">
                  <label
                    class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                  >
                    VAT ON COMMISSION
                  </label>
                  <template #tooltip>
                    Value Added Tax (VAT) amount applicable to the commission.
                  </template>
                </x-tooltip>
              </div>
              <div>
                <span>{{ reverseValue(reversalEntry.vat_on_commission) ?? 'N/A'}}</span>
              </div>
            </div>
            <div class="grid sm:grid-cols-2 pb-1.5">
              <div>
                <x-tooltip position="left">
                  <label
                    class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                  >
                    COMMISSION VAT APPLICABLE
                  </label>
                  <template #tooltip>
                    Commission amount as per the tax invoice raised by buyer
                    that VAT is applicable. Enter commission amount without
                    including Value Added Tax (VAT). VAT will be calculated
                    separately.
                  </template>
                </x-tooltip>
              </div>
              <div>
                <span>{{ reverseValue(reversalEntry.commission_vat_applicable) ?? 'N/A' }}</span>
              </div>
            </div>
            <div class="grid sm:grid-cols-2 pb-1.5">
              <div>
                <x-tooltip position="left">
                  <label
                    class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                  >
                    TOTAL COMMISSION
                  </label>
                  <template #tooltip>
                    Display the total commission amount including VAT for this
                    transaction. Ensure it matches the calculations.
                  </template>
                </x-tooltip>
              </div>
              <div>
                <span>{{ reverseValue(reversalEntry.total_commission) ?? 'N/A' }}</span>
              </div>
            </div>

            <div class="grid sm:grid-cols-2 pb-1.5">
              <div>
                <x-tooltip position="left">
                  <label
                    class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                  >
                    COMMISSION (VAT NOT APPLICABLE)
                  </label>
                  <template #tooltip>
                    Commission amount as per the tax invoice raised by buyer
                    that VAT is not applicable.
                  </template>
                </x-tooltip>
              </div>
              <div>
                <span>{{ (reversalEntry.commission_vat_not_applicable !== '') ? reverseValue(reversalEntry.commission_vat_not_applicable) : 'N/A' }}</span>
              </div>
            </div>
            <div class="grid sm:grid-cols-2 pb-1.5">
              <div>
                <x-tooltip position="left">
                  <label
                    class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                  >
                    TOTAL VAT AMOUNT
                  </label>
                  <template #tooltip>
                    Display the total Value Added Tax (VAT) amount for this
                    transaction. Verify this amount before submission.
                  </template>
                </x-tooltip>
              </div>
              <div>
                <span>{{ reverseValue(reversalEntry.total_vat_amount) ?? 'N/A' }}</span>
              </div>
            </div>
            <div class="grid sm:grid-cols-2">
              <div class="font-bold text-right"></div>
              <div></div>
            </div>
            <div class="grid sm:grid-cols-2 pb-1.5">
              <div>
                <x-tooltip position="left">
                  <label
                    class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                  >
                    TOTAL PRICE
                  </label>
                  <template #tooltip>
                    Display the total price including all charges and VAT as per
                    tax invoice. Make sure it aligns with the final transaction
                    amount.
                  </template>
                </x-tooltip>
              </div>
              <div>
                <span>{{ reverseValue(reversalEntry.total_price) ?? 'N/A' }}</span>
              </div>
            </div>
          </div>
        </div>
        <x-divider class="my-4 mt-10" />
        <div class="flex justify-end gap-2">
          <x-button
            size="sm"
            @click="state.reversalSectionEdit = true"
            v-if="!state.reversalSectionEdit"
          >
            Edit
          </x-button>
          <template v-else>
            <x-button
              size="sm"
              color="orange"
              @click="state.reversalSectionEdit = false"
              :loading="loader.selectInvoice"
              :disabled="loader.selectInvoice"
            >
              Cancel
            </x-button>
            <x-button
              size="sm"
              color="primary"
              :loading="loader.selectInvoice"
              :disabled="loader.selectInvoice"
              @click="onUpdateReversal"
            >
              Update
            </x-button>
          </template>
        </div>
      </template>
    </Collapsible>
  </div>

  <!-- Booking Details Or New Entry -->
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible expanded>
      <template #header>
        <div class="flex justify-between gap-4 items-center">
          <h3 class="font-semibold text-primary-800 text-lg">
            Booking Details <span v-if="isCPD"> - New Entry</span>
          </h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <x-form @submit="saveBookingDetail">
          <div class="text-xs">
            <div class="grid md:grid-cols-2 gap-x-4 gap-y-2 py-4 items-center">
              <div class="grid sm:grid-cols-2 pb-1.5">
                <div>
                  <x-tooltip position="left">
                    <label 
                      class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                    >
                      INVOICE DESCRIPTION
                    </label>
                    <template #tooltip>
                      This field provides a brief description of the invoice,
                      summarizing its content or purpose within the booking.
                    </template>
                  </x-tooltip>
                </div>
                <div>
                  <span>
                    {{ bookingDetailsForm.invoice_description !== '' ? bookingDetailsForm.invoice_description : 'N/A' }}
                  </span>
                </div>
              </div>
              <div class="grid sm:grid-cols-2 pb-1.5">
                <div class="font-bold">
                  <x-tooltip position="left">
                    <label
                      class="text-gray-800 underline decoration-dotted decoration-primary-700"
                    >
                      BOOKING DATE
                    </label>
                    <template #tooltip>
                      The exact date when the booking details was successfully
                      recorded in the system.
                    </template>
                  </x-tooltip>
                </div>
                <div>
                  <span>{{ bookingDetailsForm.booking_date ?? 'N/A' }}</span>
                </div>
              </div>
              <div class="grid sm:grid-cols-2 pb-1.5">
                <div>
                  <x-tooltip position="left">
                    <label
                      class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                    >
                      TRANSACTION PAYMENT STATUS
                    </label>
                    <template #tooltip>
                      This status provides a real-time snapshot of the payment
                      progress for each insurer tax invoice. Make sure to update
                      these statuses regularly to maintain financial accuracy.
                    </template>
                  </x-tooltip>
                </div>
                <div>
                  <span>{{
                    bookingDetailsForm.transaction_payment_status ?? 'N/A'
                  }}</span>
                </div>
              </div>
              <div class="grid sm:grid-cols-2 pb-1.5">
                <div>
                  <x-tooltip position="left">
                    <label
                      class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                    >
                      LINE OF BUSINESS
                    </label>
                    <template #tooltip>
                      Signifies the specific category or type of insurance coverage associated with this booking. It helps categorize the booking by its primary insurance focus, allowing for better organization and classification of insurance transactions.
                    </template>
                  </x-tooltip>
                </div>
                <div>
                  <span>{{ quoteType ?? 'N/A' }}</span>
                </div>
              </div>
              <div class="grid sm:grid-cols-2">
                <div>
                  <x-tooltip position="left">
                    <label
                      class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                    >
                      INSURER INVOICE DATE
                    </label>
                    <template #tooltip>
                      Signifies the date when the insurer's invoice within the
                      booking was issued.
                    </template>
                  </x-tooltip>
                </div>
                <div>
                  <DatePicker
                    v-model="bookingDetailsForm.invoice_date"
                    name="issuance_date"
                    :disabled="!state.isEdit"
                    placeholder="Enter Insurer Invoice date"
                    :rules="[isRequired]"
                    size="xs"
                    no-margin
                  />
                  <!-- <span>{{ bookingDetailsForm.invoice_date }}</span> -->
                </div>
              </div>
              <div class="grid sm:grid-cols-2 pb-1.5">
                <div>
                  <x-tooltip position="left">
                    <label
                      class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                    >
                      SUB CLASS
                    </label>
                    <template #tooltip>
                      Identifies the specific coverage or insurance plan offered
                      by the provider.
                    </template>
                  </x-tooltip>
                </div>
                <div>N/A</div>
              </div>
              <div class="grid sm:grid-cols-2">
                <div>
                  <x-tooltip position="left">
                    <label
                      class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                    >
                      INSURER TAX INVOICE NUMBER
                    </label>
                    <template #tooltip>
                      Enter the unique tax invoice number provided by the
                      insurer. It helps in proper identification and tracking of
                      transactions
                    </template>
                  </x-tooltip>
                </div>
                <div>
                  <template v-if="isCPD">
                    {{ bookingDetailsForm.insurer_tax_invoice_number !== '' ? bookingDetailsForm.insurer_tax_invoice_number : 'N/A'}}
                  </template>
                  <x-input
                    v-else
                    maxlength="60"
                    v-model="bookingDetailsForm.insurer_tax_invoice_number"
                    class="!mb-0 w-full"
                    :disabled="!state.isEdit"
                    placeholder="Enter insurer Tax Invoice Number"
                    :rules="[isRequired]"
                    size="xs"
                  />
                  <!-- <span>{{ bookingDetailsForm.insurer_tax_invoice_number }}</span> -->
                </div>
              </div>
              <div class="grid sm:grid-cols-2">
                <div>
                  <x-tooltip position="left">
                    <label
                      class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                    >
                      BROKER INVOICE NUMBER
                    </label>
                    <template #tooltip>
                      Invoice number provided by the broker.
                    </template>
                  </x-tooltip>
                </div>
                <div>
                  <span>{{
                    bookingDetailsForm.broker_invoice_number !== '' ? bookingDetailsForm.broker_invoice_number : 'N/A'
                  }}</span>
                </div>
              </div>
              <div class="grid sm:grid-cols-2">
                <div>
                  <x-tooltip position="left">
                    <label
                      class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                    >
                      INSURER COMMISSION INVOICE NUMBER
                    </label>
                    <template #tooltip>
                      Input the invoice number issued by the insurer for
                      commission purposes. Double-check for accuracy.
                    </template>
                  </x-tooltip>
                </div>
                <div>
                  <template v-if="isCPD">
                    {{ bookingDetailsForm.insurer_commission_invoice_number !== '' ? bookingDetailsForm.insurer_commission_invoice_number : 'N/A'}}
                  </template>
                  <x-input
                    v-else
                    maxlength="60"
                    v-model="
                      bookingDetailsForm.insurer_commission_invoice_number
                    "
                    class="!mb-0 w-full"
                    :disabled="!state.isEdit"
                    placeholder="Enter Commission Tax Invoice No"
                    :rules="[isRequired]"
                    size="xs"
                  />
                  <!--<span>{{ bookingDetailsForm.insurer_commission_invoice_number }}</span>-->
                </div>
              </div>
              <div class="grid sm:grid-cols-2">
                <div>
                  <x-tooltip position="left">
                    <label
                      class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                    >
                      DISCOUNT
                    </label>
                    <template #tooltip>
                      If applicable, this field indicates the exact amount or
                      percentage reduced from the original price.
                    </template>
                  </x-tooltip>
                </div>
                <div>
                  <span>{{ bookingDetailsForm.discount !== '0.00' ? bookingDetailsForm.discount : 'N/A' }}</span>
                </div>
              </div>
              <div class="grid sm:grid-cols-2">
                <div>
                  <x-tooltip position="left">
                    <label
                      class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                    >
                      PRICE (VAT APPLICABLE)
                    </label>
                    <template #tooltip>
                      Price as per the insurer's tax invoice that VAT is
                      applicable. Please enter the price without including Value
                      Added Tax (VAT). VAT will be calculated separately.
                    </template>
                  </x-tooltip>
                </div>
                <div>
                  <x-input
                    type="number"
                    min="0"
                    add step="any"
                    v-model="bookingDetailsForm.price_vat_applicable"
                    @change="calculateCommission"
                    class="!mb-0 w-full"
                    :disabled="!state.isEdit"
                    placeholder="Enter Price"
                    :rules="[isRequired]"
                    size="xs"
                  />
                  <!-- <span>{{ bookingDetailsForm.price_vat_applicable }}</span> -->
                </div>
              </div>
              <div class="grid sm:grid-cols-2">
                <div>
                  <x-tooltip position="left">
                    <label
                      class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                    >
                      COMMISSION (%)
                    </label>
                    <template #tooltip>
                      Commission percentage for this transaction.
                    </template>
                  </x-tooltip>
                </div>
                <div>
                  <span>{{ (bookingDetailsForm.commission_percentage !== '') ? 
                    bookingDetailsForm.commission_percentage + '%' : 
                    'N/A' 
                  }}</span>
                </div>
              </div>
              <div class="grid sm:grid-cols-2">
                <div>
                  <x-tooltip position="left">
                    <label
                      class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                    >
                      PRICE (VAT NOT APPLICABLE)
                    </label>
                    <template #tooltip>
                      Price that VAT is not applicable. Remember, VAT is exempt
                      for Life Insurance policies.
                    </template>
                  </x-tooltip>
                </div>
                <div>
                  <span>{{
                    bookingDetailsForm.price_vat_not_applicable !== '0.00' ? bookingDetailsForm.price_vat_not_applicable : 'N/A'
                  }}</span>
                </div>
              </div>
              <div class="grid sm:grid-cols-2">
                <div>
                  <x-tooltip position="left">
                    <label
                      class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                    >
                      VAT ON COMMISSION
                    </label>
                    <template #tooltip>
                      Value Added Tax (VAT) amount applicable to the commission.
                    </template>
                  </x-tooltip>
                </div>
                <div>
                  <span>{{
                    bookingDetailsForm.vat_on_commission !== '' ? thousandSeparator(bookingDetailsForm.vat_on_commission) : 'N/A'
                  }}</span>
                </div>
              </div>
              <div class="grid sm:grid-cols-2">
                <div>
                  <x-tooltip position="left">
                    <label
                      class="pt-1 font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                    >
                      COMMISSION VAT APPLICABLE
                    </label>
                    <template #tooltip>
                      Commission amount as per the tax invoice raised by buyer
                      that VAT is applicable. Enter commission amount without
                      including Value Added Tax (VAT). VAT will be calculated
                      separately.
                    </template>
                  </x-tooltip>
                </div>
                <div>
                  <x-input
                    type="number"
                    min="0"
                    add step="any"
                    v-model="bookingDetailsForm.commission_vat_applicable"
                    @change="calculateCommission"
                    class="!mb-0 w-full"
                    :disabled="!state.isEdit"
                    placeholder="Enter Commission Amount"
                    :rules="[isRequired]"
                    size="xs"
                  />
                  <!-- <span>{{ bookingDetailsForm.commission_vat_applicable }}</span> -->
                </div>
              </div>
              <div class="grid sm:grid-cols-2">
                <div>
                  <x-tooltip position="left">
                    <label
                      class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                    >
                      TOTAL COMMISSION
                    </label>
                    <template #tooltip>
                      Display the total commission amount including VAT for this
                      transaction. Ensure it matches the calculations.
                    </template>
                  </x-tooltip>
                </div>
                <div>
                  <span>{{
                    bookingDetailsForm.total_commission !== '' ? thousandSeparator(bookingDetailsForm.total_commission) : 'N/A'
                  }}</span>
                </div>
              </div>
              <div class="grid sm:grid-cols-2">
                <div>
                  <x-tooltip position="left">
                    <label
                      class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                    >
                      COMMISSION (VAT NOT APPLICABLE)
                    </label>
                    <template #tooltip>
                      Commission amount as per the tax invoice raised by buyer
                      that VAT is not applicable.
                    </template>
                  </x-tooltip>
                </div>
                <div>
                  <span>{{
                    bookingDetailsForm.commission_vat_not_applicable !== '' ? bookingDetailsForm.commission_vat_not_applicable : 'N/A'
                  }}</span>
                </div>
              </div>
              <div class="grid sm:grid-cols-2">
                <div>
                  <x-tooltip position="left">
                    <label
                      class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                    >
                      TOTAL VAT AMOUNT
                    </label>
                    <template #tooltip>
                      Display the total Value Added Tax (VAT) amount for this
                      transaction. Verify this amount before submission.
                    </template>
                  </x-tooltip>
                </div>
                <div>
                  <span>{{
                    bookingDetailsForm.total_vat_amount !== null ? thousandSeparator(bookingDetailsForm.total_vat_amount) : 'N/A'
                  }}</span>
                </div>
              </div>
              <div class="grid sm:grid-cols-2">
                <div class="font-bold text-right"></div>
                <div></div>
              </div>
              <div class="grid sm:grid-cols-2">
                <div>
                  <x-tooltip position="left">
                    <label
                      class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                    >
                      TOTAL PRICE
                    </label>
                    <template #tooltip>
                      Display the total price including all charges and VAT as
                      per tax invoice. Make sure it aligns with the final
                      transaction amount.
                    </template>
                  </x-tooltip>
                </div>
                <div>
                  <span>
                    {{
                      bookingDetailsForm.total_price !== '0.00' ? thousandSeparator(bookingDetailsForm.total_price) : 'N/A'
                    }}
                  </span>
                </div>
              </div>
            </div>
          </div>
          <x-divider class="my-4 mt-10" />
          <div class="flex justify-end gap-2">
            <template v-if="!state.isEdit">
              <x-button size="sm" @click="checkSectionTwoEdit"> Edit </x-button>
              <x-button
                size="sm"
                color="orange"
                v-if="props.updateBtn"
                :loading="loader.sendUpdateSectionBtn"
                @click="sendUpdateValidation"
                :disabled="sendUpdatePermissionCheck"
              >
                {{ props.updateBtn }}
              </x-button>
            </template>
            <template v-else>
              <x-button
                size="sm"
                color="orange"
                @click="onCancel"
                :loading="bookingDetailsForm.processing"
                :disabled="bookingDetailsForm.processing"
              >
                Cancel
              </x-button>
              <x-button
                size="sm"
                color="#0CA789"
                type="submit"
                :loading="bookingDetailsForm.processing"
                :disabled="bookingDetailsForm.processing"
              >
                Update
              </x-button>
            </template>
          </div>
        </x-form>
      </template>
    </Collapsible>

    <sendUpdateCustConfirmBtnTemp>
      <x-button
        size="sm"
        color="error"
        @click.prevent="submitToCustomer"
        :disabled="!modals.isConfirmed"
        :loading="isLoading"
      >
        Confirm
      </x-button>
    </sendUpdateCustConfirmBtnTemp>

    <x-modal v-model="modals.sendConfirm" show-close backdrop>
      <template #header> Send Update </template>
      <x-alert
        color="orange"
        light
        type="error"
        class="text-sm mb-4"
        v-if="isStating"
      >
        {{ isStating }}
      </x-alert>
      <x-checkbox
        v-model="modals.isConfirmed"
        label="I confirm and attest that all information recorded is correct. I confirm I am in compliance with the COC."
      />
      <template #actions>
        <div class="text-right space-x-4">
          <x-button
            size="sm"
            ghost
            :disabled="isLoading"
            @click.prevent="modals.sendConfirm = false"
          >
            Cancel
          </x-button>
          <template v-if="!modals.isConfirmed">
            <x-tooltip position="left">
              <SendUpdateCustReuseBtnTemp />
              <template #tooltip>
                Please select the checkbox to proceed
              </template>
            </x-tooltip>
          </template>
          <SendUpdateCustReuseBtnTemp v-else />
        </div>
      </template>
    </x-modal>

    <x-modal v-model="modals.paymentConfirmation" backdrop>
      <template #header> Are you sure you want to continue? </template>
      <div class="text-center">
        <p class="font-semibold">{{ paymentConfirmationMessage.status }}</p>
        <p>{{ paymentConfirmationMessage.message }}</p>
      </div>
      <template #actions>
        <div class="text-center space-x-4">
          <x-button
            size="sm"
            ghost
            @click.prevent="confirmationModalClose()"
          >
            Go Back
          </x-button>
          <x-button
            size="sm"
            color="error"
            :loading="loader.sendUpdate"
            @click.prevent="sendUpdate(false)"
          >
            Continue
          </x-button>
        </div>
      </template>
    </x-modal>

    <sendUpdateConfirmBtnTemp>
      <x-button
        size="sm"
        color="error"
        :disabled="!confirmationCheck"
        @click.prevent="sendUpdate()"
        :loading="loader.sendUpdate"
      >
        Confirm
      </x-button>
    </sendUpdateConfirmBtnTemp>

    <x-modal v-model="modals.attestRecord" size="md" show-close backdrop>
      <template #header> Send Update </template>
      <x-checkbox
        v-model="confirmationCheck"
        label="I confirm and attest that all information recorded is correct. I confirm I am in compliance with the COC."
      />
      <template #actions>
        <div class="text-right space-x-4">
          <x-button
            size="sm"
            ghost
            :disabled="isLoading"
            @click.prevent="confirmationModalClose()"
          >
            Cancel
          </x-button>
          <template v-if="!confirmationCheck">
            <x-tooltip position="left">
              <SendUpdateReuseBtnTemp />
              <template #tooltip>
                Please select the checkbox to proceed
              </template>
            </x-tooltip>
          </template>
          <SendUpdateReuseBtnTemp v-else />
        </div>
      </template>
    </x-modal>
  </div>
</template>
