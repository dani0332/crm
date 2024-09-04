<script setup>
import { defineEmits } from 'vue';
// Define the emit function
const emit = defineEmits(['plan-selected']);
import LazyCreatePlan from '@/inertia/Pages/BikeQuote/CreatePlan.vue';
import UpdateShowPlan from '@/inertia/Pages/BikeQuote/UpdateShowPlans.vue';

defineProps({
  carPlanTypeEnum: Object,
  carPlanExclusionsCodeEnum: Object,
  quote: Object,
  carPlanFeaturesCodeEnum: Object,
  carPlanAddonsCodeEnum: Object,
  planURL: String,
  insuranceProviders: Array,
  websiteURL: String,
});
const toggleLoader = ref(false);
const selectedPlans = ref([]);
const exportLoader = ref(false);
const selectedPlan = ref({});

const changeInsurerForm = useForm({
  uuid: '',
  plan_id: '',
  provider_code: '',
});

const onConfirmChangeInsurer = () => {
  changeInsurerForm.post('/bike/change-insurer', {
    preseveScroll: true,
    preserveState: true,
    onSuccess: () => {
      notification.success({
        title: 'Insurer Changed successfully',
        position: 'top',
      });
    },
    onError: err => {
      notification.error({
        title: 'Something went wrong',
        position: 'top',
      });
      conslo.log(err);
    },
    onFinish: () => {
      modals.changeInsurer = false;
    },
  });
};

const coreInsurer = ['AXA', 'OIC', 'TM', 'QIC', 'RSA'];
const halfLiveInsurer = [
  'SI',
  'OI',
  'Watania',
  'DNIRC',
  'NIA',
  'UI',
  'IHC',
  'NT',
];
const notification = useNotifications('toast');
const page = usePage();
const can = permission => useCan(permission);
const permissionEnum = page.props.permissionsEnum;
const { copy, copied } = useClipboard();

const modals = reactive({
  plan: false,
  changeInsurer: false,
  createPlan: false,
  sendConfirm: false,
});

const availablePlansItems = computed(() => {
  if (!Array.isArray(availablePlansTable.data)) {
    return [];
  }
  return typeof availablePlansTable.data !== 'string'
    ? availablePlansTable.data
    : [];
});

const totalPriceVAT = computed(() => {
  let vat = 0;

  if (availablePlansItems && availablePlansItems.value) {
    availablePlansItems.value.forEach(item => {
      if (item.addons) {
        item.addons.forEach(addon => {
          if (addon.carAddonOption) {
            addon.carAddonOption.forEach(option => {
              if (option.isSelected && option.price != 0) {
                vat += parseInt(option.price) + option.vat;
              }
            });
          }
        });
      }
    });
  }

  return vat;
});

const availablePlansTable = reactive({
  data: [],
  columns: [
    { text: 'Provider Name', value: 'providerName' },
    { text: 'Plan Name', value: 'name' },
    { text: 'Repair Type', value: 'repairType' },
    { text: 'Insurer Quote No.', value: 'insurerQuoteNo' },
    { text: 'TPL Limit', value: 'benefits' },
    { text: 'Bike Details', value: 'cubic_capacity' },
    { text: 'PAB cover', value: 'addons' },
    { text: 'Roadside assistance', value: 'roadSideAssistance' },
    { text: 'Oman cover TPL', value: 'omanCoverTPL' },
    { text: 'Actual Premium', value: 'actualPremium' },
    { text: 'Discounted Premium', value: 'discountPremium' },
    { text: 'Price with VAT.', value: 'premiumWithVat' },
    { text: 'Excess', value: 'excess' },
    { text: 'Action', value: 'action' },
  ],
});

const onLoadAvailablePlansData = async () => {
  let data = {
    jsonData: true,
  };
  let url = `/quotes/Bike/available-plans/${page.props.quote.uuid}`;
  axios
    .post(url, data)
    .then(res => {
      availablePlansTable.data = res.data;
    })
    .catch(err => {
      console.log(err);
    });
};

