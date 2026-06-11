<script setup>
const page = usePage();
const props = defineProps({
  businessInsuranceType: Object,
  quote: Object,
  gmTypes: Object,
  selectedGmType: Object,
  subSources: { type: Array, default: () => [] },
  emirates: { type: Array, default: () => [] },
  isEmirateDisabled: { type: Boolean, default: false },
  leadSourceParams: { type: Object, default: () => ({}) },
  companyActivityTypes: { type: Array, default: () => [] },
  healthPlanTypes: { type: Array, default: () => [] },
  insuranceProviders: { type: Array, default: () => [] },
  healthThirdPartyAdministrators: { type: Array, default: () => [] },
  groupMedicalNetworks: { type: Array, default: () => [] },
  groupMedicalCategories: { type: Array, default: () => [] },
  categoryCount: { type: Number }
});

const notification = useToast();
const isEdit = computed(() => (props.quote.uuid ? true : false));
const genderSelect = computed(() => {
  return Object.keys(props.genderOptions).map(status => ({
    value: status,
    label: props.genderOptions[status],
  }));
});

const formFields = computed(() => {
  return Object.keys(props.fields).map(field => ({
    value: field,
    label: props.fields[field].label,
  }));
});

const maxValidation = maxValue => {
  return value => {
    const isValid = value <= maxValue;
    return isValid || `Value must be less than or equal to ${maxValue}.`;
  };
};

const yesNoOptions = [
  { value: 1, label: 'Yes' },
  { value: 0, label: 'No' },
];

const emptyCategoryRow = () => ({
  groupMedicalCategoryId: null,
  insuranceProviderId: null,
  healthTpaId: null,
  groupMedicalNetworkId: null,
  renewalDate: null,
  numberOfPeople: null,
});

const GM_CATEGORY_ROW_FALLBACK_MAX = 26;

function getGmCategoryRowMax() {
  const count = (props.groupMedicalCategories || []).length;
  return count > 0 ? count : GM_CATEGORY_ROW_FALLBACK_MAX;
}

/**
 * Builds initial intake rows: prefers saved JSON; otherwise one empty row (appender).
 */
function buildInitialCategoryRows(quote) {
  const rowMax = getGmCategoryRowMax();
  const intake = quote?.categories;
  if (Array.isArray(intake) && intake.length > 0) {
    return intake.slice(0, rowMax).map(row => ({ ...emptyCategoryRow(), ...row }));
  }
  const savedN = parseInt(quote?.number_of_categories, 10);
  const n = Math.min(rowMax, Math.max(1, savedN || 1));

  return Array.from({ length: n }, () => emptyCategoryRow());
}

const initialGmRows = buildInitialCategoryRows(props.quote);

const gmCategoryRowMax = computed(() => getGmCategoryRowMax());

const toSelectOptions = items =>
  (items || []).map(item => ({
    value: item.id,
    label: item.text,
  }));

const companyActivitySelectOptions = computed(() =>
  toSelectOptions(props.companyActivityTypes),
);

function resolvePlanEmirateId(plan) {
  return plan?.emirates_id ?? plan?.emirate_id ?? null;
}

function normalizeEmirateId(emirateId) {
  if (emirateId === null || emirateId === undefined || emirateId === '') {
    return null;
  }

  const parsed = Number(emirateId);
  return Number.isNaN(parsed) ? null : parsed;
}

function healthPlansForEmirate(emirateId) {
  const targetEmirateId = normalizeEmirateId(emirateId);
  if (targetEmirateId === null) {
    return [];
  }

  return (props.healthPlanTypes || []).filter(plan => {
    const planEmirateId = normalizeEmirateId(resolvePlanEmirateId(plan));
    return planEmirateId !== null && planEmirateId === targetEmirateId;
  });
}

const insuranceProviderSelectOptions = computed(() =>
  toSelectOptions(props.insuranceProviders),
);

const healthTpaSelectOptions = computed(() =>
  toSelectOptions(props.healthThirdPartyAdministrators),
);

const networksCache = ref({});
const networksFetching = ref({});

async function fetchNetworksForTpa(tpaId) {
  if (!tpaId || networksCache.value[tpaId] !== undefined) return;
  networksFetching.value[tpaId] = true;
  try {
    const { data } = await axios.get(route('amt.networks'), { params: { tpa_id: tpaId } });
    networksCache.value[tpaId] = data.map(n => ({ value: n.id, label: n.text }));
  } catch {
    networksCache.value[tpaId] = [];
  } finally {
    networksFetching.value[tpaId] = false;
  }
}

