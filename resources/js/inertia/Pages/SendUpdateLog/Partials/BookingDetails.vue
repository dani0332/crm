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
  modelClass: {
    type: String,
    default: '',
  },
  isUpdateBooked: {
    type: Boolean,
    required: true,
  },
  modelClass: {
    type: String,
    default: '',
  },
  isEditDisabledForQueuedBooking: Boolean,
  isCommVatNotAppEnabled: Boolean,
  disableMainBtn: String,
});

const state = reactive({
  isEdit: false,
  reversalSectionEdit: false,
});

const page = usePage();
const notification = useToast();
const sendUpdateStatusEnum = page.props.sendUpdateStatusEnum;
const paymentStatusEnum = page.props.paymentStatusEnum;
const vat = page.props.vatValue;
const quoteTypeCodeEnum = page.props.quoteTypeCodeEnum;
const permissionsEnum = page.props.permissionsEnum;
const productionProcessTooltipEnum = page.props.productionProcessTooltipEnum;

const dateToYMD = date => {
  if (date) {
    // Check if date is already in YMD format
    const ymdRegex = /^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?$/;
    if (ymdRegex.test(date)) {
      return date.split(' ')[0]; // Return only the date part
    }
    const [day, month, year] = date.split('-');
    return `${year}-${month}-${day}`;
  }

  return null;
};

const isEF = computed(() => {
  return props.sendUpdateLog.category.code === sendUpdateStatusEnum.EF;
});

const isCPD = computed(() => {
  return props.sendUpdateLog.category.code === sendUpdateStatusEnum.CPD;
});

const isCIOrCIR = computed(() => {
  return (
    props.sendUpdateLog.category.code === sendUpdateStatusEnum.CI ||
    props.sendUpdateLog.category.code === sendUpdateStatusEnum.CIR
  );
});

const hasTaxDocuments = computed(() => {
  // tax invoice and tax invoice raised by buyer.
  return (
    props.uploadedDocuments.includes('SUTAXINV') &&
    props.uploadedDocuments.includes('SUTAXINVRB')
  );
});

const checkSectionToEdit = () => {
  if (props.isUpdateBooked) {
    notification.error({
      title: 'Update already booked',
      position: 'top',
    });

    return;
  }
  const taxInvoiceDoc = [
    sendUpdateStatusEnum.EF,
    sendUpdateStatusEnum.CI,
    sendUpdateStatusEnum.CIR,
    sendUpdateStatusEnum.CPD,
  ];
  const checkTaxInvoiceDoc = taxInvoiceDoc.includes(
    props.sendUpdateLog.category.code,
  );

  const additionalInvoiceTypes = [
    sendUpdateStatusEnum.ACB,
    sendUpdateStatusEnum.ATIB,
    sendUpdateStatusEnum.ATCRNB,
    sendUpdateStatusEnum.ATCRNB_RBB,
  ];

  if (isCPD.value && bookingDetailsForm.reversal_invoice === null) {
    notification.error({
      title: 'Please select tax invoice number for reversal. ',
      position: 'top',
    });
    return;
  }

  if (
    checkTaxInvoiceDoc &&
    !hasTaxDocuments.value &&
    !additionalInvoiceTypes.includes(props.sendUpdateLog?.option?.code)
  ) {
    notification.error({
      title: 'Please upload tax invoice and tax invoice raised by buyer. ',
      position: 'top',
    });
    return;
  }

  if (
    checkTaxInvoiceDoc &&
    additionalInvoiceTypes.includes(props.sendUpdateLog?.option?.code)
  ) {
    if (
      [sendUpdateStatusEnum.ACB, sendUpdateStatusEnum.ATCRNB_RBB].includes(
        props.sendUpdateLog?.option?.code,
      ) &&
      !props.uploadedDocuments.includes('SUTAXINVRB')
    ) {
      notification.error({
        title: 'Please upload tax invoice raised by buyer',
        position: 'top',
      });
      return;
    }

    if (
      [sendUpdateStatusEnum.ATIB, sendUpdateStatusEnum.ATCRNB].includes(
        props.sendUpdateLog?.option?.code,
      ) &&
      !props.uploadedDocuments.includes('SUTAXINV')
    ) {
      notification.error({
        title: 'Please upload tax invoice',
        position: 'top',
      });
      return;
    }
  }

  state.isEdit = !state.isEdit;
};

const transactionPaymentStatusTooltip = ref('');

const transactionPaymentStatus = computed(() => {
  let payment = props.payments[0];
  if (Number(payment?.captured_amount) < 1) {
    transactionPaymentStatusTooltip.value =
      'This status indicates that no payments have been applied to the associated insurer tax invoice. Regular follow-ups are essential to ensure timely collections.';
    return sendUpdateStatusEnum.UNPAID;
  } else if (
    Number(payment?.captured_amount + payment?.discount_value) <
    Number(payment?.total_price)
  ) {
    transactionPaymentStatusTooltip.value =
      'The invoice has received a portion of its total amount due. Please ensure that the remaining balance is collected promptly to prevent potential financial discrepancies.';
    return sendUpdateStatusEnum.PARTIALLY_PAID;
  } else if (
    Number(payment?.captured_amount + payment?.discount_value) >=
    Number(payment?.total_price)
  ) {
    transactionPaymentStatusTooltip.value =
      'This insurer tax invoice has been settled in its entirety, with no outstanding amounts. Always review payments to guarantee the accuracy of this status.';
    return sendUpdateStatusEnum.FULL_PAID;
  }

  transactionPaymentStatusTooltip.value =
    'This status indicates that no payments have been applied to the associated insurer tax invoice. Regular follow-ups are essential to ensure timely collections.';
  return 'N/A';
});

function isNotZero(value) {
  if (
    value === 0 ||
    value === '0.00' ||
    value === null ||
    value === undefined
  ) {
    return false;
  }

  return value;
}

const bookingDetailsForm = useForm({
  id: props.sendUpdateLog.id,
  send_update_type: props.sendUpdateLog.category.code,
  send_update_option: props.sendUpdateLog?.option?.code ?? null,
  booking_date: props.bookingDetails?.booking_date,
  invoice_description: props.bookingDetails?.invoice_description || '',
  broker_invoice_number: props.bookingDetails?.broker_invoice_number || '',
  transaction_payment_status: 'N/A',
  invoice_date:
    dateToYMD(props.sendUpdateLog?.invoice_date) ||
    dateToYMD(props?.payments[0]?.insurer_invoice_date) ||
    '',
  insurer_tax_invoice_number:
    props.sendUpdateLog?.insurer_tax_invoice_number ||
    props?.payments[0]?.insurer_tax_number ||
    null,
  discount:
    props?.payments[0]?.discount_value ||
    props.sendUpdateLog?.discount ||
    '0.00',
  insurer_commission_invoice_number:
    props.sendUpdateLog?.insurer_commission_invoice_number ||
    props?.payments[0]?.insurer_commmission_invoice_number ||
    '',
  commission_percentage:
    props.sendUpdateLog?.commission_percentage ||
    props?.payments[0]?.commmission_percentage ||
    '',
  commission_vat_not_applicable:
    props.sendUpdateLog?.commission_vat_not_applicable ||
    props?.payments[0]?.commission_vat_not_applicable ||
    '0.00',
  vat_on_commission:
    props.sendUpdateLog?.vat_on_commission ||
    props?.payments[0]?.commission_vat ||
    '',
  commission_vat_applicable:
    Math.abs(props.sendUpdateLog.commission_vat_applicable) ||
    props?.payments[0]?.commission_vat_applicable ||
    '',
  total_commission:
    props.sendUpdateLog?.total_commission ||
    props?.payments[0]?.commission ||
    '',
  total_vat_amount: props.sendUpdateLog?.total_vat_amount || '0.00',
  price_vat_applicable:
    Math.abs(props.sendUpdateLog.price_vat_applicable) || '0.00',
  price_vat_not_applicable:
    Math.abs(props.sendUpdateLog.price_vat_not_applicable) || '0.00',
  price_with_vat: props.sendUpdateLog?.price_with_vat || '0.00',
  // new entry section related.
  reversal_invoice: props.sendUpdateLog?.reversal_invoice || null,
});

