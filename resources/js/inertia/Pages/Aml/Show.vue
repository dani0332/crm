<script setup>
const props = defineProps({
  aml: Object,
  amlResults: Array,
  quoteStatusCode: Object,
  amlDecisionStatusCode: Object,
  quoteObject: Object,
  customerTypeEnum: Object,
  insured: Object,
  customerId: Number,
});

const page = usePage();
const notification = useToast();
const rolesEnum = page.props.rolesEnum;
const hasRole = role => useHasRole(role);
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const isScreeningIndividual = computed(() => {
  return props.aml?.search_type == props.customerTypeEnum.Individual;
});

const amlResults = ref(props.amlResults);
const selectedIds = ref(new Set());
const currentPage = ref(1);
const rowsPerPage = 25;
const loader = reactive({
  table: false,
});

const currentPageItems = computed(() => {
  const start = (currentPage.value - 1) * rowsPerPage;
  return amlResults.value.slice(start, start + rowsPerPage);
});

const isCurrentPageAllSelected = computed(
  () =>
    currentPageItems.value.length > 0 &&
    currentPageItems.value.every(item => selectedIds.value.has(item.ID)),
);

const isCurrentPagePartiallySelected = computed(
  () =>
    currentPageItems.value.some(item => selectedIds.value.has(item.ID)) &&
    !isCurrentPageAllSelected.value,
);

function toggleCurrentPage() {
  if (checkDecisionLockStatus.value) return;

  const newSet = new Set(selectedIds.value);
  const isSelecting = !isCurrentPageAllSelected.value;
  const decisionType = isSelecting
    ? props.amlDecisionStatusCode.FALSE_POSITIVE
    : props.amlDecisionStatusCode.UNKNOWN;

  currentPageItems.value.forEach(item => {
    isSelecting ? newSet.add(item.ID) : newSet.delete(item.ID);
    const index = amlResults.value.findIndex(
      x => x.EntityUniqueID == item.EntityUniqueID,
    );
    if (index !== -1) {
      amlResults.value[index].decision = decisionType;
      decisionSelected.value[item.ID] = decisionType;
    }
  });

  selectedIds.value = newSet;

  const matches = currentPageItems.value.map(item => ({
    bridger_match_id: item.ID,
    bridger_decision_type: decisionType,
  }));

  axios
    .post('/kyc/send-bridger-response-bulk', {
      aml_id: props.aml.id,
      aml_quote_url: `/kyc/aml/${props.aml.quote_type_id}/details/${props.aml.quote_request_id}`,
      quote_id: props.aml.quote_request_id,
      quote_ref_id: props.quoteObject.code,
      customer_entity_name: props.aml.input,
      quote_type_text: props.aml.quote_type_text,
      bridger_response: props.aml.results_found,
      last_updated_at: props.aml.updated_at,
      matches,
    })
    .then(res => {
      if (res.data.status === 'error') {
        notification.error({ title: res.data.message, position: 'top' });
      }
    })
    .catch(err => console.log(err));
}

function toggleItem(item) {
  if (checkDecisionLockStatus.value) return;

  const newSet = new Set(selectedIds.value);
  if (newSet.has(item.ID)) {
    newSet.delete(item.ID);
    setSelectedOption(props.amlDecisionStatusCode.UNKNOWN, item);
  } else {
    newSet.add(item.ID);
    setSelectedOption(props.amlDecisionStatusCode.FALSE_POSITIVE, item);
  }
  selectedIds.value = newSet;
}

const tableHeader = [
  { text: '', value: 'checkbox', sortable: false, width: 50 },
  { text: 'Result', value: 'result' },
  { text: 'Score', value: 'EntityScore' },
  { text: 'Name', value: 'full_name' },
  { text: 'Date of Birth', value: 'date_of_birth' },
  { text: 'Gender', value: 'gender' },
  { text: 'ID Number', value: 'customer_id' },
  { text: 'Address', value: 'address' },
  { text: 'Country', value: 'country' },
  { text: 'Customer Type', value: 'customer_type' },
  { text: 'Citizenship', value: 'citizenship' },
  { text: 'Adverse Media Description', value: 'adverse_media_description' },
  { text: 'Adverse Media Subcategory', value: 'adverse_media_subcategory' },
];
const decisionNotes = ref(props.aml.notes);
const in_adverse_media = ref(props.aml.in_adverse_media);
const is_owner_pep = ref(props.aml.is_owner_pep);
const is_controlling_pep = ref(props.aml.is_controlling_pep);
const decisionNotesModal = ref(false);
const submitDecisionLoading = ref(false);
const decisionModalHeading = ref('');
const amlDecision = ref('');
const decisionSelected = ref({});
const passingDecisions = [
  props.amlDecisionStatusCode.FALSE_POSITIVE,
  props.amlDecisionStatusCode.TRUE_MATCH_ACCEPT_RISK,
];