const onCreatePlan = async () => {
  modals.createPlan = false;
  onLoadAvailablePlansData();
};

onMounted(() => {
  onLoadAvailablePlansData();
});

const selectPlan = item => {
  selectedPlan.value = item;
  modals.plan = true;
};

function repairTypeCheck(repairType) {
  return repairType.repairType === page.props.carPlanTypeEnum.COMP
    ? coreInsurer.includes(repairType.providerCode)
      ? 'Premium workshop'
      : halfLiveInsurer.includes(repairType.providerCode)
        ? 'Non-Agency workshop'
        : 'NON-AGENCY'
    : repairType.repairType;
}

const getAddonVat = item => {
  let addonVat = 0;
  item.addons.forEach(addon => {
    (addon?.carAddonOption || []).forEach(option => {
      if (option?.isSelected && option?.price != 0) {
        addonVat += parseInt(option?.price) + option?.vat;
      }
    });
  });
  return addonVat;
};

const confirmChangeInsurer = plan => {
  modals.changeInsurer = true;
  changeInsurerForm.uuid = page.props.quote.uuid;
  changeInsurerForm.plan_id = plan.id;
  changeInsurerForm.provider_code = plan.providerCode;
};

const copyLink = () => {
  copy(page.props.planURL);
  if (copied)
    notification.success({
      title: 'Link copied to clipboardd',
      position: 'top',
    });
};

const copyPlanURL = item => {
  var paymentLink = `${page.props.websiteURL}/bike-insurance/quote/${page.props.quote.uuid}/payment/?providerCode=${item.providerCode}&planId=${item.id}`;
  copy(paymentLink);
  if (copied)
    notification.success({
      title: 'Link copied to clipboard',
      position: 'top',
    });
};

const onTogglePlans = toggle => {
  toggleLoader.value = true;

  const planIds = useArrayUnique(
    selectedPlans.value.map(p => {
      return p.id;
    }),
  ).value;

  axios
    .post(route('bikeManualPlanToggle', { quoteType: 'Bike' }), {
      modelType: 'Bike',
      planIds: planIds,
      bike_quote_uuid: usePage().props.quote.uuid,
      toggle: toggle,
    })
    .then(response => {
      notification.success({
        title: 'Plans has been updated',
        position: 'top',
      });
      router.reload({
        preserveScroll: true,
      });
    })
    .catch(error => {
      notification.error({
        title: error,
        position: 'top',
      });
    })
    .finally(() => {
      toggleLoader.value = false;
      selectedPlans.value = [];
      onLoadAvailablePlansData();
    });
};

const onExportPlans = () => {
  if (selectedPlans.value.length < 3 || selectedPlans.value.length > 5) {
    notification.error({
      title: 'Please select 3 to 5 plans to download PDF.',
      position: 'top',
    });
    return;
  }
  exportLoader.value = true;
  const planIds = selectedPlans.value.map(p => {
    return p.id;
  });
  axios
    .post(
      '/api/v1/quotes/bike/export-plans-pdf',
      {
        plan_ids: planIds,
        quote_uuid: page.props.quote.uuid,
      },
      {
        responseType: 'json',
      },
    )
    .then(response => {
      const link = document.createElement('a');
      let fileName = response.data.name;
      link.href = response.data.data;
      link.setAttribute('download', fileName);
      document.body.appendChild(link);
      link.click();
      notification.success({
        title: 'Plans Exported',
        position: 'top',
      });
    })
    .catch(error => {
      console.log(error);
    })
    .finally(() => {
      exportLoader.value = false;
    });
};