// convertToNegative function will replace all values in negative if the isNegativeValue is true.
const calculateCommisionDetailsForACB = () => {
  if (
    bookingDetailsForm.commission_vat_applicable == 0 &&
    bookingDetailsForm.commission_vat_not_applicable == 0
  ) {
    notification.error({
      title: 'Please add Commision VAT or VAT Not Applicable',
      position: 'top',
    });
    return false;
  }

  if (bookingDetailsForm.commission_vat_applicable > 0) {
    let vat_on_commission =
      bookingDetailsForm.commission_vat_applicable * Number(vat / 100);
    bookingDetailsForm.vat_on_commission = convertToNegative(vat_on_commission);

    let total_commission =
      Number(bookingDetailsForm.commission_vat_not_applicable) +
      Number(bookingDetailsForm.commission_vat_applicable) +
      vat_on_commission;
    bookingDetailsForm.total_commission = convertToNegative(total_commission);
  } else if (bookingDetailsForm.commission_vat_not_applicable > 0) {
    bookingDetailsForm.total_commission =
      bookingDetailsForm.commission_vat_not_applicable;
  } else {
    bookingDetailsForm.vat_on_commission = '';
    bookingDetailsForm.total_commission = '';
  }
};

const calculatePriceDetailsForATIB = () => {
  if (
    bookingDetailsForm.price_vat_applicable == 0 &&
    bookingDetailsForm.price_vat_not_applicable == 0
  ) {
    notification.error({
      title:
        'Please add Policy Detail Price (VAT APPLICABLE) or Price (VAT NOT APPLICABLE)',
      position: 'top',
    });
    return false;
  }

  if (
    bookingDetailsForm.price_vat_applicable > 0 ||
    bookingDetailsForm.price_vat_not_applicable > 0
  ) {
    // in this calculation, number 5 is not VAT amount, we need to * the price_vat and price_not_vat with 5% to get the total VAT amount.
    let total_price_with_vat_and_not_vat_applicable =
      Number(bookingDetailsForm.price_vat_applicable) +
      Number(bookingDetailsForm.price_vat_not_applicable);
    let total_vat_amount =
      Number(bookingDetailsForm.price_vat_applicable) * Number(vat / 100);
    bookingDetailsForm.total_vat_amount = convertToNegative(total_vat_amount);

    let price_with_vat =
      total_price_with_vat_and_not_vat_applicable + Number(total_vat_amount);
    bookingDetailsForm.price_with_vat = convertToNegative(price_with_vat);
  }
};

