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

  // const isRequired = v => !!v || 'This field is required'; // will remove after testing
  const isRequired = v =>
    (!!v && (Array.isArray(v) ? v.length > 0 : true)) ||
    'This field is required';

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

  const maxCharacters = max => v =>
    !v ||
    v.length <= max ||
    `This field may not be greater than ${max} characters.`;

  const emiratesNumber = v => {
    const pattern = /^\d{3}-\d{4}-\d{7}-\d{1}$/;
    return (
      pattern.test(v) ||
      'The entered value does not meet the required length of 8 to 17 characters. Please check and confirm.'
    );
  };
  // Add minValue rule
  const minValue = min => v => {
    return !v || Number(v) >= min || `The minimum value is ${min}.`;
  };

  // Add maxSelections rule for multiple select components
  const maxSelections = max => v => {
    return (
      !v ||
      !Array.isArray(v) ||
      v.length <= max ||
      `You can select up to ${max} items only.`
    );
  };

  const maxDateRange = value => {
    if (!value || typeof value !== 'string' || !value.includes(' - '))
      return true;
    const [start, end] = value.split(' - ').map(d => {
      const [m, d_, y] = d.split('/');
      return new Date(`${d_}-${m}-${y}`);
    });
    if ([start, end].some(dt => isNaN(dt))) return 'Invalid date format';
    const diffDays = Math.ceil((end - start) / 864e5);
    if (diffDays < 0) return 'End date must be after start date';
    if (diffDays > 30)
      return `Date range must be 30 days or less (selected: ${diffDays} days)`;
    return true;
  };

  // Custom rule for array-based date ranges (supports both array and string formats)
  const maxDateRangeArray = maxDays => value => {
    if (!value) return true;

    let startDate, endDate;

    // Handle string format: "DD/MM/YYYY - DD/MM/YYYY" or "DD/MM/YYYY HH:MM - DD/MM/YYYY HH:MM"
    if (typeof value === 'string') {
      if (!value.includes(' - ')) return true;

      const [startStr, endStr] = value.split(' - ');
      if (!startStr || !endStr) return true;

      // Parse DD/MM/YYYY format (with optional time)
      const parseDate = dateStr => {
        // Remove time part if present
        const datePart = dateStr.split(' ')[0];
        const [day, month, year] = datePart.split('/');
        return new Date(year, month - 1, day); // month is 0-indexed
      };

      startDate = parseDate(startStr);
      endDate = parseDate(endStr);
    }
    // Handle array format: ["YYYY-MM-DD", "YYYY-MM-DD"] or [Date, Date]
    else if (Array.isArray(value)) {
      if (value.length !== 2) return true;

      const [start, end] = value;
      if (!start || !end) return true;

      startDate = new Date(start);
      endDate = new Date(end);
    } else {
      return true;
    }

    if (isNaN(startDate.getTime()) || isNaN(endDate.getTime())) {
      return 'Invalid date format';
    }

    if (startDate > endDate) {
      return 'End date must be after start date';
    }

    const diffDays = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
    if (diffDays > maxDays) {
      return `Date range must be ${maxDays} days or less (selected: ${diffDays} days)`;
    }

    return true;
  };

  // For regex: only letters, spaces, hyphens (matches /^[a-zA-Z\s\-]+$/)
  const isValidName = v =>
    /^[a-zA-Z\s\-]+$/.test(v) ||
    'Only letters, spaces, and hyphens are allowed.';

  // Custom validation rule for maximum price
  const maxPrice = maxValue => {
    return value => {
      if (!value) return true; // Allow empty values (required rule handles that)
      const numValue = parseFloat(value);
      if (isNaN(numValue)) return 'Must be a valid number';
      return (
        numValue <= maxValue ||
        `Price cannot exceed ${maxValue.toLocaleString()}`
      );
    };
  };

  const minPrice = minValue => {
    return value => {
      if (!value) return true; // Allow empty values (required rule handles that)
      const numValue = parseFloat(value);
      if (isNaN(numValue)) return 'Must be a valid number';
      return numValue >= minValue || `Price cannot be less than ${minValue}`;
    };
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
    maxCharacters,
    emiratesNumber,
    minValue,
    maxSelections,
    maxDateRange,
    maxDateRangeArray,
    isValidName,
    maxPrice,
    minPrice,
  };
};
