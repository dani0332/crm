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
    key => (obj[key] === '' || obj[key].length === 0) && delete obj[key],
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

export const useGenerateQueryString = filters =>
{
  const query = {};
  Object.keys(filters).forEach(key =>
  {
    if (filters[key] !== '' && filters[key] != null)
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

export const daysSinceStale = payload =>
{
  const quoteRequest = payload;
  const stale_days = quoteRequest
    ? Math.floor((new Date() - new Date(quoteRequest)) / (1000 * 60 * 60 * 24))
    : false;
  return stale_days !== false && stale_days <= 90 ? stale_days : false;
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

export const useCalculateTotalSum = (data, key) =>
{
  const totalSum = data.reduce((accumulator, currentItem) =>
  {
    // Ensure the current item has the specified key
    if (key in currentItem)
    {
      // Parse the value to a number and add it to the accumulator
      let value = currentItem[key] != null ? currentItem[key] : 0
      accumulator += +parseFloat((value.toString()).replace(/,/g, '')) || 0;

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