const calculateCommission = () => {
  ignoreCheckDiscount.value = false;
  if (
    [sendUpdateStatusEnum.ACB, sendUpdateStatusEnum.ATCRNB_RBB].includes(
      props.sendUpdateLog?.option?.code,
    )
  ) {
    calculateCommisionDetailsForACB();
  } else if (
    [sendUpdateStatusEnum.ATIB, sendUpdateStatusEnum.ATCRNB].includes(
      props.sendUpdateLog?.option?.code,
    )
  ) {
    calculatePriceDetailsForATIB();
  } else {
    if (
      bookingDetailsForm.commission_vat_applicable > 0 ||
      bookingDetailsForm.price_vat_applicable > 0 ||
      bookingDetailsForm.price_vat_not_applicable > 0
    ) {
      if (
        Number(bookingDetailsForm.price_vat_applicable > 0) ||
        Number(bookingDetailsForm.price_vat_not_applicable > 0)
      ) {
        let vat_on_commission =
          bookingDetailsForm.commission_vat_applicable * Number(vat / 100);
        bookingDetailsForm.vat_on_commission =
          convertToNegative(vat_on_commission);

        let total_commission =
          Number(bookingDetailsForm.commission_vat_not_applicable) +
          Number(bookingDetailsForm.commission_vat_applicable) +
          vat_on_commission;
        bookingDetailsForm.total_commission =
          convertToNegative(total_commission);

        // in this calculation, number 5 is not VAT amount, we need to * the price_vat and price_not_vat with 5% to get the total VAT amount.
        let total_price_with_vat_and_not_vat_applicable =
          Number(bookingDetailsForm.price_vat_applicable) +
          Number(bookingDetailsForm.price_vat_not_applicable);
        let total_vat_amount =
          Number(bookingDetailsForm.price_vat_applicable) * Number(vat / 100);
        bookingDetailsForm.total_vat_amount =
          convertToNegative(total_vat_amount);

        let price_with_vat =
          total_price_with_vat_and_not_vat_applicable +
          Number(total_vat_amount);
        bookingDetailsForm.price_with_vat = convertToNegative(price_with_vat);

        let commissionVatApplicable = Number(
          bookingDetailsForm.commission_vat_applicable,
        );
        let commissionVatNotApplicable = Number(
          bookingDetailsForm.commission_vat_not_applicable,
        );
        bookingDetailsForm.commission_percentage = convertToNegative(
          (Number(commissionVatApplicable + commissionVatNotApplicable) /
            total_price_with_vat_and_not_vat_applicable) *
            100,
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
      bookingDetailsForm.commission_percentage = '0.00';
      bookingDetailsForm.vat_on_commission = '0.00';
      bookingDetailsForm.total_commission = '0.00';
    }
  }
};

const isReversalNegative = ref(false);

// this function is used to convert the value to negative if the isNegativeValue is true.
const isNegativeValue = computed(() => {
  return props.isNegativeValue || isReversalNegative.value;
});

function convertToNegative(value) {
  if (isNegativeValue.value) {
    value = -value;
  }
  value = isNaN(value) ? 0 : Number(value);

  return (Math.round(value * 100) / 100).toFixed(2);
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
  if (isEF.value && !childOptions.includes(props.sendUpdateLog.option.code)) {
    /* alert('payment condition will goes here. ');
    return; */
  }
  bookingDetailsForm.post(route('send-update.save-booking-details'), {
    preserveScroll: true,
    onSuccess: () => {
      notification.success({
        title: 'The request has been updated.',
        position: 'top',
      });
      if (props?.bookingDetails?.isLackingOfPayment) {
        notification.error({
          title:
            'Action Needed: Please revise payment details to reflect plan changes.',
          position: 'top',
          timeout: 10000,
        });
      }
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
  broker_invoice_number:
    props.sendUpdateLog?.reversal_broker_invoice_number ?? null,
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

watch(
  () => reversalEntry.price_with_vat,
  (newValue, oldValue) => {
    isReversalNegative.value = newValue < 0;
  },
);

const loader = reactive({
  sendUpdateSectionBtn: false,
  sendUpdate: false,
  selectInvoice: false,
});

const selectedInvoice = () => {
  loader.selectInvoice = true;
  let url = route('send-update.get-reversal-entries');
  let data = {
    quoteType: props.quoteType,
    quoteUuid: props.realQuote.uuid,
    quoteId: props.realQuote.id,
    taxInvoiceNo: bookingDetailsForm.reversal_invoice,
  };
  axios
    .post(url, data)
    .then(response => {
      let sendUpdate = response.data.send_update_log;
      let payment = response.data.payment;
      updateReversalEntries(payment, sendUpdate);
    })
    .catch(error => {
      const flash_messages = error.response.data.errors;
      Object.keys(flash_messages).forEach(function (key) {
        notification.error({
          title: flash_messages[key],
          position: 'top',
        });
      });
    })
    .finally(() => {
      loader.selectInvoice = false;
    });
};

function reverseValue(value) {
  if (value === null || value === undefined || value === '') {
    return 'N/A';
  }
  const numericValue = parseFloat(value.toString().replace(/,/g, ''));
  const reversedValue = -numericValue;

  return reversedValue.toLocaleString('en-US', { minimumFractionDigits: 2 });
}

function updateReversalEntries(payment, sendUpdate) {
  reversalEntry.transaction_payment_status = 'N/A';
  reversalEntry.invoice_description =
    payment.invoice_description || sendUpdate.invoice_description || '';
  reversalEntry.booking_date =
    props.sendUpdateLog.status !== sendUpdateStatusEnum.UPDATE_BOOKED
      ? 'N/A'
      : dateToDMY(props.realQuote?.policy_booking_date) ||
        dateToDMY(props.quote?.policy_booking_date) ||
        dateToDMY(props.sendUpdateLog?.booking_date) ||
        '';
  reversalEntry.invoice_date =
    payment.insurer_invoice_date || sendUpdate.invoice_date || '';
  reversalEntry.insurer_tax_invoice_number = payment?.insurer_tax_number
    ? payment.insurer_tax_number + '-REV'
    : sendUpdate.insurer_tax_invoice_number + '-REV';
  reversalEntry.broker_invoice_number =
    props.sendUpdateLog?.reversal_broker_invoice_number ?? null;
  reversalEntry.insurer_commission_invoice_number =
    payment?.insurer_commmission_invoice_number
      ? payment.insurer_commmission_invoice_number + '-REV'
      : sendUpdate.insurer_commission_invoice_number + '-REV';
  reversalEntry.discount = payment.discount_value || null;
  reversalEntry.price_vat_applicable =
    sendUpdate?.price_vat_applicable ||
    payment.paymentable?.price_vat_applicable;
  reversalEntry.commission_percentage =
    sendUpdate?.commission_percentage || payment.commmission_percentage || null;
  reversalEntry.price_vat_not_applicable =
    sendUpdate?.price_vat_not_applicable ||
    payment.paymentable?.price_vat_not_applicable;
  reversalEntry.vat_on_commission =
    (payment.commission_vat !== null
      ? payment.commission_vat
      : sendUpdate?.vat_on_commission) ?? null;
  reversalEntry.commission_vat_applicable =
    sendUpdate?.commission_vat_applicable ||
    payment.commission_vat_applicable ||
    null;
  reversalEntry.total_commission =
    sendUpdate?.total_commission || payment.commission || null;
  reversalEntry.commission_vat_not_applicable =
    sendUpdate?.commission_vat_not_applicable ||
    payment.commission_vat_not_applicable ||
    null;
  reversalEntry.total_vat_amount =
    sendUpdate?.total_vat_amount || props.realQuote?.vat;
  reversalEntry.price_with_vat =
    (payment.total_price !== null && payment.total_price > 0
      ? payment.total_price
      : sendUpdate?.price_with_vat) ?? null;
}

onMounted(() => {
  if (
    props.sendUpdateLog?.reversal_invoice &&
    props.sendUpdateLog?.reversal_invoice !== null
  ) {
    selectedInvoice();
  }
});

const ignoreCheckDiscount = ref(false);

const onUpdateReversal = () => {
  ignoreCheckDiscount.value = true;
  state.reversalSectionEdit = !state.reversalSectionEdit;
  bookingDetailsForm.transaction_payment_status = 'N/A';
  bookingDetailsForm.invoice_date = reversalEntry.invoice_date || '';
  bookingDetailsForm.insurer_tax_invoice_number =
    reversalEntry.insurer_tax_invoice_number.replace('REV', 'NEW');
  bookingDetailsForm.broker_invoice_number =
    props.sendUpdateLog?.broker_invoice_number ?? '';
  bookingDetailsForm.insurer_commission_invoice_number =
    reversalEntry.insurer_commission_invoice_number.replace('REV', 'NEW') || '';
  bookingDetailsForm.price_vat_applicable =
    Math.abs(reversalEntry.price_vat_applicable) || '0.00';
  bookingDetailsForm.commission_percentage =
    reversalEntry.commission_percentage || null;
  bookingDetailsForm.price_vat_not_applicable =
    Math.abs(reversalEntry.price_vat_not_applicable) || '0.00';
  bookingDetailsForm.vat_on_commission =
    reversalEntry.vat_on_commission || '0.00';
  bookingDetailsForm.commission_vat_applicable =
    Math.abs(reversalEntry.commission_vat_applicable) || null;
  bookingDetailsForm.total_commission = reversalEntry.total_commission || null;
  bookingDetailsForm.commission_vat_not_applicable =
    reversalEntry.commission_vat_not_applicable || null;
  bookingDetailsForm.total_vat_amount =
    reversalEntry.total_vat_amount || '0.00';
  bookingDetailsForm.price_with_vat = reversalEntry.price_with_vat;
  bookingDetailsForm.discount = null;
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
    return !can(permissionsEnum.BOOK_UPDATE_BUTTON);
  } else if (props.updateBtn === sendUpdateStatusEnum.SUC) {
    return !can(permissionsEnum.SEND_UPDATE_TO_CUSTOMER_BUTTON);
  } else if (props.updateBtn === sendUpdateStatusEnum.SNBU) {
    return !can(permissionsEnum.SEND_AND_BOOK_UPDATE_BUTTON);
  }

  return true;
});

const isLackingPayment = computed(() => {
  return props.bookingDetails?.isLackingOfPayment || false;
});

const sendUpdateValidationURL = computed(() => {
  return props.updateBtn === sendUpdateStatusEnum.SU ||
    props.sendUpdateLog.status === sendUpdateStatusEnum.UPDATE_SENT_TO_CUSTOMER
    ? 'book-update-validation'
    : 'send-update-customer-validation';
});
const paymentConfirmationMessage = reactive({ status: '', message: '' });

const actionButton = computed(() => {
  if (props.updateBtn === sendUpdateStatusEnum.SNBU) {
    return sendUpdateStatusEnum.ACTION_SNBU;
  } else if (props.updateBtn === sendUpdateStatusEnum.SUC) {
    return sendUpdateStatusEnum.ACTION_SUC;
  } else if (props.updateBtn === sendUpdateStatusEnum.SU) {
    return sendUpdateStatusEnum.ACTION_SU;
  }

  return '';
});

const isSendUpdateWithEmail = ref(false);
const sendUpdateValidation = () => {
  loader.sendUpdateSectionBtn = true;
  axios
    .post(sendUpdateValidationURL.value, {
      quoteType: props.quoteType,
      quoteUuid: props.realQuote.uuid,
      sendUpdateId: props.sendUpdateLog.id,
      quoteRefId: props.realQuote.id,
      action: actionButton.value,
      inslyMigrated: props.realQuote.insly_migrated,
    })
    .then(response => {
      if (response.status == 200) {
        if (
          response.data.action === sendUpdateStatusEnum.ACTION_SNBU &&
          sendUpdateValidationURL.value === 'send-update-customer-validation'
        ) {
          isSendUpdateWithEmail.value = true;
        }
        if (
          props.updateBtn === sendUpdateStatusEnum.SU ||
          response.data.action === sendUpdateStatusEnum.ACTION_SNBU
        ) {
          if (response.data.insufficientPaymentCheck == true) {
            insuficientPaymentConfirmation(response);
          } else if (response.data.insufficientPaymentCheck == false) {
            if (
              response.data.action === sendUpdateStatusEnum.ACTION_SNBU &&
              sendUpdateValidationURL.value ===
                'send-update-customer-validation'
            ) {
              modals.sendConfirm = true;
              isStating.value = response.data?.message;
            } else {
              attestRecord();
            }
          }
        } else {
          modals.sendConfirm = true;
          isStating.value = response.data?.message;
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
            updateAdditionalError();
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

// Need to update this code after Mirza's Implementation
function sendUpdate(prePaymentCheck = true) {
  loader.sendUpdate = true;
  axios
    .post('book-update', {
      quoteType: props.quoteType,
      quoteUuid: props.realQuote.uuid,
      sendUpdateId: props.sendUpdateLog.id,
      quoteRefId: props.realQuote.id,
      paymentValidated: true,
      reversalInvoice: bookingDetailsForm.reversal_invoice ?? '',
      inslyMigrated: props.realQuote.insly_migrated,
    })
    .then(response => {
      loader.sendUpdate = false;
      loader.sendUpdateSectionBtn = false;
      modals.attestRecord = false;
      modals.paymentConfirmation = false;
      router.reload({ preserveState: true });
      notification.success({
        title: response.data.message,
        position: 'top',
      });
    })
    .catch(function (errors) {
      loader.sendUpdate = false;
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

// Need to update this code after Mirza's Implementation
const submitToCustomer = (withPartialPaymentCheck = true) => {
  if (!modals.isConfirmed && withPartialPaymentCheck) {
    isNotConfirmed.value = true;
    return;
  }
  isLoading.value = true;
  let url = 'send-update-to-customer';
  let data = {
    sendUpdateId: props.sendUpdateLog.id,
    quoteType: props.quoteType,
    action: actionButton.value,
    quoteUuid: props.realQuote.uuid,
    quoteRefId: props.realQuote.id,
    paymentValidated: true,
    reversalInvoice: bookingDetailsForm.reversal_invoice ?? '',
    inslyMigrated: props.realQuote.insly_migrated,
    isEmailSent: props.sendUpdateLog.is_email_sent,
  };
  axios
    .post(url, data)
    .then(response => {
      if (response.status == 200) {
        Object.keys(response.data).forEach(function (key) {
          if (response.data[key]['status'] == 200) {
            notification.success({
              title: response.data[key]['message'],
              position: 'top',
            });
          } else {
            notification.error({
              title: response.data[key]['message'],
              position: 'top',
            });
          }
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

  bookingDetailsForm.price_vat_applicable =
    props.bookingDetails?.price_vat_applicable || '';
  bookingDetailsForm.commission_vat_applicable =
    props.bookingDetails?.commission_vat_applicable || '';

  bookingDetailsForm.insurer_commission_invoice_number =
    props.bookingDetails?.insurer_commission_invoice_number || '';
};

const [sendUpdateConfirmBtnTemp, SendUpdateReuseBtnTemp] =
  createReusableTemplate();
const [sendUpdateCustConfirmBtnTemp, SendUpdateCustReuseBtnTemp] =
  createReusableTemplate();

watch(
  () => props?.payments[0]?.discount_value,
  (newValue, oldValue) => {
    bookingDetailsForm.discount = newValue;
  },
);

const isPriceVatNotApplicableEditable = computed(() => {
  return (
    props.quoteType === quoteTypeCodeEnum.Business ||
    props.quoteType === quoteTypeCodeEnum.Health ||
    props.quoteType === quoteTypeCodeEnum.Life
  );
});

const isPriceVatApplicableEditable = computed(() => {
  return (
    (isCIOrCIR.value || isEF.value || isCPD.value) &&
    props.quoteType !== quoteTypeCodeEnum.Life
  );
});

const emit = defineEmits(['update-error-status']);

function updateAdditionalError() {
  const newErrorStatus = 'This field is required.'; // Determine the new status based on your logic
  emit('update-error-status', newErrorStatus);
}

const onReversalEdit = () => {
  if (props.isUpdateBooked) {
    notification.error({
      title: 'Update already booked',
      position: 'top',
    });
  } else {
    state.reversalSectionEdit = true;
  }
};

const checkDiscount = (newPrice, oldPrice) => {
  let paymentTotalPrice = Number(props?.payments[0]?.total_price);
  let paymentTotalAmount = Number(props?.payments[0]?.total_amount);
  let savedPriceWithVat = Number(props.sendUpdateLog?.price_with_vat);
  let savedDiscount =
    Number(props?.payments[0]?.discount_value) ||
    Number(props.sendUpdateLog.discount) ||
    0;

  if (newPrice > savedPriceWithVat) {
    bookingDetailsForm.discount = Number(
      savedDiscount + (newPrice - savedPriceWithVat),
    ).toFixed(2);
  } else {
    if (newPrice < savedPriceWithVat) {
      let paymentDifference =
        paymentTotalPrice - paymentTotalAmount - savedDiscount;
      if (paymentTotalPrice - paymentDifference == newPrice) {
        // don't use ===
        bookingDetailsForm.discount = savedDiscount;
      } else {
        let discount = Number(
          savedDiscount - (savedPriceWithVat - newPrice),
        ).toFixed(2);
        if (!(discount < 0)) {
          // negative value should not apply.
          bookingDetailsForm.discount = discount;
        }
      }
    } else {
      bookingDetailsForm.discount = savedDiscount;
    }
  }

  bookingDetailsForm.discount =
    bookingDetailsForm.discount > 0.99 ? 0 : bookingDetailsForm.discount;
};

const dateToDMY = date => {
  if (date) {
    // Check if date is already in DMY format
    const dmyRegex = /^\d{2}-\d{2}-\d{4}( \d{2}:\d{2}:\d{2})?$/;
    if (dmyRegex.test(date)) {
      return date.split(' ')[0]; // Return only the date part
    }
    const ymdRegex = /^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?$/;
    if (ymdRegex.test(date)) {
      const [year, month, day] = date.split(' ')[0].split('-');
      return `${day}-${month}-${year}`;
    }
  }

  return null;
};

watch(
  () => props.sendUpdateLog.insurance_provider_id,
  (newValue, oldValue) => {
    bookingDetailsForm.broker_invoice_number =
      props.bookingDetails.broker_invoice_number;
    bookingDetailsForm.invoice_description =
      props.bookingDetails.invoice_description;
  },
);

const noDiscountType = computed(() => {
  const noDiscountTypeOptions = [
    sendUpdateStatusEnum.MPC,
    sendUpdateStatusEnum.MDOM,
    sendUpdateStatusEnum.DM,
    sendUpdateStatusEnum.DTSI,
    sendUpdateStatusEnum.DOV,
    sendUpdateStatusEnum.ED,
    sendUpdateStatusEnum.ATIB,
    sendUpdateStatusEnum.ACB,
    sendUpdateStatusEnum.ATCRNB,
    sendUpdateStatusEnum.ATCRNB_RBB,
    sendUpdateStatusEnum.ATCRN_CRNRBB,
  ];

  return (
    noDiscountTypeOptions.includes(props.sendUpdateLog?.option?.code) ||
    isCIOrCIR.value
  );
});

watch(
  () => bookingDetailsForm.price_with_vat,
  (newValue, oldValue) => {
    if (!(noDiscountType.value || ignoreCheckDiscount.value)) {
      checkDiscount(newValue, oldValue);
    }
  },
);

watch(
  () => props.bookingDetails?.broker_invoice_number,
  (newValue, oldValue) => {
    bookingDetailsForm.broker_invoice_number = newValue;
  },
);

const disableCommissionVatNotApplicable = ref(false);

watch(
  () => bookingDetailsForm.commission_vat_applicable,
  (newValue, oldValue) => {
    if (props.isCommVatNotAppEnabled && newValue > 0) {
      disableCommissionVatNotApplicable.value = true;
    } else {
      disableCommissionVatNotApplicable.value = false;
    }
  },
);

const disableCommissionVatApplicable = ref(false);

watch(
  () => bookingDetailsForm.commission_vat_not_applicable,
  (newValue, oldValue) => {
    if (props.isCommVatNotAppEnabled && newValue > 0) {
      disableCommissionVatApplicable.value = true;
    } else {
      disableCommissionVatApplicable.value = false;
    }
  },
);
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
                <x-tooltip>
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
                <x-tooltip>
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
                <span>{{
                  reversalEntry.invoice_description !== ''
                    ? reversalEntry.invoice_description
                    : 'N/A'
                }}</span>
              </div>
            </div>
            <div class="grid sm:grid-cols-2 pb-1.5">
              <div>
                <x-tooltip>
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
                <span>{{ reversalEntry.booking_date }}</span>
              </div>
            </div>
            <div class="grid sm:grid-cols-2 pb-1.5">
              <div>
                <x-tooltip>
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
                  reversalEntry.transaction_payment_status ?? 'N/A'
                }}</span>
              </div>
            </div>
            <div class="grid sm:grid-cols-2 pb-1.5">
              <div>
                <x-tooltip>
                  <label
                    class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                  >
                    LINE OF BUSINESS
                  </label>
                  <template #tooltip>
                    Signifies the specific category or type of insurance
                    coverage associated with this booking. It helps categorize
                    the booking by its primary insurance focus, allowing for
                    better organization and classification of insurance
                    transactions.
                  </template>
                </x-tooltip>
              </div>
              <div>
                <span>{{ quoteType ?? 'N/A' }}</span>
              </div>
            </div>
            <div class="grid sm:grid-cols-2 pb-1.5">
              <div>
                <x-tooltip>
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
                <span>{{
                  dateToDMY(reversalEntry.invoice_date) ?? 'N/A'
                }}</span>
              </div>
            </div>
            <div class="grid sm:grid-cols-2 pb-1.5">
              <div>
                <x-tooltip>
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
                <x-tooltip>
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
                <span>{{
                  reversalEntry.insurer_tax_invoice_number ?? 'N/A'
                }}</span>
              </div>
            </div>
            <div class="grid sm:grid-cols-2 pb-1.5">
              <div>
                <x-tooltip>
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
                <x-tooltip>
                  <label
                    class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                  >
                    INSURER COMMISSION TAX INVOICE NUMBER
                  </label>
                  <template #tooltip>
                    {{
                      productionProcessTooltipEnum.INSURER_COMMISSION_TAX_INVOICE_NUMBER
                    }}
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
                <x-tooltip>
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
                <span>{{
                  reversalEntry.discount !== null
                    ? reverseValue(reversalEntry.discount)
                    : 'N/A'
                }}</span>
              </div>
            </div>
            <div class="grid sm:grid-cols-2 pb-1.5">
              <div>
                <x-tooltip>
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
                <span>{{
                  reversalEntry.price_vat_applicable !== null
                    ? reverseValue(reversalEntry.price_vat_applicable)
                    : 'N/A'
                }}</span>
              </div>
            </div>
            <div class="grid sm:grid-cols-2 pb-1.5">
              <div>
                <x-tooltip>
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
                <span>{{
                  reversalEntry.commission_percentage !== null
                    ? reverseValue(reversalEntry.commission_percentage) + '%'
                    : 'N/A'
                }}</span>
              </div>
            </div>
            <div class="grid sm:grid-cols-2 pb-1.5">
              <div>
                <x-tooltip>
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
                  reversalEntry.price_vat_not_applicable !== null
                    ? reverseValue(reversalEntry.price_vat_not_applicable)
                    : 'N/A'
                }}</span>
              </div>
            </div>
            <div class="grid sm:grid-cols-2 pb-1.5">
              <div>
                <x-tooltip>
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
                  reversalEntry.vat_on_commission !== null
                    ? reverseValue(reversalEntry.vat_on_commission)
                    : 'N/A'
                }}</span>
              </div>
            </div>
            <div class="grid sm:grid-cols-2 pb-1.5">
              <div>
                <x-tooltip>
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
                <span>{{
                  reversalEntry.commission_vat_applicable !== null
                    ? reverseValue(reversalEntry.commission_vat_applicable)
                    : 'N/A'
                }}</span>
              </div>
            </div>
            <div class="grid sm:grid-cols-2 pb-1.5">
              <div>
                <x-tooltip>
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
                  reversalEntry.total_commission !== null
                    ? reverseValue(reversalEntry.total_commission)
                    : 'N/A'
                }}</span>
              </div>
            </div>

            <div class="grid sm:grid-cols-2 pb-1.5">
              <div>
                <x-tooltip>
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
                  reversalEntry.commission_vat_not_applicable !== null
                    ? reverseValue(reversalEntry.commission_vat_not_applicable)
                    : 'N/A'
                }}</span>
              </div>
            </div>
            <div class="grid sm:grid-cols-2 pb-1.5">
              <div>
                <x-tooltip>
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
                  reversalEntry.total_vat_amount !== null
                    ? reverseValue(reversalEntry.total_vat_amount)
                    : 'N/A'
                }}</span>
              </div>
            </div>
            <div class="grid sm:grid-cols-2">
              <div class="font-bold text-right"></div>
              <div></div>
            </div>
            <div class="grid sm:grid-cols-2 pb-1.5">
              <div>
                <x-tooltip>
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
                <span>{{
                  reversalEntry.price_with_vat !== null
                    ? reverseValue(reversalEntry.price_with_vat)
                    : 'N/A'
                }}</span>
              </div>
            </div>
          </div>
        </div>
        <x-divider class="my-4 mt-10" />
        <div class="flex justify-end gap-2">
          <template v-if="!state.reversalSectionEdit">
            <x-tooltip v-if="props.isEditDisabledForQueuedBooking">
              <x-button
                class="focus:ring-2 focus:ring-black"
                size="sm"
                @click="onReversalEdit"
                :disabled="props.isEditDisabledForQueuedBooking"
              >
                Edit
              </x-button>
              <template #tooltip>
                <span class="custom-tooltip-content">
                  No further action can be taken on Update Booking Queued or
                  Failed status.
                </span>
              </template>
            </x-tooltip>
            <x-button
              v-else
              class="focus:ring-2 focus:ring-black"
              size="sm"
              @click="onReversalEdit"
            >
              Edit
            </x-button>
          </template>
          <template v-else>
            <x-button
              class="focus:ring-2 focus:ring-black"
              size="sm"
              color="orange"
              @click="state.reversalSectionEdit = false"
              :loading="loader.selectInvoice"
              :disabled="loader.selectInvoice"
            >
              Cancel
            </x-button>
            <x-button
              class="focus:ring-2 focus:ring-black"
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
                  <x-tooltip>
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
                    {{
                      bookingDetailsForm.invoice_description !== ''
                        ? bookingDetailsForm.invoice_description
                        : 'N/A'
                    }}
                  </span>
                </div>
              </div>
              <div class="grid sm:grid-cols-2 pb-1.5">
                <div class="font-bold">
                  <x-tooltip>
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
              <div
                v-if="
                  ![
                    sendUpdateStatusEnum.ACB,
                    sendUpdateStatusEnum.ATIB,
                    sendUpdateStatusEnum.ATCRNB,
                    sendUpdateStatusEnum.ATCRNB_RBB,
                  ].includes(props.sendUpdateLog.option?.code)
                "
                class="grid sm:grid-cols-2 pb-1.5"
              >
                <div>
                  <x-tooltip>
                    <label
                      class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                    >
                      TRANSACTION PAYMENT STATUS
                    </label>
                    <template #tooltip>
                      {{ transactionPaymentStatusTooltip }}
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
                  <x-tooltip>
                    <label
                      class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                    >
                      LINE OF BUSINESS
                    </label>
                    <template #tooltip>
                      Signifies the specific category or type of insurance
                      coverage associated with this booking. It helps categorize
                      the booking by its primary insurance focus, allowing for
                      better organization and classification of insurance
                      transactions.
                    </template>
                  </x-tooltip>
                </div>
                <div>
                  <span>{{ quoteType ?? 'N/A' }}</span>
                </div>
              </div>
              <div class="grid sm:grid-cols-2">
                <div>
                  <x-tooltip>
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
                  <x-tooltip>
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
              <div
                v-if="
                  ![
                    sendUpdateStatusEnum.ACB,
                    sendUpdateStatusEnum.ATCRNB_RBB,
                  ].includes(props.sendUpdateLog.option?.code)
                "
                class="grid sm:grid-cols-2"
              >
                <div>
                  <x-tooltip>
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
                  <x-input
                    maxlength="60"
                    v-model="bookingDetailsForm.insurer_tax_invoice_number"
                    class="!mb-0 w-full"
                    :disabled="!state.isEdit"
                    placeholder="Enter insurer Tax Invoice Number"
                    :rules="[isRequired]"
                    size="xs"
                  />
                </div>
              </div>
              <div class="grid sm:grid-cols-2">
                <div>
                  <x-tooltip>
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
                    bookingDetailsForm.broker_invoice_number !== ''
                      ? bookingDetailsForm.broker_invoice_number
                      : 'N/A'
                  }}</span>
                </div>
              </div>
              <div
                v-if="
                  ![
                    sendUpdateStatusEnum.ATIB,
                    sendUpdateStatusEnum.ATCRNB,
                  ].includes(props.sendUpdateLog.option?.code)
                "
                class="grid sm:grid-cols-2"
              >
                <div>
                  <x-tooltip>
                    <label
                      class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                    >
                      INSURER COMMISSION TAX INVOICE NUMBER
                    </label>
                    <template #tooltip>
                      {{
                        productionProcessTooltipEnum.INSURER_COMMISSION_TAX_INVOICE_NUMBER
                      }}
                    </template>
                  </x-tooltip>
                </div>
                <div>
                  <x-input
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
                </div>
              </div>
              <div
                v-if="
                  ![
                    sendUpdateStatusEnum.ACB,
                    sendUpdateStatusEnum.ATIB,
                    sendUpdateStatusEnum.ATCRNB,
                    sendUpdateStatusEnum.ATCRNB_RBB,
                  ].includes(props.sendUpdateLog.option?.code)
                "
                class="grid sm:grid-cols-2"
              >
                <div>
                  <x-tooltip>
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
                  <span>{{
                    bookingDetailsForm.discount !== null
                      ? bookingDetailsForm.discount
                      : 'N/A'
                  }}</span>
                </div>
              </div>
              <div
                v-if="
                  ![
                    sendUpdateStatusEnum.ACB,
                    sendUpdateStatusEnum.ATCRNB_RBB,
                  ].includes(props.sendUpdateLog.option?.code)
                "
                class="grid sm:grid-cols-2"
              >
                <div>
                  <x-tooltip>
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
                <div v-if="isPriceVatApplicableEditable">
                  <x-input
                    type="number"
                    min="0"
                    add
                    step="any"
                    v-model="bookingDetailsForm.price_vat_applicable"
                    @change="calculateCommission"
                    class="!mb-0 w-full"
                    :class="isNegativeValue ? ' icon-padding' : ''"
                    :disabled="!state.isEdit"
                    placeholder="Enter Price"
                    :rules="[isRequired]"
                    size="xs"
                    :icon-left="isNegativeValue ? 'minus' : ''"
                  />
                </div>
                <div v-else>
                  <span>N/A</span>
                </div>
              </div>
              <div
                v-if="
                  ![
                    sendUpdateStatusEnum.ACB,
                    sendUpdateStatusEnum.ATIB,
                    sendUpdateStatusEnum.ATCRNB,
                    sendUpdateStatusEnum.ATCRNB_RBB,
                  ].includes(props.sendUpdateLog.option?.code)
                "
                class="grid sm:grid-cols-2"
              >
                <div>
                  <x-tooltip>
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
                  <span>{{
                    bookingDetailsForm.commission_percentage !== ''
                      ? bookingDetailsForm.commission_percentage + '%'
                      : 'N/A'
                  }}</span>
                </div>
              </div>
              <div
                v-if="
                  ![
                    sendUpdateStatusEnum.ACB,
                    sendUpdateStatusEnum.ATCRNB_RBB,
                  ].includes(props.sendUpdateLog.option?.code)
                "
                class="grid sm:grid-cols-2"
              >
                <div>
                  <x-tooltip>
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

                <div v-if="isPriceVatNotApplicableEditable">
                  <x-input
                    type="number"
                    min="0"
                    add
                    step="any"
                    v-model="bookingDetailsForm.price_vat_not_applicable"
                    @change="calculateCommission"
                    class="!mb-0 w-full"
                    :class="isNegativeValue ? ' icon-padding' : ''"
                    :disabled="!state.isEdit"
                    placeholder="Enter Price"
                    :rules="[isRequired]"
                    size="xs"
                    :icon-left="isNegativeValue ? 'minus' : ''"
                  />
                </div>
                <div v-else>
                  <span>{{
                    bookingDetailsForm.price_vat_not_applicable !== '0.00'
                      ? bookingDetailsForm.price_vat_not_applicable
                      : 'N/A'
                  }}</span>
                </div>
              </div>
              <div
                v-if="
                  ![
                    sendUpdateStatusEnum.ATIB,
                    sendUpdateStatusEnum.ATCRNB,
                  ].includes(props.sendUpdateLog.option?.code)
                "
                class="grid sm:grid-cols-2"
              >
                <div>
                  <x-tooltip>
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
                    bookingDetailsForm.vat_on_commission !== ''
                      ? thousandSeparator(bookingDetailsForm.vat_on_commission)
                      : 'N/A'
                  }}</span>
                </div>
              </div>
              <div
                v-if="
                  ![
                    sendUpdateStatusEnum.ATIB,
                    sendUpdateStatusEnum.ATCRNB,
                  ].includes(props.sendUpdateLog.option?.code)
                "
                class="grid sm:grid-cols-2"
              >
                <div>
                  <x-tooltip>
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
                  <x-tooltip v-if="disableCommissionVatApplicable">
                    <x-input
                      v-model="bookingDetailsForm.commission_vat_applicable"
                      class="!mb-0 w-full"
                      :class="isNegativeValue ? ' icon-padding' : ''"
                      :disabled="
                        !state.isEdit || disableCommissionVatApplicable
                      "
                      placeholder="Enter Commission Amount"
                      size="xs"
                      :icon-left="isNegativeValue ? 'minus' : ''"
                    />
                    <template #tooltip>
                      This option is disabled because Commission (VAT not
                      applicable) has already been entered.
                    </template>
                  </x-tooltip>
                  <x-input
                    v-else
                    type="number"
                    min="0"
                    add
                    step="any"
                    v-model="bookingDetailsForm.commission_vat_applicable"
                    @change="calculateCommission"
                    class="!mb-0 w-full"
                    :class="isNegativeValue ? ' icon-padding' : ''"
                    :disabled="!state.isEdit || disableCommissionVatApplicable"
                    placeholder="Enter Commission Amount"
                    :rules="[isRequired]"
                    size="xs"
                    :icon-left="isNegativeValue ? 'minus' : ''"
                  />
                </div>
              </div>
              <div
                v-if="
                  ![
                    sendUpdateStatusEnum.ATIB,
                    sendUpdateStatusEnum.ATCRNB,
                  ].includes(props.sendUpdateLog.option?.code)
                "
                class="grid sm:grid-cols-2"
              >
                <div>
                  <x-tooltip>
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
                    bookingDetailsForm.total_commission !== ''
                      ? thousandSeparator(bookingDetailsForm.total_commission)
                      : 'N/A'
                  }}</span>
                </div>
              </div>
              <div
                v-if="
                  ![
                    sendUpdateStatusEnum.ATIB,
                    sendUpdateStatusEnum.ATCRNB,
                  ].includes(props.sendUpdateLog.option?.code)
                "
                class="grid sm:grid-cols-2"
              >
                <div>
                  <x-tooltip>
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
                <div v-if="props.isCommVatNotAppEnabled">
                  <x-tooltip v-if="disableCommissionVatNotApplicable">
                    <x-input
                      type="number"
                      v-model="bookingDetailsForm.commission_vat_not_applicable"
                      class="!mb-0 w-full"
                      :class="isNegativeValue ? ' icon-padding' : ''"
                      :disabled="
                        !state.isEdit || disableCommissionVatNotApplicable
                      "
                      placeholder="Enter Commission Amount"
                      size="xs"
                      :icon-left="isNegativeValue ? 'minus' : ''"
                    />
                    <template #tooltip>
                      This option is disabled because Commission (VAT
                      applicable) has already been entered.
                    </template>
                  </x-tooltip>
                  <x-input
                    v-else
                    type="number"
                    min="0"
                    add
                    step="any"
                    v-model="bookingDetailsForm.commission_vat_not_applicable"
                    @change="calculateCommission"
                    class="!mb-0 w-full"
                    :class="isNegativeValue ? ' icon-padding' : ''"
                    :disabled="!state.isEdit"
                    placeholder="Enter Commission Amount"
                    :rules="[isRequired]"
                    size="xs"
                    :icon-left="isNegativeValue ? 'minus' : ''"
                  />
                </div>
                <div v-else>
                  <span>{{
                    bookingDetailsForm.commission_vat_not_applicable !== null
                      ? bookingDetailsForm.commission_vat_not_applicable
                      : 'N/A'
                  }}</span>
                </div>
              </div>
              <div
                v-if="
                  ![
                    sendUpdateStatusEnum.ACB,
                    sendUpdateStatusEnum.ATCRNB_RBB,
                  ].includes(props.sendUpdateLog.option?.code)
                "
                class="grid sm:grid-cols-2"
              >
                <div>
                  <x-tooltip>
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
                    bookingDetailsForm.total_vat_amount !== '0.00'
                      ? thousandSeparator(bookingDetailsForm.total_vat_amount)
                      : 'N/A'
                  }}</span>
                </div>
              </div>
              <div class="grid sm:grid-cols-2">
                <div class="font-bold text-right"></div>
                <div></div>
              </div>
              <div
                v-if="
                  ![
                    sendUpdateStatusEnum.ACB,
                    sendUpdateStatusEnum.ATCRNB_RBB,
                  ].includes(props.sendUpdateLog.option?.code)
                "
                class="grid sm:grid-cols-2"
              >
                <div>
                  <x-tooltip>
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
                      bookingDetailsForm.price_with_vat !== '0.00'
                        ? thousandSeparator(bookingDetailsForm.price_with_vat)
                        : 'N/A'
                    }}
                  </span>
                </div>
              </div>
            </div>
          </div>
          <x-divider class="my-4 mt-10" />
          <div class="flex justify-end gap-2">
            <SageAPILogs
              :quoteType="props.quoteType"
              :record="props.sendUpdateLog"
              :modelClass="props.modelClass"
              :permissionsEnum="page.props.permissionsEnum"
            />
            <template v-if="!state.isEdit">
              <x-tooltip v-if="props.isEditDisabledForQueuedBooking">
                <x-button
                  class="focus:ring-2 focus:ring-black"
                  size="sm"
                  @click="checkSectionToEdit"
                  :disabled="props.isEditDisabledForQueuedBooking"
                >
                  Edit
                </x-button>
                <template #tooltip>
                  <span class="custom-tooltip-content">
                    No further action can be taken on Update Booking Queued or
                    Failed status.
                  </span>
                </template>
              </x-tooltip>
              <x-button
                v-else
                class="focus:ring-2 focus:ring-black"
                size="sm"
                @click="checkSectionToEdit"
              >
                Edit
              </x-button>

              <template
                v-if="props.updateBtn && (isLackingPayment || disableMainBtn)"
              >
                <div>
                  <x-tooltip>
                    <x-button
                      class="focus:ring-2 focus:ring-black"
                      size="sm"
                      color="orange"
                      :loading="loader.sendUpdateSectionBtn"
                      @click="sendUpdateValidation"
                      :disabled="isLackingPayment || disableMainBtn"
                    >
                      {{ props.updateBtn }}
                    </x-button>
                    <template #tooltip>
                      <span class="custom-tooltip-content">
                        {{
                          disableMainBtn
                            ? disableMainBtn
                            : 'Action Needed: Please revise payment details to reflect plan changes.'
                        }}
                      </span>
                    </template>
                  </x-tooltip>
                </div>
              </template>
              <template v-else-if="props.updateBtn">
                <x-button
                  class="focus:ring-2 focus:ring-black"
                  size="sm"
                  color="orange"
                  :loading="loader.sendUpdateSectionBtn"
                  @click="sendUpdateValidation"
                  :disabled="sendUpdatePermissionCheck"
                >
                  {{ props.updateBtn }}
                </x-button>
              </template>
            </template>
            <template v-else>
              <x-button
                class="focus:ring-2 focus:ring-black"
                size="sm"
                color="orange"
                @click="onCancel"
                :loading="bookingDetailsForm.processing"
                :disabled="bookingDetailsForm.processing"
              >
                Cancel
              </x-button>
              <x-button
                class="focus:ring-2 focus:ring-black"
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
        class="focus:ring-2 focus:ring-black"
        size="sm"
        color="error"
        @click.prevent="submitToCustomer"
        :disabled="!modals.isConfirmed"
        :loading="isLoading"
      >
        Confirm
      </x-button>
    </sendUpdateCustConfirmBtnTemp>

    <x-modal
      v-model="modals.sendConfirm"
      size="md"
      :title="props.updateBtn"
      show-close
      backdrop
    >
      <x-alert
        color="orange"
        light
        type="error"
        class="text-sm mb-4"
        v-if="isStating"
      >
        {{ isStating }}
      </x-alert>
      <x-label class="flex items-center gap-2">
        <x-checkbox v-model="modals.isConfirmed" />
        <div>
          I confirm and attest that all information recorded is correct.
          <br />
          I confirm I am in compliance with the COC.
        </div>
      </x-label>
      <template #actions>
        <div class="flex gap-4 justify-end">
          <x-button
            class="focus:ring-2 focus:ring-black"
            size="sm"
            ghost
            :disabled="isLoading"
            @click.prevent="modals.sendConfirm = false"
          >
            Cancel
          </x-button>
          <div>
            <template v-if="!modals.isConfirmed">
              <x-tooltip>
                <SendUpdateCustReuseBtnTemp />
                <template #tooltip>
                  Please select the checkbox to proceed
                </template>
              </x-tooltip>
            </template>
            <SendUpdateCustReuseBtnTemp v-else />
          </div>
        </div>
      </template>
    </x-modal>

    <x-modal
      v-model="modals.paymentConfirmation"
      title="Are you sure you want to continue?"
      backdrop
    >
      <div class="text-center">
        <p class="font-semibold">{{ paymentConfirmationMessage.status }}</p>
        <p>{{ paymentConfirmationMessage.message }}</p>
      </div>
      <template #actions>
        <div class="text-center space-x-4">
          <x-button size="sm" ghost @click.prevent="confirmationModalClose()">
            Go Back
          </x-button>
          <x-button
            class="focus:ring-2 focus:ring-black"
            size="sm"
            color="error"
            :loading="isSendUpdateWithEmail ? isLoading : loader.sendUpdate"
            @click.prevent="
              isSendUpdateWithEmail
                ? submitToCustomer(false)
                : sendUpdate(false)
            "
          >
            Continue
          </x-button>
        </div>
      </template>
    </x-modal>

    <sendUpdateConfirmBtnTemp>
      <x-button
        class="focus:ring-2 focus:ring-black"
        size="sm"
        color="error"
        :disabled="!confirmationCheck"
        @click.prevent="sendUpdate()"
        :loading="loader.sendUpdate"
      >
        Confirm
      </x-button>
    </sendUpdateConfirmBtnTemp>

    <x-modal
      v-model="modals.attestRecord"
      size="md"
      :title="
        props.updateBtn === sendUpdateStatusEnum.SU
          ? 'Book Update'
          : props.updateBtn
      "
      show-close
      backdrop
    >
      <x-label class="flex items-center gap-2">
        <x-checkbox v-model="confirmationCheck" />
        <div>
          I confirm and attest that all information recorded is correct.
          <br />
          I confirm I am in compliance with the COC.
        </div>
      </x-label>
      <template #actions>
        <div class="flex gap-4 justify-end">
          <x-button
            size="sm"
            ghost
            :disabled="isLoading"
            @click.prevent="confirmationModalClose()"
          >
            Cancel
          </x-button>
          <div>
            <template v-if="!confirmationCheck">
              <x-tooltip>
                <SendUpdateReuseBtnTemp />
                <template #tooltip>
                  Please select the checkbox to proceed
                </template>
              </x-tooltip>
            </template>
            <SendUpdateReuseBtnTemp v-else />
          </div>
        </div>
      </template>
    </x-modal>
  </div>
</template>

<style scoped>
.icon-padding input {
  padding-left: 4vh !important;
}

.v-popper {
  width: 100% !important;
}
</style>