const confirmSendEmail = () => {
  const first_name = page.props.quote.first_name || '';
  const last_name = page.props.quote.last_name || '';
  axios
    .post(
      `/quotes/bike/${page.props.quote.uuid}/bike-send-email-one-click-buy`,
      {
        quote_type_id: page.props.quoteTypeId,
        quote_id: page.props.quote.id,
        quote_uuid: page.props.quote.uuid,
        quote_cdb_id: page.props.quote.code,
        quote_previous_expiry_date:
          page.props.quote.previous_policy_expiry_date,
        quote_currently_insured_with: page.props.quote.currently_insured_with,
        quote_car_make: page.props.carMakeText,
        quote_car_model: page.props.carModelText,
        quote_car_year_of_manufacture: page.props.quote.year_of_manufacture,
        quote_previous_policy_number:
          page.props.quote.previous_quote_policy_number,
        customer_name: `${first_name} ${last_name}`,
        customer_email: page.props.quote.email,
        advisor_name: page.props.quote.advisor
          ? page.props.quote.advisor.name
          : null,
        advisor_email: page.props.quote.advisor
          ? page.props.quote.advisor.email
          : null,
        advisor_mobile_no: page.props.quote.advisor
          ? page.props.quote.advisor.mobile_no
          : null,
        advisor_landline_no: page.props.quote.advisor
          ? page.props.quote.advisor.landline_no
          : null,
      },
      {
        responseType: 'json',
      },
    )

    .then(response => {
      notification.success({
        title: response.data.success,
        position: 'top',
      });
    })
    .catch(error => {
      console.log(error);
    })
    .finally(() => {
      modals.sendConfirm = false;
    });
};
const selectedProviderPlan = ref({
  id: page.props.quote?.car_plan?.id,
  planName: page.props.quote?.car_plan?.text,
  providerName: page.props.quote?.car_plan?.insurance_provider?.text,
  premium: page.props.quote?.premium,
});
const handlePlanSelected = plan => {
  selectedProviderPlan.value.id = plan.id;
  selectedProviderPlan.value.planName = plan.planName;
  selectedProviderPlan.value.providerName = plan.providerName;
  selectedProviderPlan.value.premium = plan.premium;
  router.reload({
    preserveState: true,
    preserveScroll: true,
    only: ['payments', 'paymentEntityModel'],
  });
  emit('plan-selected', plan);
  onLoadAvailablePlansData();
};

