<script setup>
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  routeName: {
    type: String,
    required: true,
  },
  subSources: {
    type: Array,
    default: () => [],
  },
  isPcpAllowed: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:modelValue', 'confirmed']);

const { isRequired, isEmail, isMobileNo } = useRules();
const notification = useToast();
const page = usePage();

const authRoles = computed(() => page.props.auth?.roles ?? []);
const authPermissions = computed(() => page.props.auth?.permissions ?? []);

const isAnyManager = computed(() =>
  authRoles.value.some(role => role.toLowerCase().includes('manager')),
);
const canCollaborate = computed(() =>
  authPermissions.value.includes('ea-collaborate'),
);
const hasEAReferralAccess = computed(
  () =>
    authRoles.value.includes('EA_REFERRAL') ||
    authRoles.value.includes('EA_MANAGER') ||
    authRoles.value.includes('ADMIN') ||
    authRoles.value.includes('ENGINEERING') ||
    canCollaborate.value,
);

const hasLifeAdvisor = computed(() => authRoles.value.includes('LIFE_ADVISOR'));

const leadForm = useForm({
  type: '',
  sub_source_id: null,
  sub_source_options_id: null,
  // EA model fields
  ea_model: null,
  quote_type_id: null,
  first_name: '',
  last_name: '',
  email: '',
  mobile_no: '',
  business_type_of_insurance_id: null,
  health_plan_type_id: null,
});

const isModalOpen = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value),
});

const duplicateInfo = ref(null);
const isSubmittingEA = ref(false);

const quoteTypes = computed(() => page.props.quoteTypes ?? []);

const healthPlanTypeOptions = computed(() =>
  (page.props.healthPlanTypes ?? []).map(t => ({ value: t.id, label: t.text })),
);

const businessTypeOfInsuranceOptions = computed(() =>
  (page.props.businessTypeOfInsurances ?? []).map(t => ({
    value: t.id,
    label: t.text,
  })),
);

// Car (1), Travel (8), Health (3), and GroupMedical (102) are always excluded from collaborate (referral only).
// Life (4) requires a specific advisor role to use collaborate.
const collaborateExcludedLobs = computed(() => {
  const excluded = [1, 8, 3, 102];
  if (!hasLifeAdvisor.value) excluded.push(4);
  return excluded;
});

const allLobOptions = computed(() =>
  quoteTypes.value.map(qt => ({ value: qt.id, label: qt.name })),
);
const collaborateEligibleLobOptions = computed(() =>
  allLobOptions.value.filter(
    opt => !collaborateExcludedLobs.value.includes(Number(opt.value)),
  ),
);

const lobOptions = computed(() => {
  if (leadForm.ea_model !== 'collaborate') return allLobOptions.value;
  return collaborateEligibleLobOptions.value;
});
const hasCollaborateEligibleLob = computed(
  () => collaborateEligibleLobOptions.value.length > 0,
);

const isCorpline = computed(() => leadForm.quote_type_id == 101);
const isHealthLob = computed(() => leadForm.quote_type_id == 3);

const hasEAReferralRole = computed(
  () =>
    authRoles.value.includes('EA_REFERRAL') ||
    authRoles.value.includes('EA_MANAGER') ||
    authRoles.value.includes('ADMIN') ||
    authRoles.value.includes('ENGINEERING'),
);

const eaModelOptions = computed(() => {
  const options = [];

  if (isAnyManager.value || hasEAReferralRole.value) {
    options.push({ value: 'referral', label: 'Referral' });
  }

  if (canCollaborate.value && hasCollaborateEligibleLob.value) {
    options.push({ value: 'collaborate', label: 'Collaborate' });
  }

  return options;
});

const collaborateShowRouteMap = {
  2: 'home-quotes-show',
  4: 'life-quotes-show',
  5: 'business.show',
  6: 'bike-quotes-show',
  7: 'yacht-quotes-show',
  9: 'pet-quotes-show',
  10: 'cycle-quotes-show',
  11: 'jetski-quotes-show',
  18: 'savings-quotes-show',
  101: 'business.show',
};

const onConfirmCreateLead = async isValid => {
  if (!isValid) return;

  if (leadForm.type === 'expert_advisor_model') {
    await submitEALead();
    return;
  }

  const leadData = {
    type: leadForm.type,
    subSourceId: leadForm.sub_source_id,
    subSourceOptionsId: leadForm.sub_source_options_id,
  };

  if (leadForm.type === 'referral') {
    router.get(route(props.routeName), leadData);
  }

  emit('confirmed', leadData);
  isModalOpen.value = false;
  resetForm();
};

