<script setup>
import QuoteDocuments from '@/inertia/Pages/PersonalQuote/Partials/QuoteDocuments.vue';
import PaymentTableNew from '../../Components/PaymentTableNew.vue';
import MigratePayment from '../../Components/MigratePayment.vue';
import EntityRiskRatingScoreDetails from '../../Components/EntityRiskRatingScoreDetails.vue';

defineProps({
  quote: Object,
  quoteDetails: Object,
  allowedDuplicateLOB: Array,
  genderOptions: Object,
  typeCode: String,
  lostReasons: Object,
  quoteStatuses: Object,
  quoteStatusEnum: Object,
  customerAdditionalContacts: Array,
  customerTypeEnum: Object,
  companyTypes: Array,
  nationalities: Array,
  UBORelations: Array,
  UBOsDetails: Array,
  canAddBatchNumber: Boolean,
  documentTypes: Object,
  storageUrl: String,
  insuranceProviders: Object,
  vatPercentage: Number,
  paymentStatusEnum: Object,
  paymentTooltipEnum: Object,
  paymentMethods: Array,
  isNewPaymentStructure: Boolean,
  isAmlClearedForPayment: Boolean,
});

const page = usePage();
const notification = useToast();
const { isRequired } = useRules();

const can = permission => useCan(permission);
const hasAnyRole = roles => useHasAnyRole(roles);
const hasRole = role => useHasRole(role);
const permissionsEnum = page.props.permissionsEnum;
const rolesEnum = page.props.rolesEnum;

const historyData = ref(null),
  historyLoading = ref(false);

const isDuplicateAllowed = computed(() => {
  return page.props.allowedDuplicateLOB.includes(page.props.typeCode);
});

const genderText = gender =>
  computed(() => {
    return page.props.genderOptions[gender];
  });


const dateFormat = date =>
  date ? useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value : '-';
  
const modals = reactive({
  duplicate: false,
  member: false,
  memberConfirm: false,
  doc: false,
  docConfirm: false,
  plan: false,
  createPlan: false,
  activity: false,
  activityConfirm: false,
  addContact: false,
  contactDeleteConfirm: false,
  contactPrimaryConfirm: false,
  customerEntityNotFound: false,
});

const leadDuplicateForm = useForm({
  lob_team: [],
  lob_team_sub_selection: null,
});

const openDuplicate = () => {
  modals.duplicate = true;
  leadDuplicateForm.reset();
};

const onCreateDuplicate = isValid => {
  if (!isValid) return;
  let data = {
    modelType: 'business',
    parentType: 'business',
    entityId: page.props.quote.id,
    entityCode: page.props.quote.code,
    entityUId: page.props.quote.uuid,
    lob_team: leadDuplicateForm.lob_team,
    lob_team_sub_selection: leadDuplicateForm.lob_team_sub_selection,
  };
  axios
    .post(route('createDuplicate'), data)
    .then(res => {
      modals.duplicate = false;
      notification.success({
        title: 'Lead duplicated successfully',
        position: 'top',
      });
    })
    .catch(err => {
      notification.error('Something went wrong');
    });
};

const leadStatusForm = useForm({
  modelType: 'Business',
  leadId: page.props.quote.id,
  quote_uuid: page.props.quote.uuid,
  assigned_to_user_id: page.props.quote.advisor_id,
  leadStatus: page.props.quote.quote_status_id || null,
  notes: page.props.quoteDetails.notes || null,
  trans_code: page.props.quote.transapp_code || null,
  lostReason: page.props.quoteDetails.lost_reason_id || null,
});

const leadStatusOptions = computed(() => {
  return page.props.quoteStatuses.map(status => ({
    value: status.id,
    label: status.text,
  }));
});

const onLeadStatus = () => {
  let data = {
    modelType: 'Business',
    leadId: leadStatusForm.leadId,
    quote_uuid: leadStatusForm.quote_uuid,
    assigned_to_user_id: leadStatusForm.assigned_to_user_id,
    leadStatus: leadStatusForm.leadStatus,
    notes: leadStatusForm.notes,
    trans_code: leadStatusForm.trans_code,
    lostReason: leadStatusForm.lostReason,
  };
  axios
    .post(
      route('updateLeadStatus', {
        QuoteUId: page.props.quote.id,
        modelType: 'Business',
      }),
      data,
    )
    .then(res => {
      notification.success({
        title: 'Lead Status Updated',
        position: 'top',
      });
    })
    .catch(err => {
      const flash_messages = err.response.data.errors.value;
      Object.keys(flash_messages).forEach(function (key) {
        notification.error({
          title: flash_messages[key],
          position: 'top',
        });
      });
    });
};