function networkOptionsForRow(idx) {
  const tpaId = quoteForm.categories[idx]?.healthTpaId;
  if (!tpaId) return [];
  return networksCache.value[tpaId] ?? [];
}

function isNetworkLoadingForRow(idx) {
  const tpaId = quoteForm.categories[idx]?.healthTpaId;
  return !!tpaId && networksFetching.value[tpaId];
}

const groupMedicalCategorySelectOptions = computed(() =>
  toSelectOptions(props.groupMedicalCategories),
);

const quoteForm = useForm({
  modelType: '"Business"',
  first_name: props.quote.first_name,
  last_name: props.quote.last_name,
  email: props.quote.email,
  mobile_no: props.quote.mobile_no,
  source: props.quote.source,
  premium: props.quote.premium,
  company_name: props.quote.company_name,
  number_of_employees: props.quote.number_of_employees,
  business_type_of_insurance_id: props.quote.business_type_of_insurance_id,
  group_medical_type_id: props.selectedGmType ?? '',
  emirate_of_registration_id: props.quote?.emirate_of_registration_id ?? null,
  brief_details: props.quote.brief_details,
  // Additional notes
  additional_notes: props.quote?.additional_notes || '',
  sub_source_id:
    parseInt(
      props.quote?.sub_source_id || props.leadSourceParams?.subSource || 0,
    ) || null,
  sub_source_options_id:
    parseInt(
      props.quote?.sub_source_options_id ||
        props.leadSourceParams?.subSourceOption ||
        0,
    ) || null,
  nature_of_company_activity_id:
    props.quote?.nature_of_company_activity_id ?? null,
  has_existing_group_health_insurance:
    props.quote?.has_existing_group_health_insurance === undefined ||
    props.quote?.has_existing_group_health_insurance === null
      ? null
      : props.quote.has_existing_group_health_insurance
        ? 1
        : 0,
  health_plan_type_id: props.quote?.health_plan_type_id ?? null,
  number_of_categories: props.categoryCount, //initialGmRows.length,
  categories: initialGmRows,
});

const selectedEmirateId = computed(() =>
  normalizeEmirateId(quoteForm.emirate_of_registration_id),
);

const healthPlanTypeSelectOptions = computed(() =>
  toSelectOptions(healthPlansForEmirate(selectedEmirateId.value)),
);

const isHealthPlanTypeSelectDisabled = computed(
  () => selectedEmirateId.value === null,
);

const totalPeopleToBeInsured = computed(() =>
  quoteForm.categories.reduce((sum, row) => {
    const n = parseInt(row.numberOfPeople) || 0;
    return sum + n;
  }, 0),
);

watch(totalPeopleToBeInsured, val => {
  quoteForm.number_of_employees = val > 0 ? val : null;
}, { immediate: true });

/**
 * Group medical category options for one row; disables categories already picked elsewhere.
 */
function memberCategoryOptionsForRow(rowIndex) {
  const selectedElsewhere = new Set(
    quoteForm.categories
      .map((row, idx) =>
        idx !== rowIndex && row.groupMedicalCategoryId != null
          ? Number(row.groupMedicalCategoryId)
          : null,
      )
      .filter(id => id !== null),
  );

  return groupMedicalCategorySelectOptions.value.map(option => ({
    ...option,
    disabled: selectedElsewhere.has(Number(option.value)),
  }));
}

const duplicateCategoryMessage = computed(() => {
  const ids = quoteForm.categories
    .map(row => row.groupMedicalCategoryId)
    .filter(id => id != null && id !== '');
  return ids.length !== new Set(ids.map(id => Number(id))).size
    ? 'Each category can only be selected once.'
    : null;
});

function syncNumberOfCategoriesFromIntake() {
  quoteForm.number_of_categories = quoteForm.categories.length;
}

watch(
  () => quoteForm.number_of_categories,
  count => {
    const n = parseInt(count) || 0;
    if (n <= 0) return;
    const current = quoteForm.categories.length;
    if (n > current) {
      for (let i = current; i < n; i++) {
        quoteForm.categories.push(emptyCategoryRow());
      }
    } else if (n < current) {
      quoteForm.categories.splice(n);
    }
  },
  { immediate: true },
);

function addCategoryRow() {
  if (quoteForm.categories.length >= gmCategoryRowMax.value) {
    notification.warning({
      title: `You can add at most ${gmCategoryRowMax.value} category rows.`,
      position: 'top',
    });
    return;
  }
  quoteForm.categories.push(emptyCategoryRow());
  syncNumberOfCategoriesFromIntake();
}

