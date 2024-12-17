<script setup>
import QuotePolicy from '@/inertia/Pages/PersonalQuote/Partials/QuotePolicy.vue';
import LazyAvailablePlan from './Partials/AvailablePlans.vue';
import LazyCreatePlan from './Partials/CreatePlan.vue';
import LazyDocumentUploader from './Partials/DocumentUploader.vue';

defineProps({
  quote: Object,
  lostReasons: Array,
  advisors: Array,
  leadStatuses: Array,
  quoteStatusEnum: Object,
  carPlanFeaturesCode: Object,
  carPlanExclusionsCode: Object,
  carPlanAddonsCode: Object,
  payments: Array,
  quoteRequest: Object,
  can: Object,
  paymentMethods: Object,
  listQuotePlans: {
    type: [Array, String],
    default: () => [],
  },
  quoteDocuments: Object,
  sendPolicy: Boolean,
  cdnPath: String,
  activities: Array,
  documentTypes: Object,
  customerAdditionalContacts: Array,
  ecomCarInsuranceQuoteUrl: String,
  paymentTooltipEnum: Object,
  paymentStatusEnum: Object,
  storageUrl: String,
});

const page = usePage();
const notification = useToast();
const hasRole = role => useHasRole(role);
const hasPermission = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

const fixedValue = number => {
  if (number == Math.floor(number)) {
    return number;
  } else {
    return number.toFixed(2);
  }
};

const calculateVAT = addonOption => {
  if (addonOption.isSelected === true && addonOption.price !== 0) {
    totalVAT.value += addonOption.price + addonOption.vat;
  }
};

const modals = reactive({
  doc: false,
  docConfirm: false,
  plan: false,
  createPlan: false,
  activity: false,
  activityConfirm: false,
  addContact: false,
  contactDeleteConfirm: false,
  contactPrimaryConfirm: false,
});

const advisorOptions = computed(() => {
  return page.props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.name,
  }));
});

const confirmDeleteData = reactive({
  docs: null,
  member: null,
  activity: null,
  contact: null,
});

const confirmData = reactive({
  contactPrimary: null,
});

const activityActionEdit = ref(false),
  selectedPlan = ref(null),
  selectedPlans = ref([]),
  exportLoader = ref(false),
  toggleLoader = ref(false),
  contactLoader = ref(false),
  historyLoading = ref(false),
  isDisabled = ref(false),
  totalVAT = ref(false);

const { copy, copied } = useClipboard();

const { isRequired, isEmail, isNumber, isMobile } = useRules();

const onCopyText = text => {
  copy(text);
  if (copied)
    notification.success({
      title: 'Link copied to clipboard',
      position: 'top',
    });
};

const leadStatusOptions = computed(() => {
  return page.props.leadStatuses.map(status => ({
    value: status.id,
    label: status.text,
  }));
});

const leadStatusForm = useForm({
  modelType: 'Car',
  leadId: page.props.quote.id,
  quote_uuid: page.props.quote.uuid,
  assigned_to_user_id: page.props.quote.advisor_id,
  leadStatus: page.props.quote.quote_status_id || null,
  notes: page.props.quote.notes || null,
  lostReason: page.props.quote.lost_reason_id || null,
  isInertia: true,
});

const onLeadStatus = () => {
  leadStatusForm.post(`/quotes/car/${page.props.quote.id}/update-lead-status`, {
    preserveScroll: true,
    onError: errors => {
      console.log(errors);
    },
    onSuccess: () => {
      notification.success({
        title: 'Lead Status Updated',
        position: 'top',
      });
    },
  });
};

// plans
const plansTable = reactive({
  isLoading: false,
  columns: [
    {
      text: 'Provider Name',
      value: 'providerName',
    },
    {
      text: 'Plan Name',
      value: 'name',
    },
    {
      text: 'Repair Type',
      value: 'repairType',
    },
    {
      text: 'Insurer Quote No.',
      value: 'insurerQuoteNo',
    },
    {
      text: 'TPL Limit',
      value: 'tplLimit',
    },
    {
      text: 'Car Trim',
      value: 'insurerTrimText',
    },
    {
      text: 'PAB cover',
      value: 'pab_cover',
    },
    {
      text: 'Roadside assistance',
      value: 'roadside_assistance',
    },
    {
      text: 'Oman cover TPL',
      value: 'oman_cover_tpl',
    },
    {
      text: 'Actual Premium',
      value: 'actualPremium',
    },
    {
      text: 'Discounted Premium',
      value: 'discountPremium',
    },
    {
      text: 'Premium with VAT.',
      value: 'total',
    },
    {
      text: 'Excess',
      value: 'excess',
    },
    {
      text: 'Action',
      value: 'action',
    },
  ],
});

