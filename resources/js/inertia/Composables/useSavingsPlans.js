import { useSavingsCalculator } from '@/inertia/Composables/useSavingsCalculator';
import { useFormatPrice } from '@/inertia/Composables/utilities';
import axios from 'axios';
import { computed, reactive, ref, toRaw } from 'vue';

const modals = reactive({
  planDetails: false,
  createPlan: false,
  sendConfirm: false,
  savingsCalculator: false,
});

const availablePlansTable = reactive({
  data: [],
  isLoading: false,
});

const planExchangeRate = ref(1);
const sharedAvailablePlans = ref([]);
const exchangeRates = ref({});
const plansUpdateTrigger = ref(0);

export function isSukoonPurpleInvestmentPlan(
  planOrItem,
  insuranceProviderCodeEnum,
) {
  return (
    planOrItem?.providerCode === insuranceProviderCodeEnum?.OIC &&
    planOrItem?.instantPolicy === true &&
    planOrItem?.name?.trim() === 'Purple Investment'
  );
}

export function useSavingsPlans(options = {}) {
  const {
    quote,
    localLookups = ref({}),
    lookUpData = ref({}),
    insuranceProviders = ref([]),
    notification,
  } = options;

  const ridersData = ref([]);
  const providerPlans = ref([]);
  const providerPlansLoading = ref(false);
  const planRates = ref({});

  const currencyOptions = computed(() => {
    return (
      localLookups.value?.currencies?.map(item => ({
        value: item.code || item.id,
        label: item.text,
        id: item.id,
      })) || []
    );
  });

  const investmentFrequencyOptions = computed(() => {
    return (
      localLookups.value?.investmentFrequencies?.map(item => ({
        value: item.code?.toLowerCase() || item.text?.toLowerCase(),
        label: item.text,
        id: item.id,
      })) || [
        { value: 'regular', label: 'Regular', id: null },
        { value: 'lumpsum', label: 'Lumpsum', id: null },
      ]
    );
  });

  const paymentTermOptions = computed(() => {
    return (
      localLookups.value?.paymentTerms?.map(item => ({
        value: item.value,
        label: item.text,
      })) || []
    );
  });

  const tenureOfSavingsOptions = computed(() => {
    if (lookUpData.value?.savingsTenure?.length) {
      return lookUpData.value.savingsTenure.map(item => ({
        value: parseInt(item.code) || parseInt(item.text) || item.id,
        label: item.text,
        id: item.id,
      }));
    }
    const options = [];
    for (let i = 1; i <= 30; i++) {
      options.push({
        value: i,
        label: `${i} Year${i > 1 ? 's' : ''}`,
        id: null,
      });
    }
    return options;
  });

  const planTypeOptions = computed(() => {
    return (
      localLookups.value?.planTypes?.map(item => ({
        value: item.code || item.id,
        label: item.text,
      })) || [
        { value: 'savings', label: 'Savings' },
        { value: 'whole_of_life', label: 'Whole of Life' },
      ]
    );
  });

  const insuranceProviderOptions = computed(() => {
    return (
      insuranceProviders.value?.map(provider => ({
        value: provider.id,
        label: provider.text,
      })) || []
    );
  });

  const getFrequencyFromPaymentTerm = term => {
    const termValue = parseInt(term);
    const map = {
      12: 'Monthly',
      3: 'Quarterly',
      6: 'Half Yearly',
      1: 'Yearly',
      0: 'Single Payment',
    };
    return map[termValue] || 'Monthly';
  };

  const getPaymentTermLabel = paymentTerm => {
    const term = parseInt(paymentTerm);
    const labels = {
      0: 'Single Payment',
      1: 'Annual',
      3: 'Quarterly',
      6: 'Semi-Annual',
      12: 'Monthly',
    };
    return labels[term] || 'N/A';
  };

  const getPaymentTermTitle = paymentTerm => {
    if (!paymentTerm && paymentTerm !== 0) return null;
    const term = parseInt(paymentTerm);
    const map = {
      0: 'Single Payment',
      1: 'Annual',
      3: 'Quarterly',
      6: 'Semi-Annual',
      12: 'Monthly',
    };
    return map[term] || null;
  };

  const isLumpsumFrequency = (frequency, options = null) => {
    if (!frequency) return false;

    if (
      typeof frequency === 'string' &&
      frequency.toLowerCase() === 'lumpsum'
    ) {
      return true;
    }

    const optionsToUse = options || investmentFrequencyOptions.value;
    const selectedOption = optionsToUse.find(
      opt => opt.value === frequency || opt.id === frequency,
    );
    return selectedOption?.label?.toLowerCase() === 'lumpsum';
  };

  const formatPrice = value => {
    if (!value && value !== 0) return '';
    return useFormatPrice(value, true);
  };

  const formatNumber = value => {
    if (value === 'N/A' || value === null || value === undefined) return 'N/A';
    return new Intl.NumberFormat('en-US', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(value);
  };

  const getEligibilityValue = (plan, code) => {
    if (plan?.eligibilities && Array.isArray(plan.eligibilities)) {
      const found = plan.eligibilities.find(item => item.code === code);
      return found ? found.value : 'N/A';
    }
    return 'N/A';
  };

  const calculateTotalAnnualPrice = item => {
    const price = parseFloat(item.totalPrice || item.actualPremium || 0);
    if (!price) return null;

    const paymentTerm = parseInt(item.paymentTerm);
    let multiplier = 1;

    if (paymentTerm === 12) {
      multiplier = 12;
    } else if (paymentTerm === 3) {
      multiplier = 4;
    } else if (paymentTerm === 6) {
      multiplier = 2;
    } else if (paymentTerm === 1 || paymentTerm === 0) {
      multiplier = 1;
    }

    return price * multiplier;
  };

  const toTitleCase = str => {
    if (!str) return '';
    return str.toLowerCase().replace(/\b\w/g, char => char.toUpperCase());
  };

  const findCurrencyOption = currencyId => {
    if (!currencyId) return null;
    return (
      currencyOptions.value.find(
        opt => opt.value === currencyId || opt.id === currencyId,
      ) || null
    );
  };

  const findInvestmentFrequencyOption = frequency => {
    if (!frequency) return null;
    return (
      investmentFrequencyOptions.value.find(
        opt => opt.value === frequency || opt.id === frequency,
      ) || null
    );
  };

  const calculatePlanPayout = planData => {
    if (!planData) return null;

    const { calculatePayout } = useSavingsCalculator();

    const amount = parseFloat(
      planData.actualPremium || planData.investment_amount || 0,
    );
    const rate = parseFloat(
      planData.expectedRor || planData.expected_rate_of_return || 0,
    );
    const years = parseInt(planData.tenure || planData.tenure_of_savings || 0);

    if (!amount || !rate || !years) {
      if (notification) {
        notification.warning({
          title: 'Please fill Investment Amount, Rate of Return, and Tenure',
          position: 'top',
        });
      }
      return null;
    }

    const frequencyValue =
      planData.investmentFrequency || planData.investment_frequency;
    const isLumpsum = isLumpsumFrequency(frequencyValue);
    const frequency = isLumpsum
      ? 'Single Payment'
      : getFrequencyFromPaymentTerm(
          planData.paymentTerm || planData.payment_term,
        );

    const payout = calculatePayout({ amount, rate, years, frequency });

    return {
      payout,
      formattedPayout: formatPrice(payout),
    };
  };

  const extractLumpSumFromProviderPlanResponse = data => {
    if (data == null || typeof data !== 'object') {
      return null;
    }
    const pick = v => {
      const n = typeof v === 'number' ? v : parseFloat(v);
      return Number.isFinite(n) ? n : null;
    };
    if (data.lumpSumPayout != null) {
      return pick(data.lumpSumPayout);
    }
    // KEN /fetch-savings-provider-plan returns { plan: { lumpSumPayout, ... } }
    if (data.plan?.lumpSumPayout != null) {
      return pick(data.plan.lumpSumPayout);
    }
    if (data.lumpSum != null) {
      return pick(data.lumpSum);
    }
    if (data.planData?.lumpSumPayout != null) {
      return pick(data.planData.lumpSumPayout);
    }

    return null;
  };

  const fetchSavingsProviderPlanLumpSum = async (planDetails, quoteUuid) => {
    const investmentFrequencyOption = findInvestmentFrequencyOption(
      planDetails.investmentFrequency,
    );
    const processedRiders = processRidersForAPI(ridersData.value);
    const { data } = await axios.post(
      `/quotes/savings/${quoteUuid}/fetch-savings-provider-plan`,
      {
        planId: planDetails.id || planDetails.planId,
        providerCode: planDetails.providerCode,
        isIndividualLoading: true,
        lang: 'en',
        planData: {
          investmentAmount: parseFloat(planDetails.actualPremium) || 0,
          currency: planDetails.currency || 'AED',
          currencyId: planDetails.currencyId,
          paymentTerm: parseInt(planDetails.paymentTerm, 10) || 0,
          tenure:
            planDetails.tenure != null && typeof planDetails.tenure === 'number'
              ? String(planDetails.tenure)
              : planDetails.tenure,
          tenureId: planDetails.tenureId,
          investmentFrequency:
            investmentFrequencyOption?.label ||
            planDetails.investmentFrequency ||
            'Regular',
          investmentFrequencyId:
            investmentFrequencyOption?.id || planDetails.investmentFrequencyId,
          riders: processedRiders,
        },
      },
    );

    return extractLumpSumFromProviderPlanResponse(data);
  };

  const showRiders = computed(() => {
    return ridersData.value.length > 0;
  });

  const totalRiderPrice = computed(() => {
    return ridersData.value
      .filter(rider => rider.active)
      .reduce((total, rider) => {
        const price = parseFloat(rider.coverValue2 || rider.price || 0);
        return total + price;
      }, 0);
  });

  const getRiderDetails = async (planId, existingRiders = []) => {
    if (!planId) return;

    const planRiders = Array.isArray(existingRiders) ? existingRiders : [];

    // If plan already has riders, use that data - no API call needed
    if (planRiders.length > 0) {
      ridersData.value = planRiders.map((rider, index) => ({
        id: rider.id ?? rider.riderId ?? index,
        riderId: rider.riderId ?? rider.rider_id,
        active: rider.active ? 1 : 0,
        price: Number(rider.price ?? rider.coverValue2 ?? 0) || 0,
        coverValue: Number(rider.coverValue ?? 0) || 0,
        coverValue2: Number(rider.coverValue2 ?? rider.price ?? 0) || 0,
        text: rider.text || 'Rider',
        code: rider.code ?? null,
        inputRequired: rider.inputRequired ?? rider.input_required ?? false,
        inputType: rider.inputType ?? rider.input_type ?? null,
        coverType: rider.coverType ?? rider.cover_type ?? null,
        maxAge: rider.maxAge ?? rider.max_age ?? null,
      }));
      return;
    }

    try {
      const res = await axios.get(`/personal-quotes/savings/riders/${planId}`);

      const riders = Array.isArray(res.data) ? res.data : [];

      ridersData.value =
        riders.length > 0
          ? riders.map(rider => ({
              id: rider.id,
              riderId: rider.rider_id,
              active: 0,
              price: 0,
              coverValue: 0,
              coverValue2: 0,
              text: rider.rider?.text || 'Rider',
              code: rider.rider?.code,
              inputRequired: rider.input_required || false,
              inputType: rider.input_type || null,
              coverType: rider.cover_type || null,
              maxAge: rider.max_age || null,
            }))
          : [];
    } catch (error) {
      if (notification) {
        notification.error({
          title: 'Error fetching rider details',
          position: 'top',
        });
      }
      ridersData.value = [];
    }
  };

  const processRidersForAPI = (riders = null) => {
    const ridersToProcess = riders || ridersData.value;
    return ridersToProcess.map(rider => ({
      riderId: rider.riderId || rider.rider_id || rider.id,
      active: rider.active ? true : false,
      price:
        Number(parseFloat(rider.coverValue2 || rider.price || 0).toFixed(2)) ||
        0,
      coverValue: Number(parseFloat(rider.coverValue || 0).toFixed(2)) || 0,
    }));
  };

  const onLoadAvailablePlansData = async (savingQuoteUuid = null) => {
    let quoteUuid = savingQuoteUuid || quote.uuid;
    availablePlansTable.isLoading = true;
    try {
      const url = `/quotes/savings/available-plans/${quoteUuid}`;
      const { data } = await axios.post(url, { jsonData: true });

      const processedPlans = data.map(plan => ({
        ...plan,
        currency: plan.currencyName || plan.currency || 'USD',
        minimumInvestment: getEligibilityValue(plan, 'minimumInvestmentAmount'),
        policyTerm: getEligibilityValue(plan, 'policyTerm'),
        isManualUpdate: plan.isManualUpdate || false,
        isDisabled: plan.isDisabled || false,
        actualPremium: plan.actualPremium || 0,
        insuranceProviderId: plan.providerId || plan.insuranceProviderId,
        providerCode: plan.providerCode,
        instantPolicy: plan.instantPolicy ?? false,
      }));

      availablePlansTable.data = processedPlans;
      sharedAvailablePlans.value = processedPlans;
    } catch (err) {
      availablePlansTable.data = [];
      return [];
    } finally {
      availablePlansTable.isLoading = false;
    }
  };

  const getPlanDetails = async (planId, quoteUuid) => {
    try {
      const foundPlan = availablePlansTable.data.find(
        plan => plan.id === planId,
      );
      if (foundPlan) {
        // Deep clone so PlanDetails edits (Calculate, v-models) do not mutate
        // the row object in availablePlansTable by reference.
        return structuredClone(toRaw(foundPlan));
      }

      const { data } = await axios.get(
        `/savings/${quoteUuid}/plan_details/${planId}`,
      );
      return data;
    } catch (error) {
      if (notification) {
        notification.error({
          title: 'Error',
          message: 'Plan Details Not Found',
          position: 'top',
        });
      }
      return null;
    }
  };

  const fetchProviderPlans = async providerId => {
    if (!providerId) {
      providerPlans.value = [];
      return [];
    }

    providerPlansLoading.value = true;
    try {
      const { data } = await axios.get(
        `/personal-quotes/savings/provider-plans/${providerId}`,
      );

      if (data.plans) {
        providerPlans.value = data.plans.filter(
          plan =>
            !availablePlansTable.data.some(
              existingPlan => existingPlan.id === plan.id,
            ),
        );
        return providerPlans.value;
      }

      providerPlans.value = [];
      return [];
    } catch (err) {
      if (notification) {
        notification.error({
          title: 'Failed to fetch plans',
          position: 'top',
        });
      }
      providerPlans.value = [];
      return [];
    } finally {
      providerPlansLoading.value = false;
    }
  };

  const buildPlanPayload = (planData, options = {}) => {
    const { isUpdate = false } = options;

    return {
      quoteUID: planData.quoteUUID || planData.quote_uuid,
      update: isUpdate,
      plans: [
        {
          planId: planData.planId || planData.plan_id,
          plan_type: planData.planType || planData.plan_type,
          isDisabled: planData.isDisabled || false,
          isManualUpdate: isUpdate ? true : false,
          insurerQuoteNo: planData.insurerQuoteNo || '',
          investmentAmount: parseFloat(planData.investmentAmount) || 0,
          actualPremium:
            parseFloat(planData.actualPremium || planData.investmentAmount) ||
            0,
          discountPremium:
            parseFloat(planData.discountAmount || planData.investmentAmount) ||
            0,
          currency: planData.currency || 'AED',
          currencyId: planData.currencyId,
          paymentTerm: parseInt(planData.paymentTerm) || 0,
          tenure: planData.tenure,
          tenureId: planData.tenureId,
          ror: parseFloat(planData.expectedRor || planData.ror) || 0,
          investmentFrequency:
            planData.investmentFrequencyLabel ||
            planData.investmentFrequency ||
            'Regular',
          investmentFrequencyId: planData.investmentFrequencyId,
          lumpSumPayout:
            parseFloat(planData.lumpSumPayout || planData.lumpsum_payout) || 0,
          riders: planData.riders || [],
        },
      ],
    };
  };

  const updatePlan = async (planDetails, quoteUuid, options = {}) => {
    if (!planDetails || !quoteUuid) {
      notification.error({
        title: 'Error',
        message: 'Plan details and quote UUID are required',
        position: 'top',
      });
      return;
    }

    const investmentFrequencyOption = findInvestmentFrequencyOption(
      planDetails.investmentFrequency,
    );
    const currencyOption = findCurrencyOption(planDetails.currencyId);

    const processedRiders = options.riders
      ? processRidersForAPI(options.riders)
      : processRidersForAPI(ridersData.value);

    console.log('processedRiders', processedRiders);
    const apiPayload = buildPlanPayload(
      {
        quoteUUID: quoteUuid,
        actualPremium: planDetails.actualPremium,
        discountAmount: planDetails.discountAmount,
        planId: planDetails.id || planDetails.planId,
        isDisabled: planDetails.isDisabled || false,
        isManualUpdate: planDetails.isManualUpdate || false,
        insurerQuoteNo: planDetails.insurerQuoteNo || '',
        investmentAmount: planDetails.actualPremium,
        currency: planDetails.currency || 'AED',
        currencyId: planDetails.currencyId,
        paymentTerm: planDetails.paymentTerm,
        tenure: planDetails.tenure,
        tenureId: planDetails.tenureId,
        expectedRor: planDetails.expectedRor,
        investmentFrequencyLabel: investmentFrequencyOption?.label || 'Regular',
        investmentFrequencyId:
          investmentFrequencyOption?.id || planDetails.investmentFrequencyId,
        lumpSumPayout: planDetails.lumpSumPayout,
        riders: processedRiders,
      },
      { isUpdate: true },
    );

    try {
      const response = await axios.post(
        `/quotes/savings/${quoteUuid}/savings-plan-manual-process`,
        apiPayload,
      );

      if (notification) {
        notification.success({
          title: 'Plan updated successfully',
          position: 'top',
        });
      }

      return response;
    } catch (error) {
      if (notification) {
        notification.error({
          title: error.response?.data?.message || 'Failed to update plan',
          position: 'top',
        });

        if (error.response?.data?.errors) {
          Object.keys(error.response.data.errors).forEach(function (key) {
            notification.error({
              title: error.response.data.errors[key][0],
              position: 'top',
            });
          });
        }
      }
      throw error;
    }
  };

  const createPlan = async (formData, quoteUuid, options = {}) => {
    if (!formData || !quoteUuid) {
      throw new Error('Form data and quote UUID are required');
    }

    const investmentFrequencyOption = findInvestmentFrequencyOption(
      formData.investment_frequency,
    );

    const processedRiders = options.riders
      ? processRidersForAPI(options.riders)
      : processRidersForAPI(ridersData.value);

    const apiPayload = buildPlanPayload(
      {
        quoteUUID: quoteUuid,
        planId: formData.savings_plan_id,
        planType: formData.plan_type,
        isDisabled: formData.is_disabled || false,
        isManualUpdate:
          formData.is_manual_update !== undefined
            ? formData.is_manual_update
            : true,
        insurerQuoteNo: formData.insurer_quote_no || '',
        investmentAmount: formData.investment_amount || formData.actualPremium,
        currency: formData.currency,
        currencyId: formData.currency_id,
        paymentTerm: formData.payment_term,
        tenure: formData.tenure_of_savings,
        tenureId: formData.tenure_id,
        expectedRor: formData.expected_rate_of_return,
        investmentFrequencyLabel: investmentFrequencyOption?.label || 'Regular',
        investmentFrequencyId:
          investmentFrequencyOption?.id || formData.investment_frequency_id,
        lumpSumPayout: formData.lumpsum_payout,
        riders: processedRiders,
      },
      { isUpdate: false },
    );

    try {
      const response = await axios.post(
        `/quotes/savings/${quoteUuid}/savings-plan-manual-process`,
        apiPayload,
      );

      if (notification) {
        notification.success({
          title: 'Savings plan created successfully',
          position: 'top',
        });
      }

      await onLoadAvailablePlansData(quoteUuid);

      return response;
    } catch (error) {
      if (notification) {
        notification.error({
          title: error.response?.data?.message || 'Failed to create plan',
          position: 'top',
        });

        if (error.response?.data?.errors) {
          Object.keys(error.response.data.errors).forEach(function (key) {
            notification.error({
              title: error.response.data.errors[key][0],
              position: 'top',
            });
          });
        }
      }
      throw error;
    }
  };

  const togglePlanVisibility = async (
    planId,
    quoteUuid,
    isDisabled,
    providerId = null,
  ) => {
    if (!planId || !quoteUuid) {
      notification.error({
        title: 'Error',
        message: 'Plan ID or quote UUID is required',
        position: 'top',
      });
      return;
    }

    try {
      const response = await axios.post(
        route('savings-plan-toggle-visibility'),
        {
          quoteUID: quoteUuid,
          providerId: providerId || null,
          planId: planId,
          isDisabled: isDisabled,
        },
      );

      if (notification) {
        notification.success({
          title: `Plan has been ${isDisabled ? 'hidden' : 'shown'}`,
          position: 'top',
        });
      }

      return response;
    } catch (error) {
      if (notification) {
        notification.error({
          title:
            error.response?.data?.message || 'Error updating plan visibility',
          position: 'top',
        });
      }
    }
  };

  const syncCurrencyId = (form, currencyValue) => {
    const selectedOption = currencyOptions.value.find(
      opt => opt.value === currencyValue,
    );
    form.currency_id = selectedOption?.id || null;
  };

  const syncTenureId = (form, tenureValue) => {
    const selectedOption = tenureOfSavingsOptions.value.find(
      opt => opt.value === tenureValue,
    );
    form.tenure_id = selectedOption?.id || null;
    form.tenureId = selectedOption?.id || null;
  };

  const syncInvestmentFrequencyId = (form, frequencyValue) => {
    const selectedOption = investmentFrequencyOptions.value.find(
      opt =>
        opt.value.toLowerCase() === frequencyValue.toLowerCase() ||
        opt.id === frequencyValue,
    );
    form.investment_frequency_id = selectedOption?.id || null;
    form.investmentFrequencyId = selectedOption?.id || null;
  };

  const syncPaymentTermByFrequency = (form, frequency) => {
    const checkIsLumpsum = isLumpsumFrequency(frequency);

    const singlePaymentOption = paymentTermOptions.value.find(opt =>
      opt.label?.toLowerCase().includes('single'),
    );

    if (checkIsLumpsum && singlePaymentOption) {
      form.payment_term = singlePaymentOption.value;
      form.paymentTerm = singlePaymentOption.value;
    } else if (form.payment_term === singlePaymentOption?.value) {
      form.payment_term = null;
      form.paymentTerm = null;
    }
  };

  const fetchExchangeRates = async () => {
    if (Object.keys(exchangeRates.value).length > 0) return;

    try {
      const { data } = await axios.get('https://open.er-api.com/v6/latest/USD');
      if (data?.rates) {
        exchangeRates.value = { ...data?.rates };
      }
    } catch (e) {
      if (notification) {
        notification.error({
          title: 'Failed to fetch exchange rates',
          position: 'top',
        });
      }
    }
  };

  const isSelectedPlan = (item, selectedPlanId = null) => {
    const quoteValue = quote?.value || quote;
    const planId =
      selectedPlanId !== null ? selectedPlanId : quoteValue?.plan_id;

    if (!planId || !item) return false;

    return (
      String(item.id) === String(planId) ||
      String(item.planId) === String(planId) ||
      String(item.plan_id) === String(planId)
    );
  };

  const getExchangeRate = item => {
    const id = item.id;
    const cur = (item.currency || 'USD').toUpperCase();
    const quoteValue = quote?.value || quote;

    const isPlanSelected = isSelectedPlan(item);

    if (isPlanSelected) {
      const quoteExchangeRate =
        quoteValue?.exchange_rate ||
        quoteValue?.savings_quote?.exchange_rate ||
        quoteValue?.savingsQuote?.exchange_rate;

      if (quoteExchangeRate !== null && quoteExchangeRate !== undefined) {
        return parseFloat(quoteExchangeRate);
      }
    }

    if (planRates.value[id] !== undefined) return planRates.value[id];

    const usdToAed = exchangeRates.value['AED'] || null;

    if (!usdToAed) return null;

    if (cur === 'AED') return 1;
    if (cur === 'USD') return Math.round(usdToAed * 10000) / 10000;

    const usdToCur = exchangeRates.value[cur];
    return usdToCur ? Math.round((usdToAed / usdToCur) * 10000) / 10000 : null;
  };

  const setExchangeRate = (planId, rate) => {
    const num = parseFloat(rate);
    if (!isNaN(num) && num > 0) {
      planRates.value[planId] = num;
    }
  };

  const convertToAED = (amount, item) => {
    const rate = getExchangeRate(item);
    return rate ? Math.round(amount * rate * 100) / 100 : null;
  };

  const getEcomDisplayPrice = item => {
    if (!item) return 0;
    return parseFloat(item.totalPrice || item.actualPremium || 0);
  };

  const ecomDetail = computed(() => {
    const allPlans = sharedAvailablePlans.value || [];
    const quoteValue = quote?.value || quote;
    const selectedPlanId = quoteValue?.plan_id;

    if (!allPlans.length || !selectedPlanId) {
      return null;
    }

    const foundPlan = allPlans.find(plan => {
      const matchesId = isSelectedPlan(plan, selectedPlanId);
      const isNotDisabled = !plan.isDisabled;
      return matchesId && isNotDisabled;
    });

    if (foundPlan) {
      return {
        ...foundPlan,
        providerName:
          foundPlan.providerName || foundPlan.provider?.text || null,
        planName: foundPlan.name || foundPlan.planName || foundPlan.text,
        actualPremium: parseFloat(
          foundPlan.actualPremium || foundPlan.totalPrice || 0,
        ),
        totalPrice: parseFloat(
          foundPlan.totalPrice || foundPlan.actualPremium || 0,
        ),
        currency: foundPlan.currency || foundPlan.currencyName || 'AED',
        paymentTerm: foundPlan.paymentTerm,
        isApi: foundPlan.isApi || false,
        isUnderwritten: foundPlan.isUnderwritten || false,
      };
    }

    return null;
  });

  const updateEcomDetailFromPlans = (allPlans = []) => {
    sharedAvailablePlans.value = allPlans;
  };

  const totalAnnualPrice = computed(() => {
    const ecom = ecomDetail.value;
    if (!ecom) return 'N/A';
    const displayPrice = getEcomDisplayPrice(ecom);
    const paymentTerm =
      ecom?.paymentTerm ??
      (quote?.value || quote)?.savings_quote?.payment_term ??
      1;
    return (
      calculateTotalAnnualPrice({ actualPremium: displayPrice, paymentTerm }) ||
      'N/A'
    );
  });

  const totalPriceAED = computed(() => {
    const ecom = ecomDetail.value;
    if (!ecom) return 'N/A';
    const displayPrice = getEcomDisplayPrice(ecom);
    const priceInAED = convertToAED(displayPrice, ecom);
    const paymentTerm =
      ecom?.paymentTerm ??
      (quote?.value || quote)?.savings_quote?.payment_term ??
      1;
    return (
      calculateTotalAnnualPrice({
        actualPremium: priceInAED,
        paymentTerm: 1,
      }) || 'N/A'
    );
  });

  const getTotalAnnualPriceAED = computed(() => {
    const ecom = ecomDetail.value;
    if (!ecom) return 'N/A';
    const displayPrice = getEcomDisplayPrice(ecom);
    const priceInAED = convertToAED(displayPrice, ecom);
    const paymentTerm =
      ecom?.paymentTerm ??
      (quote?.value || quote)?.savings_quote?.payment_term ??
      1;
    return formatNumber(
      calculateTotalAnnualPrice({ actualPremium: priceInAED, paymentTerm }) ||
        0,
    );
  });

  return {
    ridersData,
    providerPlans,
    providerPlansLoading,
    availablePlansTable,
    modals,
    exchangeRates,
    planRates,
    currencyOptions,
    investmentFrequencyOptions,
    paymentTermOptions,
    tenureOfSavingsOptions,
    planTypeOptions,
    insuranceProviderOptions,
    getFrequencyFromPaymentTerm,
    getPaymentTermLabel,
    getPaymentTermTitle,
    isLumpsumFrequency,
    formatPrice,
    formatNumber,
    getEligibilityValue,
    calculateTotalAnnualPrice,
    toTitleCase,
    findCurrencyOption,
    findInvestmentFrequencyOption,
    calculatePlanPayout,
    fetchSavingsProviderPlanLumpSum,
    showRiders,
    totalRiderPrice,
    getRiderDetails,
    processRidersForAPI,
    onLoadAvailablePlansData,
    getPlanDetails,
    fetchProviderPlans,
    updatePlan,
    createPlan,
    togglePlanVisibility,
    syncCurrencyId,
    syncTenureId,
    syncInvestmentFrequencyId,
    syncPaymentTermByFrequency,
    fetchExchangeRates,
    getExchangeRate,
    setExchangeRate,
    convertToAED,
    isSelectedPlan,
    ecomDetail,
    planExchangeRate,
    sharedAvailablePlans,
    getEcomDisplayPrice,
    totalAnnualPrice,
    totalPriceAED,
    getTotalAnnualPriceAED,
    updateEcomDetailFromPlans,

    plansUpdateTrigger,
  };
}
