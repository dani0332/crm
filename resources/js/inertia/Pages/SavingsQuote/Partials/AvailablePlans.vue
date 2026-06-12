<script setup>
import SavingsCalculator from '@/inertia/Components/SavingsCalculator.vue';
import SelectPlan from '@/inertia/Components/SelectPlan.vue';
import {
  isSukoonPurpleInvestmentPlan,
  useSavingsPlans,
} from '@/inertia/Composables/useSavingsPlans';
import { router } from '@inertiajs/vue3';
import LazyCreatePlan from './CreatePlan.vue';
import PlanDetails from './PlanDetails.vue';

const props = defineProps({
  quote: Object,
  payments: Array,
  insuranceProviders: Object,
  ecomSavingsInsuranceQuoteUrl: String,
  websiteURL: String,
  lockLeadSectionsDetails: Object,
  lookUpData: Object,
  localLookups: Object,
});

const page = usePage();
const notification = useNotifications('toast');
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

// Initialize useSavingsPlans composable
const {
  // State
  modals,
  // Helpers
  toTitleCase,
  formatNumber,
  getPaymentTermLabel,
  calculateTotalAnnualPrice,
  // Plans Data
  availablePlansTable,
  onLoadAvailablePlansData,
  getPlanDetails,
  // Exchange Rates
  fetchExchangeRates,
  getExchangeRate,
  setExchangeRate,
  convertToAED,
  exchangeRates,
  planRates,
  isSelectedPlan,
} = useSavingsPlans({
  quote: props.quote,
  localLookups: computed(() => props.localLookups),
  lookUpData: computed(() => props.lookUpData),
  insuranceProviders: computed(() => props.insuranceProviders),
  notification,
});

// Alias for templates that use different names
const fmt = formatNumber;
const getRate = getExchangeRate;
const setRate = setExchangeRate;
const toAED = convertToAED;