const submitEALead = async () => {
  isSubmittingEA.value = true;
  duplicateInfo.value = null;

  try {
    const response = await axios.post(route('ea-leads.store'), {
      ea_model: leadForm.ea_model,
      quote_type_id: leadForm.quote_type_id,
      first_name: leadForm.first_name,
      last_name: leadForm.last_name,
      email: leadForm.email,
      mobile_no: leadForm.mobile_no,
      business_type_of_insurance_id: leadForm.business_type_of_insurance_id,
      health_plan_type_id: leadForm.health_plan_type_id,
    });

    notification.success({
      title: response?.data?.message || 'EA lead created successfully.',
      position: 'top',
    });

    const isCollaborate = leadForm.ea_model === 'collaborate';
    const quoteTypeId = leadForm.quote_type_id;

    emit('confirmed', { type: 'expert_advisor_model' });
    isModalOpen.value = false;
    resetForm();

    if (isCollaborate) {
      const showRouteName = collaborateShowRouteMap[Number(quoteTypeId)];
      if (showRouteName && response?.data?.uuid) {
        router.visit(route(showRouteName, response.data.uuid));
      }
    }
  } catch (err) {
    const data = err?.response?.data;
    if (data?.duplicate) {
      duplicateInfo.value = data;
      notification.error({
        title: data?.message || 'Duplicate lead found.',
        position: 'top',
      });
      return;
    }

    notification.error({
      title: data?.message || 'Failed to create EA lead.',
      position: 'top',
    });
  } finally {
    isSubmittingEA.value = false;
  }
};

const onCancel = () => {
  isModalOpen.value = false;
  resetForm();
};

const resetForm = () => {
  leadForm.type = '';
  leadForm.sub_source_id = null;
  leadForm.sub_source_options_id = null;
  leadForm.ea_model = null;
  leadForm.quote_type_id = null;
  leadForm.first_name = '';
  leadForm.last_name = '';
  leadForm.email = '';
  leadForm.mobile_no = '';
  leadForm.business_type_of_insurance_id = null;
  leadForm.health_plan_type_id = null;
  duplicateInfo.value = null;
  if (typeof leadForm.reset === 'function') leadForm.reset();
};

const subSourceOptions = computed(() => {
  const options = props.subSources?.map(source => ({
    value: source.id,
    label: source.text,
    suffix: source.description || null,
  }));
  return options;
});

const subSourceChildOptions = computed(() => {
  if (!leadForm.sub_source_id) return [];

  const selectedSource = props.subSources.find(
    source => source.id == leadForm.sub_source_id,
  );
  if (!selectedSource || !selectedSource.childs) return [];

  const pcpOnlyOptions = ['pcp-cross-sell', 'pcp-customer-referral'];
  return selectedSource.childs.map(child => ({
    value: child.id,
    label: child.text,
    code: child.code,
    suffix: child.description || null,
    disabled:
      !props.isPcpAllowed && pcpOnlyOptions.includes(String(child.code)),
  }));
});

watch(isModalOpen, newValue => {
  if (!newValue) {
    resetForm();
  }
});

watch(
  () => leadForm.type,
  () => {
    leadForm.sub_source_id = null;
    leadForm.sub_source_options_id = null;
    leadForm.ea_model = null;
    leadForm.quote_type_id = null;
    duplicateInfo.value = null;
  },
);

watch(
  () => leadForm.sub_source_id,
  () => {
    leadForm.sub_source_options_id = null;
  },
);

watch(
  () => leadForm.ea_model,
  () => {
    // Preserve selected LOB when EA model changes, unless it is invalid for collaborate.
    if (
      leadForm.ea_model === 'collaborate' &&
      leadForm.quote_type_id &&
      collaborateExcludedLobs.value.includes(Number(leadForm.quote_type_id))
    ) {
      leadForm.quote_type_id = null;
    }
    leadForm.first_name = '';
    leadForm.last_name = '';
    leadForm.email = '';
    leadForm.mobile_no = '';
    leadForm.business_type_of_insurance_id = null;
    leadForm.health_plan_type_id = null;
    duplicateInfo.value = null;
  },
);

watch(
  () => leadForm.quote_type_id,
  () => {
    leadForm.business_type_of_insurance_id = null;
    leadForm.health_plan_type_id = null;
    duplicateInfo.value = null;
  },
);
</script>