function removeCategoryRow(idx) {
  if (quoteForm.categories.length <= 1) {
    return;
  }
  quoteForm.categories.splice(idx, 1);
  syncNumberOfCategoriesFromIntake();
}

const {
  isRequired,
  emptyOrDecimal,
  isNumber,
  isEmail,
  isMobileNo,
  maxCharacters,
} = useRules();

// Sub-source options and flags like Life
const subSourceOptions = computed(() => {
  return (props.subSources || []).map(item => ({
    value: item.id,
    label: item.text,
    suffix: item.description || null,
  }));
});

const subSourceOptionOptions = computed(() => {
  if (!quoteForm.sub_source_id) return [];
  const selectedSubSource = props.subSources?.find(
    source => source.id == quoteForm.sub_source_id,
  );
  const pcpOnlyOptions = ['pcp-cross-sell', 'pcp-customer-referral'];
  return (
    selectedSubSource?.childs?.map(option => ({
      value: option.id,
      label: option.text,
      code: option.code,
      suffix: option.description || null,
      disabled:
        !isPcpSubSourceOptionAllowed.value &&
        pcpOnlyOptions.includes(String(option.code)),
    })) || []
  );
});

const isReferralType = computed(() => {
  return (
    props.leadSourceParams?.type === 'referral' || quoteForm.source === 'IMCRM'
  );
});

const rolesEnum = page.props.rolesEnum;
const teamNamesEnum = page.props.teamNamesEnum;
const isPcpSubSourceOptionAllowed = ref(
  useHasRole(rolesEnum.Admin) || useHasAnyTeam([{ name: teamNamesEnum.PCP }]),
);
const canEditSubSourceFields = computed(() => {
  return useHasAnyRole([
    rolesEnum.GMManager,
    rolesEnum.Admin,
    rolesEnum.LeadPool,
  ]);
});

watch(
  () => quoteForm.sub_source_id,
  (newValue, oldValue) => {
    if (newValue !== oldValue) {
      quoteForm.sub_source_options_id = null;
    }
  },
);

watch(selectedEmirateId, (newEmirate, oldEmirate) => {
  if (newEmirate === oldEmirate) {
    return;
  }

  const validPlanIds = healthPlansForEmirate(newEmirate).map(plan =>
    Number(plan.id),
  );
  if (
    quoteForm.health_plan_type_id != null &&
    !validPlanIds.includes(Number(quoteForm.health_plan_type_id))
  ) {
    quoteForm.health_plan_type_id = null;
  }
});

watch(
  () => quoteForm.categories.map(row => row.healthTpaId),
  (newTpaIds, oldTpaIds) => {
    newTpaIds.forEach((tpaId, idx) => {
      const oldTpaId = oldTpaIds?.[idx];
      if (tpaId !== oldTpaId) {
        quoteForm.categories[idx].groupMedicalNetworkId = null;
      }
      if (tpaId) {
        fetchNetworksForTpa(tpaId);
      }
    });
  },
);

onMounted(() => {
  const uniqueTpaIds = [
    ...new Set(
      quoteForm.categories
        .map(row => row.healthTpaId)
        .filter(id => !!id),
    ),
  ];
  uniqueTpaIds.forEach(fetchNetworksForTpa);
});

const emirateOfRegistrationFieldError = computed(() => {
  if (quoteForm.errors.emirate_of_registration_id) {
    return quoteForm.errors.emirate_of_registration_id;
  }
  if (!isEdit.value) {
    return null;
  }
  const value = quoteForm.emirate_of_registration_id;
  const isEmpty =
    value === null ||
    value === undefined ||
    value === '' ||
    value === false ||
    value === 0;
  if (isEmpty) {
    return 'Please update Emirate of registration in Entity Profile';
  }
  return null;
});

const isEmptyField = ref(false);

function onSubmit(isValid) {
  if (!isValid) return;
  if (duplicateCategoryMessage.value) {
    quoteForm.setError('categories', duplicateCategoryMessage.value);
    return;
  }
  syncNumberOfCategoriesFromIntake();
  const method = isEdit.value ? 'put' : 'post';
  const url = isEdit.value
    ? route('amt.update', props.quote.uuid)
    : route('amt.store');

  const options = {
    onError: errors => {
      quoteForm.setError(errors);
    },
    onStart: () => {
      quoteForm.clearErrors();
    },
  };
  quoteForm.submit(method, url, options);
}
</script>

