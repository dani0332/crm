export const useRules = () => {
  const isEmail = v =>
    /^\w+([.-]?\w+)*@\w+([.-]?\w+)*(\.\w{2,})+$/.test(v) ||
    'E-mail must be valid';

  const isMobile = v => {
    if (v) {
      return v.length <= 10 || 'Mobile Number should be 10 digits long';
    }

    return true;
  };

  const isRequired = v => !!v || 'This field is required';

  const allowEmpty = v => true || 'This field is required';

  const isNumber = v =>
    !v || /^\d+$/.test(v) || !isNaN(Number(v)) || 'This field must be a number';

  const isNumberOrDecimal = v =>
    /^\d+(\.\d+)?$/.test(v) || 'This field must be a number';

  const policy_number = v => {
    if (v) {
      return (
        v.length <= 50 || 'Policy Number should be less than 50 characters'
      );
    }
    return true;
  };

  const policy_start_date = v => {
    if (v) {
      const date = new Date(v);
      return !isNaN(date.getTime());
    }
    return true;
  };

  const policy_expiry_date = v => {
    if (v) {
      const date = new Date(v);
      if (policyDetails.policy_start_date) {
        const startDate = new Date(policyDetails.policy_start_date);
        if (startDate >= date) {
          return 'Expiry date should be greater than Start Date';
        }
      }
      return !isNaN(date.getTime());
    }
    return true;
  };

  const premium = v => {
    if (v) {
      const premium = parseFloat(v);
      if (premium < 0 || isNaN(premium)) {
        return 'Premium should be greater than 0';
      }
    }
    return true;
  };

  const isDecimal = v => /^\d+(\.\d{1,2})?$/.test(v) || 'Must be a decimal';
  const emptyOrDecimal = v =>
    !v || /^\d+(\.\d{1,2})?$/.test(v) || 'Must be a decimal';

  const isMobileNo = v => {
    if (v) {
      const regex = /^[0-9+\-\s]+$/;
      if (v.length < 10) {
        return 'Mobile Number should be 10 digits long';
      }
      if (v.length > 20) {
        return 'Mobile Number should be less than 20 digits long';
      }
      return regex.test(v) || 'Invalid mobile number';
    }
  };
  const price_vat_notapplicable = v => {
    return (
      !v ||
      /^\d+(\.\d{1,2})?$/.test(v) ||
      'Price (VAT NOT APPLICABLE) should be number with 2 decimals and greater than 0'
    );
  };
  const price_vat_applicable = v => {
    return (
      !v ||
      /^\d+(\.\d{1,2})?$/.test(v) ||
      'Price (VAT APPLICABLE) should be number with 2 decimals  and greater than 0'
    );
  };
  const vat = v => {
    return (
      !v ||
      /^\d+(\.\d{1,2})?$/.test(v) ||
      'Total VAT Amount should be number and greater than 0'
    );
  };
  const amount_with_vat = v => {
    return (
      !v ||
      /^\d+(\.\d{1,2})?$/.test(v) ||
      'Price should be number and greater than 0'
    );
  };
  const emptyOrNumericAndNoSpecialChar = v => {
    return (
      !v ||
      /^[0-9]+$/.test(v) ||
      'This field must be a number, special characters are not allowed.'
    );
  };

  const isRequiredNumber = v => {
    if (v === 0) return true;

    if (!v) return 'This field is required';

    return (
      /^\d+$/.test(v) || !isNaN(Number(v)) || 'This field must be a number'
    );
  };

  return {
    name,
    isEmail,
    isMobile,
    isRequired,
    allowEmpty,
    isNumber,
    isNumberOrDecimal,
    policy_number,
    policy_start_date,
    policy_expiry_date,
    premium,
    isDecimal,
    emptyOrDecimal,
    isMobileNo,
    price_vat_notapplicable,
    price_vat_applicable,
    vat,
    amount_with_vat,
    emptyOrNumericAndNoSpecialChar,
    isRequiredNumber,
  };
};