const availablePlansTableColumns = reactive([
  {
    text: 'Provider Name',
    value: 'providerName',
    fixed: true,
    width: 250,
  },
  {
    text: 'Plan',
    value: 'name',
    fixed: true,
    width: 200,
  },
  {
    text: 'Type of Plan',
    value: 'planTypeName',
    fixed: true,
    width: 100,
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
    value: 'actualPremium',
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
    value: 'tenure',
  },
  {
    text: 'Expected Rate of Return',
    value: 'expectedRor',
  },
  {
    text: 'Lumpsum Amount',
    value: 'lumpSumPayout',
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
]);

const selectedPlans = ref([]);
const toggleLoader = ref(false);
const viewButtonLoading = ref(false);
const planDetails = ref(null);

// Toggle plans visibility (Show/Hide)
const onTogglePlans = async toggle => {
  toggleLoader.value = true;

  const planIds = [...new Set(selectedPlans.value.map(p => p.id))];

  axios
    .post(route('manualPlanToggle', { quoteType: 'savings' }), {
      modelType: 'Savings',
      planIds: planIds,
      quote_uuid: props.quote.uuid,
      toggle: toggle,
    })
    .then(async response => {
      notification.success({
        title: 'Plans have been updated',
        position: 'top',
      });
      await onLoadAvailablePlansData(props.quote.uuid);
      selectedPlans.value = [];
    })
    .catch(error => {
      notification.error({
        title: 'Error updating plans',
        position: 'top',
      });
    })
    .finally(() => {
      toggleLoader.value = false;
    });
};

const selectedProviderPlan = ref({
  id: props.quote?.plan_id,
  planName: props.quote?.plans?.name,
  providerName: props.quote?.plans?.providerName,
  premium: props.quote?.plans?.premium,
});

const updatedExchangeRates = ref(new Set());

const handlePlanSelected = async plan => {
  if (plan.insurerQuoteNo === '' || plan.insurerQuoteNo === null) {
    notification.error({
      title: 'Please select a plan with an insurer quote number',
      position: 'top',
    });
    return;
  } else {
    selectedProviderPlan.value.id = plan.id;
    selectedProviderPlan.value.planName = plan.planName;
    selectedProviderPlan.value.providerName = plan.providerName;
    selectedProviderPlan.value.premium = plan.premium;
  }

  try {
    await axios.post(
      route('savings-plan-update-exchange-rate', {
        quoteUID: props.quote.uuid,
        exchangeRate: null,
      }),
    );
    updatedExchangeRates.value.delete(plan.id);
    planRates.value = {};
    await onLoadAvailablePlansData(props.quote.uuid);
    router.reload({
      preserveScroll: true,
      only: ['payments', 'quoteRequest', 'quote', 'bookPolicyDetails'],
    });
  } catch (error) {
    // Silently fail - exchange rate reset is optional
  }
};

const isEditable = item =>
  item.currency?.toUpperCase() !== 'AED' &&
  String(selectedProviderPlan.value.id) === String(item.id);

const fetchPlanDetails = async id => {
  viewButtonLoading.value = true;
  try {
    planDetails.value = await getPlanDetails(id, props.quote.uuid);
    if (planDetails.value) {
      modals.planDetails = true;
    }
  } catch (err) {
    console.error(err);
  } finally {
    viewButtonLoading.value = false;
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

// Sukoon Purple Investment (instant policy) - same logic as MetLife for Life, without questionnaire
const quoteStatusEnum = page.props.quoteStatusEnum;

const isSukoonPurpleInvestment = item =>
  isSukoonPurpleInvestmentPlan(item, page.props.insuranceProviderCodeEnum);

const canSelectSukoonPurplePlan = computed(() => {
  const q = props.quote;
  if (!q) return false;
  return q.quote_status_id === quoteStatusEnum?.ApplicationPending;
});

// Open Savings Calculator
const openSavingsCalculator = () => {
  modals.savingsCalculator = true;
};

// OCA Email functionality
const isOcaButtonDisabled = ref(false);
const processingOCAEmail = ref(false);

const confirmSendOCAEmail = () => {
  if (selectedPlans.value.length === 0) {
    notification.error({
      title: 'Please select at least one plan',
      position: 'top',
    });
    return;
  }

  if (selectedPlans.value.length > 5) {
    notification.error({
      title: 'Maximum 5 plans can be selected',
      position: 'top',
    });
    return;
  }

  const first_name = props.quote.first_name || '';
  const last_name = props.quote.last_name || '';
  processingOCAEmail.value = true;

  const planIds = selectedPlans.value.map(p => p.id);

  axios
    .post(
      route('savingsSendOCAEmail', { quoteUuId: props.quote.uuid }),
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
        plan_ids: planIds,
      },
      {
        responseType: 'json',
      },
    )
    .then(response => {
      notification.success({
        title: 'OCA email sent successfully',
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
    })
    .finally(() => {
      modals.sendConfirm = false;
      processingOCAEmail.value = false;
    });
};

// Create Plan functionality
const onCreatePlan = async () => {
  modals.createPlan = false;
  await onLoadAvailablePlansData(props.quote.uuid);
  router.reload({
    preserveScroll: true,
    only: ['payments', 'quoteRequest', 'quote', 'bookPolicyDetails'],
  });
};

const onPlanError = errors => {
  console.error('Plan creation error:', errors);
};

const readOnlyMode = reactive({
  isDisable: true,
});

const updateExchangeRate = (item, exchangeRate = null) => {
  const currentRate = exchangeRate !== null ? exchangeRate : getRate(item);

  if (!currentRate || currentRate <= 0) {
    notification.error({
      title: 'Please enter a valid exchange rate',
      position: 'top',
    });
    return;
  }

  const isPlanSelected = isSelectedPlan(item);

  axios
    .post(
      route('savings-plan-update-exchange-rate', {
        quoteUID: props.quote.uuid,
        exchangeRate: currentRate,
      }),
    )
    .then(response => {
      notification.success({
        title: 'Exchange rate updated successfully',
        position: 'top',
      });

      // Mark this plan's exchange rate as updated
      updatedExchangeRates.value.add(item.id);

      router.reload({
        preserveScroll: true,
        only: ['payments', 'quoteRequest', 'quote', 'bookPolicyDetails'],
      });
    })
    .catch(error => {
      notification.error({
        title: 'Failed to update exchange rate',
        position: 'top',
      });
    });
};

onMounted(() => {
  onLoadAvailablePlansData(props.quote.uuid);
  fetchExchangeRates();
  readOnlyMode.isDisable = !can(permissionsEnum.All_QUOTES_VIEWONLY_ACCESS);

  if (props.quote?.plan_id) {
    const quoteExchangeRate =
      props.quote?.exchange_rate ||
      props.quote?.savings_quote?.exchange_rate ||
      props.quote?.savingsQuote?.exchange_rate;

    if (quoteExchangeRate !== null && quoteExchangeRate !== undefined) {
      updatedExchangeRates.value.add(props.quote.plan_id);
    }
  }
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
          class="flex flex-wrap gap-3 justify-between items-center mb-4"
          v-if="
            readOnlyMode.isDisable === true &&
            !availablePlansTable.isLoading &&
            availablePlansTable.data &&
            typeof availablePlansTable.data !== 'string'
          "
        >
          <!-- Savings Calculator Button -->
          <div>
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

          <!-- Right side buttons -->
          <div class="flex gap-3">
            <!-- Send OCA Email Button -->
            <x-tooltip
              v-if="
                availablePlansTable.data && availablePlansTable.data.length > 0
              "
              placement="top"
              align="left"
            >
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
              color="orange"
              @click.prevent="copyLink"
            >
              Copy Link
            </x-button>

            <!-- Add Plan Button -->
            <x-button
              @click.prevent="modals.createPlan = true"
              size="sm"
              color="emerald"
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
            :headers="availablePlansTableColumns"
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
            <template #header-lumpSumPayout>
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
            <template #item-actualPremium="item">
              <span>{{ item.totalPrice || item.actualPremium || 'N/A' }}</span>
            </template>
            <template #item-exchangeRate="item">
              <div
                v-if="isEditable(item)"
                class="flex items-center justify-center gap-2"
              >
                <x-input
                  :key="`exchange-rate-${item.id}`"
                  :model-value="getRate(item)"
                  @update:model-value="val => setRate(item.id, val)"
                  type="number"
                  step="0.0001"
                  min="0"
                  class="w-24 mt-1"
                  size="sm"
                  :disabled="updatedExchangeRates.has(item.id)"
                />
                <x-icon
                  v-if="updatedExchangeRates.has(item.id)"
                  icon="lock"
                  size="xl"
                  color="text-primary"
                  class="cursor-not-allowed opacity-50"
                />
                <x-icon
                  v-else
                  icon="unlock"
                  size="xl"
                  color="text-primary"
                  class="cursor-pointer hover:text-primary-600"
                  @click.prevent="updateExchangeRate(item, getRate(item))"
                />
              </div>
              <span v-else>{{ getRate(item) ?? 'N/A' }}</span>
            </template>
            <template #item-priceAed="item">
              <span>{{
                fmt(toAED(item.totalPrice || item.actualPremium || 0, item))
              }}</span>
            </template>
            <template #item-investmentFrequency="item">
              <span>{{ item.investmentFrequency || 'N/A' }}</span>
            </template>
            <template #item-paymentTerm="item">
              <span>{{ getPaymentTermLabel(item.paymentTerm) }}</span>
            </template>
            <template #item-tenure="item">
              <span>{{ item.tenure ? `${item.tenure} years` : 'N/A' }}</span>
            </template>
            <template #item-expectedRor="item">
              <span>{{
                item.expectedRor ? `${item.expectedRor}%` : 'N/A'
              }}</span>
            </template>
            <template #item-lumpSumPayout="item">
              <span>{{
                item.lumpSumPayout != null && item.lumpSumPayout !== ''
                  ? fmt(item.lumpSumPayout)
                  : 'N/A'
              }}</span>
            </template>
            <template #item-totalAnnualPrice="item">
              <span>{{
                calculateTotalAnnualPrice(item)
                  ? fmt(calculateTotalAnnualPrice(item))
                  : 'N/A'
              }}</span>
            </template>
            <template #item-totalAnnualPriceAed="item">
              <span>{{
                fmt(toAED(calculateTotalAnnualPrice(item) || 0, item))
              }}</span>
            </template>
            <template #item-action="item">
              <div class="flex gap-2">
                <x-button
                  size="xs"
                  color="primary"
                  outlined
                  @click.prevent="fetchPlanDetails(item.id)"
                  :loading="viewButtonLoading"
                >
                  View
                </x-button>
                <x-button
                  v-if="!isSukoonPurpleInvestment(item)"
                  size="xs"
                  color="emerald"
                  outlined
                  @click.prevent="copyPlanURL(item)"
                >
                  Copy
                </x-button>
                <span>
                  <x-tooltip
                    v-if="
                      isSukoonPurpleInvestment(item) &&
                      !canSelectSukoonPurplePlan
                    "
                    placement="top"
                  >
                    <x-button
                      size="xs"
                      color="emerald"
                      outlined
                      :disabled="true"
                    >
                      Select
                    </x-button>
                    <template #tooltip>
                      This plan cannot be manually selected. To proceed, you can
                      guide the client to click 'Buy Now'.
                    </template>
                  </x-tooltip>

                  <SelectPlan
                    v-else-if="
                      !selectedProviderPlan?.id ||
                      String(selectedProviderPlan.id) !== String(item.id)
                    "
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
                    button-size="xs"
                  />

                  <x-button
                    v-else
                    size="xs"
                    color="orange"
                    outlined
                    :disabled="true"
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
          :lookUpData="lookUpData"
          :localLookups="localLookups"
          :payments="payments"
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
            :lookUpData="lookUpData"
            :localLookups="localLookups"
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
