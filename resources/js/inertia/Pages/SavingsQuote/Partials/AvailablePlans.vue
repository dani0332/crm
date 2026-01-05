<script setup>
import SavingsCalculator from '@/inertia/Components/SavingsCalculator.vue';
import SelectPlan from '@/inertia/Components/SelectPlan.vue';
import LazyCreatePlan from './CreatePlan.vue';
import PlanDetails from './PlanDetails.vue';

const emit = defineEmits(['plan-selected']);

const props = defineProps({
  quote: Object,
  payments: Array,
  insuranceProviders: Object,
  savingsCalculatorUrl: String,
  ecomSavingsInsuranceQuoteUrl: String,
  websiteURL: String,
  lockLeadSectionsDetails: Object,
});

const page = usePage();
const notification = useNotifications('toast');
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

// Helper function to convert text to title case (e.g., "METLIFE GULF" -> "Metlife Gulf")
const toTitleCase = str => {
  if (!str) return '';
  return str.toLowerCase().replace(/\b\w/g, char => char.toUpperCase());
};

const modals = reactive({
  planDetails: false,
  createPlan: false,
  sendConfirm: false,
  savingsCalculator: false,
});

const availablePlansTable = reactive({
  data: [],
  isLoading: false,
  columns: [
    {
      text: 'Provider Name',
      value: 'providerName',
    },
    {
      text: 'Plan',
      value: 'name',
    },
    {
      text: 'Type of Plan',
      value: 'typeOfPlan',
    },
    {
      text: 'Insurer Quote Number',
      value: 'insurerQuoteNo',
    },
    {
      text: 'Currency',
      value: 'currency',
    },
    {
      text: 'Price',
      value: 'price',
    },
    {
      text: 'Exchange Rate',
      value: 'exchangeRate',
    },
    {
      text: 'Price (AED)',
      value: 'priceAed',
    },
    {
      text: 'Investment Frequency',
      value: 'investmentFrequency',
    },
    {
      text: 'Payment Term',
      value: 'paymentTerm',
    },
    {
      text: 'Tenure of Savings',
      value: 'tenureOfSavings',
    },
    {
      text: 'Expected Rate of Return',
      value: 'expectedRateOfReturn',
    },
    {
      text: 'Lumpsum Amount',
      value: 'lumpsumAmount',
    },
    {
      text: 'Total Annual Price',
      value: 'totalAnnualPrice',
    },
    {
      text: 'Total Annual Price (AED)',
      value: 'totalAnnualPriceAed',
    },
    {
      text: 'Action',
      value: 'action',
    },
  ],
});

const selectedPlans = ref([]);
const selectedPlanType = ref(null);
const toggleLoader = ref(false);
const viewButtonLoading = ref(false);
const planDetails = ref(null);

const selectedProviderPlan = ref({
  id: props.quote?.plan_id,
  planName: props.quote?.plans?.name,
  providerName: props.quote?.plans?.providerName,
  premium: props.quote?.plans?.premium,
});

const handlePlanSelected = plan => {
  selectedProviderPlan.value.id = plan.id;
  selectedProviderPlan.value.planName = plan.planName;
  selectedProviderPlan.value.providerName = plan.providerName;
  selectedProviderPlan.value.premium = plan.premium;
  router.reload({
    preserveState: true,
    preserveScroll: true,
    only: ['payments', 'quoteRequest', 'quote', 'bookPolicyDetails'],
  });
  emit('plan-selected', plan);
  onLoadAvailablePlansData();
};

