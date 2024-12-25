export const useRoundIt = (num, decimalPlaces = 2) => {
  const p = Math.pow(10, decimalPlaces);
  const n = num * p * (1 + Number.EPSILON);
  return Math.round(n) / p;
};

export const useCleanObj = reactive => {
  Object.keys(reactive).forEach(key => {
    if (
      reactive[key] === null ||
      reactive[key] === undefined ||
      reactive[key] === '' ||
      reactive[key] === false ||
      reactive[key].length === 0
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
    2: route('home.show', uuid),
    3: route('health.show', uuid),
    4: route('life-quotes-show', uuid),
    5: business_route,
    6: route('bike-quotes-show', uuid),
    7: route('yacht-quotes-show', uuid),
    8: route('travel.show', uuid),
    9: route('pet-quotes-show', uuid),
    10: route('cycle-quotes-show', uuid),
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
    2: { code: 'HOM', id: 'home', link: '/quotes' },
    3: { code: 'HEA', id: 'health', link: '/quotes' },
    4: { code: 'LIF', id: 'life', link: '/quotes' },
    5: { code: 'BUS', id: 'business', link: '/quotes' },
    6: { code: 'BIK', id: 'bike', link: '/personal-quotes' },
    7: { code: 'YAC', id: 'yacht', link: '/personal-quotes' },
    8: { code: 'TRA', id: 'travel', link: '/quotes' },
    9: { code: 'PET', id: 'pet', link: '/personal-quotes' },
    10: { code: 'CYC', id: 'cycle', link: '/personal-quotes' },
  };
  return types[id] ? types[id][returnType] : '';
}

export function buildCdbidLink(quote_uuid, quote_type_id) {
  if (quote_uuid) {
    const url = `${getQuoteType(quote_type_id, 'link')}/${getQuoteType(quote_type_id, 'id')}/${quote_uuid}`;
    const CDBID = `${getQuoteType(quote_type_id, 'code')}-${quote_uuid.toUpperCase()}`;
    return `<a target="_blank" class="text-primary-500 hover:underline flex items-center space-x-1" href="${url}">${CDBID}</a>`;
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
    return diffDays;
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
  return axios
    .post('/quotes/export-logs/create', payload)
    .then(res => {
      return res.data.success;
    })
    .catch(err => {
      throw err;
    })
    .finally(() => {
      window.open(payload.url);
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
