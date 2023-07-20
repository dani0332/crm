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