const onLoadAvailablePlansData = async () => {
  availablePlansTable.isLoading = true;
  let data = {
    jsonData: true,
  };
  let url = `/quotes/savings/available-plans/${props.quote.uuid}`;
  axios
    .post(url, data)
    .then(res => {
      // Process the response data to flatten regular and lumpsum plans
      const processedPlans = [];

      // Process regular plans
      if (res.data.regular && Array.isArray(res.data.regular)) {
        res.data.regular.forEach(plan => {
          const processedPlan = {
            ...plan,
            investmentFrequency: 'Regular',
            currency: plan.currencyName || 'USD',
            minimumInvestment: getEligibilityValue(
              plan,
              'minimumInvestmentAmount',
            ),
            policyTerm: getEligibilityValue(plan, 'policyTerm'),
            isManualUpdate: plan.isManualUpdate || false,
            isDisabled: plan.isDisabled || false,
            actualPremium: plan.actualPremium,
            insuranceProviderId: plan.providerId || plan.insuranceProviderId,
            providerCode: plan.providerCode,
          };
          processedPlans.push(processedPlan);
        });
      }

      // Process lumpsum plans
      if (res.data.lumpsum && Array.isArray(res.data.lumpsum)) {
        res.data.lumpsum.forEach(plan => {
          const processedPlan = {
            ...plan,
            investmentFrequency: 'Lumpsum',
            currency: plan.currencyName || 'USD',
            minimumInvestment: getEligibilityValue(
              plan,
              'minimumInvestmentAmount',
            ),
            policyTerm: getEligibilityValue(plan, 'policyTerm'),
            isManualUpdate: plan.isManualUpdate || false,
            isDisabled: plan.isDisabled || false,
            actualPremium: plan.actualPremium,
            insuranceProviderId: plan.providerId || plan.insuranceProviderId,
            providerCode: plan.providerCode,
          };
          processedPlans.push(processedPlan);
        });
      }

      availablePlansTable.data = processedPlans;
    })
    .catch(err => {
      console.log(err);
      availablePlansTable.data = [];
    })
    .finally(() => {
      availablePlansTable.isLoading = false;
    });
};

// Helper function to extract value from eligibility array
const getEligibilityValue = (plan, code) => {
  if (plan?.eligibilities && Array.isArray(plan.eligibilities)) {
    const found = plan.eligibilities.find(item => item.code === code);
    return found ? found.value : 'N/A';
  }
  return 'N/A';
};

const getPlanDetails = id => {
  viewButtonLoading.value = true;
  try {
    const foundPlan = availablePlansTable.data.find(plan => plan.id === id);
    if (foundPlan) {
      planDetails.value = foundPlan;
      modals.planDetails = true;
      viewButtonLoading.value = false;
    } else {
      axios
        .get(`/savings/${props.quote.uuid}/plan_details/${id}`)
        .then(res => {
          planDetails.value = res.data;
          modals.planDetails = true;
          viewButtonLoading.value = false;
        })
        .catch(err => {
          notification.error({
            title: 'Error',
            message: 'Plan Details Not Found',
            position: 'top',
          });
          console.log(err);
          viewButtonLoading.value = false;
        });
    }
  } catch (err) {
    console.log(err);
    notification.error({
      title: 'Error',
      message: 'Something went wrong',
      position: 'top',
    });
    viewButtonLoading.value = false;
  }
};

const onLoadAvailablePlansDataAndPlanDetails = async () => {
  await onLoadAvailablePlansData();
  if (modals.planDetails && planDetails.value) {
    getPlanDetails(planDetails.value.id);
  }
};

const { copy, copied } = useClipboard();

// Copy link functionality for Available Plans
const copyLink = () => {
  const link = props.ecomSavingsInsuranceQuoteUrl
    ? props.ecomSavingsInsuranceQuoteUrl + props.quote.uuid
    : `/quotes/savings/${props.quote.uuid}`;
  copy(link);
  if (copied)
    notification.success({
      title: 'Link copied to clipboard',
      position: 'top',
    });
};

// Copy plan-specific payment URL
const copyPlanURL = item => {
  const baseUrl =
    props.ecomSavingsInsuranceQuoteUrl ||
    props.websiteURL + '/savings-insurance/quote/';
  const paymentLink = `${baseUrl}${props.quote.uuid}/payment/?providerCode=${item.providerCode}&planId=${item.id}`;
  copy(paymentLink);
  if (copied)
    notification.success({
      title: 'Plan link copied to clipboard',
      position: 'top',
    });
};