const onLoadHistoryData = async () => {
  historyLoading.value = true;
  const res = await fetch(
    route('getLeadHistory', {
      modelType: 'business',
      recordId: page.props.quote.id,
    }),
  );
  const finalRes = await res.json();
  historyData.value = finalRes;
  historyLoading.value = false;
};

const historyDataTable = [
  { text: 'Modified At', value: 'ModifiedAt' },
  { text: 'Modified By', value: 'ModifiedBy' },
  { text: 'Notes', value: 'NewNotes' },
  { text: 'Lead Status', value: 'NewStatus' },
];

const companyConcernOptions = [
  { label: 'Parent', value: 'Parent' },
  { label: 'Sub Entity', value: 'SubEntity' },
];

const companyTypeOptions = computed(() => {
  return page.props.companyTypes.map(comp_type => ({
    value: comp_type.code,
    label: comp_type.text,
  }));
});
const emiratesOptions = computed(() => {
  return page.props.emirates.map(em => ({
    value: em.id,
    label: em.text,
  }));
});

const isProfileUpdateAllow = computed(() => {
  return hasAnyRole([
    page.props.rolesEnum.PA,
    page.props.rolesEnum.OE,
    page.props.rolesEnum.NRA,
  ]);
});

const customerProfileForm = useForm({
  customer_id: page.props.quote.customer_id,
  customer_type: page.props.quote.customer_type,
  quote_type: page.props.modelType,
  quote_type_id: page.props.quoteTypeId,
  quote_request_id: page.props.quote.id,

  insured_first_name: page.props.quote?.customer.insured_first_name || '',
  insured_last_name: page.props.quote?.customer.insured_last_name || '',
  emirates_id_number: page.props.quote?.customer.emirates_id_number || null,
  emirates_id_expiry_date:
    page.props.quote?.customer.emirates_id_expiry_date || null,

  entity_id: page.props.quote?.quote_request_entity_mapping?.entity_id ?? null,
  trade_license_no:
    page.props.quote?.quote_request_entity_mapping?.entity?.trade_license_no ??
    null,
  company_name:
    page.props.quote.company_name ??
    page.props.quote?.quote_request_entity_mapping?.entity?.company_name ??
    null,
  company_address:
    page.props.quote?.quote_request_entity_mapping?.entity?.company_address ??
    null,
  entity_type_code:
    page.props.quote?.quote_request_entity_mapping?.entity_type_code ??
    'Parent',
  industry_type_code:
    page.props.quote?.quote_request_entity_mapping?.entity
      ?.industry_type_code ?? null,
  emirate_of_registration_id:
    page.props.quote?.quote_request_entity_mapping?.entity
      ?.emirate_of_registration_id ?? null,
});

const updateProfileDetails = isValid => {
  if (!isValid) return;

  customerProfileForm.post(route('update-customer-profile'), {
    preserveScroll: true,
    onSuccess: () => {
      notification.success({
        title: 'Customer profile details update Successfully',
        position: 'top',
      });
    },
    onError: errors => {
      Object.keys(errors).forEach(function (key) {
        notification.error({
          title: errors[key],
          position: 'top',
        });
      });
    },
  });
};

const entityDetailsFound = ref(false);
const getParentEntityModel = ref(false);
const tradeLicenseEntity = reactive({
  entity_id: null,
  trade_license: null,
  company_name: null,
  company_address: null,
  triggeredFrom: false,
});

const entityTypeChange = event => {
  if (event === 'SubEntity') {
    getParentEntityModel.value = true;
  }
};

