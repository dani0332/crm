/**
 * Savings Calculator Composable
 * Reusable logic for savings/investment calculations
 *
 * Usage:
 * const { calculate, calculateLumpsum, calculateSIP, formatNumber } = useSavingsCalculator()
 */

export function useSavingsCalculator() {
  /**
   * Get periods per year based on frequency
   */
  const getPeriodsPerYear = frequency => {
    const map = {
      'Single Payment': 1,
      Monthly: 12,
      Quarterly: 4,
      'Half Yearly': 2,
      Yearly: 1,
    };
    return map[frequency] || 12;
  };

  /**
   * Calculate Future Value of Lumpsum Investment
   * FV = P × (1 + r)^n
   *
   * @param {number} principal - Initial investment amount
   * @param {number} rate - Annual interest rate (e.g., 8 for 8%)
   * @param {number} years - Investment duration in years
   * @returns {object} { futureValue, totalInvestment, wealthGained }
   */
  const calculateLumpsum = (principal, rate, years) => {
    const r = rate / 100;
    const futureValue = principal * Math.pow(1 + r, years);
    const totalInvestment = principal;
    const wealthGained = futureValue - totalInvestment;

    return {
      futureValue: Math.round(futureValue),
      totalInvestment: Math.round(totalInvestment),
      wealthGained: Math.round(wealthGained),
    };
  };

  /**
   * Calculate Future Value of SIP (Systematic Investment Plan) - Annuity Due
   * FV = P × [((1 + r)^n - 1) / r] × (1 + r)
   *
   * @param {number} payment - Periodic payment amount
   * @param {number} rate - Annual interest rate (e.g., 8 for 8%)
   * @param {number} years - Investment duration in years
   * @param {string} frequency - Payment frequency (Monthly, Quarterly, etc.)
   * @returns {object} { futureValue, totalInvestment, wealthGained }
   */
  const calculateSIP = (payment, rate, years, frequency = 'Monthly') => {
    const periodsPerYear = getPeriodsPerYear(frequency);
    const totalPeriods = years * periodsPerYear;
    const periodicRate = rate / 100 / periodsPerYear;

    // Annuity Due formula (payment at beginning of period)
    const futureValue =
      payment *
      ((Math.pow(1 + periodicRate, totalPeriods) - 1) / periodicRate) *
      (1 + periodicRate);
    const totalInvestment = payment * totalPeriods;
    const wealthGained = futureValue - totalInvestment;

    return {
      futureValue: Math.round(futureValue),
      totalInvestment: Math.round(totalInvestment),
      wealthGained: Math.round(wealthGained),
    };
  };

  /**
   * Calculate Required Lumpsum for a Goal Amount
   * P = FV / (1 + r)^n
   *
   * @param {number} goalAmount - Target future value
   * @param {number} rate - Annual interest rate (e.g., 8 for 8%)
   * @param {number} years - Investment duration in years
   * @returns {object} { requiredPayment, totalInvestment, wealthGained, futureValue }
   */
  const calculateRequiredLumpsum = (goalAmount, rate, years) => {
    const r = rate / 100;
    const requiredPayment = goalAmount / Math.pow(1 + r, years);
    const wealthGained = goalAmount - requiredPayment;

    return {
      requiredPayment: Math.round(requiredPayment),
      totalInvestment: Math.round(requiredPayment),
      wealthGained: Math.round(wealthGained),
      futureValue: goalAmount,
    };
  };

  /**
   * Calculate Required SIP Payment for a Goal Amount
   * PMT = FV × [r / ((1 + r)^n - 1)] / (1 + r)
   *
   * @param {number} goalAmount - Target future value
   * @param {number} rate - Annual interest rate (e.g., 8 for 8%)
   * @param {number} years - Investment duration in years
   * @param {string} frequency - Payment frequency (Monthly, Quarterly, etc.)
   * @returns {object} { requiredPayment, totalInvestment, wealthGained, futureValue }
   */
  const calculateRequiredSIP = (
    goalAmount,
    rate,
    years,
    frequency = 'Monthly',
  ) => {
    const periodsPerYear = getPeriodsPerYear(frequency);
    const totalPeriods = years * periodsPerYear;
    const periodicRate = rate / 100 / periodsPerYear;

    // Annuity Due PMT formula
    const requiredPayment =
      (goalAmount *
        (periodicRate / (Math.pow(1 + periodicRate, totalPeriods) - 1))) /
      (1 + periodicRate);
    const totalInvestment = requiredPayment * totalPeriods;
    const wealthGained = goalAmount - totalInvestment;

    return {
      requiredPayment: Math.round(requiredPayment),
      totalInvestment: Math.round(totalInvestment),
      wealthGained: Math.round(wealthGained),
      futureValue: goalAmount,
    };
  };

  /**
   * Calculate Lumpsum Payout (Future Value at maturity)
   * Useful for CreatePlan section
   *
   * @param {object} params - { amount, rate, years, frequency }
   * @returns {number} Future value / payout amount
   */
  const calculatePayout = ({
    amount,
    rate,
    years,
    frequency = 'Single Payment',
  }) => {
    if (!amount || !rate || !years) return 0;

    if (frequency === 'Single Payment') {
      return calculateLumpsum(amount, rate, years).futureValue;
    }
    return calculateSIP(amount, rate, years, frequency).futureValue;
  };

  /**
   * Generate yearly progression data for charts
   *
   * @param {object} params - { amount, rate, years, frequency }
   * @returns {Array} [{ year, value }, ...]
   */
  const getYearlyProgression = ({
    amount,
    rate,
    years,
    frequency = 'Monthly',
  }) => {
    const data = [];

    for (let year = 1; year <= years; year++) {
      let value;
      if (frequency === 'Single Payment') {
        value = calculateLumpsum(amount, rate, year).futureValue;
      } else {
        value = calculateSIP(amount, rate, year, frequency).futureValue;
      }
      data.push({ year, value });
    }

    return data;
  };

  /**
   * Main calculate function - unified interface
   *
   * @param {object} params
   * @param {string} params.mode - 'invest' or 'goal'
   * @param {number} params.amount - Investment amount or goal amount
   * @param {number} params.rate - Expected rate of return (%)
   * @param {number} params.years - Duration in years
   * @param {string} params.frequency - 'Single Payment', 'Monthly', 'Quarterly', 'Half Yearly', 'Yearly'
   * @returns {object} Calculation results
   */
  const calculate = ({
    mode = 'invest',
    amount,
    rate,
    years,
    frequency = 'Monthly',
  }) => {
    if (mode === 'invest') {
      if (frequency === 'Single Payment') {
        return calculateLumpsum(amount, rate, years);
      }
      return calculateSIP(amount, rate, years, frequency);
    } else {
      // Goal mode
      if (frequency === 'Single Payment') {
        return calculateRequiredLumpsum(amount, rate, years);
      }
      return calculateRequiredSIP(amount, rate, years, frequency);
    }
  };

  // Formatting helpers
  const formatNumber = num => {
    if (num == null) return '0';
    return Math.round(num).toLocaleString('en-US');
  };

  const formatNumberShort = num => {
    if (num >= 1_000_000) return (num / 1_000_000).toFixed(2) + 'M';
    if (num >= 1_000) return Math.round(num / 1_000) + 'k';
    return num.toString();
  };

  return {
    // Core calculation functions
    calculate,
    calculateLumpsum,
    calculateSIP,
    calculateRequiredLumpsum,
    calculateRequiredSIP,
    calculatePayout,

    // Helpers
    getPeriodsPerYear,
    getYearlyProgression,
    formatNumber,
    formatNumberShort,
  };
}
