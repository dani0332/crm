export const useAMLKYC = () => {
  const page = usePage();
  const kycEnums = page.props.kycEnums;

  /**
   * Checks if the AML verification is complete
   * Bypass AML if it's travel and insurer is other than GIG and payment is non CC
   *
   * @param {Object} quoteRequest - The quote request object
   * @param {String} quoteType - The quote type code
   * @param {Array} payments - The array of payments
   * @returns {Boolean} - Whether AML verification is complete
   */
  const isAmlVerified = (quoteRequest, quoteType, payments) => {
    const quoteTypeCodeEnum = page.props.quoteTypeCodeEnum;
    const amlBypassEligibleQuoteTypes = [
      quoteTypeCodeEnum.Travel,
      quoteTypeCodeEnum.CYBER,
    ];
    let isAmlBypassEligibleQuote =
      amlBypassEligibleQuoteTypes.includes(quoteType);
    let isGIGInsuranceProvider =
      page.props?.bookPolicyDetails?.isGIGInsuranceProvider ||
      page.props?.bookingDetails?.isGIGInsuranceProvider ||
      false;
    let paymentMethodCC =
      payments[0]?.payment_methods_code ===
      page.props.paymentMethodsEnum.CreditCard;

    if (isAmlBypassEligibleQuote) {
      if (isGIGInsuranceProvider && paymentMethodCC) {
        return (
          quoteRequest.aml_status ===
          page.props.amlStatusEnum.AMLScreeningCleared
        );
      }
      return true;
    }

    return (
      quoteRequest.aml_status === page.props.amlStatusEnum.AMLScreeningCleared
    );
  };

  /**
   * Checks if the KYC verification is complete
   * Bypass KYC if it's travel and insurer is other than GIG and payment is non CC
   *
   * @param {Object} quoteRequest - The quote request object
   * @param {String} quoteType - The quote type code
   * @param {Array} payments - The array of payments
   * @returns {Boolean} - Whether KYC verification is complete
   */
  const isKycVerified = (quoteRequest, quoteType, payments) => {
    const quoteTypeCodeEnum = page.props.quoteTypeCodeEnum;
    const kycBypassEligibleQuoteTypes = [
      quoteTypeCodeEnum.Travel,
      quoteTypeCodeEnum.CYBER,
    ];
    let isKycBypassEligibleQuote =
      kycBypassEligibleQuoteTypes.includes(quoteType);
    let isGIGInsuranceProvider =
      page.props?.bookPolicyDetails?.isGIGInsuranceProvider ||
      page.props?.bookingDetails?.isGIGInsuranceProvider ||
      false;
    let paymentMethodCC =
      payments[0]?.payment_methods_code ===
      page.props.paymentMethodsEnum.CreditCard;

    if (isKycBypassEligibleQuote) {
      if (isGIGInsuranceProvider && paymentMethodCC) {
        return quoteRequest.kyc_decision === kycEnums.COMPLETE;
      }
      return true;
    }

    return quoteRequest.kyc_decision === kycEnums.COMPLETE;
  };

  /**
   * Checks if the Insurer AML verification is complete
   * Bypass Insurer AML if it's travel and insurer is other than GIG and payment is non CC
   *
   * @param {Object} quoteRequest - The quote request object
   * @param {String} quoteType - The quote type code
   * @param {Array} payments - The array of payments
   * @returns {Boolean} - Whether Insurer AML verification is complete
   */
  const isInsurerAmlVerified = (quoteRequest, quoteType, payments) => {
    const quoteTypeCodeEnum = page.props.quoteTypeCodeEnum;
    let isInsurerAmlBypassEligibleQuote =
      quoteType === quoteTypeCodeEnum.Travel;
    let isGIGInsuranceProvider =
      page.props?.bookPolicyDetails?.isGIGInsuranceProvider ||
      page.props?.bookingDetails?.isGIGInsuranceProvider ||
      false;
    let isPaymentMethodCC =
      payments[0]?.payment_methods_code ===
      page.props.paymentMethodsEnum.CreditCard;

    let insurerAMLStatus = quoteRequest?.insurer_aml_status || 'N/A';
    let insurerAmlClearedStatuses = [
      page.props.amlStatusEnum.InsurerAMLScreeningNA,
      page.props.amlStatusEnum.InsurerAMLScreeningCleared,
    ];
    let isInsurerAmlCleared =
      insurerAmlClearedStatuses.includes(insurerAMLStatus);

    if (isInsurerAmlBypassEligibleQuote) {
      if (isGIGInsuranceProvider && isPaymentMethodCC) {
        // Insurer AML is required if its travel and insurer is GIG and payment is CC
        return isInsurerAmlCleared;
      }
      //Bypass Insurer AML if its travel and insurer is other than GIG and payment is non CC
      return true;
    } else if (isPaymentMethodCC) {
      // Insurer AML is required if its non travel and payment is CC
      return isInsurerAmlCleared;
    }

    return true;
  };

  return {
    isAmlVerified,
    isKycVerified,
    isInsurerAmlVerified,
  };
};
