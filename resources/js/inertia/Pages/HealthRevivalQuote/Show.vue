<script setup>
import LazyDocumentUploader from '../HealthQuote/Partials/DocumentUploader.vue';

const props = defineProps({
  quote: Object,
  customerTypeEnum: Array,
  genderOptions: Object,
  customerAdditionalContactsData: Array,
  memberCategories: Array,
  membersDetail: Array,
  memberRelations: Array,
  nationalities: Array,
  leadStatuses: Array,
  quoteType: String,
  ecomDetails: Object,
  storageUrl: String,
  quoteType: String,
  paymentTooltipEnum: Object,
  paymentStatusEnum: Array,
  quoteRequest: Object,
  documentTypes: Object,
  paymentMethods: Object,
  isAmlClearedForPayment: Boolean,
  payments: Array,
  quoteDocuments: Array,
  activities: Array,
  advisors: Array,
});

console.log('quote', props.quote);
const page = usePage();

const permissionsEnum = page.props.permissionsEnum;
const can = permission => useCan(permission);

const paymentStatusEnum = page.props.paymentStatusEnum;
const notification = useToast();
const hasRole = role => useHasRole(role);
const hasAnyRole = roles => useHasAnyRole(roles);
const rolesEnum = page.props.rolesEnum;

const { isRequired, isEmail, isNumber, isMobileNo } = useRules();
const dateFormat = date =>
  date ? useDateFormat(date, 'DD-MMM-YYYY').value : '-';

const assignSubteam = ref(page.props.quote.health_team_type || ''),
  assignLead = ref(null),
  memberActionEdit = ref(false),
  activityActionEdit = ref(false),
  selectedPlan = ref(null),
  selectedPlans = ref([]),
  exportLoader = ref(false),
  toggleLoader = ref(false),
  historyLoading = ref(false),
  isDisabled = ref(false);

//activities
const activityTable = [
  { text: 'Done', value: 'status', width: 60, align: 'center' },
  { text: 'Title', value: 'title' },
  { text: 'Client Name', value: 'client_name' },
  { text: 'Followup Date', value: 'due_date' },
  { text: 'Assigned To', value: 'assignee' },
  { text: 'Action', value: 'action' },
];

