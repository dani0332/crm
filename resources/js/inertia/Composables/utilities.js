export const useRoundIt = (num, decimalPlaces = 2) => {
  const p = Math.pow(10, decimalPlaces);
  const n = num * p * (1 + Number.EPSILON);
  return Math.round(n) / p;
};

/**
 * Format a Date object to YYYY-MM-DD string format
 * @param {Date|string} date - Date object or date string to format
 * @returns {string} - Date in YYYY-MM-DD format, empty string if invalid
 */
export const useFormatDateToYMD = (date) => {
  if (!date) return '';
  const d = new Date(date);
  if (isNaN(d.getTime())) return '';
  
  return d.getFullYear() + '-' + 
         String(d.getMonth() + 1).padStart(2, '0') + '-' + 
         String(d.getDate()).padStart(2, '0');
};

export const useCleanObj = reactive => {
  Object.keys(reactive).forEach(key => {
    if (
      reactive[key] === null ||
      reactive[key] === undefined ||
      reactive[key] === '' ||
      reactive[key] === false ||
      reactive[key].length === 0 ||
      (typeof reactive[key] === 'string' && reactive[key].trim() === '')
    ) {
      delete reactive[key];
    }
  });
  return reactive;
};

export const useObjToUrl = obj => {
  Object.keys(obj).forEach(
    key => (obj[key] === '' || obj[key]?.length === 0) && delete obj[key],
  );
  return Object.keys(obj)
    .map(key => {
      if (Array.isArray(obj[key])) {
        return obj[key].map(value => `${key}[]=${value}`).join('&');
      }
      return `${key}=${obj[key]}`;
    })
    .join('&');
};

export const useGetShowPageRoute = (
  uuid,
  quoteTypeId,
  business_type_of_insurance_id,
) => {
  let business_route =
    business_type_of_insurance_id == 5
      ? route('amt.show', uuid)
      : route('business.show', uuid);

  const routesObj = {
    1: route('car.show', uuid),
    2: route('home-quotes-show', uuid),
    3: route('health.show', uuid),
    4: route('life-quotes-show', uuid),
    5: business_route,
    6: route('bike-quotes-show', uuid),
    7: route('yacht-quotes-show', uuid),
    8: route('travel.show', uuid),
    9: route('pet-quotes-show', uuid),
    10: route('cycle-quotes-show', uuid),
    18: route('savings-quotes-show', uuid),
  };

  return routesObj[quoteTypeId];
};

// Function to format the date
export const formatDate = dateObject => {
  if (dateObject && dateObject.$date && dateObject.$date.$numberLong) {
    const timestamp = parseInt(dateObject.$date.$numberLong);
    const formattedDate = new Date(timestamp);
    const options = { year: 'numeric', month: '2-digit', day: '2-digit' };
    return formattedDate.toLocaleDateString('en-US', options);
  } else if (dateObject && dateObject.includes('-')) {
    return dateObject;
  }
  return null;
};

export const useGenerateQueryString = filters => {
  const query = {};
  Object.keys(filters).forEach(key => {
    if (Array.isArray(filters[key]) && filters[key].length > 0) {
      query[key] = filters[key];
    } else if (filters[key] !== '' && filters[key] != null) {
      query[key] = filters[key];
    }
  });
  return query;
};

export const useConvertDate = date => {
  if (date == null) {
    return null;
  }

  const splitedDate = date.split('-');
  if (splitedDate[0].length === 4) {
    return date;
  }

  const [day, month, year] = date.split('-');
  return `${year}-${month}-${day}`;
};

export const useDaysSinceStale = payload => {
  const quoteRequest = payload;
  let stale_days = quoteRequest
    ? Math.round((new Date() - new Date(quoteRequest)) / (1000 * 60 * 60 * 24))
    : false;

  if (typeof stale_days === 'number' && stale_days <= 90) {
    stale_days += 1;
    if (stale_days == 1) {
      return stale_days + ' day';
    } else return stale_days + ' days';
  } else {
    return false;
  }
};

export const useFormatPrice = (price, thousandSeparator = false) => {
  return thousandSeparator
    ? parseFloat(price).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      })
    : parseFloat(price).toFixed(2);
};

