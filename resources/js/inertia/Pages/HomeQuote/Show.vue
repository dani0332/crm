<script setup>
const props = defineProps({
  quote: Object,
  quoteType: String,
  quoteTypeId: Number,
  leadStatuses: Array,
  advisors: Array,
  activities: Array,
  customerAdditionalContacts: Array,
  lostReasons: Array,
  modelType: String,
  notProductionApproval: Boolean,
  allowedDuplicateLOB: Array,
  can: Object,
  isBetaUser: Boolean,
  payments: Array,
  quoteRequest: Object,
  permissions: Object,
  paymentMethods: Object,
  insuranceProviders: Array,
  embeddedProducts: Array,
  customerTypeEnum: Object,
  nationalities: Array,
  memberRelations: Array,
  membersDetails: Array,
  industryType: Object,
  UBORelations: Array,
  UBOsDetails: Array,
  canAddBatchNumber: Boolean,
  quoteDocuments: Object,
  documentTypes: Object,
  noteDocumentType: Object,
  storageUrl: String,
  quoteNotes: Object,
  cdnPath: String,
  vatPercentage: Number,
  paymentTooltipEnum: Object,
  bookPolicyDetails: Array,
  isNewPaymentStructure: Boolean,

  sendUpdateOptions: Array,
  sendUpdateLogs: Array,
  hasPolicyIssuedStatus: Boolean,
  linkedQuoteDetails: Object,
  lockLeadSectionsDetails: Object,
  paymentDocument: Array,
  amlStatusName: String,
});

const page = usePage();
const { isRequired } = useRules();
const notification = useNotifications('toast');
const hasAnyRole = roles => useHasAnyRole(roles);
const rolesEnum = page.props.rolesEnum;
const hasRole = role => useHasRole(role);
const permissionEnum = page.props.permissionsEnum;
const canAny = permissions => useCanAny(permissions);
const paymentStatusEnum = page.props.paymentStatusEnum;
const quoteStatusEnum = page.props.quoteStatusEnum;
const can = permission => useCan(permission);
const modelClass = 'App\\Models\\HomeQuote';

const countDays = computed(() =>
  useDaysSinceStale(props.quoteRequest?.stale_at),
);
const compareDueDate = useCompareDueDate;

const modals = reactive({
  duplicate: false,
  activity: false,
  activityConfirm: false,
});

const allowStatusUpdate = computed(() => {
  if (canAny([permissionEnum.SUPER_LEAD_STATUS_CHANGE])) {
    if (props.quote.quote_status_id == quoteStatusEnum.PolicyBooked) {
      return true;
    }
    return false;
  }
  return (
    (props.quote.quote_status_id == quoteStatusEnum.TransactionApproved ||
      props.quote.quote_status_id == quoteStatusEnum.Lost) ??
    false
  );
});

const leadDuplicateForm = useForm({
  modelType: 'home',
  parentType: 'home',
  entityId: page.props.quote.id,
  entityCode: page.props.quote.code,
  entityUId: page.props.quote.uid,
  lob_team: [],
  lob_team_sub_selection: null,
});

const openDuplicate = () => {
  modals.duplicate = true;
  leadDuplicateForm.reset();
};

const onCreateDuplicate = isValid => {
  if (!isValid) return;
  leadDuplicateForm.post('/quotes/createDuplicate', {
    preserveScroll: true,
    onSuccess: () => {
      notification.success({
        title: 'Quote duplicated successfully',
        position: 'top',
      });
    },
    onFinish: () => {
      modals.duplicate = false;
    },
  });
};

const confirmDeleteData = reactive({
  activity: null,
  contact: null,
});

const contactLoader = ref(false),
  activityActionEdit = ref(false),
  historyLoading = ref(false);

const rules = {
  isRequired: v => !!v || 'This field is required',
};

const industryTypeOptions = computed(() => {
  return page.props.industryType.map(indType => ({
    value: indType.code,
    label: indType.text,
  }));
});

const advisorOptions = computed(() => {
  return page.props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.name,
  }));
});

const leadStatusOptions = computed(() => {
  return page.props.leadStatuses.map(status => ({
    value: status.id,
    label: status.text,
  }));
});

const emiratesOptions = computed(() => {
  return page.props.emirates.map(em => ({
    value: em.id,
    label: em.text,
  }));
});