const loader = reactive({
  tradeSearch: false,
  tradeDetail: false,
});
const searchByTradeLicense = trigger => {
  loader.tradeSearch = true;
  let url = `/kyc/aml-fetch-entity?trade_license=${customerProfileForm.trade_license_no}`;
  axios
    .get(url)
    .then(res => {
      if (res.data.status) {
        let response = res.data.response;
        entityDetailsFound.value = true;
        tradeLicenseEntity.entity_id = response.id;
        tradeLicenseEntity.trade_license = response.trade_license_no;
        tradeLicenseEntity.company_name = response.company_name;
        tradeLicenseEntity.company_address = response.company_address;
        tradeLicenseEntity.triggeredFrom = trigger === 'SubEntity';

        notification.success({
          title: res.data.message,
          position: 'top',
        });
      } else {
        notification.error({
          title: res.data.message,
          position: 'top',
        });
      }
    })
    .catch(err => {
      console.log(err);
    })
    .finally(() => (loader.tradeSearch = false));
};

const linkEntity = () => {
  loader.tradeDetail = true;
  let entityDetails = {
    quote_type_id: page.props.quoteTypeId,
    quote_request_id: page.props.quote.id,
    entity_id: tradeLicenseEntity.entity_id,
    triggeredFrom: tradeLicenseEntity.triggeredFrom,
  };
  axios
    .post(route('link-entity-details'), entityDetails)
    .then(res => {
      if (res.data.status) {
        let response = res.data.response;

        // Append Entity data in fields
        customerProfileForm.trade_license_no = response.trade_license_no;
        customerProfileForm.company_name = response.company_name;
        customerProfileForm.company_address = response.company_address;
        customerProfileForm.entity_type_code =
          response?.quote_request_entity_mapping[0]?.entity_type_code ?? '';
        customerProfileForm.industry_type_code = response.industry_type_code;
        customerProfileForm.emirate_of_registration_id =
          response.emirate_of_registration_id;

        notification.success({
          title: res.data.message,
          position: 'top',
        });
        entityDetailsFound.value = false;
      }
    })
    .catch(err => {
      console.log(err);
    })
    .finally(() => (loader.tradeDetail = false));
};
const readOnlyMode = reactive({
  isDisable: true,
});
onMounted(() => {
  readOnlyMode.isDisable = !can(permissionsEnum.All_QUOTES_VIEWONLY_ACCESS);
});
</script>