export const useFileUploadErrorMessage = (doc, rejectReason) => {
  let errorMessage = '';
  if (rejectReason.code == 'file-too-large') {
    errorMessage =
      'File size must be less than ' + doc.max_size + ' MB for ' + doc.text;
  } else if (rejectReason.code == 'file-invalid-type') {
    errorMessage =
      'You can only upload a ' + doc.accepted_files + ' for ' + doc.text;
  } else {
    errorMessage =
      'You can only upload a ' +
      doc.accepted_files +
      ' or File size must be less than ' +
      doc.max_size +
      ' MB for ' +
      doc.text;
  }
  return errorMessage;
};

// export const fileUploadErrorMessage = (doc, rejectReason) => {
//   let errorMessage = "";
//   if (rejectReason.code == "file-too-large")
//   {
//     errorMessage = "File size must be less than " + doc.max_size + " MB for " + doc.text;
//   } else if (rejectReason.code == "file-invalid-type")
//   {
//     errorMessage = "You can only upload a " + doc.accepted_files + " for " + doc.text;
//   } else
//   {
//     errorMessage = "You can only upload a " + doc.accepted_files + " or File size must be less than " + doc.max_size + " MB for " + doc.text;
//   }
//   return errorMessage;
// };

export const useCompareDueDate = dueDateString => {
  // reminder needs to be changed after testing
  const currentDate = new Date();

  const [day, month, year, hour, minute, second] = dueDateString.split(/[- :]/);
  const dueDate = new Date(year, month - 1, day, hour, minute, second);

  // Set time component to midnight for both dates
  // currentDate.setHours(0, 0, 0, 0);
  // dueDate.setHours(0, 0, 0, 0);

  return currentDate > dueDate;
};

export const useCalculateTotalSum = (data, key) => {
  const totalSum = data.reduce((accumulator, currentItem) => {
    // Ensure the current item has the specified key
    if (key in currentItem) {
      // Parse the value to a number and add it to the accumulator
      let value = currentItem[key] != null ? currentItem[key] : 0;
      accumulator += +parseFloat(value.toString().replace(/,/g, '')) || 0;
    }
    return accumulator;
  }, 0);

  return totalSum.toFixed(2);
};

export const getPreviousDate = (days = 30, format = 'DD-MMM-YYYY') => {
  // Get the current date
  let currentDate = new Date();

  // Calculate the previous day
  let previousDate = new Date(currentDate);
  previousDate.setDate(currentDate.getDate() - days);
  return useDateFormat(previousDate, format).value;
};

export const setQueryStringFilters = (params, filters) => {
  for (const [key] of Object.entries(params)) {
    if (key.includes('[]')) {
      filters[key.substring(0, key.length - 2)] = params[key];
    } else {
      filters[key] = params[key];
    }
  }
};

export const saveQueryParams = () => {
  const page = usePage();
  let { component, url } = page;
  let routes = [
    'HealthQuote/Index',
    'PetQuote/Index',
    'CycleQuote/Index',
    'CorpLineQuote/Index',
    'YachtQuote/Index',
    'HomeQuote/Index',
    'SavingsQuote/Index',
  ];

  if (routes.includes(component)) {
    const urlWithParams = { url: component, params: url };
    // Serialize the object to JSON
    localStorage.setItem(component, JSON.stringify(urlWithParams));
  }
};

export const removedSavedParams = () => {
  const page = usePage();
  let { component } = page;
  localStorage.removeItem(component);
};

export const getSavedQueryParams = () => {
  const page = usePage();
  let { component } = page;
  const savedParams = localStorage.getItem(component);
  if (savedParams) {
    let routerInfo = JSON.parse(savedParams);
    const params = new URLSearchParams(routerInfo.params.split('?')[1]);

    // Convert the URLSearchParams object into an object
    const queryParams = {};
    for (const [key, value] of params.entries()) {
      queryParams[key] = value;
    }

    router.reload({ method: 'get', data: queryParams });
    return queryParams;
  }
  return false;
};

