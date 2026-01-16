export function useSavingsPlans()
{
    const availablePlansData = ref([]);
    const selectedPlan = ref(null);
    const selectedPlanType = ref(null);
    const toggleLoader = ref(false);
    const viewButtonLoading = ref(false);
    const planDetails = ref(null);
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

    const selectedPlans = ref([]);
    const selectedProviderPlan = ref({
        id: props.quote?.plan_id,
        planName: props.quote?.plans?.name,
        providerName: props.quote?.plans?.providerName,
        premium: props.quote?.plans?.premium,
    });

    const exchangeRates = ref({});
    const planRates = ref({});
    const ratesLoading = ref(false);


    async function onLoadAvailablePlansData()
    {
        availablePlansTable.isLoading = true;
        let data = {
            jsonData: true,
        };
        let url = `/quotes/savings/available-plans/${props.quote.uuid}`;
        await axios
            .post(url, data)
            .then(res =>
            {
                // Process flat array - each item already has investmentFrequency
                const processedPlans = res.data.map(plan => ({
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
            })
            .catch(err =>
            {
                console.log(err);
                availablePlansTable.data = [];
            })
            .finally(() =>
            {
                availablePlansTable.isLoading = false;
            });
    };

    function onLoadAvailablePlansDataAndPlanDetails()
    {
        onLoadAvailablePlansData();
        if (modals.planDetails && planDetails.value)
        {
            getPlanDetails(planDetails.value.id);
        }
    }
    function getPlanDetails(id)
    {
        viewButtonLoading.value = true;
        try
        {
            const foundPlan = availablePlansTable.data.find(plan => plan.id === id);
            if (foundPlan)
            {
                planDetails.value = foundPlan;
                modals.planDetails = true;
                viewButtonLoading.value = false;
            } else
            {
                axios.get(`/savings/${props.quote.uuid}/plan_details/${id}`).then(res =>
                {
                    planDetails.value = res.data;
                    modals.planDetails = true;
                    viewButtonLoading.value = false;
                }).catch(err =>
                {
                    notification.error({
                        title: 'Error',
                        message: 'Plan Details Not Found',
                        position: 'top',
                    });
                    console.log(err);
                    viewButtonLoading.value = false;
                });
            }
        } catch (err)
        {
            console.log(err);
            notification.error({
                title: 'Error',
                message: 'Something went wrong',
                position: 'top',
            });
            viewButtonLoading.value = false;
        }
    }
    function handlePlanSelected(plan)
    {
        if (plan.insurerQuoteNo === '' || plan.insurerQuoteNo === null)
        {
            notification.error({
                title: 'Please select a plan with an insurer quote number',
                position: 'top',
            });
            return;
        } else
        {
            selectedProviderPlan.value.id = plan.id;
            selectedProviderPlan.value.planName = plan.planName;
            selectedProviderPlan.value.providerName = plan.providerName;
            selectedProviderPlan.value.premium = plan.premium;
        }
        router.reload({
            preserveState: true,
            preserveScroll: true,
            only: ['payments', 'quoteRequest', 'quote', 'bookPolicyDetails'],
        });
        emit('plan-selected', plan);
        onLoadAvailablePlansData();
    }

    return {
        availablePlansData,
        selectedPlan,
        selectedPlanType,
        toggleLoader,
        viewButtonLoading,
        planDetails,
        modals,
        availablePlansTable,
        selectedPlans,
        selectedProviderPlan,
        exchangeRates,
        planRates,
        ratesLoading,
    }
}