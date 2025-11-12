export const usePayment = () => {
  const page = usePage();
  const paymentStatusEnum = page.props.paymentStatusEnum;
  const productionProcessTooltipEnum = page.props.productionProcessTooltipEnum;
  const paymentAllocationStatus = page.props.paymentAllocationStatus;

  const formatDate = (date, timeFlag = false) => {
    // Format date string to DD-MM-YYYY with optional time component (HH:MM:SS)
    const parsedDate = new Date(date);
    const day = parsedDate.getDate().toString().padStart(2, '0');
    const month = (parsedDate.getMonth() + 1).toString().padStart(2, '0');
    const year = parsedDate.getFullYear();
    const formatedDate = `${day}-${month}-${year}`;
    if (!timeFlag) {
      return formatedDate;
    }
    const hours = parsedDate.getHours().toString().padStart(2, '0');
    const minutes = parsedDate.getMinutes().toString().padStart(2, '0');
    const seconds = parsedDate.getSeconds().toString().padStart(2, '0');
    const formattedTime = `${hours}:${minutes}:${seconds}`;
    return formatedDate.concat(' ', formattedTime);
  };

  const formatAmount = amount => {
    // Format numerical value to display with thousands separators and 2 decimal places
    const parsedAmount = parseFloat(amount);
    if (isNaN(parsedAmount)) {
      return '0.00';
    }
    const formattedAmount = parsedAmount.toLocaleString('en-US', {
      style: 'decimal',
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
    return formattedAmount;
  };

  const formatString = input => {
    // Convert string to title case format and replace underscores with spaces
    if (input === '' || input === undefined || input === null) {
      return '';
    }
    const lowercaseString = input.toLowerCase();
    const words = lowercaseString.replace(/_/g, ' ').split(' ');
    for (let i = 0; i < words.length; i++) {
      words[i] = words[i][0].toUpperCase() + words[i].slice(1);
    }
    const formattedString = words.join(' ');
    return formattedString;
  };

  const filterCCPayments = payment => {
    // Return only payment splits that use Credit Card (CC) payment method
    return payment.payment_splits.filter(
      item =>
        item.payment_method.code === page.props.paymentMethodsEnum.CreditCard,
    );
  };

  const hasAnyCCSplitPayment = payment => {
    return payment?.payment_splits?.some(
      item =>
        item.payment_method.code === page.props.paymentMethodsEnum.CreditCard,
    );
  };

  const getCaptureValidStatuses = paymentSplitRec => {
    // Check if payment status is among valid statuses for capture (AUTHORISED, PAID, PARTIALLY_PAID)
    const validStatuses = [
      paymentStatusEnum.AUTHORISED,
      paymentStatusEnum.PAID,
      paymentStatusEnum.PARTIALLY_PAID,
    ];
    return validStatuses.includes(paymentSplitRec.payment_status_id);
  };

  const filterCAPayments = payment => {
    // Return only payment splits with Credit Approved status
    return payment.payment_splits.filter(
      item => item.payment_status_id == paymentStatusEnum.CREDIT_APPROVED,
    );
  };

  const verifyCreditApproved = paymentRecord => {
    // Check if all Credit Approval (CA) payment methods have CREDIT_APPROVED status
    let caPaymentStatus = paymentRecord.payment_splits.filter(
      item =>
        item.payment_method.code ===
        page.props.paymentMethodsEnum.CreditApproval,
    );
    if (caPaymentStatus.length > 0) {
      let caApproved = filterCAPayments(paymentRecord);
      return caApproved.length === caPaymentStatus.length;
    }
    return false;
  };

  const paymentAllocationStatusTooltip = payment_allocation_status => {
    // Return appropriate tooltip message based on payment allocation status
    // First convert to upper case as some of the values are in lower case & some of without space
    payment_allocation_status = formatString(payment_allocation_status);
    // Then converting to accordingly to match with the enum values
    payment_allocation_status = payment_allocation_status
      .replace(/ /g, '_')
      .toLowerCase();
    if (payment_allocation_status == paymentAllocationStatus.NOT_ALLOCATED) {
      return productionProcessTooltipEnum.PAYMENT_ALLOCATION_STATUS_NOT_ALLOCATED;
    } else if (
      payment_allocation_status == paymentAllocationStatus.PARTIALLY_ALLOCATED
    ) {
      return productionProcessTooltipEnum.PAYMENT_ALLOCATION_STATUS_PARTIALLY_ALLOCATED;
    } else if (
      payment_allocation_status == paymentAllocationStatus.FULLY_ALLOCATED
    ) {
      return productionProcessTooltipEnum.PAYMENT_ALLOCATION_STATUS_FULLY_ALLOCATED;
    } else if (payment_allocation_status == paymentAllocationStatus.UNPAID) {
      return productionProcessTooltipEnum.TRANSACTION_PAYMENT_STATUS_NOT_PAID;
    }
    return '';
  };

  /**
   * Checks if any master payment in the given payments array has an authorized/settled status.
   * Returns an object with hasAuthorized (boolean) and statusText (string or null).
   * Breaks on first matching master payment.
   */
  const hasAuthorizedSplit = (payments) => {
    const authorizedStatuses = [
      paymentStatusEnum.AUTHORISED,
      paymentStatusEnum.PAID,
      paymentStatusEnum.CAPTURED,
      paymentStatusEnum.PARTIAL_CAPTURED,
      paymentStatusEnum.PARTIALLY_PAID
    ];
    
    if (!Array.isArray(payments)) {
      return {
        hasAuthorized: false,
        statusText: null
      };
    }
    
    for (const payment of payments) {
      if (authorizedStatuses.includes(payment.payment_status_id)) {
        return {
          hasAuthorized: true,
          statusText: formatString(payment.payment_status?.text)
        };
      }
    }
    
    return {
      hasAuthorized: false,
      statusText: null
    };
  };

  return {
    formatDate,
    formatAmount,
    formatString,
    filterCCPayments,
    getCaptureValidStatuses,
    filterCAPayments,
    verifyCreditApproved,
    hasAnyCCSplitPayment,
    paymentAllocationStatusTooltip,
    hasAuthorizedSplit,
  };
};