// Open Savings Calculator
const openSavingsCalculator = () => {
  modals.savingsCalculator = true;
};

// OCA Email functionality
const isOcaButtonDisabled = ref(false);
const processingOCAEmail = ref(false);

const confirmSendOCAEmail = () => {
  const first_name = props.quote.first_name || '';
  const last_name = props.quote.last_name || '';
  processingOCAEmail.value = true;

  axios
    .post(
      `/quotes/savings/${props.quote.uuid}/send-oca`,
      {
        quote_type_id: page.props.quoteTypeId,
        quote_id: props.quote.id,
        quote_uuid: props.quote.uuid,
        quote_cdb_id: props.quote.code,
        customer_name: `${first_name} ${last_name}`,
        customer_email: props.quote.email,
        customer_id: props.quote.customer_id,
        advisor_name: props.quote.advisor?.name || null,
        advisor_email: props.quote.advisor?.email || null,
        advisor_mobile_no: props.quote.advisor?.mobile_no || null,
        advisor_landline_no: props.quote.advisor?.landline_no || null,
      },
      {
        responseType: 'json',
      },
    )
    .then(response => {
      notification.success({
        title: response.data.success || 'OCA email sent successfully',
        position: 'top',
      });
      isOcaButtonDisabled.value = true;
      router.reload({
        replace: true,
        preserveScroll: true,
        preserveState: true,
      });
    })
    .catch(error => {
      notification.error({
        title: 'OCA email sending failed, please try again.',
        position: 'top',
      });
      console.error(error);
    })
    .finally(() => {
      modals.sendConfirm = false;
      processingOCAEmail.value = false;
    });
};

// Create Plan functionality
const onCreatePlan = async () => {
  modals.createPlan = false;
  onLoadAvailablePlansData();
  router.reload({
    preserveScroll: true,
    only: ['payments', 'quoteRequest', 'quote', 'bookPolicyDetails'],
  });
};

const onPlanError = errors => {
  console.error('Plan creation error:', errors);
};

// Handle plan details update from PlanDetails component
const onPlanDetailsUpdate = () => {
  onLoadAvailablePlansDataAndPlanDetails();
};

const readOnlyMode = reactive({
  isDisable: true,
});