<template>
  <x-modal
    v-model="isModalOpen"
    size="lg"
    title="Create Lead"
    show-close
    backdrop
  >
    <x-form
      id="createLeadForm"
      @submit="onConfirmCreateLead"
      :auto-focus="false"
    >
      <div class="w-full grid md:grid-cols-2 gap-5">
        <p class="text-md font-bold text-gray-500">
          Select reason to create manual lead <span class="error">*</span>
        </p>
      </div>

      <div class="flex w-full flex-col gap-5 mt-4 mb-4">
        <x-form-group v-model="leadForm.type">
          <x-radio value="referral" label="Referral" />
          <x-radio value="early_renewal" label="Early Renewal" />
          <x-radio value="payment_status" label="Payment Status" />
          <x-radio
            v-if="hasEAReferralAccess"
            value="expert_advisor_model"
            label="Expert Advisor Model"
          />
        </x-form-group>

        <!-- Conditional dropdowns for referral option -->
        <div v-if="leadForm.type === 'referral'" class="flex flex-col gap-4">
          <x-select
            v-model="leadForm.sub_source_id"
            label="IMCRM SUB-SOURCE"
            name="subSource"
            :options="subSourceOptions"
            placeholder="Please select IMCRM SUB-SOURCE"
            class="w-full"
            filterable
            :rules="[isRequired]"
            :required="true"
            tooltip="Manually created lead in IMCRM"
          >
            <template #suffix="{ item }">
              <x-tooltip v-if="item.suffix" placement="right">
                <x-icon icon="info" color="error" />
                <template #tooltip>
                  {{ item.suffix }}
                </template>
              </x-tooltip>
            </template>
          </x-select>

          <x-select
            v-if="leadForm.sub_source_id && subSourceChildOptions.length > 0"
            v-model="leadForm.sub_source_options_id"
            label="SUB SOURCE OPTIONS"
            name="subSourceOption"
            :options="subSourceChildOptions"
            placeholder="Please select sub source option"
            class="w-full"
            filterable
            :rules="subSourceChildOptions.length > 0 ? [isRequired] : []"
            :required="subSourceChildOptions.length > 0"
            tooltip="Type of referral lead"
          >
            <template #suffix="{ item }">
              <x-tooltip v-if="item.suffix" placement="right">
                <x-icon icon="info" color="error" />
                <template #tooltip>
                  {{ item.suffix }}
                </template>
              </x-tooltip>
            </template>
          </x-select>
        </div>

        <!-- Expert Advisor Model section -->
        <div
          v-if="leadForm.type === 'expert_advisor_model'"
          class="flex flex-col gap-4"
        >
          <!-- EA Model selector (Referral / Collaborate) -->
          <x-select
            v-model="leadForm.ea_model"
            label="EA MODEL"
            name="eaModel"
            :options="eaModelOptions"
            placeholder="Select EA Model"
            class="w-full"
            :rules="[isRequired]"
            :required="true"
          />

          <!-- LOB selector -->
          <x-select
            v-if="leadForm.ea_model"
            :key="`lob-${leadForm.ea_model}`"
            v-model="leadForm.quote_type_id"
            label="LINE OF BUSINESS"
            name="quoteTypeId"
            :options="lobOptions"
            placeholder="Select Line of Business"
            class="w-full"
            filterable
            :rules="[isRequired]"
            :required="true"
          />

          <!-- Generic lead fields -->
          <template v-if="leadForm.quote_type_id">
            <div class="grid grid-cols-2 gap-4">
              <x-input
                v-model="leadForm.first_name"
                label="FIRST NAME"
                name="firstName"
                :rules="[isRequired]"
                :required="true"
              />
              <x-input
                v-model="leadForm.last_name"
                label="LAST NAME"
                name="lastName"
                :rules="[isRequired]"
                :required="true"
              />
            </div>

            <x-input
              v-model="leadForm.email"
              label="EMAIL ADDRESS"
              name="email"
              type="email"
              :rules="[isRequired, isEmail]"
              :required="true"
            />

            <x-input
              v-model="leadForm.mobile_no"
              label="PHONE NUMBER"
              name="mobileNo"
              :rules="[isRequired, isMobileNo]"
              :required="true"
            />

            <!-- Corpline: Business Type of Insurance -->
            <x-select
              v-if="isCorpline"
              v-model="leadForm.business_type_of_insurance_id"
              label="BUSINESS TYPE OF INSURANCE"
              name="businessTypeOfInsuranceId"
              :options="businessTypeOfInsuranceOptions"
              placeholder="Select Business Type"
              class="w-full"
              :rules="[isRequired]"
              :required="true"
            />

            <!-- Health: Plan Type -->
            <x-select
              v-if="isHealthLob"
              v-model="leadForm.health_plan_type_id"
              label="PLAN TYPE"
              name="healthPlanTypeId"
              :options="healthPlanTypeOptions"
              placeholder="Select Plan Type"
              class="w-full"
              :rules="[isRequired]"
              :required="true"
            />
          </template>

          <!-- Duplicate warning popup -->
          <div
            v-if="duplicateInfo"
            class="mt-2 p-4 rounded-lg border border-yellow-400 bg-yellow-50 text-yellow-800"
          >
            <p class="font-semibold">Duplicate Lead Found</p>
            <p class="text-sm mt-1">{{ duplicateInfo.message }}</p>
            <p v-if="duplicateInfo.existing_advisor" class="text-sm mt-1">
              Existing Advisor:
              <strong>{{ duplicateInfo.existing_advisor }}</strong>
            </p>
          </div>
        </div>
      </div>
    </x-form>

    <template #actions>
      <x-button
        ghost
        tabindex="-1"
        size="md"
        type="button"
        @click.prevent="onCancel"
      >
        Cancel
      </x-button>
      <x-button
        size="md"
        color="emerald"
        type="submit"
        form="createLeadForm"
        :loading="isSubmittingEA"
      >
        Confirm
      </x-button>
    </template>
  </x-modal>
</template>