<template>
  <div>
    <Head title="Group Medical Lead Detail" />
    <div class="flex justify-between items-center flex-wrap gap-2 mb-5">
      <h2 class="text-xl font-semibold">Group Medical Lead Detail</h2>
      <div class="flex gap-2" v-if="readOnlyMode.isDisable === true">
        <x-button
          v-if="isDuplicateAllowed"
          size="sm"
          color="#ff5e00"
          @click.prevent="openDuplicate"
        >
          Duplicate Lead
        </x-button>
        <Link :href="route('amt.index')" preserve-scroll>
          <x-button size="sm" color="primary" tag="div">
            Group Medical List
          </x-button>
        </Link>
        <Link
          v-if="!can(permissionsEnum.canEditQuote)"
          :href="route('amt.edit', quote.uuid)"
        >
          <x-button size="sm" tag="div">Edit</x-button>
        </Link>
      </div>
    </div>

    <x-modal v-model="modals.duplicate" size="lg" show-close backdrop>
      <template #header> Duplicate Lead </template>
      <x-form @submit="onCreateDuplicate" :auto-focus="false">
        <div class="grid gap-4">
          <x-select
            v-model="leadDuplicateForm.lob_team"
            label="LOBs"
            :options="
              allowedDuplicateLOB.map(lob => ({
                value: lob,
                label: lob,
              }))
            "
            :rules="[isRequired]"
            placeholder="Select LOB For Duplication"
            class="w-full"
            multiple
          />
          <x-select
            v-model="leadDuplicateForm.lob_team_sub_selection"
            label="Reason"
            :rules="[isRequired]"
            class="w-full"
            :options="[
              { value: 'new_enquiry', label: 'New enquiry' },
              { value: 'record_only', label: 'Record purposes only' },
            ]"
          />
          <x-button
            color="orange"
            type="submit"
            :loading="leadDuplicateForm.processing"
          >
            Create Duplicate
          </x-button>
        </div>
      </x-form>
    </x-modal>

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 break-words">
          <div
            class="grid sm:grid-cols-2"
            v-if="hasAnyRole([rolesEnum.Admin, rolesEnum.Engineering])"
          >
            <dt class="font-medium">ID</dt>
            <dd>{{ quote.id }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <div>
              <x-tooltip position="bottom">
                <label
                  class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-700"
                >
                  Ref-ID
                </label>
                <template #tooltip> Reference ID </template>
              </x-tooltip>
            </div>
            <div>{{ quote.code }}</div>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CUSTOMER TYPE</dt>
            <dd>{{ quote.customer_type }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">FIRST NAME</dt>
            <dd>{{ quote.first_name }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">LAST NAME</dt>
            <dd>{{ quote.last_name }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">MOBILE NUMBER</dt>
            <dd>{{ quote.mobile_no }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">EMAIL</dt>
            <dd class="break-words">{{ quote.email }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">COMPANY NAME</dt>
            <dd class="break-words">{{ quote.company_name }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">NEXT FOLLOWUP DATE</dt>
            <dd>{{ quote.next_followup_date }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">SOURCE</dt>
            <dd>{{ quote.source }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">LOST REASON</dt>
            <dd>
              {{ quote?.business_quote_request_detail?.lost_reason?.text }}
            </dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">ADVISOR</dt>
            <dd>{{ quote?.advisor?.name }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CREATED DATE</dt>
            <dd>{{ quote.created_at }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">LAST MODIFIED DATE</dt>
            <dd>{{ quote.updated_at }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">NUMBER OF EMPLOYEES</dt>
            <dd>{{ quote.number_of_employees }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">BUSINESS INSURANCE TYPE</dt>
            <dd>Group Medical</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">BRIEF DETAILS</dt>
            <dd class="break-words">{{ quote.brief_details }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">RENEWAL EXPIRY DATE</dt>
            <dd>{{ quote.renewal_expiry_date }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">RENEWAL BATCH</dt>
            <dd>{{ quote.renewal_batch }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">GENDER</dt>
            <dd>{{ genderText(quote.gender).value }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <div>
              <x-tooltip position="bottom">
                <label
                  class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-700"
                >
                  Parent Ref-ID
                </label>
                <template #tooltip> Parent Reference ID </template>
              </x-tooltip>
            </div>
            <div>{{ quote.parent_duplicate_quote_id }}</div>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">RENEWAL IMPORT CODE</dt>
            <dd>{{ quote.renewal_import_code }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">DEVICE</dt>
            <dd>{{ quote.device }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PRICE</dt>
            <dd>{{ quote.premium }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">TRANSACTION APPROVED AT</dt>
           <dd>{{ dateFormat(quote.transaction_approved_at) }}</dd>
          </div>
        </dl>
      </div>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="flex justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">Entity Profile</h3>
        <x-tag color="success" v-if="quote.kyc_decision === 'Complete'">
          KYC - Complete
        </x-tag>
        <x-tag color="amber" v-else> KYC - Pending </x-tag>
      </div>
      <x-divider class="mb-4 mt-1" />
      <x-form @submit="updateProfileDetails" :auto-focus="false">
        <div class="text-sm">
          <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 break-words">
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">FIRST NAME</dt>
              <dd>{{ quote.first_name }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">LAST NAME</dt>
              <dd>{{ quote.last_name }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">MOBILE NUMBER</dt>
              <dd>{{ quote.mobile_no }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">EMAIL</dt>
              <dd>{{ quote.email }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">COMPANY NAME</dt>
              <dd>{{ customerProfileForm.company_name }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">TRADE LICENSE NO</dt>
              <dd>
                <x-input
                  v-model="customerProfileForm.trade_license_no"
                  placeholder="TRADE LICENSE NO"
                  type="text"
                  class="w-full"
                />
                <x-button
                  @click.prevent="searchByTradeLicense"
                  size="xs"
                  color="primary"
                  class="mt-1"
                  :loading="loader.tradeSearch"
                  v-if="readOnlyMode.isDisable === true"
                >
                  Search
                </x-button>
              </dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">EMIRATES OF REGISTRATION</dt>
              <dd>
                <ComboBox
                  v-model="customerProfileForm.emirate_of_registration_id"
                  :single="true"
                  placeholder="SELECT EMIRATES OF REGISTRATION"
                  :options="emiratesOptions"
                  class="w-full"
                />
              </dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">COMPANY ADDRESS</dt>
              <dd>
                <x-input
                  v-model="customerProfileForm.company_address"
                  placeholder="COMPANY ADDRESS"
                  type="text"
                  class="w-full"
                />
              </dd>
            </div>

            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">INDUSTRY TYPE</dt>
              <dd>
                <x-select
                  v-model="customerProfileForm.industry_type_code"
                  :options="companyTypeOptions"
                  placeholder="SELECT COMPANY TYPE"
                  class="w-full"
                />
              </dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">ENTITY TYPE</dt>
              <dd>
                <ComboBox
                  @update:modelValue="entityTypeChange($event)"
                  :single="true"
                  v-model:modelValue="customerProfileForm.entity_type_code"
                  :options="companyConcernOptions"
                  placeholder="SELECT COMPANY CONCERN"
                  class="w-full"
                />
              </dd>
            </div>
            <EntityRiskRatingScoreDetails
              :quote="quote"
              :modelType="'business'"
            />
          </dl>
          <div class="flex justify-end">
            <x-button
              v-if="isProfileUpdateAllow"
              class="mt-4"
              color="emerald"
              size="sm"
              :loading="customerProfileForm.processing"
              type="submit"
            >
              Update Profile
            </x-button>
          </div>
        </div>
      </x-form>
    </div>
    <x-modal v-model="getParentEntityModel" size="lg" show-close backdrop>
      <h3 class="font-semibold text-center text-lg mb-10">
        Search Entity by Parent Entity Trade License No
      </h3>
      <dl class="grid md:grid-cols-1 gap-x-6 gap-y-4">
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Parent Entity Trade License No</dt>
          <dd>
            <x-input
              v-model="customerProfileForm.trade_license_no"
              placeholder="TRADE LICENSE NO"
              type="text"
              class="w-full"
            />
          </dd>
        </div>
      </dl>
      <div class="flex justify-end">
        <x-button
          class="mt-4"
          color="primary"
          size="sm"
          :loading="customerProfileForm.processing"
          @click.prevent="searchByTradeLicense('SubEntity')"
          v-if="readOnlyMode.isDisable === true"
        >
          Search
        </x-button>
      </div>
    </x-modal>
    <x-modal v-model="entityDetailsFound" size="lg" show-close backdrop>
      <h3 class="font-semibold text-center text-lg mb-10">
        Entity found with the entered Trade License number
      </h3>
      <dl class="grid md:grid-cols-1 gap-x-6 gap-y-4">
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Trade License No</dt>
          <dd>
            <x-input
              v-model="tradeLicenseEntity.trade_license"
              placeholder="TRADE LICENSE NO"
              type="text"
              class="w-full"
              disabled
            />
          </dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Company Name</dt>
          <dd>
            <x-input
              v-model="tradeLicenseEntity.company_name"
              placeholder="Company Name"
              type="text"
              class="w-full"
              disabled
            />
          </dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Company Address</dt>
          <dd>
            <x-input
              v-model="tradeLicenseEntity.company_address"
              placeholder="Company Address"
              type="text"
              class="w-full"
              disabled
            />
          </dd>
        </div>
        <div class="text-left space-x-4">
          <x-button
            size="sm"
            color="orange"
            @click.prevent="linkEntity"
            :loading="loader.tradeDetail"
          >
            Link
          </x-button>
        </div>
      </dl>
    </x-modal>

    <UBODetails
      v-if="quote.customer_type == page.props.customerTypeEnum.Entity"
      :quote="quote"
      :UBOsDetails="UBOsDetails"
      :nationalities="nationalities"
      :UBORelations="UBORelations"
      quote_type="Business"
    />

    <!-- Additional Contact -->
    <CustomerAdditionalContacts
      quoteType="Business"
      :customerId="quote.customer_id"
      :quoteId="quote.id"
      :contacts="customerAdditionalContacts"
      :quoteEmail="quote.email"
      :quoteMobile="quote.mobile_no"
    />

    <LastYearPolicyDetail
      v-if="
        quote.source == $page.props.leadSource.RENEWAL_UPLOAD ||
        quote.source == $page.props.leadSource.INSLY
      "
      modelType="Business"
      :quote="quote"
      :insly-id="quoteDetails?.insly_id"
      :canAddBatchNumber="canAddBatchNumber"
    />

    <QuoteDocuments
      :document-types="documentTypes"
      :quote-documents="quote.documents || []"
      :storageUrl="storageUrl"
      :quote="quote"
      :insly-id="quoteDetails?.insly_id"
    />

    <div class="p-4 rounded shadow mb-6 bg-primary-50/25">
      <div>
        <h3 class="font-semibold text-primary-800 text-lg">Lead Status</h3>
        <x-divider class="mb-4 mt-1" />
      </div>
      <div class="flex flex-wrap md:flex-nowrap gap-6 w-full">
        <div class="w-full md:w-1/2">
          <div class="flex flex-col gap-4">
            <x-select
              v-model="leadStatusForm.leadStatus"
              label="Status"
              :options="leadStatusOptions"
              :disabled="
                quote.quote_status_id == quoteStatusEnum.TransactionApproved
              "
              placeholder="Lead Status"
              class="w-full"
            />
            <x-textarea
              v-model="leadStatusForm.notes"
              type="text"
              label="Notes"
              placeholder="Lead Notes"
              class="w-full"
              :disabled="
                quote.quote_status_id == quoteStatusEnum.TransactionApproved
              "
            />
          </div>
        </div>
        <div class="w-full md:w-2/3">
          <x-select
            v-if="leadStatusForm.leadStatus == quoteStatusEnum.Lost"
            v-model="leadStatusForm.lostReason"
            label="Lost Reason"
            :options="
              lostReasons?.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            placeholder="Lost Reason is required"
            class="w-full"
            :error="leadStatusForm.errors.lostReason"
          />

          <x-field label="Transaction Type">
            <x-input
              type="text"
              :value="quote.transaction_type_text"
              class="w-full"
              :disabled="true"
            />
          </x-field>
        </div>
      </div>
      <div class="flex justify-end">
        <x-button
          class="mt-4"
          color="emerald"
          size="sm"
          :loading="leadStatusForm.processing"
          @click.prevent="onLeadStatus"
          :disabled="
            quote.quote_status_id == quoteStatusEnum.TransactionApproved
          "
          v-if="readOnlyMode.isDisable === true"
        >
          Change Status
        </x-button>
      </div>
    </div>
    <PlanDetails
      :insuranceProviders="insuranceProviders"
      :quote="quote"
      :quoteType="page.props.quoteType"
      :vatPrice="vatPercentage"
    />

    <MigratePayment
      v-if="!isNewPaymentStructure"
      :quoteId="quote.id"
      :paymentCode="quote.code"
      :quoteType="page.props.quoteType"
      :payments="quote.payments"
    />
    <PaymentTableNew
      v-if="isNewPaymentStructure"
      :quoteType="page.props.quoteType"
      :payments="quote.payments"
      :paymentDocument="
        documentTypes.filter(
          item =>
            item.code === 'GMQPD' ||
            item.code === 'GMQPDR' ||
            item.code === 'GMQDPDR',
        )
      "
      :quoteRequest="quote"
      :paymentStatusEnum="paymentStatusEnum"
      :paymentTooltipEnum="paymentTooltipEnum"
      :paymentMethods="
        paymentMethods.map(pm => {
          return { value: pm.code, label: pm.name, tooltip: pm.tool_tip };
        })
      "
      :storageUrl="storageUrl"
      quoteSubType="Group Medical"
      :isAmlClearedForPayment="isAmlClearedForPayment"
    />

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div>
        <h3 class="font-semibold text-primary-800 text-lg">Lead History</h3>
        <x-divider class="mb-4 mt-1" />
      </div>
      <div v-if="historyData === null" class="text-center py-3">
        <x-button
          size="sm"
          color="primary"
          outlined
          @click.prevent="onLoadHistoryData"
          :loading="historyLoading"
        >
          Load History Data
        </x-button>
      </div>
      <DataTable
        v-else
        table-class-name="compact"
        :headers="historyDataTable"
        :items="historyData || []"
        border-cell
        hide-rows-per-page
        :rows-per-page="15"
        :hide-footer="historyData.length < 15"
      />
    </div>

    <AuditLogs
      :type="'App\\Models\\BusinessQuote'"
      :id="$page.props.quote.id"
    />
  </div>
</template>