onMounted(() => {
  onLoadAvailablePlansData();
  readOnlyMode.isDisable = !can(permissionsEnum.All_QUOTES_VIEWONLY_ACCESS);
});
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="true">
      <template #header>
        <div class="flex flex-wrap gap-4 justify-between items-center">
          <h3 class="font-semibold text-primary-800 text-lg">
            Available Plans
            <x-tag size="sm">{{ availablePlansTable.data?.length || 0 }}</x-tag>
          </h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />

        <!-- Action buttons for Available Plans -->
        <div
          class="flex justify-between items-center flex-wrap gap-2 mb-4"
          v-if="
            readOnlyMode.isDisable === true &&
            !availablePlansTable.isLoading &&
            availablePlansTable.data &&
            typeof availablePlansTable.data !== 'string'
          "
        >
          <div class="flex gap-2">
            <!-- Savings Calculator Button -->
            <x-tooltip placement="top" align="left">
              <x-button
                @click.prevent="openSavingsCalculator"
                size="sm"
                color="emerald"
              >
                Savings Calculator
              </x-button>
              <template #tooltip>
                <div>
                  Open the Savings Calculator to help customers explore
                  investment options and estimate returns.
                </div>
              </template>
            </x-tooltip>
          </div>

          <div class="flex gap-2">
            <!-- Send OCA Email Button -->
            <x-tooltip placement="top" align="left">
              <x-button
                @click.prevent="modals.sendConfirm = true"
                size="sm"
                color="orange"
                :disabled="
                  quote.advisor_id != $page.props.auth.user.id ||
                  isOcaButtonDisabled
                "
              >
                Send OCA Email to Client
              </x-button>
              <template #tooltip>
                <div>
                  When clicked, this button sends the One Click Apply (OCA)
                  email to the customer with available savings plans and
                  options, helping them finalize their investment with ease.
                </div>
              </template>
            </x-tooltip>

            <!-- Copy Link Button -->
            <x-button
              v-if="
                availablePlansTable.data && availablePlansTable.data.length > 0
              "
              size="sm"
              color="emerald"
              @click.prevent="copyLink"
            >
              Copy Link
            </x-button>

            <!-- Add Plan Button -->
            <x-button
              @click.prevent="modals.createPlan = true"
              size="sm"
              color="orange"
            >
              Add Plan
            </x-button>
          </div>
        </div>

        <div
          v-if="
            availablePlansTable.data &&
            typeof availablePlansTable.data == 'string'
          "
        >
          <p
            class="text-center text-primary-600 uppercase"
            v-if="typeof availablePlansTable.data == 'string'"
          >
            {{ availablePlansTable.data }}
          </p>
        </div>
        <div v-else>
          <div
            v-if="availablePlansTable.isLoading"
            class="flex justify-center my-8"
          >
            <x-spinner size="lg" />
          </div>
          <DataTable
            v-else
            table-class-name="tablefixed compact-rows"
            v-model:items-selected="selectedPlans"
            :headers="availablePlansTable.columns"
            :items="availablePlansTable.data || []"
            border-cell
            hide-rows-per-page
            :rows-per-page="15"
            :hide-footer="availablePlansTable.data.length < 15"
          >
            <!-- Header Templates -->
            <template #header-providerName>
              <div class="flex items-center gap-2">
                <x-tooltip placement="bottom">
                  <span
                    class="underline decoration-dotted decoration-primary-700"
                    >Provider Name</span
                  >
                  <template #tooltip>Insurance provider</template>
                </x-tooltip>
                <span class="diamond-icon"></span>
              </div>
            </template>
            <template #header-name>
              <x-tooltip placement="bottom">
                <span class="underline decoration-dotted decoration-primary-700"
                  >Plan</span
                >
                <template #tooltip>Plan name</template>
              </x-tooltip>
            </template>
            <template #header-typeOfPlan>
              <x-tooltip placement="bottom">
                <span class="underline decoration-dotted decoration-primary-700"
                  >Type of Plan</span
                >
                <template #tooltip>Type of savings plan</template>
              </x-tooltip>
            </template>
            <template #header-insurerQuoteNo>
              <x-tooltip placement="bottom">
                <span class="underline decoration-dotted decoration-primary-700"
                  >Insurer Quote Number</span
                >
                <template #tooltip>Quote number from insurer</template>
              </x-tooltip>
            </template>
            <template #header-currency>
              <x-tooltip placement="bottom">
                <span class="underline decoration-dotted decoration-primary-700"
                  >Currency</span
                >
                <template #tooltip>Currency of the plan</template>
              </x-tooltip>
            </template>
            <template #header-price>
              <x-tooltip placement="bottom">
                <span class="underline decoration-dotted decoration-primary-700"
                  >Price</span
                >
                <template #tooltip>Price in original currency</template>
              </x-tooltip>
            </template>
            <template #header-exchangeRate>
              <x-tooltip placement="bottom">
                <span class="underline decoration-dotted decoration-primary-700"
                  >Exchange Rate</span
                >
                <template #tooltip>Exchange rate to AED</template>
              </x-tooltip>
            </template>
            <template #header-priceAed>
              <x-tooltip placement="bottom">
                <span class="underline decoration-dotted decoration-primary-700"
                  >Price (AED)</span
                >
                <template #tooltip>Price converted to AED</template>
              </x-tooltip>
            </template>
            <template #header-investmentFrequency>
              <x-tooltip placement="bottom">
                <span class="underline decoration-dotted decoration-primary-700"
                  >Investment Frequency</span
                >
                <template #tooltip>How often you plan to invest</template>
              </x-tooltip>
            </template>
            <template #header-paymentTerm>
              <x-tooltip placement="bottom">
                <span class="underline decoration-dotted decoration-primary-700"
                  >Payment Term</span
                >
                <template #tooltip>Payment term duration</template>
              </x-tooltip>
            </template>
            <template #header-tenureOfSavings>
              <x-tooltip placement="bottom">
                <span class="underline decoration-dotted decoration-primary-700"
                  >Tenure of Savings</span
                >
                <template #tooltip>Duration of savings plan</template>
              </x-tooltip>
            </template>
            <template #header-expectedRateOfReturn>
              <x-tooltip placement="bottom">
                <span class="underline decoration-dotted decoration-primary-700"
                  >Expected Rate of Return</span
                >
                <template #tooltip>Expected return on investment</template>
              </x-tooltip>
            </template>
            <template #header-lumpsumAmount>
              <x-tooltip placement="bottom">
                <span class="underline decoration-dotted decoration-primary-700"
                  >Lumpsum Amount</span
                >
                <template #tooltip>Lumpsum investment amount</template>
              </x-tooltip>
            </template>
            <template #header-totalAnnualPrice>
              <x-tooltip placement="bottom">
                <span class="underline decoration-dotted decoration-primary-700"
                  >Total Annual Price</span
                >
                <template #tooltip
                  >Total annual price in original currency</template
                >
              </x-tooltip>
            </template>
            <template #header-totalAnnualPriceAed>
              <x-tooltip placement="bottom">
                <span class="underline decoration-dotted decoration-primary-700"
                  >Total Annual Price (AED)</span
                >
                <template #tooltip>Total annual price in AED</template>
              </x-tooltip>
            </template>

            <!-- Item Templates -->
            <template #item-providerName="item">
              <p>
                {{ toTitleCase(item.providerName) || 'N/A' }}
              </p>
              <div class="flex gap-1">
                <x-tag
                  v-if="item.isManualUpdate"
                  size="xs"
                  color="primary"
                  class="mt-0.5 text-[10px]"
                >
                  Manual
                </x-tag>
                <x-tag
                  v-if="item.isDisabled"
                  size="xs"
                  color="error"
                  class="mt-0.5 text-[10px]"
                >
                  Hidden
                </x-tag>
              </div>
            </template>
            <template #item-name="item">
              <span>{{ toTitleCase(item.name) || 'N/A' }}</span>
            </template>
            <template #item-typeOfPlan="item">
              <span>{{ item.typeOfPlan || 'N/A' }}</span>
            </template>
            <template #item-insurerQuoteNo="item">
              <span>{{ item.insurerQuoteNo || 'N/A' }}</span>
            </template>
            <template #item-currency="item">
              <span>{{ item.currency || 'N/A' }}</span>
            </template>
            <template #item-price="item">
              <span>{{ item.price || 'N/A' }}</span>
            </template>
            <template #item-exchangeRate="item">
              <span>{{ item.exchangeRate || 'N/A' }}</span>
            </template>
            <template #item-priceAed="item">
              <span>{{ item.priceAed || 'N/A' }}</span>
            </template>
            <template #item-investmentFrequency="item">
              <span>{{ item.investmentFrequency || 'N/A' }}</span>
            </template>
            <template #item-paymentTerm="item">
              <span>{{ item.paymentTerm || 'N/A' }}</span>
            </template>
            <template #item-tenureOfSavings="item">
              <span>{{ item.tenureOfSavings || 'N/A' }}</span>
            </template>
            <template #item-expectedRateOfReturn="item">
              <span>{{ item.expectedRateOfReturn || 'N/A' }}</span>
            </template>
            <template #item-lumpsumAmount="item">
              <span>{{ item.lumpsumAmount || 'N/A' }}</span>
            </template>
            <template #item-totalAnnualPrice="item">
              <span>{{ item.totalAnnualPrice || 'N/A' }}</span>
            </template>
            <template #item-totalAnnualPriceAed="item">
              <span>{{ item.totalAnnualPriceAed || 'N/A' }}</span>
            </template>
            <template #item-action="item">
              <div class="flex gap-3">
                <x-button
                  size="sm"
                  color="primary"
                  outlined
                  @click.prevent="
                    selectedPlanType = 'normalPlans';
                    getPlanDetails(item.id);
                  "
                  :loading="viewButtonLoading"
                  class="min-w-[100px] !rounded-xl !px-5 !py-1 !font-normal"
                >
                  View
                </x-button>
                <x-button
                  size="sm"
                  color="error"
                  outlined
                  @click.prevent="copyPlanURL(item)"
                  class="min-w-[100px] !rounded-xl !px-5 !py-1 !font-normal"
                >
                  Copy
                </x-button>
                <span>
                  <SelectPlan
                    v-if="selectedProviderPlan.id != item.id"
                    @update:selectedPlanChanged="handlePlanSelected"
                    :plan="item"
                    :quoteType="'Savings'"
                    :uuid="quote.uuid"
                    :code="quote.code"
                    :plans="availablePlansTable.data || []"
                    :extraDetails="{
                      selectedPlansIds: [selectedProviderPlan?.id],
                    }"
                    :payments="payments"
                    :insuranceProviderId="item.insuranceProviderId"
                    button-size="sm"
                    button-class="!rounded-xl !px-5 !py-1 !font-normal"
                  />

                  <x-button
                    v-else
                    size="sm"
                    color="orange"
                    outlined
                    :disabled="true"
                    class="min-w-[100px] !rounded-xl !px-5 !py-1 !font-normal"
                  >
                    Selected
                  </x-button>
                </span>
              </div>
            </template>
          </DataTable>
        </div>

        <!-- Plan Details Modal -->
        <PlanDetails
          v-model="modals.planDetails"
          :planDetails="planDetails"
          :quote="quote"
          :lockLeadSectionsDetails="lockLeadSectionsDetails"
          @update="onPlanDetailsUpdate"
        />

        <!-- Send OCA Email Confirmation Modal -->
        <x-modal
          v-model="modals.sendConfirm"
          title="Send OCA Email"
          show-close
          backdrop
        >
          <p>Are you sure you want to send the OCA email to the customer?</p>
          <p class="text-sm text-gray-500 mt-2">
            This will send an email with available savings plans to:
            <strong>{{ quote.email }}</strong>
          </p>
          <template #actions>
            <div class="text-right space-x-4">
              <x-button
                size="sm"
                ghost
                @click.prevent="modals.sendConfirm = false"
                :disabled="processingOCAEmail"
              >
                Cancel
              </x-button>
              <x-button
                size="sm"
                color="orange"
                :loading="processingOCAEmail"
                @click.prevent="confirmSendOCAEmail"
              >
                Send Email
              </x-button>
            </div>
          </template>
        </x-modal>

        <!-- Create Plan Modal -->
        <x-modal
          v-model="modals.createPlan"
          title="Create Savings Plan"
          size="xl"
          show-close
          backdrop
        >
          <LazyCreatePlan
            :quote="quote"
            :insuranceProviders="insuranceProviders"
            :available-plans="availablePlansTable.data"
            @success="onCreatePlan"
            @error="onPlanError"
          />
        </x-modal>

        <!-- Savings Calculator Modal -->
        <SavingsCalculator v-model="modals.savingsCalculator" />
      </template>
    </Collapsible>
  </div>
</template>

<style scoped>
.compact-rows :deep(tbody tr) {
  height: 40px !important;
}

.compact-rows :deep(tbody td) {
  padding: 8px 12px !important;
  vertical-align: middle;
}

.compact-rows :deep(thead th) {
  padding: 10px 12px !important;
}

.compact-rows :deep(thead th:first-child .header-text) {
  display: flex;
  align-items: center;
  gap: 8px;
}

.compact-rows :deep(.diamond-icon) {
  display: inline-block;
  width: 8px;
  height: 8px;
  background-color: transparent;
  transform: rotate(45deg);
  margin-left: 8px;
  border: 1px solid white;
}

.compact-rows :deep(.easy-checkbox label:before) {
  border-color: #10b981 !important;
}

.compact-rows
  :deep(.easy-checkbox input[type='checkbox']:checked + label:before) {
  background-color: #10b981 !important;
  border-color: #10b981 !important;
}
</style>
