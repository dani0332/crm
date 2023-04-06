export const useRules = () => {
  const isEmail = v =>
    /^\w+([.-]?\w+)*@\w+([.-]?\w+)*(\.\w{2,3})+$/.test(v) ||
    'E-mail must be valid';
  const isRequired = v => !!v || 'This field is required';

  const isNumber = v => /^\d+$/.test(v) || 'This field must be a number';

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

  const renewal_expiry_date = v => {
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

  return {
    isEmail,
    isRequired,
    isNumber,
    policy_number,
    policy_start_date,
    renewal_expiry_date,
    premium,
    isDecimal,
    emptyOrDecimal,
  };
};