const complianceComponent = reactive({
  insured_id: props.insured?.id ?? null,
  customer_id: props.customerId ?? null,
  pep: props.insured?.insured_kyc?.pep ?? null,
  financial_sanctions: props.insured?.insured_kyc?.financial_sanctions ?? null,
  dual_nationality: props.insured?.insured_kyc?.dual_nationality ?? null,
  in_sanction_list: props.insured?.insured_kyc?.in_sanction_list ?? null,
  deal_sanction_list: props.insured?.insured_kyc?.deal_sanction_list ?? null,
  is_operation_high_risk:
    props.insured?.insured_kyc?.is_operation_high_risk ?? null,
  transaction_pattern: props.insured?.insured_kyc?.transaction_pattern ?? null,
  // individual
  is_partner: props.insured?.insured_kyc?.is_partner ?? null,
  // entity
  is_sanction_match: props.insured?.insured_kyc?.is_sanction_match ?? null,
  in_fatf: props.insured?.insured_kyc?.in_fatf ?? null,
  is_owner_high_risk: props.insured?.insured_kyc?.is_owner_high_risk ?? null,
  transaction_volume: props.insured?.insured_kyc?.transaction_volume ?? null,
  transaction_activities:
    props.insured?.insured_kyc?.transaction_activities ?? null,
});

const transactionVolumeOptions = [
  {
    value: 'less-than-3-in-month',
    label: 'Less than 3 transactions in a month',
  },
  { value: '4-7-in-month', label: '4 to 7 transactions in a month' },
  {
    value: 'more-than-8-in-month',
    label: 'More than 8 transactions in a month',
  },
];
const transactionActivitiesOptions = [
  {
    value: 'less_expected_annual_activity',
    label: 'Less than expected Annual Activity',
  },
  {
    value: 'near_expected_annual_activity',
    label: 'Near to expected Annual Activity',
  },
  {
    value: 'more_expected_annual_activity',
    label: 'More than expected Annual Activity',
  },
];
const transactionPatternOptions = [
  { value: 'count_pattern_changes', label: 'Yes - Count Pattern Changes' },
  { value: 'behaviour_changes', label: 'Yes - Behaviour Changes' },
  { value: 'business_model_changes', label: 'Yes - Business Model Changes' },
  { value: 'no_changes', label: 'No Changes' },
  { value: 'not_applicable', label: 'Not Applicable' },
];

const decisionTitles = {
  FalsePositive: 'False Positive',
  TrueMatchAcceptRisk: 'True Match - Accept Risk',
  TrueMatchRejectRisk: 'True Match - Reject Risk',
};

function submitDecision(decision) {
  if (
    (props.aml.quote_type_id == 5 &&
      fieldValidationscompany() &&
      fieldValidationspartner() &&
      fieldValidationsperson() &&
      complianceRules()) ||
    (props.aml.quote_type_id != 5 &&
      fieldValidationscompany() &&
      fieldValidationsowner() &&
      complianceRules())
  ) {
    submitDecisionLoading.value = true;
    let quoteStatusCode = passingDecisions.includes(decision)
      ? props.quoteStatusCode.AMLScreeningCleared
      : props.quoteStatusCode.AMLScreeningFailed;
    let url = route('quoteStatusUpdate', {
      quoteTypeId: props.aml.quote_type_id,
      quoteRequestId: props.aml.quote_request_id,
      quoteTypeCode: quoteStatusCode,
    });

    axios
      .get(url, {
        params: {
          notes: decisionNotes.value,
          in_adverse_media: in_adverse_media.value,
          is_owner_pep: is_owner_pep.value,
          is_controlling_pep: is_controlling_pep.value,
          aml_id: props.aml.id,
          aml_decision: decision,
          decisonsForUpdatePortal: `[${JSON.stringify(decisionSelected.value)}]`,
          result_id: JSON.parse(props.aml.results)[0].ResultID,
          complianceComponent: complianceComponent,
        },
      })
      .then(response => {
        submitDecisionLoading.value = false;
        decisionNotesModal.value = false;
        if (response.data.status === 'success') {
          notification.success({
            title: response.data.message,
            position: 'top',
          });
          window.location = `/kyc/aml/${props.aml.quote_type_id}/details/${props.aml.quote_request_id}`;
        } else {
          notification.error({
            title: response.data.message,
            position: 'top',
          });
        }
      })
      .catch(err => {
        console.log(err);
      })
      .finally(() => {
        router.reload({
          only: ['insured'],
        });
      });
  }
}