const activityForm = useForm({
  entityUId: page.props.quote.uuid,
  entityId: page.props.quote.id,
  modelType: 'Health',
  parentType: 'Health',
  quoteType: 3,
  title: null,
  description: null,
  due_date: null,
  assignee_id: null,
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
    activityForm.post(`/activities/${activityForm.uuid}/update`, {
      preserveScroll: true,
      onSuccess: () => {
        activityForm.reset();
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
    activityForm.post(`/activities/create-activity`, {
      preserveScroll: true,
      onSuccess: () => {
        activityForm.reset();
        notification.success({
          title: 'Activity Added',
          position: 'top',
        });
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
    `/activities/${confirmDeleteData.activity}/delete`,
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

//doc
const quoteDocumentsTable = reactive({
  isLoading: false,
  columns: [
    {
      text: 'Document Type',
      value: 'document_type_text',
    },
    {
      text: 'Document Name',
      value: 'original_name',
    },
    {
      text: 'Created At',
      value: 'created_at',
    },
    {
      text: 'Created By',
      value: 'created_by_name',
    },
  ],
});

const onAddMemberModal = () => {
  memberForm.reset();
  memberActionEdit.value = false;
  modals.member = true;
  memberForm.nationality_id = page.props.quote.nationality_id;
};

const memberFieldReq = reactive({
  nationality: false,
  dob: false,
});

const advisorOptions = computed(() => {
  return page.props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.name,
  }));
});

const memberForm = useForm({
  id: null,
  gender: null,
  dob: null,
  nationality_id: page.props.membersDetail.length
    ? null
    : page.props.quote.nationality_id,
  salary_band_id: null,
  emirate_of_your_visa_id: page.props.membersDetail.length
    ? null
    : page.props.quote.emirate_of_your_visa_id,
  member_category_id: null,
  quote_request_id: page.props.quote.id,
  update_lead_against_member: null,
  first_name: null,
  last_name: null,
  relation_code: null,
  quote_type: page.props.modelType,
  customer_id: page.props.quote.customer_id,
  customer_type: page.props.quote.customer_type,
  customer_member_id: null,
  quoteId: page.props.quote.uuid,
});

const nationalityOptions = computed(() => {
  return props.nationalities.map(nat => ({
    value: nat.id,
    label: nat.text,
  }));
});
const emiratesOptions = computed(() => {
  return page.props.emirates.map(em => ({
    value: em.id,
    label: em.text,
  }));
});
const memberCategoriesOptions = computed(() => {
  return page.props.memberCategories.map(cat => ({
    value: cat.id,
    label: cat.text,
  }));
});
const memberRelationOptions = computed(() => {
  return page.props.memberRelations.map(relation => ({
    value: relation.code,
    label: relation.text,
  }));
});
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
  planFilters: false,
});

const memberCategoryText = memberCategoryId =>
  computed(() => {
    return page.props.memberCategories.find(
      category => category.id === memberCategoryId,
    )?.text;
  });

const genderSelect = computed(() => {
  return Object.keys(page.props.genderOptions).map(status => ({
    value: status,
    label: page.props.genderOptions[status],
  }));
});

const memberDataDocs = membersDetail => {
  const data = membersDetail
    .map(member => ({
      id: member.id,
      name: memberCategoryText(member.member_category_id).value,
    }))
    .filter(member => member.name !== undefined);

  return data;
};

const salaryBandsOptions = computed(() => {
  return page.props.salaryBands.map(sal => ({
    value: sal.id,
    label: sal.text,
  }));
});

const memberDetailsTable = reactive({
  isLoading: false,
  columns: [
    {
      text: 'Member Name',
      value: 'first_name',
    },
    {
      text: 'Gender',
      value: 'gender',
    },
    {
      text: 'DOB',
      value: 'dob',
    },
    {
      text: 'Relation',
      value: 'relation',
    },
    {
      text: 'Nationality',
      value: 'nationality',
    },
    {
      text: 'Emirate of Visa',
      value: 'emirate',
    },
    {
      text: 'Member Category',
      value: 'member_category_id',
    },
    {
      text: 'Action',
      value: 'action',
    },
  ],
});
const fixedValue = number => {
  if (number == Math.floor(number)) {
    return number.toLocaleString();
  } else {
    return number.toLocaleString(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  }
};
const selectedProviderPlan = ref({
  id: page.props.quote.plan_id,
  planName: page.props.quote.health_plan_name_text,
  providerName: page.props.quote.plan_provider_name_text,
  premium: page.props.ecomDetails.priceWithVAT,
});

const onDocDelete = name => {
  modals.docConfirm = true;
  confirmDeleteData.docs = name;
};

const confirmDeleteData = reactive({
  docs: null,
  member: null,
  activity: null,
  contact: null,
});

const confirmDeleteDoc = () => {
  quoteDocumentsTable.isLoading = true;
  router.post(
    `/documents/delete`,
    {
      docName: confirmDeleteData.docs,
      quoteId: page.props.quote.id,
    },
    {
      preserveScroll: true,
      onFinish: () => {
        modals.docConfirm = false;
        quoteDocumentsTable.isLoading = false;
        notification.error({
          title: 'File Deleted',
          position: 'top',
        });
      },
    },
  );
};

const sendPolicyToClient = () => {
  if (confirm('Are you sure you want to send documents to customer?')) {
    let quoteType = page.props.modelType;
    let quoteUuId = page.props.quote.uuid;
    let url =
      '/quotes/' + quoteType + '/' + quoteUuId + '/send-policy-documents';
    axios.post(url).then(response => {
      if (response.status == 200) {
        notification.success({
          title: 'Documents Sent',
          position: 'top',
        });
      } else {
        notification.error({
          title: 'Documents Sending Failed',
          position: 'top',
        });
      }
    });
  }
};

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

const initialEditCategoryId = ref(null);
const previouslySelectedCategoryId = ref(null);

function onEditMember(data) {
  memberActionEdit.value = true;
  modals.member = true;

  memberForm.id = data.id;
  memberForm.gender = data.gender;
  memberForm.dob = data.dob;
  memberForm.nationality_id = data.nationality_id;
  memberForm.emirate_of_your_visa_id = data.emirate_of_your_visa_id;
  memberForm.member_category_id = data.member_category_id;
  memberForm.salary_band_id = data.salary_band_id;
  memberForm.first_name = data.first_name;
  memberForm.last_name = data.last_name;
  memberForm.relation_code = data.relation_code;
  memberForm.update_lead_against_member = data.index === 1;

  // set initialEditCategoryId to member_category_id when any member is edited
  initialEditCategoryId.value = data.member_category_id;

  // set previouslySelectedCategoryId for the refernece of initialEditCategoryId
  previouslySelectedCategoryId.value = initialEditCategoryId.value;
}

const membersDetailsUpdated = ref(false);

const onMemberSubmit = isValid => {
  if (memberForm.nationality_id == null) {
    memberFieldReq.nationality = true;
  } else {
    memberFieldReq.nationality = false;
  }
  if (memberForm.dob == null) {
    memberFieldReq.dob = true;
  } else {
    memberFieldReq.dob = false;
  }

  if (!isValid) return;
  if (memberActionEdit.value) {
    memberForm.put(`/health-quote-update-member`, {
      preserveScroll: true,
      onSuccess: () => {
        notification.success({
          title: 'Member Updated',
          position: 'top',
        });
        memberForm.reset();
        onLoadAvailablePlansData();
        // location.reload();
      },
      onError: errors => {
        notification.error({
          title: errors.error || 'Data not saved',
          position: 'top',
        });
      },
      onFinish: () => {
        modals.member = false;
        membersDetailsUpdated.value = true;
      },
    });
  } else {
    memberForm.post(`/health-quote-add-member`, {
      // new mavonic endpoint
      preserveScroll: true,
      onSuccess: () => {
        notification.success({
          title: 'Member Added',
          position: 'top',
        });
        onLoadAvailablePlansData();
        // location.reload();
      },
      onError: errors => {
        notification.error({
          title: errors.error || 'Data not saved',
          position: 'top',
        });
      },
      onFinish: () => {
        modals.member = false;
        membersDetailsUpdated.value = true;
      },
    });
  }
};

const onLeadStatus = () => {
  leadStatusForm.post(
    `/quotes/Health/${page.props.quote.id}/update-lead-status`,
    {
      preserveScroll: true,
      onError: errors => {
        notification.error({ title: errors.value, position: 'top' });
      },
      onSuccess: response => {
        const flash_messages = response.props.flash;
        if (!flash_messages) {
          notification.success({
            title: 'Lead Status Updated',
            position: 'top',
          });
        }
      },
    },
  );
};

const leadStatusOptions = computed(() => {
  return page.props.leadStatuses.map(status => ({
    value: status.id,
    label: status.text,
  }));
});

const leadStatusForm = useForm({
  modelType: 'Health',
  leadId: page.props.quote.id,
  quote_uuid: page.props.quote.uuid,
  assigned_to_user_id: page.props.quote.advisor_id,
  leadStatus: page.props.quote.quote_status_id || null,
  notes: page.props.quote.notes || null,
  trans_code: page.props.quote.transapp_code || null,
  lostReason: page.props.quote.lost_reason_id || null,
});

const genderText = gender =>
  computed(() => {
    return page.props.genderOptions[gender];
  });
</script>

<template>
  <div>
    <Head title="Health Revival Detail" />
    <div class="flex justify-between items-center flex-wrap gap-2">
      <h2 class="text-xl font-semibold">Health Revival Detail</h2>
      <div class="flex gap-2"></div>
    </div>

    <x-divider class="my-4" />

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 break-words">
          <div
            v-if="hasAnyRole([rolesEnum.Admin, rolesEnum.Engineering])"
            class="grid sm:grid-cols-2"
          >
            <dt class="font-medium">ID</dt>
            <dd>{{ props.quote.id }}</dd>
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
            <div>{{ props.quote.code }}</div>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CUSTOMER TYPE</dt>
            <dd>{{ props.quote.customer_type }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CREATED DATE</dt>
            <dd>{{ props.quote.created_at }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">SUBTEAM</dt>
            <dd>{{ props.quote.health_team_type }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">ADVISOR</dt>
            <dd>{{ props.quote.advisor_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">SOURCE</dt>
            <dd>{{ props.quote.source }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">LAST MODIFIED DATE</dt>
            <dd>{{ props.quote.updated_at }}</dd>
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
            <div>{{ props.quote.parent_duplicate_quote_id }}</div>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">IS ECOMMERCE</dt>
            <dd>{{ props.quote.is_ecommerce ? 'Yes' : 'No' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">IS EBP RENEWAL</dt>
            <dd>{{ props.quote.is_ebp_renewal ? 'Yes' : 'No' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">LOST REASON</dt>
            <dd>{{ props.quote.lost_reason }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">DEVICE</dt>
            <dd>{{ props.quote.device }}</dd>
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
            <dt class="font-medium">
              FOR WHOM DO YOU REQUIRE HEALTH INSURANCE?
            </dt>
            <dd>{{ props.quote.cover_for_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CURRENTLY INSURED WITH</dt>
            <dd>{{ props.quote.currently_insured_with_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">TYPE OF PLAN</dt>
            <dd>---</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">NEXT FOLLOWUP DATE</dt>
            <dd>{{ dateFormat(props.quote.next_followup_date) }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">DETAILS</dt>
            <dd>{{ props.quote.details }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">ADDITIONAL NOTES</dt>
            <dd>{{ props.quote.additional_notes }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">ENQUIRY COUNT</dt>
            <dd>{{ props.quote.enquiry_count }}</dd>
          </div>
        </dl>
      </div>
    </div>
    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="flex justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">
          {{
            quote.customer_type == page.props.customerTypeEnum.Individual
              ? 'Customer'
              : 'Entity '
          }}
          Profile
        </h3>
        <x-tag color="success" v-if="quote.kyc_decision === 'Complete'">
          KYC - Complete
        </x-tag>
        <x-tag color="amber" v-else> KYC - Pending </x-tag>
      </div>
      <x-divider class="mb-4 mt-1" />
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
              <dd class="break-words">{{ quote.email }}</dd>
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
              <dt class="font-medium">EMIRATE OF VISA</dt>
              <dd>{{ quote.emirate_of_your_visa_id_text }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">GENDER</dt>
              <dd>{{ genderText(quote.gender).value }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">MARITAL STATUS</dt>
              <dd>{{ quote.marital_status_id_text }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">SALARY BAND</dt>
              <dd>{{ quote.salary_band_id_text }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">MEMBER CATEGORY</dt>
              <dd>{{ quote.member_category_id_text }}</dd>
            </div>
            <RiskRatingScoreDetails :quote="quote" :modelType="quoteType" />
          </dl>
          <dl
            v-if="quote.customer_type === page.props.customerTypeEnum.Entity"
            class="grid md:grid-cols-2 gap-x-6 gap-y-4"
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
              <dd>{{ quote.email }}</dd>
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
    </div>

    <div
      v-if="quote.customer_type == page.props.customerTypeEnum.Individual"
      class="p-4 rounded shadow mb-6 bg-white"
    >
      <div class="flex justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">
          Member Details
          <x-tag size="sm">{{ membersDetail.length || 0 }}</x-tag>
        </h3>
        <x-button @click.prevent="onAddMemberModal" size="sm" color="orange">
          Add Member
        </x-button>
      </div>

      <DataTable
        table-class-name="tablefixed compact"
        :headers="memberDetailsTable.columns"
        :items="membersDetail || []"
        border-cell
        hide-rows-per-page
        hide-footer
      >
        <template #item-first_name="{ first_name, last_name }">
          {{ first_name + ' ' + (last_name == null ? '' : last_name) }}
        </template>
        <template #item-gender="{ gender }">
          {{ genderText(gender).value }}
        </template>
        <template #item-dob="{ dob }">
          {{ dateFormat(dob) }}
        </template>
        <template #item-relation="{ relation }">
          {{ relation?.text }}
        </template>
        <template #item-nationality="{ nationality }">
          {{ nationality?.text }}
        </template>
        <template #item-emirate="{ emirate }">
          {{ emirate?.text }}
        </template>
        <template #item-member_category_id="{ member_category_id }">
          {{ memberCategoryText(member_category_id).value }}
        </template>
        <template #item-action="item">
          <div class="flex gap-2">
            <x-button
              size="xs"
              color="primary"
              outlined
              @click.prevent="onEditMember(item)"
            >
              Edit
            </x-button>
            <x-button
              size="xs"
              color="error"
              outlined
              @click.prevent="memberDelete(item.id)"
            >
              Delete
            </x-button>
          </div>
        </template>
      </DataTable>

      <x-modal v-model="modals.member" size="lg" show-close backdrop>
        <template #header>
          {{ memberActionEdit ? 'Edit' : 'Add' }} Member
        </template>

        <x-form @submit="onMemberSubmit" :auto-focus="false">
          <div
            v-if="isManualPlansCount > 0"
            class="bg-red-100 border border-red-400 text-red-700 rounded-b px-4 py-3 shadow-md mb-4"
            role="alert"
          >
            <div class="flex">
              <div class="py-1">
                <svg
                  class="fill-current h-6 w-6 text-read-900 mr-4"
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 20 20"
                >
                  <path
                    d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z"
                  />
                </svg>
              </div>
              <div>
                <p class="font-bold">ALERT! Manual Plan(s) exists.</p>
                <p class="text-sm">
                  Please revist all manual plan(s) and update the per member
                  price
                </p>
              </div>
            </div>
          </div>
          <div class="grid md:grid-cols-2 gap-4 md:pb-16">
            <input type="hidden" :value="memberForm.id" />
            <x-input
              maxLength="60"
              v-model="memberForm.first_name"
              label="First Name"
              placeholder="First Name"
              :rules="[isRequired]"
            />
            <x-input
              maxLength="60"
              v-model="memberForm.last_name"
              label="Last Name"
              placeholder="Last Name"
              :rules="[isRequired]"
            />
            <ComboBox
              v-model="memberForm.nationality_id"
              label="Nationality"
              :options="nationalityOptions"
              placeholder="Select Nationality"
              :single="true"
              :hasError="memberFieldReq.nationality"
            />

            <x-select
              v-model="memberForm.emirate_of_your_visa_id"
              label="Emirate of Visa*"
              :options="emiratesOptions"
              :rules="[isRequired]"
              placeholder="Select Emirate of Visa"
              class="w-full"
            />

            <x-select
              v-model="memberForm.gender"
              label="Gender*"
              :options="genderSelect"
              :rules="[isRequired]"
              placeholder="Select Gender"
              class="w-full"
            />
            <DatePicker
              v-model="memberForm.dob"
              label="DOB*"
              :max-date="new Date()"
              :rules="[isRequired]"
              :hasError="memberFieldReq.dob"
            />
            <x-select
              v-model="memberForm.member_category_id"
              label="Member Category*"
              :options="memberCategoriesOptions"
              :rules="[isRequired]"
              placeholder="Select Member Category"
              class="w-full"
            />
            <x-select
              v-model="memberForm.relation_code"
              label="Relation"
              :options="memberRelationOptions"
              placeholder="Select Relation"
              class="w-full"
            />
            <x-select
              v-model="memberForm.salary_band_id"
              label="Salary Band"
              :options="salaryBandsOptions"
              placeholder="Select Salary Band"
              class="w-full"
            />
          </div>

          <div class="flex justify-end gap-3">
            <x-button size="sm" @click.prevent="modals.member = false">
              Cancel
            </x-button>

            <x-button
              size="sm"
              color="emerald"
              :loading="memberForm.processing"
              type="submit"
              class="px-6"
            >
              {{ memberActionEdit ? 'Update' : 'Save' }}
            </x-button>
          </div>
        </x-form>
      </x-modal>

      <!-- <x-modal v-model="modals.memberConfirm" show-close backdrop>
        <template #header> Delete Member Detail </template>
        <div
          v-if="isManualPlansCount > 0"
          class="w-full bg-red-100 border border-red-400 text-red-700 rounded-b px-4 py-3 shadow-md mb-4"
          role="alert"
        >
          <div class="flex">
            <div class="py-1">
              <svg
                class="fill-current h-6 w-6 text-read-900 mr-4"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
              >
                <path
                  d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z"
                />
              </svg>
            </div>
            <div>
              <p class="font-bold">ALERT! Manual Plan(s) exists.</p>
              <p class="text-sm">
                Please revist all manual plan(s) and update the per member price
              </p>
            </div>
          </div>
        </div>
        <p>Are you sure you want to delete this?</p>
        <template #actions>
          <div class="text-right space-x-4">
            <x-button
              size="sm"
              ghost
              @click.prevent="modals.memberConfirm = false"
            >
              Cancel
            </x-button>
            <x-button
              size="sm"
              color="error"
              @click.prevent="memberDeleteConfirmed"
              :loading="memberForm.processing"
            >
              Delete
            </x-button>
          </div>
        </template>
      </x-modal> -->
    </div>

    <CustomerAdditionalContacts
      :quoteType="quoteType"
      :customerId="quote.customer_id"
      :quoteId="quote.id"
      :contacts="customerAdditionalContactsData"
      :quoteEmail="quote.email"
      :quoteMobile="quote.mobile_no"
    />

    <LastYearPolicyDetail
      v-if="
        quote.source == $page.props.leadSource.RENEWAL_UPLOAD ||
        quote.source == $page.props.leadSource.INSLY
      "
      modelType="Health"
      :quote="quote"
      :insly-id="quote?.insly_id"
      :canAddBatchNumber="canAddBatchNumber"
    />

    <div class="p-4 rounded shadow mb-6 bg-primary-50/25">
      <div>
        <h3 class="font-semibold text-primary-800 text-lg">Lead Status</h3>
        <x-divider class="mb-4 mt-1" />
      </div>
      <div class="flex flex-wrap md:flex-nowrap gap-6 w-full">
        <div class="w-full md:w-50">
          <div class="flex flex-col gap-4">
            <x-select
              v-model="leadStatusForm.leadStatus"
              label="Status"
              :options="leadStatusOptions"
              :disabled="quote.quote_status_id == 15"
              placeholder="Lead Status"
              class="w-full"
            />
            <x-textarea
              v-model="leadStatusForm.notes"
              type="text"
              label="Notes"
              placeholder="Lead Notes"
              class="w-full"
              :disabled="quote.quote_status_id == 15"
            />
          </div>
        </div>
        <div class="w-full md:w-50">
          <div class="flex flex-col gap-4">
            <x-select
              v-if="leadStatusForm.leadStatus == 17"
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
            <x-field class="" label="Transaction Type">
              <x-input
                type="text"
                :value="quote.transaction_type_text"
                class="w-full"
                :disabled="true"
              />
            </x-field>
          </div>
        </div>
      </div>
      <x-divider class="mb-1 mt-10" />
      <div class="flex justify-end">
        <x-button
          class="mt-4"
          color="emerald"
          size="sm"
          :loading="leadStatusForm.processing"
          @click.prevent="onLeadStatus"
        >
          Change Status
        </x-button>
      </div>
    </div>
  </div>

  <div class="p-4 rounded shadow mb-6 bg-white">
    <div>
      <h3 class="font-semibold text-primary-800 text-lg">E-COM Details</h3>
      <x-divider class="mb-4 mt-1" />
    </div>
    <div class="text-sm">
      <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">PLAN NAME</dt>
          <dd>{{ selectedProviderPlan.planName }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">PROVIDER NAME</dt>
          <dd>{{ selectedProviderPlan.providerName }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">PAYMENT STATUS</dt>
          <dd>{{ quote.payment_status_text }}</dd>
        </div>
        <div
          class="grid sm:grid-cols-2"
          v-if="
            page.props.quote.payment_status_id == paymentStatusEnum.DECLINED
          "
        >
          <dt class="font-medium">REASON</dt>
          <dd>{{ mainPayment?.payment_status_message }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">PAID AT</dt>
          <dd>{{ ecomDetails.paidAt }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">NETWORK</dt>
          <dd>{{ ecomDetails.network }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">TOTAL PRICE (with VAT)</dt>
          <dd>{{ fixedValue(selectedProviderPlan.premium) }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">CO-PAY / CO-INSURANCE</dt>
          <dd>{{ coPayment ? coPayment.text : 'N/A' }}</dd>
        </div>
      </dl>
    </div>
  </div>
  <!-- plans -->

  <!-- payments -->

  <PaymentTableNew
    quoteType="Health"
    :payments="payments"
    :paymentDocument="
      documentTypes.QUOTE.filter(
        item =>
          item.code === 'HPD' || item.code === 'HPDR' || item.code === 'HDPDR',
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
    :eCommercePrice="ecomDetails.priceWithVAT ? ecomDetails.priceWithVAT : 0"
    :isAmlClearedForPayment="isAmlClearedForPayment"
  />

  <!-- QuoteDocuments -->
  <div class="p-4 rounded shadow mb-6 bg-white">
    <div class="flex justify-between items-center mb-4">
      <h3 class="font-semibold text-primary-800 text-lg">
        Documents
        <x-tag size="sm">{{ quoteDocuments.length || 0 }}</x-tag>
      </h3>
      <div class="flex gap-2">
        <Link
          v-if="quote?.insly_id && can(permissionsEnum.VIEW_LEGACY_DETAILS)"
          :href="`/legacy-policy/${quote.insly_id}`"
          preserve-scroll
        >
          <x-button size="sm" color="#ff5e00" tag="div">
            View Legacy policy
          </x-button>
        </Link>
        <x-button @click.prevent="modals.doc = true" size="sm" color="primary">
          Upload Documents
        </x-button>
        <x-button
          size="sm"
          color="red"
          v-if="sendPolicy"
          @click="sendPolicyToClient"
        >
          Send Policy
        </x-button>
      </div>
    </div>
    <DataTable
      table-class-name="compact"
      :headers="quoteDocumentsTable.columns"
      :items="quoteDocuments || []"
      border-cell
      hide-rows-per-page
      :rows-per-page="15"
      :hide-footer="quoteDocuments.length < 15"
    >
      <template #item-original_name="item">
        <a
          :href="cdnPath + item.doc_url"
          target="_blank"
          class="text-primary-600"
        >
          {{ item.original_name }}
        </a>
      </template>
      <template #item-action="{ doc_name }">
        <div>
          <x-button
            size="xs"
            color="error"
            outlined
            @click.prevent="onDocDelete(doc_name)"
          >
            Delete
          </x-button>
        </div>
      </template>
    </DataTable>

    <x-modal v-model="modals.doc" size="xl" show-close backdrop>
      <template #header> Upload Documents </template>
      <LazyDocumentUploader
        :members="memberDataDocs(membersDetail)"
        :doc-types="documentTypes"
        :docs="quoteDocuments || []"
        :cdn="cdnPath"
      />
    </x-modal>
    <x-modal v-model="modals.docConfirm" show-close backdrop>
      <template #header> Delete Document </template>
      <p>Are you sure you want to delete this document?</p>
      <template #actions>
        <div class="text-right space-x-4">
          <x-button size="sm" ghost @click.prevent="modals.docConfirm = false">
            Cancel
          </x-button>
          <x-button
            size="sm"
            color="error"
            @click.prevent="confirmDeleteDoc"
            :loading="quoteDocumentsTable.isLoading"
          >
            Delete
          </x-button>
        </div>
      </template>
    </x-modal>
  </div>

  <!-- Lead activities -->

  <div class="p-4 rounded shadow mb-6 bg-white">
    <div class="flex justify-between items-center mb-4">
      <h3 class="font-semibold text-primary-800 text-lg">
        Lead Activities
        <x-tag size="sm">{{ activities.length || 0 }}</x-tag>
      </h3>
      <x-button size="sm" color="orange" @click.prevent="addActivity">
        Add Activity
      </x-button>
    </div>
    <x-divider class="my-4" />

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
          >
            Edit
          </x-button>
          <x-button
            size="xs"
            color="error"
            :disabled="item.status === 1"
            outlined
            @click.prevent="activityDelete(item.id)"
          >
            Delete
          </x-button>
        </div>
      </template>
    </DataTable>
    <x-modal v-model="modals.activity" size="lg" show-close backdrop>
      <template #header>
        {{ activityActionEdit ? 'Edit' : 'Add' }} Lead Activity
      </template>

      <x-form @submit="onActivitySubmit" :auto-focus="false">
        <div class="grid gap-4">
          <x-input
            v-model="activityForm.title"
            label="Title"
            :rules="[isRequired]"
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
            label="Assignee"
            :options="advisorOptions"
            :rules="[isRequired]"
            placeholder="Select Assignee"
            class="w-full"
          />

          <date-picker
            v-model="activityForm.due_date"
            label="Due Date"
            :rules="[isRequired]"
            class="w-full"
            withTime
            :timezone="'UTC'"
          />
        </div>

        <div class="text-right space-x-4 mt-12">
          <x-button size="sm" @click.prevent="modals.activity = false">
            Cancel
          </x-button>

          <x-button
            size="sm"
            color="emerald"
            :loading="activityForm.processing"
            type="submit"
          >
            {{ activityActionEdit ? 'Update' : 'Save' }}
          </x-button>
        </div>
      </x-form>
    </x-modal>
    <x-modal v-model="modals.activityConfirm" show-close backdrop>
      <template #header> Delete Activity </template>
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
</template>
