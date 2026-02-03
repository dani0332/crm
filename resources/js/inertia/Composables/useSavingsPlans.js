import { useSavingsCalculator } from '@/inertia/Composables/useSavingsCalculator';
import { useFormatPrice } from '@/inertia/Composables/utilities';
import axios from 'axios';
import { computed, reactive, ref } from 'vue';

/**
 * Comprehensive composable for savings plans business logic
 * @param {Object} options - Configuration options
 * @param {Object} options.quote - Current quote object
 * @param {Object} options.localLookups - Local lookup data
 * @param {Object} options.lookUpData - Additional lookup data
 * @param {Array} options.insuranceProviders - Insurance providers list
 * @param {Object} options.notification - Notification instance
 * @returns {Object} All reactive state, computed properties, and functions
 */

const modals = reactive({
  planDetails: false,
  createPlan: false,
  sendConfirm: false,
  savingsCalculator: false,
});

// Global shared state for ecom details (shared across all component instances)
const planExchangeRate = ref(1);
const sharedAvailablePlans = ref([]);
const exchangeRates = ref({});

export function useSavingsPlans(options = {})
{
  const {
    quote,
    localLookups = ref({}),
    lookUpData = ref({}),
    insuranceProviders = ref([]),
    notification,
  } = options;

  // =========================================================================
  // 1. REACTIVE STATE
  // =========================================================================

  const ridersData = ref([]);
  const providerPlans = ref([]);
  const providerPlansLoading = ref(false);
  const availablePlansTable = reactive({
    data: [],
    isLoading: false,
  });
  const planDetails = ref(null);

  const planRates = ref({});
  const ratesLoading = ref(false);

  // =========================================================================
  // 2. LOOKUP OPTIONS (Computed)
  // =========================================================================

  /**
   * Currency options from localLookups
   */
  const currencyOptions = computed(() =>
  {
    return (
      localLookups.value?.currencies?.map(item => ({
        value: item.id || item.code,
        label: item.text,
        id: item.id,
      })) || []
    );
  });

  /**
   * Investment frequency options from localLookups
   */
  const investmentFrequencyOptions = computed(() =>
  {
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

  /**
   * Payment term options from localLookups
   */
  const paymentTermOptions = computed(() =>
  {
    return (
      localLookups.value?.paymentTerms?.map(item => ({
        value: item.value,
        label: item.text,
      })) || []
    );
  });

  /**
   * Tenure of savings options from lookUpData (1-30 years)
   */
  const tenureOfSavingsOptions = computed(() =>
  {
    if (lookUpData.value?.savingsTenure?.length)
    {
      return lookUpData.value.savingsTenure.map(item => ({
        value: parseInt(item.code) || parseInt(item.text) || item.id,
        label: item.text,
        id: item.id,
      }));
    }
    // Fallback: generate 1-30 years
    const options = [];
    for (let i = 1; i <= 30; i++)
    {
      options.push({
        value: i,
        label: `${i} Year${i > 1 ? 's' : ''}`,
        id: null,
      });
    }
    return options;
  });

  /**
   * Plan type options from localLookups
   */
  const planTypeOptions = computed(() =>
  {
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

  /**
   * Insurance provider options
   */
  const insuranceProviderOptions = computed(() =>
  {
    return (
      insuranceProviders.value?.map(provider => ({
        value: provider.id,
        label: provider.text,
      })) || []
    );
  });

  // =========================================================================
  // 3. HELPER FUNCTIONS
  // =========================================================================

  /**
   * Map payment term value to frequency label
   * @param {Number} term - Payment term (0, 1, 3, 6, 12)
   * @returns {String} Frequency label
   */
  const getFrequencyFromPaymentTerm = term =>
  {
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

  /**
   * Get payment term display label
   * @param {Number} paymentTerm - Payment term value
   * @returns {String} Display label or 'N/A'
   */
  const getPaymentTermLabel = paymentTerm =>
  {
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

  /**
   * Get payment term display title (for Show.vue)
   * @param {Number} paymentTerm - Payment term value
   * @returns {String|null} Display title
   */
  const getPaymentTermTitle = paymentTerm =>
  {
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

  /**
   * Check if investment frequency is lumpsum type
   * @param {String|Number} frequency - Frequency value
   * @param {Array} options - Investment frequency options (optional, uses computed if not provided)
   * @returns {Boolean}
   */
  const isLumpsumFrequency = (frequency, options = null) =>
  {
    if (!frequency) return false;

    // Check by string value
    if (
      typeof frequency === 'string' &&
      frequency.toLowerCase() === 'lumpsum'
    )
    {
      return true;
    }

    // Check by comparing with options
    const optionsToUse = options || investmentFrequencyOptions.value;
    const selectedOption = optionsToUse.find(
      opt => opt.value === frequency || opt.id === frequency,
    );
    return selectedOption?.label?.toLowerCase() === 'lumpsum';
  };

  /**
   * Format price with commas and decimals
   * @param {Number} value - Price value
   * @returns {String} Formatted price
   */
  const formatPrice = value =>
  {
    if (!value && value !== 0) return '';
    return useFormatPrice(value, true);
  };

  /**
   * Format number with 2 decimal places
   * @param {Number} value - Number to format
   * @returns {String} Formatted number or 'N/A'
   */
  const formatNumber = value =>
  {
    if (value === 'N/A' || value === null || value === undefined) return 'N/A';
    return new Intl.NumberFormat('en-US', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(value);
  };

  /**
   * Extract eligibility value from plan
   * @param {Object} plan - Plan object
   * @param {String} code - Eligibility code
   * @returns {String} Eligibility value or 'N/A'
   */
  const getEligibilityValue = (plan, code) =>
  {
    if (plan?.eligibilities && Array.isArray(plan.eligibilities))
    {
      const found = plan.eligibilities.find(item => item.code === code);
      return found ? found.value : 'N/A';
    }
    return 'N/A';
  };

  /**
   * Calculate total annual price based on payment term
   * @param {Object} item - Plan item with actualPremium and paymentTerm
   * @returns {Number|null} Total annual price
   */
  const calculateTotalAnnualPrice = item =>
  {
    const price = parseFloat(item.actualPremium || item.price || 0);
    if (!price) return null;

    const paymentTerm = parseInt(item.paymentTerm);
    let multiplier = 1; // Default to annual/lumpsum

    if (paymentTerm === 12)
    {
      multiplier = 12; // Monthly - multiply by 12
    } else if (paymentTerm === 3)
    {
      multiplier = 4; // Quarterly - multiply by 4
    } else if (paymentTerm === 6)
    {
      multiplier = 2; // Semi-Annual - multiply by 2
    } else if (paymentTerm === 1 || paymentTerm === 0)
    {
      multiplier = 1; // Annual or Lumpsum - multiply by 1
    }

    return price * multiplier;
  };

  /**
   * Convert text to title case
   * @param {String} str - String to convert
   * @returns {String} Title cased string
   */
  const toTitleCase = str =>
  {
    if (!str) return '';
    return str.toLowerCase().replace(/\b\w/g, char => char.toUpperCase());
  };

  /**
   * Find currency option by id or value
   * @param {String|Number} currencyId - Currency ID or value
   * @returns {Object|null} Currency option or null
   */
  const findCurrencyOption = currencyId =>
  {
    if (!currencyId) return null;
    return (
      currencyOptions.value.find(
        opt => opt.value === currencyId || opt.id === currencyId,
      ) || null
    );
  };

  /**
   * Find investment frequency option by value or id
   * @param {String|Number} frequency - Frequency value or id
   * @returns {Object|null} Investment frequency option or null
   */
  const findInvestmentFrequencyOption = frequency =>
  {
    if (!frequency) return null;
    return (
      investmentFrequencyOptions.value.find(
        opt => opt.value === frequency || opt.id === frequency,
      ) || null
    );
  };

  /**
   * Calculate plan payout using savings calculator
   * @param {Object} planData - Plan data with amount, rate, years, paymentTerm, investmentFrequency
   * @returns {Object|null} { payout, formattedPayout } or null if validation fails
   */
  const calculatePlanPayout = planData =>
  {
    if (!planData) return null;

    const { calculatePayout } = useSavingsCalculator();

    const amount = parseFloat(
      planData.actualPremium || planData.investment_amount || 0,
    );
    const rate = parseFloat(
      planData.expectedRor || planData.expected_rate_of_return || 0,
    );
    const years = parseInt(planData.tenure || planData.tenure_of_savings || 0);

    // Validate required fields
    if (!amount || !rate || !years)
    {
      if (notification)
      {
        notification.warning({
          title: 'Please fill Investment Amount, Rate of Return, and Tenure',
          position: 'top',
        });
      }
      return null;
    }

    // Determine frequency based on investment type
    const frequencyValue =
      planData.investmentFrequency || planData.investment_frequency;
    const isLumpsum = isLumpsumFrequency(frequencyValue);
    const frequency = isLumpsum
      ? 'Single Payment'
      : getFrequencyFromPaymentTerm(
        planData.paymentTerm || planData.payment_term,
      );

    // Calculate payout
    const payout = calculatePayout({ amount, rate, years, frequency });

    return {
      payout,
      formattedPayout: formatPrice(payout),
    };
  };

  // =========================================================================
  // 4. RIDERS MANAGEMENT
  // =========================================================================

  /**
   * Show riders if available
   */
  const showRiders = computed(() =>
  {
    return ridersData.value.length > 0;
  });

  /**
   * Calculate total rider price from active riders
   */
  const totalRiderPrice = computed(() =>
  {
    return ridersData.value
      .filter(rider => rider.active)
      .reduce((total, rider) =>
      {
        const price = parseFloat(rider.coverValue2 || rider.price || 0);
        return total + price;
      }, 0);
  });

  /**
   * Fetch rider details for a plan
   * @param {Number} planId - Plan ID
   */
  const getRiderDetails = async planId =>
  {
    if (!planId) return;

    try
    {
      const res = await axios.get(`/personal-quotes/savings/riders/${planId}`);

      // Map riders data
      ridersData.value = res.data.map(rider => ({
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
      }));
    } catch (error)
    {
      if (notification)
      {
        notification.error({
          title: 'Error fetching rider details',
          position: 'top',
        });
      }
      ridersData.value = [];
    }
  };

  /**
   * Process riders for API submission
   * @param {Array} riders - Riders data (optional, uses ridersData if not provided)
   * @returns {Array} Processed riders array
   */
  const processRidersForAPI = (riders = null) =>
  {
    const ridersToProcess = riders || ridersData.value;
    return ridersToProcess.map(rider => ({
      riderId: rider.riderId,
      active: rider.active ? true : false,
      price:
        Number(parseFloat(rider.coverValue2 || rider.price || 0).toFixed(2)) ||
        0,
      coverValue: Number(parseFloat(rider.coverValue || 0).toFixed(2)) || 0,
    }));
  };

  /**
   * Reset riders data
   */
  const resetRiders = () =>
  {
    ridersData.value = [];
  };

  // =========================================================================
  // 5. PLANS DATA MANAGEMENT
  // =========================================================================

  /**
   * Load available plans data
   * @param {String} quoteUuid - Quote UUID
   * @returns {Promise<Array>} Available plans
   */
  const onLoadAvailablePlansData = async (savingQuoteUuid = null) =>
  {
    let quoteUuid = savingQuoteUuid || quote.uuid;
    availablePlansTable.isLoading = true;
    try
    {
      const url = `/quotes/savings/available-plans/${quoteUuid}`;
      const { data } = await axios.post(url, { jsonData: true });

      // Process flat array
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
      }));

      availablePlansTable.data = processedPlans;
      // Update global shared plans so ecomDetail computed property can reactively update
      sharedAvailablePlans.value = processedPlans;
      return processedPlans;
    } catch (err)
    {
      availablePlansTable.data = [];
      return [];
    } finally
    {
      availablePlansTable.isLoading = false;
    }
  };

  /**
   * Get plan details by ID
   * @param {Number} planId - Plan ID
   * @param {String} quoteUuid - Quote UUID
   * @returns {Promise<Object|null>} Plan details
   */
  const getPlanDetails = async (planId, quoteUuid) =>
  {
    try
    {
      // First try to find in availablePlansTable
      const foundPlan = availablePlansTable.data.find(
        plan => plan.id === planId,
      );
      if (foundPlan)
      {
        return foundPlan;
      }

      // If not found, fetch from API
      const { data } = await axios.get(
        `/savings/${quoteUuid}/plan_details/${planId}`,
      );
      return data;
    } catch (error)
    {
      if (notification)
      {
        notification.error({
          title: 'Error',
          message: 'Plan Details Not Found',
          position: 'top',
        });
      }
      return null;
    }
  };

  /**
   * Fetch provider plans by provider ID
   * @param {Number} providerId - Provider ID
   * @returns {Promise<Array>} Provider plans
   */
  const fetchProviderPlans = async providerId =>
  {
    if (!providerId)
    {
      providerPlans.value = [];
      return [];
    }

    providerPlansLoading.value = true;
    try
    {
      const { data } = await axios.get(
        `/personal-quotes/savings/provider-plans/${providerId}`,
      );

      if (data.plans)
      {
        providerPlans.value = data.plans;
        return data.plans;
      }

      providerPlans.value = [];
      return [];
    } catch (err)
    {
      if (notification)
      {
        notification.error({
          title: 'Failed to fetch plans',
          position: 'top',
        });
      }
      providerPlans.value = [];
      return [];
    } finally
    {
      providerPlansLoading.value = false;
    }
  };

  // =========================================================================
  // 6. API PAYLOAD BUILDING
  // =========================================================================

  /**
   * Build API payload for plan creation/update
   * @param {Object} planData - Plan data
   * @param {Object} options - Additional options
   * @returns {Object} API payload
   */
  const buildPlanPayload = (planData, options = {}) =>
  {
    const { isUpdate = false } = options;

    return {
      quoteUID: planData.quoteUUID || planData.quote_uuid,
      update: isUpdate,
      plans: [
        {
          planId: planData.planId || planData.plan_id,
          plan_type: planData.planType || planData.plan_type,
          isDisabled: planData.isDisabled || false,
          isManualUpdate: planData.isManualUpdate || false,
          insurerQuoteNo: planData.insurerQuoteNo || '',
          investmentAmount: parseFloat(planData.investmentAmount) || 0,
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

  /**
   * Update plan via API - unified function for plan updates
   * @param {Object} planDetails - Plan details object
   * @param {String} quoteUuid - Quote UUID
   * @param {Object} options - Additional options (riders, etc.)
   * @returns {Promise} API response promise
   */
  const updatePlan = async (planDetails, quoteUuid, options = {}) =>
  {
    if (!planDetails || !quoteUuid)
    {
      throw new Error('Plan details and quote UUID are required');
    }

    // Use helper functions to find options
    const investmentFrequencyOption = findInvestmentFrequencyOption(
      planDetails.investmentFrequency,
    );
    const currencyOption = findCurrencyOption(planDetails.currencyId);

    // Process riders if provided
    const processedRiders = options.riders
      ? processRidersForAPI(options.riders)
      : processRidersForAPI(ridersData.value);

    // Build payload using composable helper
    const apiPayload = buildPlanPayload(
      {
        quoteUUID: quoteUuid,
        planId: planDetails.id || planDetails.planId,
        isDisabled: planDetails.isDisabled || false,
        isManualUpdate: planDetails.isManualUpdate || false,
        insurerQuoteNo: planDetails.insurerQuoteNo || '',
        investmentAmount: planDetails.actualPremium,
        currency:
          currencyOption?.value ||
          currencyOption?.label ||
          planDetails.currency ||
          'AED',
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

    // Make API call
    try
    {
      const response = await axios.post(
        `/quotes/savings/${quoteUuid}/savings-plan-manual-process`,
        apiPayload,
      );

      if (notification)
      {
        notification.success({
          title: 'Plan updated successfully',
          position: 'top',
        });
      }

      return response;
    } catch (error)
    {
      if (notification)
      {
        notification.error({
          title: error.response?.data?.message || 'Failed to update plan',
          position: 'top',
        });

        // Show validation errors if present
        if (error.response?.data?.errors)
        {
          Object.keys(error.response.data.errors).forEach(function (key)
          {
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

  /**
   * Create plan via API - unified function for plan creation
   * @param {Object} formData - Form data object (from Inertia form or plain object)
   * @param {String} quoteUuid - Quote UUID
   * @param {Object} options - Additional options (riders, etc.)
   * @returns {Promise} API response promise
   */
  const createPlan = async (formData, quoteUuid, options = {}) =>
  {
    if (!formData || !quoteUuid)
    {
      throw new Error('Form data and quote UUID are required');
    }

    // Use helper function to find investment frequency option
    const investmentFrequencyOption = findInvestmentFrequencyOption(
      formData.investment_frequency,
    );

    // Process riders if provided
    const processedRiders = options.riders
      ? processRidersForAPI(options.riders)
      : processRidersForAPI(ridersData.value);

    // Build payload using composable helper
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
        investmentAmount: formData.investment_amount || formData.actual_premium,
        currency: formData.currency || 'AED',
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

    // Make API call
    try
    {
      const response = await axios.post(
        `/quotes/savings/${quoteUuid}/savings-plan-manual-process`,
        apiPayload,
      );

      if (notification)
      {
        notification.success({
          title: 'Savings plan created successfully',
          position: 'top',
        });
      }

      await onLoadAvailablePlansData(quoteUuid);
      router.reload({
        preserveScroll: true,
        only: ['availablePlansTable.data'],
      });

      return response;
    } catch (error)
    {
      if (notification)
      {
        notification.error({
          title: error.response?.data?.message || 'Failed to create plan',
          position: 'top',
        });

        // Show validation errors if present
        if (error.response?.data?.errors)
        {
          Object.keys(error.response.data.errors).forEach(function (key)
          {
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

  /**
   * Toggle plan visibility (hide/show) via API
   * @param {Number} planId - Plan ID
   * @param {String} quoteUuid - Quote UUID
   * @param {Boolean} isDisabled - Whether plan should be disabled (hidden)
   * @param {Number} providerId - Provider ID (optional, will try to get from planDetails if not provided)
   * @returns {Promise} API response promise
   */
  const togglePlanVisibility = async (
    planId,
    quoteUuid,
    isDisabled,
    providerId = null,
  ) =>
  {
    if (!planId || !quoteUuid)
    {
      throw new Error('Plan ID and quote UUID are required');
    }

    try
    {
      const response = await axios.post(
        route('savings-plan-toggle-visibility'),
        {
          quoteUID: quoteUuid,
          providerId: providerId || null, // Will be set by backend if not provided
          planId: planId,
          isDisabled: isDisabled, // true = hide, false = show
        },
      );

      if (notification)
      {
        notification.success({
          title: `Plan has been ${isDisabled ? 'hidden' : 'shown'}`,
          position: 'top',
        });
      }

      return response;
    } catch (error)
    {
      if (notification)
      {
        notification.error({
          title:
            error.response?.data?.message || 'Error updating plan visibility',
          position: 'top',
        });
      }
      throw error;
    }
  };

  // =========================================================================
  // 7. FORM SYNCHRONIZATION HELPERS
  // =========================================================================

  /**
   * Sync currency ID from currency value
   * @param {Object} form - Form object
   * @param {String} currencyValue - Currency value
   */
  const syncCurrencyId = (form, currencyValue) =>
  {
    const selectedOption = currencyOptions.value.find(
      opt => opt.value === currencyValue,
    );
    form.currency_id = selectedOption?.id || null;
    form.currencyId = selectedOption?.id || null;
  };

  /**
   * Sync tenure ID from tenure value
   * @param {Object} form - Form object
   * @param {Number} tenureValue - Tenure value
   */
  const syncTenureId = (form, tenureValue) =>
  {
    const selectedOption = tenureOfSavingsOptions.value.find(
      opt => opt.value === tenureValue,
    );
    form.tenure_id = selectedOption?.id || null;
    form.tenureId = selectedOption?.id || null;
  };

  /**
   * Sync investment frequency ID from frequency value
   * @param {Object} form - Form object
   * @param {String|Number} frequencyValue - Frequency value
   */
  const syncInvestmentFrequencyId = (form, frequencyValue) =>
  {
    const selectedOption = investmentFrequencyOptions.value.find(
      opt => opt.value === frequencyValue || opt.id === frequencyValue,
    );
    form.investment_frequency_id = selectedOption?.id || null;
    form.investmentFrequencyId = selectedOption?.id || null;
  };

  /**
   * Auto-select payment term based on investment frequency
   * @param {Object} form - Form object
   * @param {String|Number} frequency - Investment frequency
   */
  const syncPaymentTermByFrequency = (form, frequency) =>
  {
    const checkIsLumpsum = isLumpsumFrequency(frequency);

    // Find Single Payment option by label
    const singlePaymentOption = paymentTermOptions.value.find(opt =>
      opt.label?.toLowerCase().includes('single'),
    );

    // Auto-select payment term based on frequency
    if (checkIsLumpsum && singlePaymentOption)
    {
      form.payment_term = singlePaymentOption.value;
      form.paymentTerm = singlePaymentOption.value;
    } else if (form.payment_term === singlePaymentOption?.value)
    {
      form.payment_term = null;
      form.paymentTerm = null;
    }
  };

  // =========================================================================
  // 8. EXCHANGE RATE MANAGEMENT (For Show.vue & AvailablePlans)
  // =========================================================================

  /**
   * Fetch exchange rates from API
   */
  const fetchExchangeRates = async () =>
  {
    if (Object.keys(exchangeRates.value).length > 0) return;

    ratesLoading.value = true;
    try
    {
      const { data } = await axios.get('https://v6.exchangerate-api.com/v6/7defc3b81189dcc54b09144a/latest/USD');
      if (data?.conversion_rates)
      {
        exchangeRates.value = { ...data?.conversion_rates };
      }
    } catch (e)
    {
      notification.error({
        title: 'Failed to fetch exchange rates',
        position: 'top',
      });
    } finally
    {
      ratesLoading.value = false;
    }
  };

  /**
   * Get exchange rate for a plan item
   * @param {Object} item - Plan item with currency
   * @returns {Number|null} Exchange rate or null
   */
  const getExchangeRate = item =>
  {
    const id = item.id;
    const cur = (item.currency || 'USD').toUpperCase();
    const usdToAed = exchangeRates.value['AED'] || null;

    // Use user-edited rate if exists
    if (planRates.value[id] !== undefined) return planRates.value[id];

    if (!usdToAed) return null; // API not loaded yet

    if (cur === 'AED') return 1;
    if (cur === 'USD') return Math.round(usdToAed * 10000) / 10000;

    // Other currencies: rate = usdToAed / (USD to Currency)
    const usdToCur = exchangeRates.value[cur];
    return usdToCur ? Math.round((usdToAed / usdToCur) * 10000) / 10000 : null;
  };

  /**
   * Set custom exchange rate for a plan
   * @param {Number} planId - Plan ID
   * @param {Number} rate - Exchange rate
   */
  const setExchangeRate = (planId, rate) =>
  {
    const num = parseFloat(rate);
    if (!isNaN(num) && num > 0)
    {
      planRates.value[planId] = num;
    }
  };

  /**
   * Convert amount to AED
   * @param {Number} amount - Amount to convert
   * @param {Object} item - Plan item with currency
   * @returns {Number|null} Amount in AED
   */
  const convertToAED = (amount, item) =>
  {
    const rate = getExchangeRate(item);
    return rate ? Math.round(amount * rate * 100) / 100 : null;
  };

  // =========================================================================
  // 9. ECOM DETAILS MANAGEMENT
  // =========================================================================
  // Note: planExchangeRate and sharedAvailablePlans are global shared state
  // ecomDetail is a computed property that reactively derives from sharedAvailablePlans and quote

  /**
   * Get ecom display price from plan item
   */
  const getEcomDisplayPrice = item =>
  {
    if (!item) return 0;
    return parseFloat(item.actualPremium || item.totalPrice || 0);
  };

  /**
   * Computed property: ecomDetail automatically updates when sharedAvailablePlans or quote.plan_id changes
   */
  const ecomDetail = computed(() =>
  {
    const allPlans = sharedAvailablePlans.value || [];
    const quoteValue = quote?.value || quote;
    const selectedPlanId = quoteValue?.plan_id;

    if (!allPlans.length || !selectedPlanId)
    {
      return null;
    }

    const foundPlan = allPlans.find(plan =>
    {
      const matchesId =
        String(plan.id) === String(selectedPlanId) ||
        String(plan.planId) === String(selectedPlanId) ||
        String(plan.plan_id) === String(selectedPlanId);
      const isNotDisabled = !plan.isDisabled;
      return matchesId && isNotDisabled;
    });

    if (foundPlan)
    {
      return {
        ...foundPlan,
        providerName:
          foundPlan.providerName ||
          foundPlan.provider?.text ||
          foundPlan.providerName,
        planName: foundPlan.name || foundPlan.planName || foundPlan.text,
        actualPremium: parseFloat(
          foundPlan.actualPremium || foundPlan.totalPrice || 0,
        ),
        totalPrice: parseFloat(
          foundPlan.actualPremium || foundPlan.totalPrice || 0,
        ),
        currency: foundPlan.currency || foundPlan.currencyName || 'AED',
        paymentTerm: foundPlan.paymentTerm,
        isApi: foundPlan.isApi || false,
        isUnderwritten: foundPlan.isUnderwritten || false,
      };
    }

    return null;
  });

  /**
   * Update sharedAvailablePlans (for backward compatibility and manual updates)
   * @param {Array} allPlans - Array of plans to set
   */
  const updateEcomDetailFromPlans = (allPlans = []) =>
  {
    sharedAvailablePlans.value = allPlans;
  };

  /**
   * Calculate total annual price for ecom detail
   */
  const totalAnnualPrice = computed(() =>
  {
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

  const totalPriceAED = computed(() =>
  {
    const ecom = ecomDetail.value;
    if (!ecom) return 'N/A';
    const displayPrice = getEcomDisplayPrice(ecom);
    const priceInAED = convertToAED(displayPrice, ecom);
    const paymentTerm =
      ecom?.paymentTerm ??
      (quote?.value || quote)?.savings_quote?.payment_term ??
      1;
    return (
      calculateTotalAnnualPrice({ actualPremium: priceInAED, paymentTerm: 1 }) ||
      'N/A'
    );
  });

  /**
   * Get total annual price in AED
   */
  const getTotalAnnualPriceAED = () =>
  {
    const ecom = ecomDetail.value;
    if (!ecom) return 'N/A';
    const displayPrice = getEcomDisplayPrice(ecom);
    const priceInAED = convertToAED(displayPrice, ecom);
    const paymentTerm =
      ecom?.paymentTerm ??
      (quote?.value || quote)?.savings_quote?.payment_term ??
      1;
    console.log(priceInAED, paymentTerm);
    return formatNumber(
      calculateTotalAnnualPrice({ actualPremium: priceInAED, paymentTerm }) ||
      0,
    );
  };

  // =========================================================================
  // RETURN ALL EXPORTS
  // =========================================================================

  return {
    // State
    ridersData,
    providerPlans,
    providerPlansLoading,
    availablePlansTable,
    planDetails,
    modals,
    exchangeRates,
    planRates,
    ratesLoading,
    // Lookup Options
    currencyOptions,
    investmentFrequencyOptions,
    paymentTermOptions,
    tenureOfSavingsOptions,
    planTypeOptions,
    insuranceProviderOptions,

    // Helper Functions
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

    // Riders
    showRiders,
    totalRiderPrice,
    getRiderDetails,
    processRidersForAPI,
    resetRiders,

    // Plans Data
    onLoadAvailablePlansData,
    getPlanDetails,
    fetchProviderPlans,

    // API
    buildPlanPayload,
    updatePlan,
    createPlan,
    togglePlanVisibility,

    // Form Sync
    syncCurrencyId,
    syncTenureId,
    syncInvestmentFrequencyId,
    syncPaymentTermByFrequency,

    // Exchange Rates (for Show.vue & AvailablePlans)
    fetchExchangeRates,
    getExchangeRate,
    setExchangeRate,
    convertToAED,

    // Ecom Details
    ecomDetail,
    planExchangeRate,
    sharedAvailablePlans,
    getEcomDisplayPrice,
    totalAnnualPrice,
    totalPriceAED,
    getTotalAnnualPriceAED,
    updateEcomDetailFromPlans,
  };
}