const submitAMLDecision = decision => {
  setAllDecisionSelected();
  decisionNotes.value = '';
  decisionNotesModal.value = true;
  decisionModalHeading.value = decisionTitles[decision];
  amlDecision.value = decision;
};

const setAllDecisionSelected = () => {
  amlResults.value.filter(x => {
    if (x.decision == 'FalsePositive' || x.decision == 'TrueMatch') {
      decisionSelected.value[x.ID] = x.decision;
    }
  });
};
const setSelectedOption = (e, item) => {
  decisionSelected.value[item.ID] = e;
  let index = amlResults.value.findIndex(
    x => x.EntityUniqueID == item.EntityUniqueID,
  );

  if (index != -1) amlResults.value[index].decision = e;

  const newSet = new Set(selectedIds.value);
  if (e === props.amlDecisionStatusCode.FALSE_POSITIVE) {
    newSet.add(item.ID);
  } else {
    newSet.delete(item.ID);
  }
  selectedIds.value = newSet;

  let data = {
    aml_id: props.aml.id,
    aml_quote_url: `/kyc/aml/${props.aml.quote_type_id}/details/${props.aml.quote_request_id}`,
    quote_id: props.aml.quote_request_id,
    quote_ref_id: props.quoteObject.code,
    customer_entity_name: props.aml.input,
    quote_type_text: props.aml.quote_type_text,
    bridger_response: props.aml.results_found,
    last_updated_at: props.aml.updated_at,
    bridger_match_id: item.ID,
    bridger_decision_type: e,
  };

  axios
    .post(`/kyc/send-bridger-response`, data)
    .then(res => {
      if (res.data.status === 'success') {
        checkDecisionLockStatus.value =
          res.data.result_state === props.amlDecisionStatusCode.SENT_FOR_REVIEW;
        notification.success({
          title: res.data.message,
          position: 'top',
        });
      } else if (res.data.status === 'error') {
        notification.error({
          title: res.data.message,
          position: 'top',
        });
      }
    })
    .catch(err => {
      console.log(err);
    });
};

const checkDecisionLockStatus = ref(
  (props.aml.decision === props.amlDecisionStatusCode.TRUE_MATCH_REJECT_RISK ||
    props.aml.decision === props.amlDecisionStatusCode.SENT_FOR_REVIEW) &&
    hasRole(rolesEnum.COMPLIANCE),
);

const isTrue = computed(() => {
  return amlResults.value.some(x => x.decision == 'TrueMatch');
});

const falsePositive = computed(() => {
  if (!amlResults.value.length) return false;
  const allDropdownsFP = amlResults.value.every(
    x => x.decision == 'FalsePositive',
  );
  const allCheckboxesSelected =
    selectedIds.value.size === amlResults.value.length;
  return allDropdownsFP || allCheckboxesSelected;
});

const notesRequired = ref(false);

function complianceRules() {
  if (
    (hasRole(rolesEnum.COMPLIANCE) || hasRole(rolesEnum.ComplianceSuperUser)) &&
    (decisionNotes.value == '' || decisionNotes.value == null)
  ) {
    notesRequired.value = 'This field is required';
    return false;
  }

  notesRequired.value = false;
  return true;
}
const fieldRequired = ref(false);
const fieldRequiredowner = ref(false);
function fieldValidationsowner() {
  if (is_owner_pep.value === '' || is_owner_pep.value === null) {
    fieldRequiredowner.value = 'This field is required';
    return false;
  }
  fieldRequiredowner.value = false;
  return true;
}
const fieldRequiredcompany = ref(false);
function fieldValidationscompany() {
  if (in_adverse_media.value === '' || in_adverse_media.value == null) {
    fieldRequiredcompany.value = 'This field is required';
    return false;
  }
  fieldRequiredcompany.value = false;
  return true;
}
const fieldRequiredpartner = ref(false);
function fieldValidationspartner() {
  if (is_owner_pep.value === '' || is_owner_pep.value === null) {
    fieldRequiredpartner.value = 'This field is required';
    return false;
  }
  fieldRequiredpartner.value = false;
  return true;
}
const fieldRequiredperson = ref(false);
function fieldValidationsperson() {
  if (is_controlling_pep.value === '' || is_controlling_pep.value === null) {
    fieldRequiredperson.value = 'This field is required';
    return false;
  }
  fieldRequiredperson.value = false;
  return true;
}
</script>

