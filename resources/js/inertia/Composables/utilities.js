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

export const useConvertDate = date => {
  const [day, month, year] = date.split('-');
  return `${year}-${month}-${day}`;
};