export const parseDate = dateString => {
  // Preliminary check for the DD-MM-YYYY format
  const ddMmYyyyRegex = /^\d{2}-\d{2}-\d{4}$/;
  if (ddMmYyyyRegex.test(dateString)) {
    return dateString;
  }

  const patterns = [
    // Pattern: 24-Jun-2024, 24-6-2024, 6-24-2024, fri-6-2024, fri jun 2024, 05-Oct-2024 12:00am, 24 june 2024, 2024-06-21
    {
      regex: /^(\d{1,2})-([a-zA-Z]+)-(\d{4})$/,
      parts: ['day', 'month', 'year'],
    },
    { regex: /^(\d{1,2})-(\d{1,2})-(\d{4})$/, parts: ['day', 'month', 'year'] },
    { regex: /^(\d{1,2})-(\d{1,2})-(\d{4})$/, parts: ['month', 'day', 'year'] },
    { regex: /^[a-zA-Z]+-(\d{1,2})-(\d{4})$/, parts: ['month', 'year'] },
    { regex: /^[a-zA-Z]+ ([a-zA-Z]+) (\d{4})$/, parts: ['month', 'year'] },
    {
      regex: /(\d{2})-(\w{3})-(\d{4}) (\d{2}):(\d{2})(am|pm)/,
      parts: ['day', 'month', 'year'],
    },
    {
      regex: /^(\d{1,2}) ([a-zA-Z]+) (\d{4})$/,
      parts: ['day', 'month', 'year'],
    },
    { regex: /^(\d{4})-(\d{2})-(\d{2})$/, parts: ['year', 'month', 'day'] },
  ];

  const months = [
    'jan',
    'feb',
    'mar',
    'apr',
    'may',
    'jun',
    'jul',
    'aug',
    'sep',
    'oct',
    'nov',
    'dec',
  ];

  for (const { regex, parts } of patterns) {
    const match = dateString.match(regex);
    if (match) {
      const dateParts = parts.reduce((acc, part, index) => {
        acc[part] =
          part === 'month' && isNaN(match[index + 1])
            ? months.indexOf(match[index + 1].substring(0, 3).toLowerCase()) + 1
            : parseInt(match[index + 1], 10);
        return acc;
      }, {});

      const date = new Date(
        dateParts.year,
        (dateParts.month || 1) - 1,
        dateParts.day || 1,
      );

      return [
        String(date.getDate()).padStart(2, '0'),
        String(date.getMonth() + 1).padStart(2, '0'),
        date.getFullYear(),
      ].join('-');
    }
  }

  throw new Error('Invalid date format');
};

export function getQuoteType(id, returnType = 'code') {
  const types = {
    1: { code: 'CAR', id: 'car', link: '/quotes' },
    2: { code: 'HOM', id: 'home', link: '/personal-quotes' },
    3: { code: 'HEA', id: 'health', link: '/quotes' },
    4: { code: 'LIF', id: 'life', link: '/personal-quotes' },
    5: { code: 'BUS', id: 'business', link: '/quotes' },
    6: { code: 'BIK', id: 'bike', link: '/personal-quotes' },
    7: { code: 'YAC', id: 'yacht', link: '/personal-quotes' },
    8: { code: 'TRA', id: 'travel', link: '/quotes' },
    9: { code: 'PET', id: 'pet', link: '/personal-quotes' },
    10: { code: 'CYC', id: 'cycle', link: '/personal-quotes' },
    11: { code: 'JSK', id: 'jetski', link: '/personal-quotes' },
    18: { code: 'SAV', id: 'savings', link: '/personal-quotes' },
  };
  return types[id] ? types[id][returnType] : '';
}

export function buildCdbidLink(quote_uuid, quote_type_id, customLabel = null) {
  if (quote_uuid) {
    const url = `${getQuoteType(quote_type_id, 'link')}/${getQuoteType(quote_type_id, 'id')}/${quote_uuid}`;
    const CDBID = `${getQuoteType(quote_type_id, 'code')}-${quote_uuid.toUpperCase()}`;
    return `<a target="_blank" class="text-primary-500 hover:underline flex items-center space-x-1" href="${url}">${customLabel || CDBID}</a>`;
  } else {
    return '';
  }
}

export const userHasRequiredTeams = (givenTeams, userTeams) => {
  return givenTeams.every(team => userTeams.includes(team));
};

export const calculateDaysDifference = (start_date, end_date) => {
  if (start_date && end_date) {
    const start = new Date(start_date);
    const end = new Date(end_date);
    const diffTime = Math.abs(end - start);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays + 1;
  }
  return 0;
};

export const calculateMonthsDifference = (start_date, end_date) => {
  if (start_date && end_date) {
    const start = new Date(start_date);
    const end = new Date(end_date);

    // Calculate year and month difference
    const yearDiff = end.getFullYear() - start.getFullYear();
    const monthDiff = end.getMonth() - start.getMonth();

    // Total months difference
    const totalMonths = yearDiff * 12 + monthDiff;

    // Return absolute difference in months
    // (e.g., March 31 to April 1 = 1 month, March 15 to March 20 = 0 months)
    return Math.abs(totalMonths);
  }
  return 0;
};

