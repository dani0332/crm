<script setup>
import { computed, ref, reactive, onMounted } from 'vue';
import { Head, usePage, router, useForm } from '@inertiajs/vue3';
import { useDateFormat, useClipboard } from '@vueuse/core';
import LazyDocumentUploader from './Partials/DocumentUploader.vue';
import LazyAvailablePlan from './Partials/AvailablePlans.vue';
import { useNotifications } from '@indielayer/ui';

defineProps({
  quote: Object,
  genderOptions: Object,
  leadStatuses: Array,
  ecomDetails: Object,
  membersDetail: Array,
  memberCategories: Array,
  salaryBands: Array,
  nationalities: Array,
  emirates: Array,
  advisors: Array,
  listQuotePlans: Array,
  quoteDocuments: Object,
  documentTypes: Object,
  cdnPath: String,
  ecomHealthInsuranceQuoteUrl: String,
  activities: Array,
  customerAdditionalContacts: Array,
});

const notification = useNotifications('toast');

const dateFormat = date => useDateFormat(date, 'DD/MM/YYYY');

const modals = reactive({
  member: false,
  memberConfirm: false,
  doc: false,
  docConfirm: false,
  plan: false,
  activity: false,
  activityConfirm: false,
  addContact: false,
});

const confirmDeleteData = reactive({
  docs: null,
  member: null,
  activity: null,
  contact: null,
});

const assignSubteam = ref(usePage().props.quote.health_team_type || ''),
  assignLead = ref(null),
  leadStatus = ref(usePage().props.quote.quote_status_id || null),
  leadNotes = ref(usePage().props.quote.notes),
  memberActionEdit = ref(false),
  activityActionEdit = ref(false),
  selectedPlan = ref(null),
  historyLoading = ref(false),
  isDisabled = ref(false);

const { copy, copied } = useClipboard();

const rules = {
  isRequired: v => !!v || 'Field is required',
};

const onCopyText = text => {
  copy(text);
  if (copied) notification.success('Link copied to clipboard');
};

const genderText = gender =>
  computed(() => {
    return usePage().props.genderOptions[gender];
  });

const memberCategoryText = memberCategoryId =>
  computed(() => {
    return usePage().props.memberCategories.find(
      category => category.id === memberCategoryId,
    )?.text;
  });

const goBack = () => {
  window.history.length > 2
    ? window.history.back()
    : router.get('/quotes/health');
};

const subTeamOptions = [
  { value: 'RM-NB', label: 'RM-NB' },
  { value: 'RM-Speed', label: 'RM-Speed' },
  { value: 'EBP', label: 'EBP' },
  { value: 'Wow-Call', label: 'Wow-Call' },
  { value: 'No-Type', label: 'No-Type' },
];

const advisorOptions = computed(() => {
  return usePage().props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.name,
  }));
});

const genderSelect = computed(() => {
  return Object.keys(usePage().props.genderOptions).map(status => ({
    value: status,
    label: usePage().props.genderOptions[status],
  }));
});

const leadStatusOptions = computed(() => {
  return usePage().props.leadStatuses.map(status => ({
    value: status.id,
    label: status.text,
  }));
});

const nationalityOptions = computed(() => {
  return usePage().props.nationalities.map(nat => ({
    value: nat.id,
    label: nat.text,
  }));
});

const memberCategoriesOptions = computed(() => {
  return usePage().props.memberCategories.map(cat => ({
    value: cat.id,
    label: cat.text,
  }));
});

const emiratesOptions = computed(() => {
  return usePage().props.emirates.map(em => ({
    value: em.id,
    label: em.text,
  }));
});

const salaryBandsOptions = computed(() => {
  return usePage().props.salaryBands.map(sal => ({
    value: sal.id,
    label: sal.text,
  }));
});

const onTeamAssign = () => {
  if (!assignSubteam.value) {
    notification.error('Please select a subteam');
    return;
  }
  router.post(
    `/quotes/health/healthTeamAssign`,
    {
      modelType: 'Health',
      entityId: usePage().props.quote.id,
      assign_team: assignSubteam.value,
    },
    {
      preserveScroll: true,
      onBefore: () => {
        isDisabled.value = true;
      },
      onSuccess: () => {
        notification.success('Team Assigned');
      },
      onFinish: () => {
        isDisabled.value = false;
      },
    },
  );
};

