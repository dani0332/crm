<script setup>
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
});

const page = usePage();
const notification = useToast();
const { isRequired } = useRules();

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

const historyData = ref(null),
  historyLoading = ref(false);

const isDuplicateAllowed = computed(() => {
  return page.props.allowedDuplicateLOB.includes(page.props.typeCode);
});

const genderText = gender =>
  computed(() => {
    return page.props.genderOptions[gender];
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
  addContact: false,
  contactDeleteConfirm: false,
  contactPrimaryConfirm: false,
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
    .post('/quotes/createDuplicate', data)
    .then(res => {
      modals.duplicate = false;
      notification.success('Lead duplicated successfully');
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
    .post(`/quotes/Business/${page.props.quote.id}/update-lead-status`, data)
    .then(res => {
      console.log(res);
      notification.success({
        title: 'Lead Status Updated',
        position: 'top',
      });
    })
    .catch(err => {
      console.log(err);
    });
};

const onLoadHistoryData = async () => {
  historyLoading.value = true;
  const res = await fetch(
    `/quotes/getLeadHistory?modelType=business&recordId=${page.props.quote.id}`,
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
</script>

<template>
  <div>
    <Head title="Group Medical Lead Detail" />
    <div class="flex justify-between items-center flex-wrap gap-2 mb-5">
      <h2 class="text-xl font-semibold">Group Medical Lead Detail</h2>
      <div class="flex gap-2">
        <x-button
          v-if="isDuplicateAllowed"
          size="sm"
          color="#ff5e00"
          @click.prevent="openDuplicate"
        >
          Duplicate Lead
        </x-button>
        <Link href="/medical/amt" preserve-scroll>
          <x-button size="sm" color="primary" tag="div">
            Group Medical List
          </x-button>
        </Link>
        <Link
          v-if="can(permissionsEnum.canEditQuote)"
          :href="`${quote.uuid}/edit`"
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
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
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
            <dd>{{ quote.company_name }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">NEXT FOLLOWUP DATE</dt>
            <dd>{{ quote.next_followup_date }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">TRANSAPP CODE</dt>
            <dd>{{ quote.transapp_code }}</dd>
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
            <dd>{{ quote.brief_details }}</dd>
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
        </dl>
      </div>
    </div>

    <LastYearPolicyDetail
      v-if="
        quote.source == $page.props.leadSource.RENEWAL_UPLOAD ||
        quote.source == $page.props.leadSource.INSLY
      "
      :quote="quote"
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
          <x-input
            v-if="
              leadStatusForm.leadStatus == quoteStatusEnum.TransactionApproved
            "
            :disabled="
              quote.quote_status_id == quoteStatusEnum.TransactionApproved
            "
            v-model="leadStatusForm.trans_code"
            label="TransApp Code"
            placeholder="TransApp Code is required"
            class="w-full"
            :error="leadStatusForm.errors.trans_code"
          />
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
        >
          Change Status
        </x-button>
      </div>
    </div>

    <!-- Additional Contact -->
    <customerAdditionalContacts
      quoteType="Business"
      :customerId="quote.customer_id"
      :quoteId="quote.id"
      :contacts="customerAdditionalContacts"
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
  </div>
</template>