// Function to get the quote type ID based on quote type name
export const getQuoteTypeId = (quoteTypes, quoteType) => {
  return quoteTypes.filter(item => item.name === quoteType)[0]?.id;
};

// Function to log quote export and open the URL
export const logAndExportQuotes = async payload => {
  payload.ip_address = await getIp();
  let isSuccess = false;

  return axios
    .post('/quotes/export-logs/create', payload)
    .then(async res => {
      const exportResponse = await axios({
        method: payload.method || 'get',
        url: payload.url,
        data: payload.data || null,
      })
        .then(resp => {
          isSuccess = true;
          return resp.data;
        })
        .catch(err => {
          throw err;
        });
      res.data.message = exportResponse.message;
      return res;
    })
    .catch(err => {
      throw err;
    })
    .finally(() => {
      // Only redirect if the export was successful and it's not an email export
      if (isSuccess && payload.exportType !== 'email') {
        window.open(payload.url);
      }
    });
};

// Function to get the IP address
export const getIp = async () => {
  try {
    const res = await axios.get('https://api.ipify.org?format=json');
    return res.data.ip;
  } catch (err) {
    return null;
  }
};

// Function to calculate age
export const calculateAge = birthDateString => {
  const birthDate = new Date(birthDateString);

  const today = new Date();

  let age = today.getFullYear() - birthDate.getFullYear();

  const monthDifference = today.getMonth() - birthDate.getMonth();
  const dayDifference = today.getDate() - birthDate.getDate();

  if (monthDifference < 0 || (monthDifference === 0 && dayDifference < 0)) {
    age--;
  }

  return age;
};

// Function to calculate BMI
export const calculateBMI = (heightInCm, weightInKg) => {
  if (!heightInCm || !weightInKg) {
    return 0;
  }

  const heightInMeters = heightInCm / 100;
  const bmi = weightInKg / heightInMeters ** 2;

  return parseFloat(bmi.toFixed(2));
};

export const resolveUserStatusText = statusId => {
  switch (parseInt(statusId)) {
    case 1:
      return 'Online';
    case 2:
      return 'Offline';
    case 3:
      return 'Unavailable';
    case 4:
      return 'Sick';
    case 5:
      return 'On leave';
    default:
      return 'Unavailable';
  }
};

export const getStatusModal = () =>
  reactive({
    show: false,
    loader: false,
    data: {
      id: 0,
      userId: 0,
      reason: 1,
      loader: false,
    },
  });
//Function to validate single field in form before submit
export const validateField = (form, fieldValue, errorField, validationRule) => {
  const validationError = validationRule(fieldValue);
  if (validationError !== true) {
    form.errors[errorField] = validationError;
    return false;
  } else {
    form.errors[errorField] = '';
    return true;
  }
};

export const applyEmiratesNumberMasking = emiratesId => {
  let emiratesIDNumber = emiratesId.replace(/\D/g, '');
  if (emiratesIDNumber?.length > 15) {
    emiratesIDNumber = emiratesIDNumber.substring(0, 15); // Limit to 15 characters
  }
  if (emiratesIDNumber?.length <= 3) {
    emiratesIDNumber = emiratesIDNumber.replace(/(\d{3})(\d{0,})/, '$1-$2');
  } else if (emiratesIDNumber?.length <= 7) {
    emiratesIDNumber = emiratesIDNumber.replace(
      /(\d{3})(\d{4})(\d{0,})/,
      '$1-$2-$3',
    );
  } else if (emiratesIDNumber?.length <= 13) {
    emiratesIDNumber = emiratesIDNumber.replace(
      /(\d{3})(\d{4})(\d{7})(\d{0,})/,
      '$1-$2-$3-$4',
    );
  } else {
    emiratesIDNumber = emiratesIDNumber.replace(
      /(\d{3})(\d{4})(\d{7})(\d{1,})/,
      '$1-$2-$3-$4',
    );
  }

  return emiratesIDNumber;
};

export const useGenerateOptions = (items, valueKey, labelKey) => {
  return items.map(item => ({
    value: item[valueKey],
    label: item[labelKey],
  }));
};