const onAssignLead = () => {
  if (!assignLead.value) {
    notification.error('Please select a lead');
    return;
  }
  router.post(
    `/quotes/health/manualLeadAssign`,
    {
      modelType: 'Health',
      entityId: usePage().props.quote.id,
      assigned_to_id_new: assignLead.value,
    },
    {
      preserveScroll: true,
      onBefore: () => {
        isDisabled.value = true;
      },
      onSuccess: () => {
        notification.success('Lead Assigned');
      },
      onFinish: () => {
        isDisabled.value = false;
      },
    },
  );
};

const onLeadStatus = () => {
  router.post(
    `/quotes/Health/${usePage().props.quote.id}/update-lead-status`,
    {
      modelType: 'Health',
      leadId: usePage().props.quote.id,
      quote_uuid: usePage().props.quote.uuid,
      assigned_to_user_id: usePage().props.quote.advisor_id,
      leadStatus: leadStatus.value,
      notes: leadNotes.value,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        notification.success('Lead Status Updated');
      },
    },
  );
};

const memberDetailsTable = reactive({
  isLoading: false,
  columns: [
    {
      text: 'Name',
      value: 'id',
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
      text: 'Nationality',
      value: 'nationality',
    },
    {
      text: 'Emirate of Visa',
      value: 'emirate',
    },
    {
      text: 'Relationship',
      value: 'member_category_id',
    },
    {
      text: 'Action',
      value: 'action',
    },
  ],
});

const memberForm = useForm({
  id: null,
  gender: null,
  dob: null,
  nationality_id: null,
  salary_band_id: null,
  emirate_of_your_visa_id: null,
  member_category_id: null,
  health_quote_request_id: usePage().props.quote.id,
});

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
}

const onAddMemberModal = () => {
  memberForm.reset();
  memberActionEdit.value = false;
  modals.member = true;
};

const onMemberSubmit = isValid => {
  if (!isValid) return;
  if (memberActionEdit.value) {
    memberForm.put(`/members/${memberForm.id}`, {
      preserveScroll: true,
      onSuccess: () => {
        notification.success('Member Updated');
      },
      onFinish: () => {
        modals.member = false;
      },
    });
  } else {
    memberForm.post(`/members`, {
      preserveScroll: true,
      onSuccess: () => {
        notification.success('Member Added');
      },
      onFinish: () => {
        modals.member = false;
      },
    });
  }
};

const memberDelete = id => {
  modals.memberConfirm = true;
  confirmDeleteData.member = id;
};

const memberDeleteConfirmed = () => {
  memberForm.delete(`/members/${confirmDeleteData.member}`, {
    preserveScroll: true,
    onSuccess: () => {
      notification.success('Member Deleted');
    },
    onFinish: () => {
      modals.memberConfirm = false;
    },
  });
};

const memberDataDocs = membersDetail => {
  return membersDetail
    .map(member => ({
      id: member.id,
      name: memberCategoryText(member.member_category_id).value,
    }))
    .filter(member => member.name !== undefined);
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
      text: 'Actual Premium with BASMAH',
      value: 'actualPremium',
    },
    {
      text: 'Premium with VAT and BASMAH',
      value: 'premiumVat',
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

// quoteDocuments

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
      quoteId: usePage().props.quote.id,
    },
    {
      preserveScroll: true,
      onFinish: () => {
        modals.docConfirm = false;
        quoteDocumentsTable.isLoading = false;
        notification.error('File Deleted');
      },
    },
  );
};

//activities
const activityTable = [
  { text: 'Title', value: 'title' },
  { text: 'Client Name', value: 'client_name' },
  { text: 'Followup Date', value: 'due_date' },
  { text: 'Assigned To', value: 'assignee' },
  { text: 'Done', value: 'status' },
  { text: 'Action', value: 'action' },
];

