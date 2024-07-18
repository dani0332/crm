export const useRoundIt = (num, decimalPlaces = 2) =>
{
  const p = Math.pow(10, decimalPlaces);
  const n = num * p * (1 + Number.EPSILON);
  return Math.round(n) / p;
};

export const useCleanObj = reactive =>
{
  Object.keys(reactive).forEach(key =>
  {
    if (
      reactive[key] === null ||
      reactive[key] === undefined ||
      reactive[key] === '' ||
      reactive[key] === false ||
      reactive[key].length === 0
    )
    {
      delete reactive[key];
    }
  });
  return reactive;
};

export const useObjToUrl = obj =>
{
  Object.keys(obj).forEach(
    key => (obj[key] === '' || obj[key]?.length === 0) && delete obj[key],
  );
  return Object.keys(obj)
    .map(key =>
    {
      if (Array.isArray(obj[key]))
      {
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
) =>
{
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
export const formatDate = dateObject =>
{
  if (dateObject && dateObject.$date && dateObject.$date.$numberLong)
  {
    const timestamp = parseInt(dateObject.$date.$numberLong);
    const formattedDate = new Date(timestamp);
    const options = { year: 'numeric', month: '2-digit', day: '2-digit' };
    return formattedDate.toLocaleDateString('en-US', options);
  } else if (dateObject && dateObject.includes('-'))
  {
    return dateObject;
  }
  return null;
};
export const useGenerateQueryString = filters =>
{
  const query = {};
  Object.keys(filters).forEach(key =>
  {
    if (Array.isArray(filters[key]) && filters[key].length > 0)
    {
      query[key] = filters[key];
    } else if (filters[key] !== '' && filters[key] != null)
    {
      query[key] = filters[key];
    }
  });
  return query;
};

export const useConvertDate = date =>
{
  if (date == null)
  {
    return null;
  }

  const splitedDate = date.split('-');
  if (splitedDate[0].length === 4)
  {
    return date;
  }

  const [day, month, year] = date.split('-');
  return `${year}-${month}-${day}`;
};

export const useDaysSinceStale = payload =>
{
  const quoteRequest = payload;
  let stale_days = quoteRequest
    ? Math.round((new Date() - new Date(quoteRequest)) / (1000 * 60 * 60 * 24))
    : false;

  if (typeof stale_days === 'number' && stale_days <= 90)
  {
    stale_days += 1;
    if (stale_days == 1)
    {
      return stale_days + ' day';
    } else
      return stale_days + ' days';
  } else
  {
    return false
  }
};

export const useFormatPrice = (price, thousandSeparator = false) =>
{
  return thousandSeparator
    ? parseFloat(price).toLocaleString('en-US', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    })
    : parseFloat(price).toFixed(2);
};

export const fileUploadErrorMessage = (doc, rejectReason) =>
{
  let errorMessage = '';
  if (rejectReason.code == 'file-too-large')
  {
    errorMessage =
      'File size must be less than ' + doc.max_size + ' MB for ' + doc.text;
  } else if (rejectReason.code == 'file-invalid-type')
  {
    errorMessage =
      'You can only upload a ' + doc.accepted_files + ' for ' + doc.text;
  } else
  {
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

export const useCompareDueDate = dueDateString =>
{
  // reminder needs to be changed after testing
  const currentDate = new Date();

  const [day, month, year, hour, minute, second] = dueDateString.split(/[- :]/);
  const dueDate = new Date(year, month - 1, day, hour, minute, second);

  // Set time component to midnight for both dates
  // currentDate.setHours(0, 0, 0, 0);
  // dueDate.setHours(0, 0, 0, 0);

  return currentDate > dueDate;
};

export const useCalculateTotalSum = (data, key) =>
{
  const totalSum = data.reduce((accumulator, currentItem) =>
  {
    // Ensure the current item has the specified key
    if (key in currentItem)
    {
      // Parse the value to a number and add it to the accumulator
      let value = currentItem[key] != null ? currentItem[key] : 0;
      accumulator += +parseFloat(value.toString().replace(/,/g, '')) || 0;
    }
    return accumulator;
  }, 0);

  return totalSum.toFixed(2);
};

export const getPreviousDate = (days = 30, format = 'DD-MMM-YYYY') =>
{
  // Get the current date
  let currentDate = new Date();

  // Calculate the previous day
  let previousDate = new Date(currentDate);
  previousDate.setDate(currentDate.getDate() - days);
  return useDateFormat(previousDate, format).value;
};

export const setQueryStringFilters = (params, filters) =>
{
  for (const [key] of Object.entries(params))
  {
    if (key.includes('[]'))
    {
      filters[key.substring(0, key.length - 2)] = params[key];
    } else
    {
      filters[key] = params[key];
    }
  }
};

export const saveQueryParams = () =>
{
  let { component, url } = router.page;
  let routes = [
    'HealthQuote/Index',
    'PetQuote/Index',
    'CycleQuote/Index',
    'CorpLineQuote/Index',
    'YachtQuote/Index',
    'HomeQuote/Index',
  ];

  if (routes.includes(component))
  {
    const urlWithParams = { url: component, params: url };
    // Serialize the object to JSON
    localStorage.setItem(component, JSON.stringify(urlWithParams));
  }
};

export const removedSavedParams = () =>
{
  let { component } = router.page;
  localStorage.removeItem(component);
};

export const getSavedQueryParams = () =>
{
  let { component } = router.page;
  const savedParams = localStorage.getItem(component);
  if (savedParams)
  {
    let routerInfo = JSON.parse(savedParams);
    const params = new URLSearchParams(routerInfo.params.split('?')[1]);

    // Convert the URLSearchParams object into an object
    const queryParams = {};
    for (const [key, value] of params.entries())
    {
      queryParams[key] = value;
    }

    router.reload({ method: 'get', data: queryParams });
    return queryParams;
  }
  return false;
};
export const maskEmail = emails =>
{
  if (!emails) return null;
  return emails
    .split(',')
    .map(email =>
    {
      const [localPart, domainPart] = email.split('@');
      const maskedLocalPart =
        localPart.substring(0, Math.ceil(localPart.length / 2)) +
        '*'.repeat(localPart.length - Math.ceil(localPart.length / 2));
      return `${maskedLocalPart}@${domainPart}`;
    })
    .join(',');
};

export const maskPhone = mobile_no =>
{
  if (mobile_no)
  {
    return mobile_no
      .split('')
      .map((char, index) => (index < mobile_no.length / 2 ? char : '*'))
      .join('');
  }
  return null;
};

export function getQuoteType(id, returnType = 'code')
{
  const types = {
    1: { code: 'CAR', id: 'car', link: '/quotes' },
    2: { code: 'HOM', id: 'home', link: '/quotes' },
    3: { code: 'HEA', id: 'health', link: '/quotes' },
    4: { code: 'LIF', id: 'life', link: '/quotes' },
    5: { code: 'BUS', id: 'business', link: '/quotes' },
    6: { code: 'BIK', id: 'bike', link: '/personal-quotes' },
    7: { code: 'YAC', id: 'yacht', link: '/personal-quotes' },
    8: { code: 'TRA', id: 'travel', link: '/quotes' },
  };
  return types[id] ? types[id][returnType] : '';
}

export function buildCdbidLink(quote_uuid, quote_type_id)
{
  if (quote_uuid)
  {
    const url = `${getQuoteType(quote_type_id, 'link')}/${getQuoteType(quote_type_id, 'id')}/${quote_uuid}`;
    const CDBID = `${getQuoteType(quote_type_id, 'code')}-${quote_uuid.toUpperCase()}`;
    return `<a target="_blank" class="text-primary-500 hover:underline flex items-center space-x-1" href="${url}">${CDBID}</a>`;
  } else
  {
    return '';
  }
}

export const userHasRequiredTeams = (givenTeams, userTeams) =>
{
  const teams = new Set(givenTeams);
  return userTeams.every(team => teams.has(team));
}