const leadStatusForm = useForm({
  modelType: 'Home',
  leadId: page.props.quote.id,
  quote_uuid: page.props.quote.uuid,
  assigned_to_user_id: page.props.quote.advisor_id,
  leadStatus: page.props.quote.quote_status_id || null,
  notes: page.props.quote.notes || null,
  lostReason: page.props.quote.lost_reason_id || null,
});

const onLeadStatus = () => {
  leadStatusForm.post(
    route('updateLeadStatus', {
      modelType: 'Home',
      QuoteUId: page.props.quote.id,
    }),
    {
      preserveScroll: true,
      onSuccess: response => {
        router.reload({ only: ['quoteRequest'] });
      },
      onError: errors => {
        notification.error({ title: errors.value, position: 'top' });
      },
    },
  );
};

//activities
const activityTable = [
  { text: 'Client Name', value: 'client_name' },
  { text: 'Lead Status', value: 'quote_status.text' },
  { text: 'Title', value: 'title' },
  { text: 'Followup Date', value: 'due_date' },
  { text: 'Assigned To', value: 'assignee' },
  { text: 'Done', value: 'status', width: 60, align: 'center' },
  { text: 'Action', value: 'action' },
];

const activityForm = useForm({
  entityUId: page.props.quote.uuid,
  entityId: page.props.quote.id,
  modelType: 'Home',
  parentType: 'Home',
  quoteType: 2,
  title: null,
  description: null,
  due_date: null,
  assignee_id: page.props?.auth?.user?.id,
  status: null,
  activity_id: null,
  uuid: null,
});

const addActivity = () => {
  activityForm.reset();
  activityActionEdit.value = false;
  modals.activity = true;
};

const onActivityStatusUpdate = id => {
  activityForm.activity_id = id;
  activityForm.post(route('activities.updateStatus'), {
    preserveScroll: true,
    onSuccess: () => {
      notification.success({
        title: 'Lead Activity Done',
        position: 'top',
      });
    },
  });
};

const activityEdit = data => {
  activityActionEdit.value = true;
  modals.activity = true;
  activityForm.activity_id = data.id;
  activityForm.uuid = data.uuid;
  activityForm.title = data.title;
  activityForm.description = data.description;
  activityForm.due_date = data.due_date
    ? data.due_date.split(' ')[0].split('-').reverse().join('-') +
      'T' +
      data.due_date.split(' ')[1]
    : null;
  activityForm.assignee_id = data.assignee_id;
  activityForm.status = data.status;
};

const onActivitySubmit = isValid => {
  if (!isValid) return;
  if (activityActionEdit.value) {
    activityForm.post(route('activities.update.activity', activityForm.uuid), {
      preserveScroll: true,
      onSuccess: () => {
        notification.success({
          title: 'Activity Updated',
          position: 'top',
        });
      },
      onFinish: () => {
        modals.activity = false;
      },
    });
  } else {
    activityForm.post(route('activities.create.activity'), {
      preserveScroll: true,
      onSuccess: () => {
        activityForm.reset();
      },
      onFinish: () => {
        modals.activity = false;
      },
    });
  }
};

const activityDelete = id => {
  modals.activityConfirm = true;
  confirmDeleteData.activity = id;
};

const activityDeleteConfirmed = () => {
  router.post(
    route('activities.destroy', confirmDeleteData.activity),
    {
      isInertia: true,
      quote_uuid: page.props.quote.uuid,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        notification.error({
          title: 'Activity Deleted',
          position: 'top',
        });
      },
      onFinish: () => {
        modals.activityConfirm = false;
      },
    },
  );
};

// history data
const historyData = ref(null);