<template>
  <div>
    <Head :title="isEdit ? 'Edit Group Medical' : 'Create Group Medical'" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        {{ isEdit ? 'Update' : 'Create' }} Group Medical Lead
      </h2>
      <div class="space-x-4">
        <Link v-if="isEdit" :href="route('amt.show', props.quote.uuid)">
          <x-button size="sm" tag="div"> View </x-button>
        </Link>
        <Link :href="route('amt.index')">
          <x-button size="sm" color="#ff5e00" tag="div"> Quotes List </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 gap-4">
        <!-- Sub Source Fields -->
        <x-select
          v-if="isReferralType"
          label="IMCRM SUB-SOURCE"
          v-model="quoteForm.sub_source_id"
          :options="subSourceOptions"
          class="w-full"
          placeholder="Select IMCRM SUB-SOURCE"
          filterable
          :disabled="!canEditSubSourceFields"
          :rules="[isRequired]"
          required
          :error="quoteForm.errors.sub_source_id"
          tooltip="Manually created lead in IMCRM"
        >
          <template #suffix="{ item }">
            <x-tooltip v-if="item.suffix" placement="right">
              <x-icon icon="info" color="error" />
              <template #tooltip>{{ item.suffix }}</template>
            </x-tooltip>
          </template>
        </x-select>

        <x-select
          v-if="isReferralType && quoteForm.sub_source_id"
          label="SUB SOURCE OPTIONS"
          v-model="quoteForm.sub_source_options_id"
          :options="subSourceOptionOptions"
          class="w-full"
          placeholder="Select Sub Source Option"
          filterable
          :disabled="!canEditSubSourceFields"
          :rules="subSourceOptionOptions.length > 0 ? [isRequired] : []"
          :required="subSourceOptionOptions.length > 0"
          :error="quoteForm.errors.sub_source_options_id"
          tooltip="Type of referral lead"
        >
          <template #suffix="{ item }">
            <x-tooltip v-if="item.suffix" placement="right">
              <x-icon icon="info" color="error" />
              <template #tooltip>{{ item.suffix }}</template>
            </x-tooltip>
          </template>
        </x-select>

        <x-input
          v-model="quoteForm.first_name"
          type="text"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.first_name"
          maxLength="20"
          label="FIRST NAME"
          required
        />

        <x-input
          v-model="quoteForm.last_name"
          type="text"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.last_name"
          maxLength="20"
          label="LAST NAME"
          required
        />

        <x-input
          v-model="quoteForm.email"
          type="email"
          :rules="[isRequired, isEmail]"
          class="w-full"
          :disabled="isEdit"
          :error="quoteForm.errors.email"
          label="EMAIL"
          required
        />

        <x-input
          v-model="quoteForm.mobile_no"
          type="tel"
          :rules="[isRequired, isMobileNo]"
          class="w-full"
          :disabled="isEdit"
          :error="quoteForm.errors.mobile_no"
          label="MOBILE NUMBER"
          required
        />

        <x-input
          v-model="quoteForm.company_name"
          type="text"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.company_name"
          label="COMPANY NAME"
          required
        />

        <x-input
          :model-value="totalPeopleToBeInsured"
          type="number"
          class="w-full"
          :error="quoteForm.errors.number_of_employees"
          label="NUMBER OF PEOPLE TO BE INSURED"
          disabled
        />

        <x-select
          v-model="quoteForm.business_type_of_insurance_id"
          :options="
            businessInsuranceType.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.business_type_of_insurance_id"
          label="Business Insurance Type"
          required
        />

        <x-select
          v-model="quoteForm.emirate_of_registration_id"
          :options="
            (props.emirates || []).map(item => ({
              value: Number(item.id),
              label: item.text,
            }))
          "
          :rules="props.isEmirateDisabled ? [] : [isRequired]"
          class="w-full"
          :error="emirateOfRegistrationFieldError"
          label="EMIRATE OF REGISTRATION"
          :required="!props.isEmirateDisabled"
          :disabled="props.isEmirateDisabled"
          tooltip="Select the Emirate where the company is legally registered or primarily operates."
        >
        </x-select>

        <x-textarea
          v-model="quoteForm.brief_details"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.brief_details"
          label="BRIEF DETAILS"
          required
        />

        <x-input
          v-model="quoteForm.premium"
          type="number"
          class="w-full"
          :rules="[emptyOrDecimal]"
          :error="quoteForm.errors.premium"
          label="PRICE"
        />

        <x-select
          v-model="quoteForm.nature_of_company_activity_id"
          :options="companyActivitySelectOptions"
          class="w-full"
          label="NATURE OF COMPANY'S ACTIVITY"
          placeholder="Select activity"
          filterable
          :rules="[isRequired]"
          required
          :error="quoteForm.errors.nature_of_company_activity_id"
        />

        <x-select
          v-model="quoteForm.has_existing_group_health_insurance"
          :options="yesNoOptions"
          class="w-full"
          label="WITH EXISTING GROUP HEALTH INSURANCE POLICY"
          placeholder="Select"
          :rules="[v => v !== null && v !== undefined && v !== '' || 'This field is required']"
          required
          :error="quoteForm.errors.has_existing_group_health_insurance"
        />

        <x-select
          :key="`health-plan-type-${selectedEmirateId ?? 'none'}`"
          v-model="quoteForm.health_plan_type_id"
          :options="healthPlanTypeSelectOptions"
          class="w-full"
          label="PLAN TYPE"
          :placeholder="
            isHealthPlanTypeSelectDisabled
              ? 'Select emirate of registration first'
              : 'Select plan type'
          "
          filterable
          :disabled="isHealthPlanTypeSelectDisabled"
          :rules="isHealthPlanTypeSelectDisabled ? [] : [isRequired]"
          :required="!isHealthPlanTypeSelectDisabled"
          :error="quoteForm.errors.health_plan_type_id"
          tooltip="Plan types are filtered by the selected emirate of registration."
      />

      <x-select
          v-model="quoteForm.number_of_categories"
          :options="[1, 2, 3, 4, 5].map(n => ({ value: n, label: String(n) }))"
          class="w-full"
          :error="quoteForm.errors.number_of_categories"
          label="Number of categories"
        />

        <div
          class="sm:col-span-2 rounded-xl border border-gray-200 bg-gradient-to-b from-slate-50/90 to-white p-4 shadow-sm ring-1 ring-gray-100"
        >
          <div
            class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
          >
            <div class="min-w-0 flex-1">
              <h3 class="text-base font-semibold text-gray-900">
                People to be insured per category
              </h3>
              <p
                id="gm-category-intake-help"
                class="mt-1 max-w-2xl text-xs leading-relaxed text-gray-500"
              >
                Add one row per insured band. Pick the
                <span class="font-medium text-gray-700">member category</span>,
                optional existing insurer / TPA / network, renewal date, and
                headcount. Use
                <span class="font-medium text-gray-700">Add row</span>
                to append lines (max {{ gmCategoryRowMax }}).
              </p>
            </div>
            <div
              class="flex shrink-0 flex-col items-stretch gap-2 sm:items-end"
            >
              <span
                class="inline-flex items-center justify-center rounded-full border border-primary-200 bg-primary-50 px-3 py-1 text-xs font-medium text-primary-800"
              >
                {{ quoteForm.categories.length }}
                {{
                  quoteForm.categories.length === 1
                    ? 'category row'
                    : 'category rows'
                }}
              </span>
            </div>
          </div>

          <div
            class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-inner ring-1 ring-gray-100"
          >
            <table
              class="min-w-[1040px] w-full border-collapse text-sm"
              aria-describedby="gm-category-intake-help"
            >
              <thead class="sticky top-0 z-10 shadow-sm">
                <tr
                  class="bg-primary-600 text-left text-xs font-semibold uppercase tracking-wide text-white"
                >
                  <th
                    scope="col"
                    class="w-14 whitespace-nowrap border-r border-primary-500/40 px-3 py-3.5 text-center"
                  >
                    S/NO
                  </th>
                  <th
                    scope="col"
                    class="min-w-[12rem] whitespace-nowrap border-r border-primary-500/40 px-3 py-3.5"
                  >
                    CATEGORY
                  </th>
                  <th
                    scope="col"
                    class="min-w-[11.5rem] whitespace-nowrap border-r border-primary-500/40 px-3 py-3.5"
                  >
                    EXISTING INSURANCE PROVIDER
                  </th>
                  <th
                    scope="col"
                    class="min-w-[11.5rem] whitespace-nowrap border-r border-primary-500/40 px-3 py-3.5"
                  >
                    EXISTING THIRD PARTY ADMINISTRATOR
                  </th>
                  <th
                    scope="col"
                    class="min-w-[11.5rem] whitespace-nowrap border-r border-primary-500/40 px-3 py-3.5"
                  >
                    EXISTING NETWORK
                  </th>
                  <th
                    scope="col"
                    class="min-w-[10.5rem] whitespace-nowrap border-r border-primary-500/40 px-3 py-3.5"
                  >
                    EXISTING POLICY RENEWAL DATE
                  </th>
                  <th
                    scope="col"
                    class="min-w-[8.5rem] whitespace-nowrap border-r border-primary-500/40 px-3 py-3.5"
                  >
                    NUMBER OF PEOPLE
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 bg-white text-gray-900">
                <tr
                  v-for="(row, idx) in quoteForm.categories"
                  :key="`gm-cat-${idx}-${row.groupMedicalCategoryId ?? 'row'}`"
                  class="align-top transition-colors even:bg-slate-50/70 hover:bg-primary-50/40"
                >
                  <td
                    class="border-r border-gray-100 px-3 py-3 text-center tabular-nums text-gray-500"
                  >
                    {{ idx + 1 }}
                  </td>
                  <td class="border-r border-gray-100 px-3 py-3">
                    <span class="block text-sm text-gray-800">
                      {{
                        memberCategoryOptionsForRow(idx).find(
                          o => o.value == row.groupMedicalCategoryId,
                        )?.label ?? '-'
                      }}
                    </span>
                  </td>
                  <td class="border-r border-gray-100 px-3 py-3">
                    <x-select
                      v-model="row.insuranceProviderId"
                      :options="insuranceProviderSelectOptions"
                      class="w-full min-w-[10rem]"
                      placeholder="Select provider"
                      filterable
                      :error="
                        quoteForm.errors[
                          `categories.${idx}.insuranceProviderId`
                        ]
                      "
                    />
                  </td>
                  <td class="border-r border-gray-100 px-3 py-3">
                    <x-select
                      v-model="row.healthTpaId"
                      :options="healthTpaSelectOptions"
                      class="w-full min-w-[10rem]"
                      placeholder="Select TPA"
                      filterable
                      :error="
                        quoteForm.errors[
                          `categories.${idx}.healthTpaId`
                        ]
                      "
                    />
                  </td>
                  <td class="border-r border-gray-100 px-3 py-3">
                    <x-select
                      v-model="row.groupMedicalNetworkId"
                      :options="networkOptionsForRow(idx)"
                      :disabled="!row.healthTpaId || isNetworkLoadingForRow(idx)"
                      :placeholder="
                        isNetworkLoadingForRow(idx)
                          ? 'Loading...'
                          : !row.healthTpaId
                            ? 'Select TPA first'
                            : 'Select network'
                      "
                      class="w-full min-w-[10rem]"
                      filterable
                      :error="
                        quoteForm.errors[
                          `categories.${idx}.groupMedicalNetworkId`
                        ]
                      "
                    />
                  </td>
                  <td class="border-r border-gray-100 px-3 py-3">
                    <x-input
                      v-model="row.renewalDate"
                      type="date"
                      class="w-full min-w-[9.5rem]"
                      size="sm"
                      :error="
                        quoteForm.errors[
                          `categories.${idx}.renewalDate`
                        ]
                      "
                    />
                  </td>
                  <td
                    class="border-r border-gray-100 px-3 py-3 text-right align-middle"
                  >
                    <x-input
                      v-model="row.numberOfPeople"
                      type="number"
                      class="w-full min-w-[7.5rem]"
                      size="sm"
                      :rules="[isRequired, isNumber, maxValidation(2147483645)]"
                      :error="
                        quoteForm.errors[
                          `categories.${idx}.numberOfPeople`
                        ]
                      "
                      :min="1"
                    />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <p
            v-if="duplicateCategoryMessage || quoteForm.errors.categories"
            class="mt-3 text-sm text-error"
          >
            {{
              duplicateCategoryMessage || quoteForm.errors.categories
            }}
          </p>
        </div>

        <x-select
          v-if="isEdit"
          v-model="quoteForm.group_medical_type_id"
          :options="
            gmTypes.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.group_medical_type_id"
          label="Group Medical Type"
          required
        />
      </div>
      <x-divider class="my-4" />
      <div class="flex justify-end gap-3 mb-4">
        <x-button
          size="md"
          color="emerald"
          type="submit"
          :loading="quoteForm.processing"
        >
          {{ isEdit ? 'Update' : 'Create' }}
        </x-button>
      </div>
    </x-form>
  </div>
</template>