<template>
  <div>
    <Head title="AML" />
    <div class="flex justify-between items-center flex-wrap gap-2 mb-5">
      <h2 class="text-xl font-semibold">AML</h2>
      <div class="flex gap-2">
        <Link href="/kyc/aml" preserve-scroll>
          <x-button size="sm" color="primary" tag="div">AML</x-button>
        </Link>
      </div>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">AML Id</dt>
            <dd>{{ aml.id }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Quote Type</dt>
            <dd>{{ aml.quote_type_text }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Quote Request ID</dt>
            <Link
              :href="`/kyc/aml/${aml.quote_type_id}/details/${aml.quote_request_id}`"
              class="text-primary-500 hover:underline"
            >
              {{ aml.quote_request_id }}
            </Link>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Input</dt>
            <dd>{{ aml.input }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Created At</dt>
            <dd>{{ aml.created_at }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Updated At</dt>
            <dd>{{ aml.updated_at }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Results Found</dt>
            <dd>{{ aml.results_found }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Search Type</dt>
            <dd>{{ aml.search_type }}</dd>
          </div>
        </dl>
      </div>
      <div class="mt-6">
        <h3 class="font-semibold text-primary-800">Results</h3>
        <x-divider class="mb-4 mt-1" />
      </div>

      <DataTable
        v-model:current-page="currentPage"
        table-class-name="compact tablefixed"
        :headers="tableHeader"
        :loading="loader.table"
        :items="amlResults || []"
        :rows-per-page="rowsPerPage"
        border-cell
      >
        <template #header="header">
          <div v-if="header.value === 'checkbox'" class="flex justify-center">
            <input
              type="checkbox"
              :checked="isCurrentPageAllSelected"
              :indeterminate="isCurrentPagePartiallySelected"
              :disabled="checkDecisionLockStatus"
              @change="toggleCurrentPage"
              class="w-4 h-4 cursor-pointer disabled:cursor-not-allowed disabled:opacity-50"
            />
          </div>
          <span v-else>{{ header.text }}</span>
        </template>

        <template #item-checkbox="item">
          <div class="flex justify-center">
            <input
              type="checkbox"
              :checked="selectedIds.has(item.ID)"
              :disabled="checkDecisionLockStatus"
              @change="toggleItem(item)"
              class="w-4 h-4 cursor-pointer disabled:cursor-not-allowed disabled:opacity-50"
            />
          </div>
        </template>

        <template #item-result="item">
          <div class="relative py-2">
            <x-select
              :modelValue="item.decision"
              :options="[
                { value: amlDecisionStatusCode.UNKNOWN, label: 'Unknown' },
                {
                  value: amlDecisionStatusCode.FALSE_POSITIVE,
                  label: 'False Positive',
                },
                {
                  value: amlDecisionStatusCode.TRUE_MATCH,
                  label: 'True Match',
                },
              ]"
              placeholder="Select Result"
              class="w-full"
              :disabled="checkDecisionLockStatus"
              @update:modelValue="setSelectedOption($event, item)"
              size="xs"
            />
          </div>
        </template>

        <template #item-full_name="{ EntityDetails }">
          {{ EntityDetails.Name?.Full ?? '' }}
        </template>

        <template #item-date_of_birth="{ EntityDetails }">
          {{
            EntityDetails.AdditionalInfo
              ? EntityDetails.AdditionalInfo.filter(x => x.Type === 'DOB')
                  .map(dob => dob.Value)
                  .toString()
              : ''
          }}
        </template>

        <template #item-gender="{ EntityDetails }">
          {{ EntityDetails.Gender ?? '' }}
        </template>

        <template #item-customer_id="{ EntityDetails }">
          {{
            EntityDetails.IDs
              ? EntityDetails.IDs.filter(x => x.Type === 'ProprietaryUID')
                  .map(ProprietaryUID => ProprietaryUID.Number)
                  .toString()
              : ''
          }}
        </template>

        <template #item-address="{ EntityDetails }">
          {{
            EntityDetails.Addresses
              ? EntityDetails.Addresses.map(
                  address =>
                    (address.City ?? '') +
                    ' ' +
                    (address.StateProvinceDistrict ?? '') +
                    ' ' +
                    (address.Country ?? ''),
                ).toString()
              : ''
          }}
        </template>

        <template #item-country="{ EntityDetails }">
          {{
            EntityDetails.Addresses
              ? EntityDetails.Addresses.map(
                  nationality => nationality.Country,
                ).toString()
              : ''
          }}
        </template>

        <template #item-customer_type>
          {{ aml.search_type }}
        </template>

        <template #item-citizenship="{ EntityDetails }">
          {{
            EntityDetails.AdditionalInfo
              ? EntityDetails.AdditionalInfo.filter(
                  x => x.Type === 'Citizenship',
                )
                  .map(citizenship => citizenship.Value)
                  .toString()
              : ''
          }}
        </template>

        <template #item-adverse_media_description="{ AdverseMedias }">
          {{
            AdverseMedias?.length
              ? AdverseMedias.map(m => m.Description)
                  .filter(Boolean)
                  .join(', ')
              : ''
          }}
        </template>

        <template #item-adverse_media_subcategory="{ AdverseMedias }">
          {{
            AdverseMedias?.length
              ? AdverseMedias.flatMap(m => m.SubCategories ?? []).join(', ')
              : ''
          }}
        </template>
      </DataTable>
      <div v-if="amlResults.length" class="flex justify-end mb-4">
        <x-button
          class="mt-2 ml-2"
          color="emerald"
          size="sm"
          @click="submitAMLDecision(amlDecisionStatusCode.FALSE_POSITIVE)"
          :disabled="!falsePositive"
        >
          False Positive
        </x-button>
        <x-button
          v-if="can(permissionsEnum.AMLDecisionUpdateTrueMatch)"
          class="mt-2 ml-2"
          color="orange"
          size="sm"
          @click="
            submitAMLDecision(amlDecisionStatusCode.TRUE_MATCH_REJECT_RISK)
          "
          :disabled="!isTrue"
        >
          True Match - Reject Risk
        </x-button>
        <x-button
          v-if="can(permissionsEnum.AMLDecisionUpdateTrueMatch)"
          class="mt-2 ml-2"
          color="red"
          size="sm"
          @click="
            submitAMLDecision(amlDecisionStatusCode.TRUE_MATCH_ACCEPT_RISK)
          "
          :disabled="!isTrue"
        >
          True Match - Accept Risk
        </x-button>
      </div>

      <AuditLogs
        :title="'AML Audit Logs'"
        :type="`App\\Models\\KycLog`"
        :id="props.aml.id"
      />

      <AuditLogs
        :title="'KYC Audit Logs'"
        :type="`App\\Models\\InsuredKyc`"
        :id="props.insured?.insured_kyc?.id"
      />
      <x-modal
        v-model="decisionNotesModal"
        :title="`${decisionModalHeading}`"
        backdrop
      >
        <template #header>
          {{ decisionModalHeading }}
        </template>
        <div v-if="aml.quote_type_id === 5">
          <x-label
            >Does the Company name or subsidiary / Affiliate entities feature in
            any adverse media?</x-label
          >
          <div class="grid md:grid-cols-2 mb-4 mt-2">
            <x-select
              v-model="in_adverse_media"
              :options="[
                { value: 1, label: 'Yes' },
                { value: 0, label: 'No' },
              ]"
              placeholder="Select Result"
              class="w-full"
              :error="fieldRequiredcompany"
              size="xs"
            />
          </div>

          <x-label
            >Does the owner/ Shareholder/Partner of the company feature in any
            PEP List/ Adverse Media?</x-label
          >
          <div class="grid md:grid-cols-2 mb-4 mt-2">
            <x-select
              v-model="is_owner_pep"
              :options="[
                { value: 1, label: 'Yes' },
                { value: 0, label: 'No' },
              ]"
              placeholder="Select Result"
              class="w-full"
              :error="fieldRequiredpartner"
              size="xs"
            />
          </div>
          <x-label
            >Is the controlling person a PEP/HIO/FPEP/Government
            Organization?</x-label
          >
          <div class="grid md:grid-cols-2 mb-4 mt-2">
            <x-select
              v-model="is_controlling_pep"
              :options="[
                { value: 1, label: 'Yes' },
                { value: 0, label: 'No' },
              ]"
              placeholder="Select Result"
              class="w-full"
              :error="fieldRequiredperson"
              size="xs"
            />
          </div>
        </div>

        <div v-else>
          <x-label>Is the Natural Person listed in any adverse media?</x-label>
          <div class="grid md:grid-cols-2 mb-4 mt-2">
            <x-select
              v-model="in_adverse_media"
              :options="[
                { value: 1, label: 'Yes' },
                { value: 0, label: 'No' },
              ]"
              placeholder="Select Result"
              class="w-full"
              size="xs"
              :error="fieldRequiredcompany"
            />
          </div>

          <x-label>Is the Natural Person listed in PEP/FPEP/HIO?</x-label>
          <div class="grid md:grid-cols-2 mb-4 mt-2">
            <x-select
              v-model="is_owner_pep"
              :options="[
                { value: 1, label: 'Yes' },
                { value: 0, label: 'No' },
              ]"
              placeholder="Select Result"
              class="w-full"
              size="xs"
              :error="fieldRequiredowner"
            />
          </div>
        </div>

        <!-- Compliance Section: For Compliance Use Only (migrated from KYCDetails.vue) -->
        <x-label>Is the customer a PEP?</x-label>
        <div class="grid md:grid-cols-2 mb-4 mt-2">
          <x-select
            v-model="complianceComponent.pep"
            :options="[
              { value: 1, label: 'Yes' },
              { value: 2, label: 'No' },
            ]"
            placeholder="Select Result"
            class="w-full"
            size="xs"
            :error="fieldRequiredowner"
          />
        </div>
        <x-label>
          Is the customer or business subjected to financial sanctions / or
          connected with prescribed terrorist organizations?
        </x-label>
        <div class="grid md:grid-cols-2 mb-4 mt-2">
          <x-select
            v-model="complianceComponent.financial_sanctions"
            :options="[
              { value: 1, label: 'Yes' },
              { value: 2, label: 'No' },
            ]"
            placeholder="Select Result"
            class="w-full"
            size="xs"
            :error="fieldRequiredowner"
          />
        </div>
        <x-label>Does the customer have dual nationality?</x-label>
        <div class="grid md:grid-cols-2 mb-4 mt-2">
          <x-select
            v-model="complianceComponent.dual_nationality"
            :options="[
              { value: 1, label: 'Yes' },
              { value: 2, label: 'No' },
            ]"
            placeholder="Select Result"
            class="w-full"
            size="xs"
            :error="fieldRequiredowner"
          />
        </div>
        <x-label v-if="isScreeningIndividual"
          >Is the Natural Person listed in any Sanction/OOL/SIP list?</x-label
        >
        <x-label v-else
          >Does the Company name or Subsidiary/Affiliate entities feature in any
          sanction list?</x-label
        >
        <div class="grid md:grid-cols-2 mb-4 mt-2">
          <x-select
            v-model="complianceComponent.in_sanction_list"
            :options="[
              { value: 1, label: 'Yes' },
              { value: 2, label: 'No' },
            ]"
            placeholder="Select Result"
            class="w-full"
            size="xs"
            :error="fieldRequiredowner"
          />
        </div>
        <x-label v-if="isScreeningIndividual"
          >Does the Natural Person intend to provide professional services in
          any sanctions-listed country/ies?</x-label
        >
        <x-label v-else
          >Does the customer intend to deal with any country listed in the
          Sanctions List?</x-label
        >
        <div class="grid md:grid-cols-2 mb-4 mt-2">
          <x-select
            v-model="complianceComponent.deal_sanction_list"
            :options="[
              { value: 1, label: 'Yes' },
              { value: 2, label: 'No' },
            ]"
            placeholder="Select Result"
            class="w-full"
            size="xs"
            :error="fieldRequiredowner"
          />
        </div>
        <x-label v-if="isScreeningIndividual"
          >Is the Natural Person controlling/involved in any business listed in
          High-Risk Countries?</x-label
        >
        <x-label v-else
          >Do the customer or subsidiary/ affiliate entities have operations in
          any High-Risk Countries?</x-label
        >
        <div class="grid md:grid-cols-2 mb-4 mt-2">
          <x-select
            v-model="complianceComponent.is_operation_high_risk"
            :options="[
              { value: 1, label: 'Yes' },
              { value: 2, label: 'No' },
            ]"
            placeholder="Select Result"
            class="w-full"
            size="xs"
            :error="fieldRequiredowner"
          />
        </div>
        <template v-if="isScreeningIndividual">
          <x-label
            >Is the Natural Person an Owner/Shareholder/Partner in any
            Organization?</x-label
          >
          <div class="grid md:grid-cols-2 mb-4 mt-2">
            <x-select
              v-model="complianceComponent.is_partner"
              :options="[
                { value: 1, label: 'Yes' },
                { value: 2, label: 'No' },
              ]"
              placeholder="Select Result"
              class="w-full"
              size="xs"
              :error="fieldRequiredowner"
            />
          </div>
        </template>
        <template v-else>
          <x-label
            >Is There A Sanction Match On The Owner/Partners/Bod, Senior
            Management, Group Company, Holding Company Or Related Company
            Names?</x-label
          >
          <div class="grid md:grid-cols-2 mb-4 mt-2">
            <x-select
              v-model="complianceComponent.is_sanction_match"
              :options="[
                { value: 1, label: 'Yes' },
                { value: 2, label: 'No' },
              ]"
              placeholder="Select Result"
              class="w-full"
              size="xs"
              :error="fieldRequiredowner"
            />
          </div>
          <x-label
            >Does the company have any subsidiary, affiliate, branch, or
            group/holding company in FATF-listed high-risk monitored
            jurisdiction?</x-label
          >
          <div class="grid md:grid-cols-2 mb-4 mt-2">
            <x-select
              v-model="complianceComponent.in_fatf"
              :options="[
                { value: 1, label: 'Yes' },
                { value: 2, label: 'No' },
              ]"
              placeholder="Select Result"
              class="w-full"
              size="xs"
              :error="fieldRequiredowner"
            />
          </div>
          <x-label
            >Is the owner/ Shareholder/ Partner/Director of the company from
            High-Risk countries?</x-label
          >
          <div class="grid md:grid-cols-2 mb-4 mt-2">
            <x-select
              v-model="complianceComponent.is_owner_high_risk"
              :options="[
                { value: 1, label: 'Yes' },
                { value: 2, label: 'No' },
              ]"
              placeholder="Select Result"
              class="w-full"
              size="xs"
              :error="fieldRequiredowner"
            />
          </div>
        </template>
        <x-divider class="mb-4 mt-1" />
        <dl class="grid md:grid-cols-3 gap-x-6 gap-y-4 items-center">
          <template v-if="!isScreeningIndividual">
            <x-field label="Transaction Volume">
              <x-select
                v-model="complianceComponent.transaction_volume"
                :options="transactionVolumeOptions"
                placeholder="Transaction Volume"
                class="w-full"
                :single="true"
                :error="fieldRequiredowner"
              />
            </x-field>
            <x-field label="Transaction Activities">
              <x-select
                v-model="complianceComponent.transaction_activities"
                :options="transactionActivitiesOptions"
                placeholder="Transaction Activities"
                class="w-full"
                :single="true"
                :error="fieldRequiredowner"
              />
            </x-field>
          </template>
          <x-field label="Transaction Pattern Changes">
            <x-select
              v-model="complianceComponent.transaction_pattern"
              :options="transactionPatternOptions"
              placeholder="Transaction Pattern Changes"
              class="w-full"
              :single="true"
              :error="fieldRequiredowner"
            />
          </x-field>
        </dl>
        <x-textarea
          v-model="decisionNotes"
          placeholder="Notes"
          class="w-full"
          :error="notesRequired"
        ></x-textarea>

        <template #actions>
          <div class="text-right space-x-4">
            <x-button @click="decisionNotesModal = false">Cancel</x-button>
            <x-button
              :loading="submitDecisionLoading"
              @click="submitDecision(amlDecision)"
              color="success"
              >Submit
            </x-button>
          </div>
        </template>
      </x-modal>
    </div>
  </div>
</template>