const activityForm = useForm({
  entityUId: usePage().props.quote.uuid,
  entityId: usePage().props.quote.id,
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
  activityForm.post(`/activities/updateStatus`, {
    preserveScroll: true,
    onSuccess: () => {
      notification.success('Lead Activity Done');
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
        notification.success('Activity Updated');
      },
      onFinish: () => {
        modals.activity = false;
      },
    });
  } else {
    activityForm.post(`/activities/create-activity`, {
      preserveScroll: true,
      onSuccess: () => {
        notification.success('Activity Added');
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
      quote_uuid: usePage().props.quote.uuid,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        notification.error('Activity Deleted');
      },
      onFinish: () => {
        modals.activityConfirm = false;
      },
    },
  );
};

// additional contact

const additionalContact = useForm({
  additional_contact_type: null,
  additional_contact_val: null,
  quote_id: usePage().props.quote.id,
  customer_id: usePage().props.quote.customer_id,
  quote_type: 3,
});

const onAdditionalContactSubmit = isValid => {
  if (!isValid) return;
  additionalContact.post(`/customer-additional-contact/add`, {
    preserveScroll: true,
    onSuccess: () => {
      notification.success('Additional Contact Added');
    },
    onFinish: () => {
      modals.addContact = false;
    },
  });
};

// history data
const historyData = ref([]);

const onLoadHistoryData = async () => {
  historyLoading.value = true;
  const res = await fetch(
    `/quotes/getLeadHistory?modelType=health&recordId=${
      usePage().props.quote.id
    }`,
  );
  const finalRes = await res.json();
  historyData.value = finalRes;
  historyLoading.value = false;
};

onMounted(() => {
  const isHealthAdvisor = usePage().props.advisors.find(
    a => a.id == usePage().props.quote.advisor_id,
  );
  if (isHealthAdvisor) assignLead.value = isHealthAdvisor.id;
});
</script>
<template>
  <div>
    <Head title="Health Detail" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Health Detail</h2>
      <div class="flex gap-2">
        <x-button size="sm" color="#ff5e00">Duplicate Lead</x-button>
        <x-button @click.prevent="goBack" size="sm" color="primary">
          Health List
        </x-button>
        <x-button size="sm">Edit</x-button>
      </div>
    </div>

    <x-divider class="my-4" />

    <div class="p-4 rounded shadow mb-6 bg-primary-50/50">
      <div class="flex gap-6 w-full">
        <div class="w-full md:w-1/2 flex gap-2 items-end">
          <x-select
            v-model="assignSubteam"
            label="Assign Subteam"
            :options="subTeamOptions"
            placeholder="Select Subteam"
            class="w-auto flex-1"
          />
          <div>
            <x-button
              color="orange"
              size="sm"
              @click.prevent="onTeamAssign"
              :loading="isDisabled"
            >
              Assign Team
            </x-button>
          </div>
        </div>
        <div class="w-full md:w-1/2 flex gap-2 items-end">
          <x-select
            v-model="assignLead"
            label="Assign Lead"
            :options="advisorOptions"
            placeholder="Select Lead"
            class="w-auto flex-1"
          />
          <div>
            <x-button
              color="orange"
              size="sm"
              @click.prevent="onAssignLead"
              :loading="isDisabled"
            >
              Assign
            </x-button>
          </div>
        </div>
      </div>
    </div>

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
            <dt class="font-medium">SUBTEAM</dt>
            <dd>{{ quote.health_team_type }}</dd>
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
            <dt class="font-medium">IS EBP RENEWAL</dt>
            <dd>{{ quote.is_ebp_renewal ? 'Yes' : 'No' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">RENEWAL BATCH</dt>
            <dd>{{ quote.renewal_batch }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">LOST REASON</dt>
            <dd>{{ quote.lost_reason }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">DEVICE</dt>
            <dd>{{ quote.device }}</dd>
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
            <dt class="font-medium">GENDER</dt>
            <dd>{{ genderText(quote.gender).value }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">MARITAL STATUS</dt>
            <dd>{{ quote.marital_status_id_text }}</dd>
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
            <dt class="font-medium">EMIRATE OF VISA</dt>
            <dd>{{ quote.emirate_of_your_visa_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">MEMBER CATEGORY</dt>
            <dd>{{ quote.member_category_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">SALARY BAND</dt>
            <dd>{{ quote.salary_band_id_text }}</dd>
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
            <dt class="font-medium">WHO ARE YOU LOOKING TO COVER?</dt>
            <dd>{{ quote.cover_for_id_text }}</dd>
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
        </dl>
      </div>

      <div class="mt-6">
        <h3 class="font-semibold text-primary-800">Policy Details</h3>
        <x-divider class="mb-4 mt-1" />
      </div>

      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">POLICY NUMBER</dt>
            <dd>{{ quote.policy_number }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">POLICY START DATE</dt>
            <dd>{{ quote.policy_start_date }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">POLICY END DATE</dt>
            <dd>{{ quote.policy_issuance_date }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PREMIUM</dt>
            <dd>{{ quote.premium }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">TRANSAPP CODE</dt>
            <dd>{{ quote.transapp_code }}</dd>
          </div>
        </dl>
      </div>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="flex justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">
          Member Details
          <x-tag size="sm">{{ membersDetail.length || 0 }}</x-tag>
        </h3>
        <x-button @click.prevent="onAddMemberModal" size="sm" color="#ff5e00">
          Add Member
        </x-button>
      </div>

      <x-divider class="my-4" />
      <x-table
        class="text-sm"
        dense
        striped
        :items="membersDetail || []"
        :headers="memberDetailsTable.columns"
        :loading="memberDetailsTable.isLoading"
      >
        <template #item-id> Member </template>
        <template #item-gender="{ item }">
          {{ genderText(item.gender).value }}
        </template>
        <template #item-dob="{ item }">
          {{ dateFormat(item.dob).value }}
        </template>
        <template #item-nationality="{ item }">
          {{ item.nationality?.text }}
        </template>
        <template #item-emirate="{ item }">
          {{ item.emirate?.text }}
        </template>
        <template #item-member_category_id="{ item }">
          {{ memberCategoryText(item.member_category_id).value }}
        </template>
        <template #item-action="{ item }">
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
      </x-table>

      <x-modal v-model="modals.member" size="lg" show-close backdrop>
        <template #header>
          {{ memberActionEdit ? 'Edit' : 'Add' }} Member
        </template>

        <x-form @submit="onMemberSubmit" :auto-focus="false">
          <div class="grid md:grid-cols-2 gap-4">
            <input type="hidden" :value="memberForm.id" />
            <x-select
              v-model="memberForm.nationality_id"
              label="Nationality"
              :options="nationalityOptions"
              :rules="[rules.isRequired]"
              placeholder="Select Nationality"
              class="w-full"
            />

            <x-select
              v-model="memberForm.emirate_of_your_visa_id"
              label="Emirate of Visa"
              :options="emiratesOptions"
              :rules="[rules.isRequired]"
              placeholder="Select Emirate of Visa"
              class="w-full"
            />

            <x-select
              v-model="memberForm.gender"
              label="Gender"
              :options="genderSelect"
              :rules="[rules.isRequired]"
              placeholder="Select Gender"
              class="w-full"
            />

            <x-input
              v-model="memberForm.dob"
              label="DOB"
              type="date"
              :rules="[rules.isRequired]"
              class="w-full"
            />

            <x-select
              v-model="memberForm.member_category_id"
              label="Relationship"
              :options="memberCategoriesOptions"
              :rules="[rules.isRequired]"
              placeholder="Select Relationship"
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

          <div class="text-right space-x-4 mt-12">
            <x-button size="sm" @click.prevent="modals.member = false">
              Cancel
            </x-button>

            <x-button
              size="sm"
              color="emerald"
              :loading="memberForm.processing"
              type="submit"
            >
              {{ memberActionEdit ? 'Update' : 'Save' }}
            </x-button>
          </div>
        </x-form>
      </x-modal>

      <x-modal v-model="modals.memberConfirm" show-close backdrop>
        <template #header> Delete Member Detail </template>
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
      </x-modal>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-primary-50/25">
      <div>
        <h3 class="font-semibold text-primary-800 text-lg">Lead Status</h3>
        <x-divider class="mb-4 mt-1" />
      </div>
      <div class="flex gap-6 w-full">
        <div class="w-full md:w-2/3">
          <x-textarea
            v-model="leadNotes"
            type="text"
            label="Notes"
            placeholder="Lead Notes"
            class="w-full"
          />
        </div>
        <div class="w-full md:w-1/3">
          <x-select
            v-model="leadStatus"
            label="Status"
            :options="leadStatusOptions"
            placeholder="Lead Status"
            class="w-full"
          />
          <div class="flex justify-end">
            <x-button
              class="mt-4"
              color="emerald"
              size="sm"
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
            <dd>{{ ecomDetails.planName }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PROVIDER NAME</dt>
            <dd>{{ ecomDetails.providerName }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PAYMENT STATUS</dt>
            <dd>{{ ecomDetails.paymentStatus }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PAID AT</dt>
            <dd>{{ ecomDetails.paidAt }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">NETWORK</dt>
            <dd>{{ ecomDetails.network }}</dd>
          </div>
        </dl>
      </div>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="flex justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">
          Available Plans
          <x-tag size="sm">{{ listQuotePlans.length || 0 }}</x-tag>
        </h3>
        <x-button
          v-if="listQuotePlans.length > 0"
          size="sm"
          color="primary"
          @click.prevent="onCopyText(ecomHealthInsuranceQuoteUrl + quote.uuid)"
        >
          Copy Link
        </x-button>
      </div>
      <x-divider class="my-4" />
      <x-table
        class="text-sm"
        dense
        striped
        :headers="plansTable.columns"
        :items="listQuotePlans || []"
        :loading="plansTable.isLoading"
      >
        <template #item-actualPremium="{ item }">
          {{ item.actualPremium + item.basmah }}
        </template>
        <template #item-premiumVat="{ item }">
          {{ item.actualPremium + item.vat + item.basmah }}
        </template>
        <template #item-action="{ item }">
          <div class="space-x-4">
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
                  ecomHealthInsuranceQuoteUrl +
                    quote.uuid +
                    `/payment/?providerCode=${item.providerCode}_${item.planCode}&planId=${item.id}`,
                )
              "
            >
              Copy
            </x-button>
          </div>
        </template>
      </x-table>

      <x-modal v-model="modals.plan" size="xl" show-close backdrop>
        <template #header>
          {{ selectedPlan.providerName }} - {{ selectedPlan.name }}
        </template>
        <LazyAvailablePlan :plan="selectedPlan" />
      </x-modal>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="flex justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">
          Documents
          <x-tag size="sm">{{ quoteDocuments.length || 0 }}</x-tag>
        </h3>
        <x-button @click.prevent="modals.doc = true" size="sm" color="orange">
          Upload Documents
        </x-button>
      </div>
      <x-divider class="my-4" />
      <x-table
        class="text-sm"
        dense
        striped
        :headers="quoteDocumentsTable.columns"
        :items="quoteDocuments || []"
        :loading="quoteDocumentsTable.isLoading"
      >
        <template #item-original_name="{ item }">
          <a
            :href="cdnPath + item.doc_url"
            target="_blank"
            class="text-primary-600"
          >
            {{ item.original_name }}
          </a>
        </template>
        <template #item-action="{ item }">
          <div>
            <x-button
              size="xs"
              color="error"
              outlined
              @click.prevent="onDocDelete(item.doc_name)"
            >
              Delete
            </x-button>
          </div>
        </template>
      </x-table>

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
      <x-table
        class="text-sm"
        dense
        striped
        :headers="activityTable"
        :items="activities"
      >
        <template #item-status="{ item }">
          <x-checkbox
            color="emerald"
            size="xl"
            :modelValue="item.status === 1"
            :disabled="item.status === 1"
            @change="onActivityStatusUpdate(item.id)"
          />
        </template>
        <template #item-action="{ item }">
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
      </x-table>
      <x-modal v-model="modals.activity" size="lg" show-close backdrop>
        <template #header>
          {{ activityActionEdit ? 'Edit' : 'Add' }} Lead Activity
        </template>

        <x-form @submit="onActivitySubmit" :auto-focus="false">
          <div class="grid gap-4">
            <x-input
              v-model="activityForm.title"
              label="Title"
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
              label="Assignee"
              :options="advisorOptions"
              :rules="[rules.isRequired]"
              placeholder="Select Assignee"
              class="w-full"
            />

            <x-input
              v-model="activityForm.due_date"
              label="Due Date"
              type="datetime-local"
              :rules="[rules.isRequired]"
              class="w-full"
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

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="flex justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">
          Customer Additional Contacts
          <x-tag size="sm">{{ 0 }}</x-tag>
        </h3>
        <x-button
          size="sm"
          color="orange"
          @click.prevent="modals.addContact = true"
        >
          Add Additional Contacts
        </x-button>
      </div>
      <x-divider class="my-4" />

      <x-modal v-model="modals.addContact" size="lg" show-close backdrop>
        <template #header> Add Additional Contacts </template>

        <x-form @submit="onAdditionalContactSubmit" :auto-focus="false">
          <div class="grid gap-4">
            <x-select
              v-model="additionalContact.additional_contact_type"
              label="Type"
              :options="[
                { value: 'email', label: 'Email' },
                { value: 'mobile_no', label: 'Mobile Number' },
              ]"
              :rules="[rules.isRequired]"
              placeholder="Select Type"
              class="w-full"
            />

            <x-input
              v-model="additionalContact.additional_contact_val"
              label="Value"
              :rules="[rules.isRequired]"
              class="w-full"
            />
          </div>

          <div class="text-right space-x-4 mt-12">
            <x-button size="sm" @click.prevent="modals.addContact = false">
              Cancel
            </x-button>

            <x-button
              size="sm"
              color="emerald"
              :loading="additionalContact.processing"
              type="submit"
            >
              Save
            </x-button>
          </div>
        </x-form>
      </x-modal>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div>
        <h3 class="font-semibold text-primary-800 text-lg">Lead History</h3>
        <x-divider class="mb-4 mt-1" />
      </div>
      <div class="text-center py-3">
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
    </div>
  </div>
</template>
