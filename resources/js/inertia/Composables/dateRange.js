export const useDateRange = () => {
  function getAdjustedDate(date, { days = 0, months = 0, setDate = null }) {
    const newDate = new Date(date);
    if (days) newDate.setDate(date.getDate() - days);
    if (months) newDate.setMonth(date.getMonth() + months);
    if (setDate !== null) newDate.setDate(setDate);
    return newDate;
  }

  const today = new Date();
  const last7Days = getAdjustedDate(today, { days: 7 });
  const last30Days = getAdjustedDate(today, { days: 30 });
  const lastMonthStart = getAdjustedDate(today, { months: -1, setDate: 1 });
  const lastMonthEnd = getAdjustedDate(today, { setDate: 0 });
  const thisMonthStart = getAdjustedDate(today, { setDate: 1 });
  const thisMonthEnd = getAdjustedDate(today, { months: 1, setDate: 0 });
  const nextMonthStart = getAdjustedDate(today, { months: 1, setDate: 1 });
  const nextMonthEnd = getAdjustedDate(today, { months: 2, setDate: 0 });

  return [
    today,
    last7Days,
    last30Days,
    lastMonthStart,
    lastMonthEnd,
    thisMonthStart,
    thisMonthEnd,
    nextMonthStart,
    nextMonthEnd,
  ];
};
