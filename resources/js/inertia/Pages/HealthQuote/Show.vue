<script setup>
import { computed, ref, reactive } from 'vue';
import { Head, usePage, router, useForm } from '@inertiajs/vue3';
import { useDateFormat } from '@vueuse/core';
import TheTable from '@/inertia/Components/TheTable.vue';
import LazyDocumentUploader from './Partials/DocumentUploader.vue';
import LazyAvailablePlan from './Partials/AvailablePlans.vue';

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
});

const dateFormat = date => useDateFormat(date, 'DD/MM/YYYY');

const assignSubteam = ref(''),
  assignLead = ref('');

const leadStatus = ref(usePage().props.quote.quote_status_id),
  leadNotes = ref(usePage().props.quote.notes),
  memberDetailModal = ref(false),
  memberActionEdit = ref(false),
  docUploadModal = ref(false),
  deleteDocModal = ref(false),
  planModal = ref(false),
  selectedPlan = ref(null),
  confirmDeleteData = reactive({
    docs: null,
  });

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

const memberDetailsTable = reactive({
  isLoading: false,
  columns: [
    {
      label: 'Name',
      field: 'name',
      isKey: true,
    },
    {
      label: 'Gender',
      field: 'gender',
    },
    {
      label: 'DOB',
      field: 'dob',
    },
    {
      label: 'Nationality',
      field: 'nationality',
    },
    {
      label: 'Emirate of Visa',
      field: 'emirate',
    },
    {
      label: 'Relationship',
      field: 'member_category_id',
    },
    {
      label: 'Action',
      field: 'action',
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
  memberDetailModal.value = true;

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
  memberDetailModal.value = true;
};

const onAddMember = () => {
  memberForm.post(`/members`, {
    preserveScroll: true,
    onFinish: () => {
      memberDetailModal.value = false;
    },
  });
};

const onUpdateMember = () => {
  memberForm.put(`/members/${memberForm.id}`, {
    preserveScroll: true,
    onFinish: () => {
      memberDetailModal.value = false;
    },
  });
};

const onDeleteMember = id => {
  memberForm.delete(`/members/${id}`, {
    preserveScroll: true,
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
      label: 'Provider Name',
      field: 'providerName',
      isKey: true,
    },
    {
      label: 'Plan Name',
      field: 'name',
    },
    {
      label: 'Actual Premium with BASMAH',
      field: 'actualPremium',
    },
    {
      label: 'Premium with VAT and BASMAH',
      field: 'premiumVat',
    },
    {
      label: 'Action',
      field: 'action',
    },
  ],
  pageOptions: [
    {
      value: 100,
      label: '100',
    },
  ],
});

const planClicked = plan => {
  selectedPlan.value = plan;
  planModal.value = true;
};

// quoteDocuments
const docsData = computed(() => {
  return Object.values(usePage().props.quoteDocuments);
});
const quoteDocumentsTable = reactive({
  isLoading: false,
  columns: [
    {
      label: 'Document Type',
      field: 'document_type_text',
    },
    {
      label: 'Document Name',
      field: 'doc_name',
    },
    {
      label: 'Created At',
      field: 'created_at',
      isKey: true,
    },
    {
      label: 'Created By',
      field: 'created_by_name',
    },
    {
      label: 'Action',
      field: 'action',
    },
  ],
  pageOptions: [
    {
      value: 100,
      label: '100',
    },
  ],
});

const onDocDelete = name => {
  deleteDocModal.value = true;
  confirmDeleteData.docs = name;
};

const confirmDeleteDoc = () => {
  quoteDocumentsTable.isLoading = true;
  console.log(confirmDeleteData.docs);
  router.post(
    `/documents/delete`,
    {
      docName: confirmDeleteData.docs,
      quoteId: usePage().props.quote.id,
    },
    {
      preserveScroll: true,
      only: [''],
      onFinish: () => {
        deleteDocModal.value = false;
        quoteDocumentsTable.isLoading = false;
      },
    },
  );
};
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
            <x-button color="orange" size="sm"> Assign Team </x-button>
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
            <x-button color="orange" size="sm">Assign</x-button>
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
        <h3 class="font-semibold text-primary-800 text-lg">Member Details</h3>
        <x-button @click.prevent="onAddMemberModal" size="sm" color="#ff5e00">
          Add Member
        </x-button>
      </div>

      <div>
        <TheTable
          :is-static-mode="true"
          :is-slot-mode="true"
          :is-hide-paging="true"
          :is-loading="memberDetailsTable.isLoading"
          :columns="memberDetailsTable.columns"
          :rows="membersDetail || []"
          :total="membersDetail.length || 0"
          @is-finished="memberDetailsTable.isLoading = false"
        >
          <template v-slot:name> Member </template>
          <template v-slot:gender="data">
            {{ genderText(data.value.gender).value }}
          </template>
          <template v-slot:dob="data">
            {{ dateFormat(data.value.dob).value }}
          </template>
          <template v-slot:nationality="data">
            {{ data.value.nationality?.text }}
          </template>
          <template v-slot:emirate="data">
            {{ data.value.emirate?.text }}
          </template>
          <template v-slot:member_category_id="data">
            {{ memberCategoryText(data.value.member_category_id).value }}
          </template>
          <template v-slot:action="data">
            <div class="flex gap-2">
              <x-button
                size="xs"
                color="primary"
                outlined
                @click.prevent="onEditMember(data.value)"
              >
                Edit
              </x-button>
              <x-button
                size="xs"
                color="error"
                outlined
                @click.prevent="onDeleteMember(data.value.id)"
              >
                Delete
              </x-button>
            </div>
          </template>
        </TheTable>
      </div>
      <x-modal v-model="memberDetailModal" size="xl" show-close backdrop>
        <template #header>
          {{ memberActionEdit ? 'Edit' : 'Add' }} Member
        </template>

        <x-form :auto-focus="false">
          <div class="grid md:grid-cols-2 gap-4">
            <input type="hidden" :value="memberForm.id" />
            <x-select
              v-model="memberForm.nationality_id"
              label="Nationality"
              :options="nationalityOptions"
              placeholder="Select Nationality"
              class="w-full"
            />

            <x-select
              v-model="memberForm.emirate_of_your_visa_id"
              label="Emirate of Visa"
              :options="emiratesOptions"
              placeholder="Select Emirate of Visa"
              class="w-full"
            />

            <x-select
              v-model="memberForm.gender"
              label="Gender"
              :options="genderSelect"
              placeholder="Select Gender"
              class="w-full"
            />

            <x-input
              v-model="memberForm.dob"
              label="DOB"
              type="date"
              class="w-full"
            />

            <x-select
              v-model="memberForm.member_category_id"
              label="Relationship"
              :options="memberCategoriesOptions"
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
        </x-form>

        <template #actions>
          <div class="text-right space-x-4">
            <x-button size="sm" @click.prevent="memberDetailModal = false">
              Cancel
            </x-button>
            <x-button
              v-if="memberActionEdit"
              size="sm"
              color="emerald"
              @click.prevent="onUpdateMember"
              :loading="memberForm.processing"
            >
              Update
            </x-button>
            <x-button
              v-else
              size="sm"
              color="emerald"
              @click.prevent="onAddMember"
              :loading="memberForm.processing"
            >
              Save
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
            <x-button class="mt-4" color="emerald" size="sm">
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
        <x-button size="sm" color="primary"> Copy Link </x-button>
      </div>
      <TheTable
        :is-static-mode="true"
        :is-slot-mode="true"
        :is-hide-paging="true"
        :is-loading="plansTable.isLoading"
        :columns="plansTable.columns"
        :rows="listQuotePlans || []"
        :total="listQuotePlans.length || 0"
        :pageOptions="plansTable.pageOptions"
        @is-finished="plansTable.isLoading = false"
      >
        <template v-slot:actualPremium="data">
          {{ data.value.actualPremium + data.value.basmah }}
        </template>
        <template v-slot:premiumVat="data">
          {{ data.value.actualPremium + data.value.vat + data.value.basmah }}
        </template>
        <template v-slot:action="data">
          <div class="space-x-4">
            <x-button
              size="xs"
              color="primary"
              outlined
              @click.prevent="planClicked(data.value)"
            >
              View
            </x-button>
            <x-button size="xs" color="emerald" outlined> Copy </x-button>
          </div>
        </template>
      </TheTable>
      <x-modal v-model="planModal" size="xl" show-close backdrop>
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
          <x-tag size="sm">{{ docsData.length || 0 }}</x-tag>
        </h3>
        <x-button
          @click.prevent="docUploadModal = true"
          size="sm"
          color="primary"
        >
          Upload Documents
        </x-button>
      </div>
      <x-modal v-model="docUploadModal" size="xl" show-close backdrop>
        <template #header> Upload Documents </template>
        <LazyDocumentUploader
          :members="memberDataDocs(membersDetail)"
          :doc-types="documentTypes"
          :docs="docsData || []"
          :cdn="cdnPath"
        />
      </x-modal>
      <TheTable
        :is-static-mode="true"
        :is-slot-mode="true"
        :is-hide-paging="true"
        :is-loading="quoteDocumentsTable.isLoading"
        :columns="quoteDocumentsTable.columns"
        :rows="docsData || []"
        :total="docsData.length || 0"
        :pageOptions="quoteDocumentsTable.pageOptions"
        @is-finished="quoteDocumentsTable.isLoading = false"
      >
        <template v-slot:doc_name="data">
          <a :href="cdnPath + data.value.doc_url" target="_blank">
            {{ data.value.doc_name }}
          </a>
        </template>
        <template v-slot:action="data">
          <div>
            <x-button
              size="xs"
              color="error"
              outlined
              @click.prevent="onDocDelete(data.value.doc_name)"
            >
              Delete
            </x-button>
          </div>
        </template>
      </TheTable>
      <x-modal v-model="deleteDocModal" show-close backdrop>
        <template #header> Delete Document </template>
        <p>Are you sure you want to delete this document?</p>
        <template #actions>
          <div class="text-right space-x-4">
            <x-button size="sm" ghost @click.prevent="deleteDocModal = false">
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
      <div>
        <h3 class="font-semibold text-primary-800 text-lg">Lead Activities</h3>
        <x-divider class="mb-4 mt-1" />
      </div>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div>
        <h3 class="font-semibold text-primary-800 text-lg">
          Customer Additional Contacts
        </h3>
        <x-divider class="mb-4 mt-1" />
      </div>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div>
        <h3 class="font-semibold text-primary-800 text-lg">Lead History</h3>
        <x-divider class="mb-4 mt-1" />
      </div>
    </div>
  </div>
</template>