const planClicked = plan => {
  selectedPlan.value = plan;
  modals.plan = true;
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
      '/api/v1/quotes/car/export-plans-pdf',
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

const onTogglePlans = toggle => {
  toggleLoader.value = true;

  const planIds = useArrayUnique(
    selectedPlans.value.map(p => {
      return p.id;
    }),
  ).value;

  axios
    .post('/quotes/car/manual-plan-toggle', {
      modelType: 'Car',
      planIds: planIds,
      quote_uuid: page.props.quote.uuid,
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
    });
};

const onCreatePlan = () => {
  router.reload({
    preserveState: true,
    preserveScroll: true,
    only: ['listQuotePlans'],
    onStart: () => {
      modals.createPlan = false;
    },
    onFinish: () => {
      notification.success({
        title: 'Plan Created',
        position: 'top',
      });
    },
  });
};

const onPlanError = () => {
  modals.createPlan = false;
  notification.error({
    title: 'Plan Creation Failed',
    position: 'top',
  });
};

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
    {
      text: 'Action',
      value: 'action',
    },
  ],
});

const onDocDelete = name => {
  modals.docConfirm = true;
  confirmDeleteData.docs = name;
};

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
  modelType: 'Car',
  parentType: 'Car',
  quoteType: 3,
  title: null,
  description: null,
  due_date: null,
  assignee_id: page.props?.auth?.user?.id,
  status: null,
  activity_id: null,
  uuid: null,
  isInertia: true,
  is_car_revival: true,
});

const addActivity = () => {
  activityForm.reset();
  activityActionEdit.value = false;
  modals.activity = true;
};