export const useformatDateTimeForPicker = dateTimeString => {
  if (!dateTimeString) return null;

  // Handle format: DD-MM-YYYY HH:mm:ss from server
  const [datePart, timePart] = dateTimeString.split(' ');
  if (!datePart || !timePart) return null;

  const [day, month, year] = datePart.split('-');
  const [hours, minutes, seconds] = timePart.split(':');

  // Create a date object but compensate for timezone to preserve exact time display
  // The server sends local time, but DatePicker with utc="preserve" still converts
  const date = new Date(
    parseInt(year),
    parseInt(month) - 1, // Month is 0-indexed
    parseInt(day),
    parseInt(hours),
    parseInt(minutes),
    parseInt(seconds) || 0,
  );

  // Get timezone offset and compensate by subtracting it
  // This ensures the DatePicker displays the exact time from server
  const timezoneOffsetMinutes = date.getTimezoneOffset();
  const compensatedDate = new Date(
    date.getTime() - timezoneOffsetMinutes * 60000,
  );

  return compensatedDate;
};
// prevent charaters, accepts only numbers, comma, and decimal point
export const preventInvalidInputs = (
  event,
  allowComma = false,
  allowDecimal = false,
) => {
  const key = event.key;

  const controlKeys = [
    'Backspace',
    'Delete',
    'ArrowLeft',
    'ArrowRight',
    'Tab',
    'Enter',
    'Home',
    'End',
  ];
  if (controlKeys.includes(key)) return;

  // Allow comma if specified
  if (allowComma && key === ',') return;

  // Allow dot (.)
  if (allowDecimal && key === '.') return;

  // Allow digits 0-9
  if (/^[0-9]$/.test(key)) return;

  // Block everything else
  event.preventDefault();
};

export const numberFormat = (price, decimals = 2) => {
  price = parseFloat(price);
  return price.toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
};

// life lob specific function
export const useFormattedNumberField = (source, fieldName) => {
  return computed({
    get() {
      const val = source[fieldName];
      return val != null ? Number(val).toLocaleString('en-US') : '';
    },
    set(newVal) {
      const cleaned = cleanFormattedValueToFloat(newVal);
      const num = parseFloat(cleaned);
      source[fieldName] = isNaN(num) || cleaned === '' ? 0 : num;
    },
  });
};

// Helper function to create formatted fields for rider arrays
export const useFormattedRiderField = (ridersArray, index, fieldName) => {
  return computed({
    get() {
      const rider = ridersArray.value[index];
      if (!rider) return '';
      const val = rider[fieldName];
      return val != null ? Number(val).toLocaleString('en-US') : '';
    },
    set(newVal) {
      const rider = ridersArray.value[index];
      if (!rider) return;
      const cleaned = cleanFormattedValueToFloat(newVal);
      const num = parseFloat(cleaned);
      rider[fieldName] = isNaN(num) || cleaned === '' ? 0 : num;
    },
  });
};

export const cleanFormattedValueToFloat = value => {
  if (typeof value !== 'string') return 0;

  // Remove commas
  const cleaned = value.replace(/,/g, '');

  // Parse to float
  const num = parseFloat(cleaned);

  // If NaN or empty, return 0
  return isNaN(num) ? 0 : num;
};

export const useIsQuoteCreatedAfterCutoff = (createdAtString, cutoffDate) => {
  if (!createdAtString || !cutoffDate) return false;

  const match = createdAtString.match(
    /^(\d{1,2})-([A-Za-z]{3,9})-(\d{4})\s+(\d{1,2}):(\d{2})(am|pm)$/i,
  );
  if (!match) return false;

  const [_, day, monthStr, year, hour, min, ampm] = match;
  const months = {
    jan: 0,
    feb: 1,
    mar: 2,
    apr: 3,
    may: 4,
    jun: 5,
    jul: 6,
    aug: 7,
    sep: 8,
    oct: 9,
    nov: 10,
    dec: 11,
  };
  let h = parseInt(hour, 10);
  if (ampm.toLowerCase() === 'pm' && h < 12) h += 12;
  if (ampm.toLowerCase() === 'am' && h === 12) h = 0;

  const createdDate = new Date(
    parseInt(year),
    months[monthStr.toLowerCase().slice(0, 3)],
    parseInt(day),
    h,
    parseInt(min),
  );

  return createdDate >= cutoffDate;
};
