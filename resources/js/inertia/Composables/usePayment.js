export const usePayment = () => {
    const page = usePage();
    const paymentStatusEnum = page.props.paymentStatusEnum;
    
    const formatDate = (date, timeFlag = false) => {
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

    const formatString = (input) => {
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
    }

    const filterCCPayments = payment => {
        return payment.payment_splits.filter(
            item => item.payment_method.code === 'CC',
        );
    };

    const hasAnyCCSplitPayment = (payments) => {
        if (payments.length > 0) {
            const paymentSplits = payments[0].payment_splits;
            return paymentSplits.some(item => item.payment_method.code === 'CC');
        }
        return false;
    };

    const getCaptureValidStatuses = paymentSplitRec => {
        const validStatuses = [
            paymentStatusEnum.AUTHORISED,
            paymentStatusEnum.PAID,
            paymentStatusEnum.PARTIALLY_PAID,
        ];
        return validStatuses.includes(paymentSplitRec.payment_status_id);
    };

    const filterCAPayments = payment => {
        return payment.payment_splits.filter(
          item => item.payment_status_id == paymentStatusEnum.CREDIT_APPROVED,
        );
    };

    // verify if all credit payments are approved for capture
    const verifyCreditApproved = paymentRecord => {
        let caPaymentStatus = paymentRecord.payment_splits.filter(
        item => item.payment_method.code === 'CA',
        );
        if (caPaymentStatus.length > 0) {
        let caApproved = filterCAPayments(paymentRecord);
        return caApproved.length === caPaymentStatus.length;
        }
        return false;
    };

    return {
        formatDate,
        formatAmount,
        formatString,
        filterCCPayments,
        getCaptureValidStatuses,
        filterCAPayments,
        verifyCreditApproved,
        hasAnyCCSplitPayment
    };
};