const onActivityStatusUpdate = id => {
  activityForm.activity_id = id;
  activityForm.post(`/activities/updateStatus`, {
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

// additional contact

const additionalContactTable = [
  { text: 'Type', value: 'key' },
  { text: 'Value', value: 'value' },
  { text: 'Created At', value: 'created_at' },
  { text: 'Action', value: 'action' },
];

const additionalContact = useForm({
  id: null,
  additional_contact_type: null,
  additional_contact_val: null,
  quote_id: page.props.quote.id,
  customer_id: page.props.quote.customer_id,
  quote_type: 'car',
});

const onAdditionalContactSubmit = isValid => {
  if (!isValid) return;
  additionalContact
    .transform(data => ({
      ...data,
      isInertia: true,
    }))
    .post(`/customer-additional-contact/add`, {
      preserveScroll: true,
      onError: errors => {
        notification.error({
          title: errors.error || 'Data not saved',
          position: 'top',
        });
      },
      onSuccess: () => {
        additionalContact.reset();
        notification.success({
          title: 'Additional Contact Added',
          position: 'top',
        });
      },
      onFinish: () => {
        modals.addContact = false;
      },
    });
};

const additionalContactPrimary = data => {
  modals.contactPrimaryConfirm = true;
  confirmData.contactPrimary = data;
};

const additionalContactPrimaryConfirmed = () => {
  const isEmail = confirmData.contactPrimary.key === 'email';
  router.post(
    `/customer-additional-contact/${
      isEmail ? confirmData.contactPrimary.id : 0
    }/make-primary`,
    {
      isInertia: true,
      quote_id: page.props.quote.id,
      key: confirmData.contactPrimary.key,
      value: confirmData.contactPrimary.value,
      quote_type: 'car',
    },
    {
      preserveScroll: true,
      onBefore: () => {
        contactLoader.value = true;
      },
      onSuccess: () => {
        notification.success({
          title: 'Primary Contact Updated',
          position: 'top',
        });
      },
      onFinish: () => {
        contactLoader.value = false;
        modals.contactPrimaryConfirm = false;
      },
    },
  );
};

// history data
const historyData = ref(null);

const onLoadHistoryData = async () => {
  historyLoading.value = true;
  const res = await fetch(
    `/quotes/getLeadHistory?modelType=car&recordId=${page.props.quote.id}`,
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
</script>
<template>
  <div>
    <Head title="Car Revival Detail" />
    <div class="flex justify-between items-center flex-wrap gap-2">
      <h2 class="text-xl font-semibold">Car Revival Detail</h2>
      <div class="flex gap-2">
        <Link href="/quotes/revival" preserve-scroll>
          <x-button size="sm" color="primary" tag="div">
            Car Revival List
          </x-button>
        </Link>

        <Link :href="`${quote.uuid}/edit`">
          <x-button size="sm" tag="div">Edit</x-button>
        </Link>
      </div>
    </div>

    <x-divider class="my-4" />

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CDB ID</dt>
            <dd>{{ quote.code }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CREATED DATE</dt>
            <dd>{{ quote.created_at }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">ADVISOR</dt>
            <dd>{{ quote.advisor_id_text }}</dd>
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
            <dt class="font-medium">PARENT CDB ID</dt>
            <dd>{{ quote.parent_duplicate_quote_id }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">IS ECOMMERCE</dt>
            <dd>{{ quote.is_ecommerce ? 'Yes' : 'No' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">BATCH</dt>
            <dd>{{ quote.quote_batch_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">LOST REASON</dt>
            <dd>{{ quote.lost_reason }}</dd>
          </div>
        </dl>
      </div>

      <div class="mt-6">
        <h3 class="font-semibold text-primary-800">Customer Profile</h3>
        <x-divider class="mb-4 mt-1" />
      </div>

      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
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
            <dt class="font-medium">NATIONALITY</dt>
            <dd>{{ quote.nationality_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">DATE OF BIRTH</dt>
            <dd>{{ quote.dob }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">EMIRATE OF REGISTRATION</dt>
            <dd>{{ quote.emirate_of_registration_id_text }}</dd>
          </div>
        </dl>
      </div>

      <div class="mt-6">
        <h3 class="font-semibold text-primary-800">Quote Details</h3>
        <x-divider class="mb-4 mt-1" />
      </div>

      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">UAE LISENCE HELD FOR</dt>
            <dd>{{ quote.uae_license_held_for_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">HOME COUNTRY DRIVING LISENCE HELD FOR</dt>
            <dd>{{ quote.back_home_license_held_for_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CURRENTLY INSURED WITH</dt>
            <dd>{{ quote.currently_insured_with_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CAR MAKE</dt>
            <dd>{{ quote.car_make_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CAR MODEL</dt>
            <dd>{{ quote.car_model_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CYLINDER</dt>
            <dd>{{ quote.cylinder }}</dd>
          </div>
          <!--                    <div class="grid sm:grid-cols-2">-->
          <!--                        <dt class="font-medium">TRIM</dt>-->
          <!--                        <dd>{{ quote.currently_insured_with_id_text }}</dd>-->
          <!--                    </div>-->
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CAR MODEL YEAR</dt>
            <dd>{{ quote.year_of_manufacture_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">FIRST REGISTRATION DATE</dt>
            <dd>{{ quote.year_of_first_registration }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CAR VALUE</dt>
            <dd>{{ quote.car_value }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CAR VALUE (AT ENQUIRY)</dt>
            <dd>{{ quote.car_value_tier }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">VEHICLE TYPE</dt>
            <dd>{{ quote.vehicle_type_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">SEAT CAPACITY</dt>
            <dd>{{ quote.seat_capacity }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">TYPE OF CAR INSURANCE</dt>
            <dd>{{ quote.car_type_insurance_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">NEXT FOLLOWUP DATE</dt>
            <dd>{{ quote.next_followup_date }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CLAIM HISTORY</dt>
            <dd>{{ quote.claim_history_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">ADVISOR ASSIGN DATE</dt>
            <dd>{{ quote.advisor_assigned_date }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">LEAD COST</dt>
            <dd>{{ quote.cost_per_lead }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">UPDATED BY</dt>
            <dd>{{ quote.updated_by }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">ADDITIONAL NOTES</dt>
            <dd>{{ quote.additional_notes }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">ADVISOR/PROMO CODE</dt>
            <dd>{{ quote.promo_code }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CALCULATED VALUE</dt>
            <dd>{{ quote.calculated_value }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CREATED BY</dt>
            <dd>{{ quote.created_by }}</dd>
          </div>
          <!--                    <div class="grid sm:grid-cols-2">-->
          <!--                        <dt class="font-medium">CAN YOU PROVIDE NO-CLAIM LETTER FROM YOUR PREVIOUS INSURERS?</dt>-->
          <!--                        <dd>{{ quote.details }}</dd>-->
          <!--                    </div>-->
        </dl>
      </div>
      <div class="mt-6">
        <h3 class="font-semibold text-primary-800">
          Last Year's Policy Details
        </h3>
        <x-divider class="mb-4 mt-1" />
      </div>

      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Renewal Batch#</dt>
            <dd>{{ quote.renewal_batch }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PREVIOUS POLICY NUMBER</dt>
            <dd>{{ quote.previous_quote_policy_number }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PREVIOUS POLICY PREMIUM</dt>
            <dd>{{ quote.previous_quote_policy_premium }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PREVIOUS POLICY EXPIRY DATE</dt>
            <dd>{{ quote.previous_policy_expiry_date }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Previous Import Code</dt>
            <!--                        <dd>{{ quote.previous_quote_policy_number }}</dd>-->
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Policy Number</dt>
            <dd>{{ quote.policy_number }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Policy Expiry Date</dt>
            <dd>{{ quote.policy_expiry_date }}</dd>
          </div>
        </dl>
      </div>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div>
        <h3 class="font-semibold text-primary-800 text-lg">Lead Status</h3>
        <x-divider class="mb-4 mt-1" />
      </div>
      <div class="flex flex-wrap md:flex-nowrap gap-6 w-full">
        <div class="w-full md:w-2/3">
          <x-textarea
            v-model="leadStatusForm.notes"
            type="text"
            label="Notes"
            placeholder="Lead Notes"
            class="w-full"
            :disabled="quote.quote_status_id == 15"
          />
        </div>
        <div class="w-full md:w-1/3">
          <div class="flex flex-col gap-4">
            <x-select
              v-model="leadStatusForm.leadStatus"
              label="Status"
              :options="leadStatusOptions"
              :disabled="quote.quote_status_id == 15"
              placeholder="Lead Status"
              class="w-full"
            />
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
          </div>

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
            <dd>{{ quote.plan_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PROVIDER NAME</dt>
            <dd>{{ quote.car_plan_provider_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PAYMENT STATUS</dt>
            <dd>{{ quote.payment_status_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PAID AT</dt>
            <dd>{{ quote.paid_at }}</dd>
          </div>
          <!--                    <div class="grid sm:grid-cols-2">-->
          <!--                        <dt class="font-medium">NETWORK</dt>-->
          <!--                        <dd>{{ quote.network }}</dd>-->
          <!--                    </div>-->
        </dl>
      </div>
    </div>

    <!-- <PaymentTable
            v-if="hasRole(page.props.rolesEnum.BetaUser)"
            :payments="payments"
            :can="can"
            :isBetaUser="hasRole(page.props.rolesEnum.BetaUser)"
            :quoteRequest="quoteRequest"
            :paymentMethods="paymentMethods"
            :quote="quote"
        /> -->

    <PaymentTableNew
      quoteType="Car"
      :payments="payments"
      :paymentDocument="
        page.props.documentTypes?.filter(
          item =>
            item.code === 'CPD' ||
            item.code === 'CPDR' ||
            item.code === 'CDPDR',
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
    />

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="flex flex-wrap gap-4 justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">
          Available Plans
          <x-tag size="sm">{{ listQuotePlans.length || 0 }}</x-tag>
        </h3>
        <div class="flex flex-wrap gap-3">
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
            @click.prevent="onExportPlans"
            :loading="exportLoader"
          >
            Download PDF
          </x-button>

          <x-button
            v-if="listQuotePlans.length > 0"
            size="sm"
            color="orange"
            @click.prevent="onCopyText(ecomCarInsuranceQuoteUrl + quote.uuid)"
          >
            Copy Link
          </x-button>
        </div>
      </div>
      <DataTable
        v-model:items-selected="selectedPlans"
        table-class-name="tablefixed compact"
        :headers="plansTable.columns"
        :items="
          Array.isArray(listQuotePlans) && listQuotePlans.length > 0
            ? listQuotePlans
            : []
        "
        border-cell
        hide-rows-per-page
        :rows-per-page="15"
        :hide-footer="listQuotePlans.length < 15"
      >
        <template
          #item-providerName="{ providerName, isManualPlan, isDisabled }"
        >
          <p>{{ providerName }}</p>
          <div class="flex gap-1">
            <x-tag
              v-if="isManualPlan"
              size="xs"
              color="primary"
              class="mt-0.5 text-[10px]"
            >
              Manual Plan
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
        <template #item-repairType="{ repairType }">
          {{ repairType == 'COMP' ? 'NON-AGENCY' : repairType }}
        </template>

        <template #item-tplLimit="{ benefits }">
          <p v-for="features in benefits.feature">
            <span
              v-if="
                features.code ==
                  page.props.carPlanFeaturesCode.TPL_DAMAGE_LIMIT ||
                features.code == page.props.carPlanFeaturesCode.DAMAGE_LIMIT
              "
              >{{ features.value }}</span
            >
            <span v-else>
              <span
                v-if="
                  features.text.toString().toLowerCase() ==
                  page.props.carPlanFeaturesCode.TPL_DAMAGE_LIMIT_TEXT
                "
                >{{ features.value }}</span
              >
            </span>
          </p>
        </template>

        <template #item-pab_cover="{ addons }">
          <template v-if="addons">
            <template v-for="addon in addons">
              <p v-for="addonOptions in addon?.carAddonOption">
                <input type="hidden" :value="calculateVAT(addonOptions)" />
                <span
                  v-if="
                    addon.code.toString().toLowerCase() ==
                      page.props.carPlanAddonsCode.DRIVER_COVER ||
                    addon.code.toString().toLowerCase() ==
                      page.props.carPlanAddonsCode.PASSENGER_COVER
                  "
                >
                  {{ addon.text + ':' + addonOptions.value }}
                </span>
                <span
                  v-else-if="
                    addon.text.toString().toLowerCase() ==
                      page.props.carPlanAddonsCode.DRIVER_COVER_TEXT ||
                    addon.text.toString().toLowerCase() ==
                      page.props.carPlanAddonsCode.PASSENGER_COVER_TEXT
                  "
                  >{{ addon.text + ':' + addonOptions.value }}</span
                >
              </p>
            </template>
          </template>
        </template>

        <template #item-roadside_assistance="{ benefits }">
          <p v-for="roadside_assist in benefits.roadSideAssistance">
            {{ roadside_assist.text + ':' + roadside_assist.value }}
          </p>
        </template>

        <template #item-oman_cover_tpl="{ benefits }">
          <p v-for="oman_exclusion in benefits.exclusion">
            <span
              v-if="
                oman_exclusion.code.toString().toLowerCase() ==
                  page.props.carPlanExclusionsCode.TPL_OMAN_COVER.toString().toLowerCase() ||
                oman_exclusion.code.toString().toLowerCase() ==
                  page.props.carPlanExclusionsCode.OMAN_COVER.toString().toLowerCase()
              "
            >
              {{ oman_exclusion.text + ':' + oman_exclusion.value }}
            </span>
          </p>
          <p v-for="oman_inclusion in benefits.inclusion">
            <span
              v-if="
                oman_inclusion.code.toString().toLowerCase() ==
                  page.props.carPlanExclusionsCode.TPL_OMAN_COVER.toString().toLowerCase() ||
                oman_inclusion.code.toString().toLowerCase() ==
                  page.props.carPlanExclusionsCode.OMAN_COVER.toString().toLowerCase()
              "
            >
              {{ oman_inclusion.text + ':' + oman_inclusion.value }}
            </span>
          </p>
        </template>

        <template #item-actualPremium="{ actualPremium }">
          {{ fixedValue(actualPremium || 0) }}
        </template>
        <template #item-discountPremium="{ discountPremium, vat }">
          {{ fixedValue(discountPremium || 0) }}
        </template>
        <template #item-excess="{ excess }">
          {{ fixedValue(excess || 0) }}
        </template>

        <template #item-total="{ discountPremium, vat }">
          {{
            fixedValue((totalVAT || 0) + (vat || 0) + (discountPremium || 0))
          }}
        </template>

        <template #item-action="item">
          <div class="flex gap-2 pr-2">
            <x-button
              size="xs"
              color="primary"
              outlined
              @click.prevent="planClicked(item)"
            >
              View
            </x-button>
            <x-button
              size="xs"
              color="emerald"
              outlined
              @click.prevent="
                onCopyText(
                  ecomCarInsuranceQuoteUrl +
                    quote.uuid +
                    `/payment/?providerCode=${item.providerCode}_${item.planCode}&planId=${item.id}`,
                )
              "
            >
              Copy
            </x-button>
          </div>
        </template>
      </DataTable>

      <x-modal
        v-model="modals.plan"
        size="xl"
        :title="`${selectedPlan?.providerName} - ${selectedPlan?.name}`"
        show-close
        backdrop
      >
        <LazyAvailablePlan :plan="selectedPlan" :quote="quote" />
      </x-modal>

      <x-modal
        v-model="modals.createPlan"
        size="lg"
        title="Create Car Quote"
        show-close
        backdrop
      >
        <LazyCreatePlan
          :uuid="quote.uuid"
          @success="onCreatePlan"
          @error="onPlanError"
        />
      </x-modal>
    </div>

    <QuotePolicy :quote="quote" :quoteStatusEnum="quoteStatusEnum" />

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="flex justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">
          Documents
          <x-tag size="sm">{{ quoteDocuments.length || 0 }}</x-tag>
        </h3>
        <div class="flex gap-2">
          <x-button @click.prevent="modals.doc = true" size="sm" color="orange">
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
            :href="cdnPath + (item.watermarked_doc_url ?? item.doc_url)"
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

      <x-modal
        v-model="modals.doc"
        size="xl"
        title="Upload Documents"
        show-close
        backdrop
      >
        <LazyDocumentUploader
          :doc-types="documentTypes"
          :docs="quoteDocuments || []"
          :cdn="cdnPath"
        />
      </x-modal>
      <x-modal
        v-model="modals.docConfirm"
        title="Delete Document"
        show-close
        backdrop
      >
        <p>Are you sure you want to delete this document?</p>
        <template #actions>
          <div class="text-right space-x-4">
            <x-button
              size="sm"
              ghost
              @click.prevent="modals.docConfirm = false"
            >
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
              v-if="
                readOnlyMode.isDisable === true &&
                item.user_id &&
                item.user_id != null
              "
            >
              Delete
            </x-button>
          </div>
        </template>
      </DataTable>
      <x-modal
        v-model="modals.activity"
        size="lg"
        :title="`${activityActionEdit ? 'Edit' : 'Add'} Lead Activity`"
        show-close
        backdrop
        is-form
        @submit="onActivitySubmit"
      >
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
      <div class="flex flex-wrap gap-3 justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">
          Customer Additional Contacts
          <x-tag size="sm">{{ customerAdditionalContacts.length || 0 }}</x-tag>
        </h3>
        <x-button
          size="sm"
          color="orange"
          @click.prevent="
            additionalContact.reset();
            modals.addContact = true;
          "
        >
          Add Additional Contacts
        </x-button>
      </div>

      <DataTable
        table-class-name="compact"
        :headers="additionalContactTable"
        :items="customerAdditionalContacts || []"
        border-cell
        hide-rows-per-page
        hide-footer
      >
        <template #item-key="{ key }">
          <span v-if="key === 'email'"> Email Address </span>
          <span v-else> Mobile Number </span>
        </template>
        <template #item-action="item">
          <x-button
            size="xs"
            color="emerald"
            outlined
            @click.prevent="additionalContactPrimary(item)"
          >
            Make Primary
          </x-button>
        </template>
      </DataTable>

      <x-modal
        v-model="modals.addContact"
        size="lg"
        title="Add Additional Contacts"
        show-close
        backdrop
        is-form
        @submit="onAdditionalContactSubmit"
      >
        <div class="grid gap-4">
          <x-select
            v-model="additionalContact.additional_contact_type"
            label="Type"
            :options="[
              { value: 'email', label: 'Email' },
              { value: 'mobile_no', label: 'Mobile Number' },
            ]"
            :rules="[isRequired]"
            placeholder="Select Type"
            class="w-full"
          />

          <x-input
            v-model="additionalContact.additional_contact_val"
            label="Value"
            :rules="[
              isRequired,
              additionalContact.additional_contact_type === 'email'
                ? isEmail
                : isNumber,
            ]"
            class="w-full"
          />
        </div>

        <template #secondary-action>
          <x-button ghost size="sm" @click.prevent="modals.addContact = false">
            Cancel
          </x-button>
        </template>
        <template #primary-action>
          <x-button
            size="sm"
            color="emerald"
            :loading="additionalContact.processing"
            type="submit"
          >
            Save
          </x-button>
        </template>
      </x-modal>

      <x-modal
        v-model="modals.contactPrimaryConfirm"
        title="Primary Additional Contact"
        show-close
        backdrop
      >
        <p>Are you sure you want to make this information as Primary?</p>
        <template #actions>
          <div class="text-right space-x-4">
            <x-button
              size="sm"
              ghost
              @click.prevent="modals.contactPrimaryConfirm = false"
            >
              Cancel
            </x-button>
            <x-button
              size="sm"
              color="emerald"
              @click.prevent="additionalContactPrimaryConfirmed"
              :loading="contactLoader"
            >
              Confirm
            </x-button>
          </div>
        </template>
      </x-modal>
    </div>

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
      :type="'App\\Models\\CarQuote'"
      :quoteType="$page.props.quoteType"
      :id="$page.props.quote.id"
      :quoteCode="$page.props.quote.code"
    />
  </div>
</template>