const readOnlyMode = reactive({
  isDisable: true,
});
onMounted(() => {
  readOnlyMode.isDisable = !can(permissionEnum.All_QUOTES_VIEWONLY_ACCESS);
});
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <div class="flex justify-between items-center mb-4">
      <h3 class="font-semibold text-primary-800 text-lg">
        Available Plans
        <x-tag size="sm">{{ availablePlansItems.length || 0 }}</x-tag>
      </h3>
      <div v-if="readOnlyMode.isDisable === true">
        <x-button-group v-if="selectedPlans.length > 0" size="sm">
          <x-button
            @click.prevent="onTogglePlans(false)"
            :loading="toggleLoader"
          >
            Show
          </x-button>
          <x-button
            @click.prevent="onTogglePlans(true)"
            :loading="toggleLoader"
          >
            Hide
          </x-button>
        </x-button-group>
        <x-button
          v-if="selectedPlans.length > 0"
          size="sm"
          color="emerald"
          class="ml-2 mr-2"
          @click.prevent="onExportPlans"
          :loading="exportLoader"
        >
          Download PDF
        </x-button>
        <x-button
          @click.prevent="modals.sendConfirm = true"
          size="sm"
          color="orange"
          class="mr-2"
          :disabled="quote.advisor_id != $page.props.auth.user.id"
        >
          Send OCB Email to Customer
        </x-button>

        <x-button
          @click.prevent="modals.createPlan = true"
          size="sm"
          color="orange"
          class="mr-2"
        >
          Add Plan
        </x-button>
        <x-button
          @click.prevent="copyLink"
          size="sm"
          color="emerald"
          v-if="
            typeof availablePlansTable.data !== 'string' &&
            availablePlansTable.data.length > 0
          "
        >
          Copy Link
        </x-button>
      </div>
    </div>
    <DataTable
      table-class-name="compact"
      v-model:items-selected="selectedPlans"
      :headers="availablePlansTable.columns"
      :items="availablePlansItems || []"
      border-cell
      hide-rows-per-page
      :rows-per-page="15"
      :hide-footer="availablePlansItems.length < 15"
    >
      <template
        #item-providerName="{
          providerName,
          isManualUpdate,
          isRenewal,
          isDisabled,
        }"
      >
        <p>{{ providerName }}</p>
        <div class="flex gap-1">
          <x-tag
            v-if="isManualUpdate"
            size="xs"
            color="primary"
            class="mt-0.5 text-[10px]"
          >
            Manual
          </x-tag>
          <x-tag
            v-if="isRenewal"
            size="xs"
            color="success"
            class="mt-0.5 text-[10px]"
          >
            Renewal
          </x-tag>
          <x-tag
            v-if="isDisabled"
            size="xs"
            color="error"
            class="mt-0.5 text-[10px]"
          >
            Hidden
          </x-tag>
        </div>
      </template>
      <template #item-name="item">
        <span
          class="text-primary-600 cursor-pointer"
          @click.prevent="selectPlan(item)"
          >{{ item.name }}</span
        >
      </template>
      <template #item-repairType="repairType">
        <span>{{ repairTypeCheck(repairType) }}</span>
      </template>
      <template #item-benefits="{ benefits }">
        <!-- <span>{{ benefits.feature }}</span> -->
        <template v-for="feature in benefits.feature" :key="feature">
          <template v-if="feature.code">
            <span
              v-if="
                feature.code === carPlanFeaturesCodeEnum.TPL_DAMAGE_LIMIT ||
                feature.code === carPlanFeaturesCodeEnum.DAMAGE_LIMIT
              "
            >
              {{ feature.value }}
            </span>
          </template>
          <span
            v-else-if="
              feature.text === carPlanFeaturesCodeEnum.TPL_DAMAGE_LIMIT_TEXT
            "
          >
            {{ feature.value }}
          </span>
        </template>
      </template>
      <template #item-cubic_capacity="cubic_capacity">
        <span>{{ quote?.bike_quote?.cubic_capacity }}</span>
      </template>
      <template #item-addons="{ addons }">
        <template v-for="addon in addons" :key="addon">
          <template v-for="option in addon.carAddonOption" :key="option">
            <span v-if="addon.code">
              <template
                v-if="
                  addon.code.toLowerCase() ===
                    carPlanAddonsCodeEnum.DRIVER_COVER.toLowerCase() ||
                  addon.code.toLowerCase() ===
                    carPlanAddonsCodeEnum.PASSENGER_COVER.toLowerCase()
                "
              >
                {{ addon.text }}: {{ option.value }} <br />
              </template>
            </span>
            <template
              v-else-if="
                addon.text.toLowerCase() ===
                  carPlanAddonsCodeEnum.DRIVER_COVER_TEXT.toLowerCase() ||
                addon.text.toLowerCase() ===
                  carPlanAddonsCodeEnum.PASSENGER_COVER_TEXT.toLowerCase()
              "
            >
              {{ addon.text }}: {{ option.value }} <br />
            </template>
          </template>
        </template>
      </template>
      <template #item-omanCoverTPL="{ benefits }">
        <template v-for="planExc in benefits.exclusion" :key="planExc">
          <span
            v-if="
              planExc.code &&
              (planExc.code.toLowerCase() ===
                carPlanExclusionsCodeEnum.TPL_OMAN_COVER.toLowerCase() ||
                planExc.code.toLowerCase() ===
                  carPlanExclusionsCodeEnum.OMAN_COVER.toLowerCase())
            "
          >
            {{ planExc.text }}: {{ planExc.value }}
          </span>
        </template>
        <template v-for="planInc in benefits.inclusion" :key="planInc">
          <span
            v-if="
              planInc.code &&
              (planInc.code.toLowerCase() ===
                carPlanExclusionsCodeEnum.TPL_OMAN_COVER.toLowerCase() ||
                planInc.code.toLowerCase() ===
                  carPlanExclusionsCodeEnum.OMAN_COVER.toLowerCase())
            "
          >
            {{ planInc.text }}: {{ planInc.value }}
          </span>
        </template>
      </template>
      <template #item-roadSideAssistance="{ benefits }">
        <template
          v-for="planAss in benefits.roadSideAssistance"
          :key="planAss.text"
        >
          {{ planAss.text }}: {{ planAss.value }} <br />
        </template>
      </template>
      <template #item-actualPremium="{ actualPremium }">
        {{ actualPremium ? parseFloat(actualPremium).toFixed(2) : '0.00' }}
      </template>
      <template #item-discountPremium="{ discountPremium }">
        {{ discountPremium ? parseFloat(discountPremium).toFixed(2) : '0.00' }}
      </template>
      <template #item-premiumWithVat="item">
        {{
          parseFloat(
            item.discountPremium + item.vat + getAddonVat(item),
          ).toFixed(2)
        }}
      </template>
      <template #item-action="item">
        <div class="flex gap-2">
          <x-button
            size="xs"
            color="primary"
            outlined
            @click.prevent="selectPlan(item)"
          >
            View
          </x-button>
          <x-button
            size="xs"
            color="error"
            outlined
            @click.prevent="copyPlanURL(item)"
            v-if="item.discountPremium + item.vat + totalPriceVAT > 0"
          >
            Copy
          </x-button>
          <!-- <template v-if="item.actualPremium > 0 && item.id != quote.plan_id">
            <x-button
              size="xs"
              color="error"
              outlined
              @click="confirmChangeInsurer(item)"
            >
              Change Insurer
            </x-button>
          </template> -->
          <span>
            <SelectPlan
              v-if="selectedProviderPlan.id != item.id"
              @update:selectedPlanChanged="handlePlanSelected"
              :plan="item"
              :quoteType="'Bike'"
              :uuid="quote.uuid"
            />

            <x-button v-else size="xs" color="orange" outlined :disabled="true">
              Selected
            </x-button>
          </span>
        </div>
      </template>
    </DataTable>

    <x-modal v-model="modals.sendConfirm" show-close backdrop>
      <template #header> Send Email </template>
      <p>Are you sure send email to customer?</p>
      <template #actions>
        <div class="text-right space-x-4">
          <x-button size="sm" ghost @click.prevent="modals.sendConfirm = false">
            Cancel
          </x-button>
          <x-button size="sm" color="error" @click.prevent="confirmSendEmail">
            Send
          </x-button>
        </div>
      </template>
    </x-modal>
    <x-modal v-model="modals.createPlan" size="xl" show-close backdrop>
      <template #header> Create Bike Quote </template>
      <LazyCreatePlan
        :quote="quote"
        :insuranceProviders="insuranceProviders"
        :available-plans="availablePlansItems"
        @success="onCreatePlan"
        @error="onPlanError"
      />
    </x-modal>

    <x-modal v-model="modals.plan" size="xl" show-close backdrop>
      <template #header>
        {{ selectedPlan.providerName }} - {{ selectedPlan.name }}
      </template>
      <UpdateShowPlan
        :plan="selectedPlan"
        :quote="quote"
        @onLoadAvailablePlansData="onLoadAvailablePlansData"
      />
    </x-modal>

    <x-modal v-model="modals.changeInsurer" show-close backdrop>
      <template #header> Change Insurer </template>
      <p>Are you sure to change insurer?</p>
      <template #actions>
        <div class="text-right space-x-4">
          <x-button
            size="sm"
            ghost
            @click.prevent="modals.changeInsurer = false"
          >
            Cancel
          </x-button>
          <x-button
            size="sm"
            color="error"
            :loading="changeInsurerForm.processing"
            @click.prevent="onConfirmChangeInsurer"
          >
            Yes
          </x-button>
        </div>
      </template>
    </x-modal>
  </div>
</template>