const onLoadHistoryData = async () => {
  historyLoading.value = true;
  const res = await fetch(
    route('getLeadHistory', {
      modelType: 'home',
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

const dateToYMD = date => {
  if (date) {
    const d = new Date(date);
    const year = d.getFullYear();
    const month = `0${d.getMonth() + 1}`.slice(-2);
    const day = `0${d.getDate()}`.slice(-2);
    return `${year}-${month}-${day}`;
  }
  return '';
};

const policyDetails = useForm({
  premium: page.props.quote.premium,
  policy_number: page.props.quote.policy_number || '',
  policy_start_date: dateToYMD(page.props.quote.policy_start_date),
  policy_expiry_date: dateToYMD(page.props.quote.policy_expiry_date) || '',
  policy_issuance_date: dateToYMD(page.props.quote.policy_issuance_date) || '',
  quote_status_id: page.props.quote.quote_status_id,
  canEdit:
    page.props.quote.quote_status_id == quoteStatusEnum.TransactionApproved &&
    page.props.notProductionApproval,
  editMode: false,
  modelType: page.props.modelType,
  quote_id: page.props.quote.id,
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

  insured_first_name: page.props.quote.insured_first_name || '',
  insured_last_name: page.props.quote.insured_last_name || '',
  emirates_id_number: page.props.quote.emirates_id_number || null,
  emirates_id_expiry_date: page.props.quote.emirates_id_expiry_date || null,

  entity_id: page.props.quote.entity_id ?? null,
  trade_license_no: page.props.quote.trade_license_no ?? null,
  company_name: page.props.quote.company_name ?? null,
  company_address: page.props.quote.company_address ?? null,
  entity_type_code: page.props.quote.entity_type_code ?? 'Parent',
  industry_type_code: page.props.quote.industry_type_code ?? null,
  emirate_of_registration_id:
    page.props.quote.emirate_of_registration_id ?? null,
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

const searchByTradeLicense = trigger => {
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
    });
};

const linkEntity = () => {
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
    });
};
const readOnlyMode = reactive({
  isDisable: true,
});
onMounted(() => {
  readOnlyMode.isDisable = !can(permissionEnum.All_QUOTES_VIEWONLY_ACCESS);
});

const sectionExpanded = computed(() => !page.props.hasPolicyIssuedStatus);
const getDetailPageRoute = (uuid, quote_type_id) =>
  useGetShowPageRoute(uuid, quote_type_id, null);

watch(
  () => page.props.quote.quote_status_id,
  (newValue, oldValue) => {
    if (newValue !== oldValue) {
      leadStatusForm.leadStatus = newValue;
    }
  },
);

const [LeadEditBtnTemplate, LeadEditBtnReuseTemplate] =
  createReusableTemplate();
const [StatusUpdateButtonTemplate, StatusUpdateButtonReuseTemplate] =
  createReusableTemplate();

const isAddUpdate = ref(false);
const onAddUpdate = () => {
  isAddUpdate.value = true;
};
</script>

<template>
  <div>
    <Head title="Home Detail" />
    <StickyHeader>
      <template v-slot:header>
        <h2 class="text-xl font-semibold">Home Detail</h2>
        <p
          class="bg-red-600 px-2 py-1 rounded text-sm text-white"
          v-if="countDays !== false"
        >
          Stale for {{ countDays }}
        </p>
      </template>
      <template #default v-if="readOnlyMode.isDisable === true">
        <LeadNotes
          :documentType="noteDocumentType"
          :notes="quoteNotes"
          :modelType="modelType"
          :quote="quote"
          :cdn="cdnPath"
        />
        <Link
          v-if="quote?.insly_id"
          :href="`/legacy-policy/${quote.insly_id}`"
          preserve-scroll
        >
          <x-button size="sm" color="#ff5e00" tag="div">
            View Legacy policy
          </x-button>
        </Link>
        <x-button size="sm" color="#ff5e00" @click.prevent="openDuplicate">
          Duplicate Lead
        </x-button>

        <Link :href="route('home.index')" preserve-scroll>
          <x-button size="sm" color="primary" tag="div"> Home List </x-button>
        </Link>

        <LeadEditBtnTemplate v-slot="{ isDisabled }">
          <Link v-if="!isDisabled" :href="route('home.edit', quote.uuid)">
            <x-button size="sm" tag="div">Edit</x-button>
          </Link>
          <x-button v-else :disabled="isDisabled" size="sm" tag="div"
            >Edit</x-button
          >
        </LeadEditBtnTemplate>

        <x-tooltip
          v-if="lockLeadSectionsDetails.lead_details"
          placement="bottom"
        >
          <LeadEditBtnReuseTemplate :isDisabled="true" />
          <template #tooltip
            >This lead is now locked as the policy has been booked. If changes
            are needed, go to 'Send Update', select 'Add Update', and choose
            'Correction of Policy'</template
          >
        </x-tooltip>
        <LeadEditBtnReuseTemplate v-else />
      </template>
    </StickyHeader>

    <x-modal
      v-model="modals.duplicate"
      title="Duplicate Lead"
      size="md"
      show-close
      backdrop
      is-form
      @submit="onCreateDuplicate"
    >
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
          :rules="[rules.isRequired]"
          placeholder="Select LOB For Duplication"
          class="w-full"
          multiple
        />
        <x-select
          v-model="leadDuplicateForm.lob_team_sub_selection"
          label="Reason"
          :rules="[rules.isRequired]"
          class="w-full"
          :options="[
            { value: 'new_enquiry', label: 'New enquiry' },
            { value: 'record_only', label: 'Record purposes only' },
          ]"
        />
      </div>
      <template #secondary-action>
        <x-button ghost tabindex="-1" @click="modals.duplicate = false"
          >Cancel</x-button
        >
      </template>
      <template #primary-action>
        <x-button
          color="orange"
          type="submit"
          :loading="leadDuplicateForm.processing"
        >
          Create Duplicate
        </x-button>
      </template>
    </x-modal>
    <div class="p-4 rounded shadow mt-6 mmmbmb-6 bg-white">
      <Collapsible :expanded="sectionExpanded">
        <template #header>
          <div class="flex justify-between items-center flex-wrap gap-2"></div>
        </template>
        <template #body>
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
                  <x-tooltip placement="bottom">
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
                <dt class="font-medium">COMPANY NAME</dt>
                <dd>{{ quote.home_company_name }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">COMPANY ADDRESS</dt>
                <dd>{{ quote.home_company_address }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">AML STATUS</dt>
                <dd>{{ amlStatusName ?? '' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">ADVISOR</dt>
                <dd>{{ quote.advisor_id_text }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CREATED DATE</dt>
                <dd>{{ quote.created_at }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">SOURCE</dt>
                <dd>{{ quote.source }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">LAST MODIFIED DATE</dt>
                <dd>{{ quote.updated_at }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <div>
                  <x-tooltip placement="bottom">
                    <label
                      class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-700"
                    >
                      PARENT REF-ID
                    </label>
                    <template #tooltip> Parent Reference ID </template>
                  </x-tooltip>
                </div>
                <div>
                  <Link
                    v-if="quote.parent_duplicate_quote_id"
                    :href="
                      getDetailPageRoute(
                        linkedQuoteDetails.uuid,
                        linkedQuoteDetails.quote_type_id,
                      )
                    "
                    class="text-primary-500 hover:underline"
                  >
                    {{ quote.parent_duplicate_quote_id ?? '' }}
                  </Link>
                </div>
              </div>
              <div
                class="grid sm:grid-cols-2"
                v-if="linkedQuoteDetails.childLeadsCount == 1"
              >
                <div>
                  <x-tooltip placement="bottom">
                    <label
                      class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-700"
                    >
                      CHILD REF-ID
                    </label>
                    <template #tooltip>
                      The Child Reference ID acts as an individual identifier
                      for dependents under the main lead. It's our way of
                      efficiently organizing and accessing each person's records
                      within the system.
                    </template>
                  </x-tooltip>
                </div>
                <div>
                  <Link
                    :href="
                      getDetailPageRoute(
                        linkedQuoteDetails.childLeadsUuid,
                        linkedQuoteDetails.quote_type_id,
                      )
                    "
                    class="text-primary-500 hover:underline"
                  >
                    {{ linkedQuoteDetails.childLeads ?? '' }}
                  </Link>
                </div>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">RENEWAL BATCH</dt>
                <dd>{{ quote.renewal_batch }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">RENEWAL IMPORT CODE</dt>
                <dd>{{ quote.renewal_import_code }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">DEVICE</dt>
                <dd>{{ quote.device }}</dd>
              </div>
            </dl>
          </div>

          <div class="mt-6">
            <h3 class="font-semibold text-primary-800">Quote Details</h3>
            <x-divider class="mb-4 mt-1" />
          </div>

          <div class="text-sm">
            <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 break-words">
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">I AM</dt>
                <dd>{{ quote.iam_possesion_type_id_text }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">I LIVE IN</dt>
                <dd>{{ quote.ilivein_accommodation_type_id_text }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">HAS CONTENTS</dt>
                <dd>{{ quote.has_contents ? 'Yes' : 'No' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CONTENTS AED</dt>
                <dd>{{ quote.contents_aed }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">HAS BUILDING</dt>
                <dd>{{ quote.has_building ? 'Yes' : 'No' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">BUILDING AED</dt>
                <dd>{{ quote.building_aed }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">HAS PERSONAL BELONGINGS</dt>
                <dd>{{ quote.has_personal_belongings ? 'Yes' : 'No' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">PERSONAL BELONGINGS AED</dt>
                <dd>{{ quote.personal_belongings_aed }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CURRENTLY INSURED WITH</dt>
                <dd>{{ quote.currently_insured_with_id_text }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">TYPE OF PLAN</dt>
                <dd>{{ quote.plan_id }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">NEXT FOLLOWUP DATE</dt>
                <dd>{{ quote.next_followup_date }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">DETAILS</dt>
                <dd>{{ quote.details }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">TRANSACTION APPROVED AT</dt>
                <dd>{{ quote.transaction_approved_at }}</dd>
              </div>
            </dl>
          </div>
        </template>
      </Collapsible>
    </div>

    <div class="p-4 rounded shadow mb-6 mt-6 bg-white">
      <Collapsible :expanded="sectionExpanded">
        <template #header>
          <div class="flex justify-between items-center">
            <h3 class="font-semibold text-primary-800 text-lg">
              {{
                quote.customer_type == page.props.customerTypeEnum.Individual
                  ? 'Customer '
                  : 'Entity '
              }}
              Profile
            </h3>
          </div>
        </template>
        <template #body>
          <x-divider class="my-4" />
          <div class="flex mb-4 justify-end">
            <x-tag color="success" v-if="quote.kyc_decision === 'Complete'">
              KYC - Complete
            </x-tag>
            <x-tag color="amber" v-else> KYC - Pending </x-tag>
          </div>

          <x-form @submit="updateProfileDetails" :auto-focus="false">
            <div class="text-sm">
              <dl
                v-if="
                  quote.customer_type === page.props.customerTypeEnum.Individual
                "
                class="grid md:grid-cols-2 gap-x-6 gap-y-4 break-words"
              >
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">FIRST NAME</dt>
                  <dd>{{ quote.first_name }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">LAST NAME</dt>
                  <dd>{{ quote.last_name }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">INSURED FIRST NAME</dt>
                  <dd>
                    <x-input
                      v-model="customerProfileForm.insured_first_name"
                      :rules="[isRequired]"
                      placeholder="INSURED FIRST NAME"
                      class="w-full"
                      :disabled="!isProfileUpdateAllow"
                    />
                  </dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">INSURED LAST NAME</dt>
                  <dd>
                    <x-input
                      v-model="customerProfileForm.insured_last_name"
                      :rules="[isRequired]"
                      placeholder="INSURED LAST NAME"
                      class="w-full"
                      :disabled="!isProfileUpdateAllow"
                    />
                  </dd>
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
                  <dt class="font-medium">NATIONALITY</dt>
                  <dd>{{ quote.nationality_id_text }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">DATE OF BIRTH</dt>
                  <dd>{{ quote.dob }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">RECEIVE MARKETING UPDATES</dt>
                  <dd>{{ quote.receive_marketing_updates ? 'Yes' : 'No' }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">EMIRATES ID NUMBER</dt>
                  <dd>
                    <x-input
                      v-model="customerProfileForm.emirates_id_number"
                      :rules="[isRequired]"
                      placeholder="EMIRATES ID NUMBER"
                      class="w-full"
                      :disabled="!isProfileUpdateAllow"
                    />
                  </dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">EMIRATES ID EXPIRY DATE</dt>
                  <dd>
                    <DatePicker
                      v-model="customerProfileForm.emirates_id_expiry_date"
                      :rules="[isRequired]"
                      placeholder="EMIRATES ID EXPIRY DATE"
                      :disabled="!isProfileUpdateAllow"
                      :min-date="new Date()"
                    />
                  </dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">ADDRESS</dt>
                  <dd>{{ quote.address }}</dd>
                </div>

                <RiskRatingScoreDetails :quote="quote" :modelType="quoteType" />
              </dl>
              <dl
                v-if="
                  quote.customer_type === page.props.customerTypeEnum.Entity
                "
                class="grid md:grid-cols-2 gap-x-6 gap-y-4 break-words"
              >
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
                  <dd class="break-words">
                    {{ customerProfileForm.company_name }}
                  </dd>
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
                    <ComboBox
                      :single="true"
                      v-model="customerProfileForm.industry_type_code"
                      placeholder="SELECT INDUSTRY TYPE"
                      :options="industryTypeOptions"
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
                      placeholder="SELECT ENTITY TYPE"
                      :options="[
                        { label: 'Parent', value: 'Parent' },
                        { label: 'Sub Entity', value: 'SubEntity' },
                      ]"
                      class="w-full"
                    />
                  </dd>
                </div>
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
        </template>
      </Collapsible>
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
      <template #actions>
        <x-button
          color="primary"
          size="sm"
          :loading="customerProfileForm.processing"
          @click.prevent="searchByTradeLicense('SubEntity')"
          v-if="readOnlyMode.isDisable === true"
        >
          Search
        </x-button>
      </template>
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
            v-if="readOnlyMode.isDisable === true"
          >
            Link
          </x-button>
        </div>
      </dl>
      <template #actions>
        <x-button size="sm" color="orange" @click.prevent="linkEntity">
          Link
        </x-button>
      </template>
    </x-modal>

    <MemberDetails
      v-if="quote.customer_type == page.props.customerTypeEnum.Individual"
      :quote="quote"
      :membersDetails="membersDetails"
      :nationalities="nationalities"
      :memberRelations="memberRelations"
      :quote_type="modelType"
      :expanded="sectionExpanded"
    />

    <UBODetails
      v-if="quote.customer_type == page.props.customerTypeEnum.Entity"
      :quote="quote"
      :UBOsDetails="UBOsDetails"
      :nationalities="nationalities"
      :UBORelations="UBORelations"
      :quote_type="modelType"
      :expanded="sectionExpanded"
    />

    <CustomerAdditionalContacts
      quoteType="Home"
      :customerId="quote.customer_id"
      :quoteId="quote.id"
      :contacts="customerAdditionalContacts"
      :quoteEmail="quote.email"
      :quoteMobile="quote.mobile_no"
      :expanded="sectionExpanded"
    />

    <LastYearPolicyDetail
      v-if="
        quote.source == $page.props.leadSource.RENEWAL_UPLOAD ||
        quote.source == $page.props.leadSource.INSLY
      "
      modelType="Home"
      :quote="quote"
      :insly-id="quote?.insly_id"
      :canAddBatchNumber="canAddBatchNumber"
      :expanded="sectionExpanded"
    />

    <div class="p-4 rounded shadow mb-6 bg-white">
      <Collapsible :expanded="sectionExpanded">
        <template #header>
          <div>
            <h3 class="font-semibold text-primary-800 text-lg">Lead Status</h3>
          </div>
        </template>
        <template #body>
          <x-divider class="my-4" />
          <div class="flex flex-wrap md:flex-nowrap gap-6 w-full">
            <div class="w-full md:w-50">
              <div class="flex flex-col gap-4">
                <x-select
                  v-model="leadStatusForm.leadStatus"
                  :options="leadStatusOptions"
                  :disabled="
                    allowStatusUpdate || lockLeadSectionsDetails.lead_status
                  "
                  placeholder="Lead Status"
                  class="w-full"
                  label="Status"
                  filterable
                />

                <x-select
                  v-if="leadStatusForm.leadStatus == quoteStatusEnum.Lost"
                  label="Lost Reason"
                  v-model="leadStatusForm.lostReason"
                  :options="
                    lostReasons?.map(item => ({
                      value: item.id,
                      label: item.text,
                    }))
                  "
                  placeholder="Lost Reason is required"
                  class="w-full"
                  :error="leadStatusForm.errors.lostReason"
                  :disabled="
                    allowStatusUpdate || lockLeadSectionsDetails.lead_status
                  "
                />
                <x-textarea
                  v-model="leadStatusForm.notes"
                  type="text"
                  label="Notes"
                  placeholder="Lead Notes"
                  class="w-full"
                  :disabled="
                    allowStatusUpdate || lockLeadSectionsDetails.lead_status
                  "
                />
              </div>
            </div>
          </div>
          <StatusUpdateButtonTemplate v-slot="{ isDisabled }">
            <x-button
              class="mt-4"
              color="emerald"
              size="sm"
              :loading="leadStatusForm.processing"
              @click.prevent="onLeadStatus"
              :disabled="allowStatusUpdate || isDisabled"
              v-if="readOnlyMode.isDisable === true"
            >
              Change Status
            </x-button>
          </StatusUpdateButtonTemplate>
          <div class="flex justify-end">
            <x-tooltip
              v-if="lockLeadSectionsDetails.lead_status"
              placement="bottom"
            >
              <StatusUpdateButtonReuseTemplate :isDisabled="true" />
              <template #tooltip>
                The lead status cannot be manually updated once it has reached
                'Transaction Approved'
              </template>
            </x-tooltip>
            <StatusUpdateButtonReuseTemplate v-else />
          </div>
        </template>
      </Collapsible>
    </div>

    <PlanDetails
      :insuranceProviders="insuranceProviders"
      :quote="quote"
      :quoteType="quoteType"
      :expanded="sectionExpanded"
      :vatPrice="vatPercentage"
      :isAddUpdate="isAddUpdate"
    />

    <MigratePayment
      v-if="!isNewPaymentStructure"
      :quoteId="quote.id"
      :paymentCode="quote.code"
      :quoteType="quoteType"
      :payments="payments"
    />
    <PaymentTableNew
      v-if="isNewPaymentStructure"
      :quoteType="quoteType"
      :payments="payments"
      :paymentDocument="paymentDocument"
      :proformaPayment="
        payments.find(
          item =>
            item.payment_methods_code ===
            page.props.paymentMethodsEnum.ProformaPaymentRequest,
        )
      "
      :quoteRequest="quoteRequest"
      :paymentStatusEnum="paymentStatusEnum"
      :paymentTooltipEnum="paymentTooltipEnum"
      :paymentMethods="
        paymentMethods.map(pm => {
          return { value: pm.code, label: pm.name, tooltip: pm.tool_tip };
        })
      "
      :storageUrl="storageUrl"
      :bookPolicyDetails="bookPolicyDetails"
      :expanded="sectionExpanded"
    />
    <PaymentTable
      v-else
      :payments="payments"
      :can="can"
      :isBetaUser="isBetaUser"
      :quoteRequest="quoteRequest"
      :paymentMethods="paymentMethods"
      :insuranceProviders="insuranceProviders"
      :quote="quote"
    />

    <EmbeddedProducts
      :data="embeddedProducts"
      :link="quote.uuid"
      :code="quote.code"
      :quote="quote"
      :modelType="quoteType"
      :expanded="sectionExpanded"
    />

    <PolicyDetail
      v-if="permissions.isQuoteDocumentEnabled"
      :quote="quote"
      modelType="home"
      :expanded="sectionExpanded"
      :policyIssuanceStatus="policyIssuanceStatus"
      :payments="payments"
    />

    <QuoteDocument
      :document-types="documentTypes"
      :quote-documents="quoteDocuments || []"
      :storageUrl="storageUrl"
      :quote="quote"
      :insly-id="quote?.insly_id"
      :expanded="sectionExpanded"
      :bookPolicyDetails="bookPolicyDetails"
    />

    <BookPolicy
      v-if="
        canAny([
          permissionEnum.VIEW_INSLY_BOOK_POLICY,
          permissionEnum.SEND_INSLY_BOOK_POLICY,
          permissionEnum.VIEW_ALL_LEADS,
        ])
      "
      :quote="quote"
      quoteType="home"
      :modelClass="modelClass"
      :bookPolicyDetails="bookPolicyDetails"
      :payments="payments"
      :expanded="sectionExpanded"
    />

    <SendUpdates
      v-if="hasPolicyIssuedStatus"
      :reportable="quote"
      :quote_type_id="$page.props.quoteTypeId"
      :options="sendUpdateOptions"
      :data="sendUpdateLogs"
      @onAddUpdate="onAddUpdate"
    />

    <div class="p-4 rounded shadow mb-6 bg-white">
      <Collapsible :expanded="sectionExpanded">
        <template #header>
          <div class="flex justify-between items-center">
            <h3 class="font-semibold text-primary-800 text-lg">
              Lead Activities
              <x-tag size="sm">{{ activities.length || 0 }}</x-tag>
            </h3>
          </div>
        </template>
        <template #body>
          <x-divider class="my-4" />
          <div class="flex justify-end mb-4">
            <x-button
              size="sm"
              color="orange"
              @click.prevent="addActivity"
              v-if="readOnlyMode.isDisable === true"
            >
              Add Activity
            </x-button>
          </div>
          <DataTable
            table-class-name="compact"
            :headers="activityTable"
            :items="activities"
            border-cell
            hide-rows-per-page
            :rows-per-page="15"
            :hide-footer="activities.length < 15"
          >
            <template #item-status="{ status, id }">
              <x-checkbox
                color="emerald"
                size="xl"
                :modelValue="status === 1"
                :disabled="status === 1"
                @change="onActivityStatusUpdate(id)"
              />
            </template>
            <template #item-action="item">
              <div class="space-x-4">
                <x-button
                  size="xs"
                  color="primary"
                  outlined
                  :disabled="item.status === 1"
                  @click.prevent="activityEdit(item)"
                  v-if="readOnlyMode.isDisable === true"
                >
                  Edit
                </x-button>
                <x-button
                  size="xs"
                  color="error"
                  :disabled="item.status === 1"
                  outlined
                  @click.prevent="activityDelete(item.id)"
                  v-if="
                    readOnlyMode.isDisable === true &&
                    item.user_id &&
                    item.user_id != null
                  "
                  :key="item.user_id"
                >
                  Delete
                </x-button>
              </div>
            </template>
          </DataTable>
        </template>
      </Collapsible>
      <x-modal
        v-model="modals.activity"
        :title="`${activityActionEdit ? 'Edit' : 'Add'} Lead Activity`"
        size="lg"
        show-close
        backdrop
        is-form
        @submit="onActivitySubmit"
      >
        <div class="grid gap-4">
          <x-input
            v-model="activityForm.title"
            label="Title*"
            :rules="[rules.isRequired]"
            class="w-full"
          />

          <x-textarea
            v-model="activityForm.description"
            label="Description"
            :adjust-to-text="false"
            class="w-full"
          />

          <x-select
            v-model="activityForm.assignee_id"
            label="Assignee*"
            :options="advisorOptions"
            :rules="[rules.isRequired]"
            placeholder="Select Assignee"
            class="w-full"
          />

          <DatePicker
            v-model="activityForm.due_date"
            withTime
            :rules="[rules.isRequired]"
            label="Due Date*"
          />
        </div>

        <template #secondary-action>
          <x-button
            ghost
            tabindex="-1"
            size="sm"
            @click.prevent="modals.activity = false"
          >
            Cancel
          </x-button>
        </template>
        <template #primary-action>
          <x-button
            size="sm"
            color="emerald"
            :loading="activityForm.processing"
            type="submit"
          >
            {{ activityActionEdit ? 'Update' : 'Save' }}
          </x-button>
        </template>
      </x-modal>
      <x-modal
        v-model="modals.activityConfirm"
        title="Delete Activity"
        show-close
        backdrop
      >
        <p>Are you sure you want to delete this activity?</p>
        <template #actions>
          <div class="text-right space-x-4">
            <x-button
              size="sm"
              ghost
              @click.prevent="modals.activityConfirm = false"
            >
              Cancel
            </x-button>
            <x-button
              size="sm"
              color="error"
              :loading="activityForm.processing"
              @click.prevent="activityDeleteConfirmed"
            >
              Delete
            </x-button>
          </div>
        </template>
      </x-modal>
    </div>
    <div class="p-4 rounded shadow mb-6 bg-white">
      <Collapsible :expanded="sectionExpanded">
        <template #header>
          <div>
            <h3 class="font-semibold text-primary-800 text-lg">Lead History</h3>
          </div>
        </template>
        <template #body>
          <x-divider class="my-4" />
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
        </template>
      </Collapsible>
    </div>

    <AuditLogs
      :type="modelClass"
      :id="$page.props.quote.id"
      :quoteType="$page.props.modelType"
      :quoteCode="$page.props.quote.code"
      :expanded="sectionExpanded"
    />

    <lead-raw-data
      :modelType="'Home'"
      :code="$page.props.quote.code"
    ></lead-raw-data>
  </div>
</template>
